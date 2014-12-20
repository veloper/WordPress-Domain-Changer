<?php
class BaseController {

    public $_http_status_codes = array(
        200 => '200 OK',                    201 => '201 Created',
        301 => '301 Moved Permanently',     302 => '302 Found',
        401 => '401 Unauthorized',          403 => '403 Forbidden', 404 => '404 Not Found',
        500 => '500 Internal Server Error'
    );

    public $session_ttl = WPDC_SESSION_TTL;

    public $data    = array(); // The data that will be passed to the view
    public $session = array();

    public $_routes  = array();
    public $_route   = null; // The route that will be processed

    public $_flash   = array( "error" => array(), "success" => array(), "warning" => array(), "info" => array() );

    // Response
    public $_code     = 200;
    public $_redirect = null;
    public $_headers  = array();
    public $_body     = "";

    public function __construct() {
        $this->routes();
        if ( empty( $this->_routes ) ) throw new Exception( "At least one route must be set!" );
    }

    public function getResponseHeaders() {
        $headers = array();

        if ( $this->getStatusCode() ) {
            $headers[] = "HTTP/1.1 " . $this->_http_status_codes[$this->getStatusCode()];
        }

        if ( $this->isRedirecting() ) {
            $headers[] = "Location: " . $this->getRedirectUrl();
        }

        return $headers;
    }

    public function getRedirectUrl() {
        return $this->_redirect;
    }

    public function setRedirectUrl( $url ) {
        $this->_redirect = $url;
    }

    public function isRedirecting() {
        return is_string( $this->getRedirectUrl() );
    }

    public function routes() { /* override me */ }

    public function addRoute( $verb, $path, $action, $options = array() ) {
        $route = array(
            "verb"    => $verb,
            "path"    => $path,
            "action"  => $action,
            "auth"    => array_key_exists( "auth", $options ) ? (bool) $options["auth"] : false,
            "root"    => array_key_exists( "root", $options ) ? (bool) $options["root"] : false,
            "options" => $options
        );
        $this->_routes[] = $route;
    }
    public function getStatusCode() {
        return $this->_code;
    }
    public function setStatusCode( $code ) {
        $this->_code = $code;
    }

    public function handleRequestAndDie() {
        if ( $route = $this->getRequestRoute() ) {
            $this->processRequestForRoute( $route );
        } else {
            if ( $route = $this->getRootRouteUrl() ) {
                $this->setRedirectUrl( $this->getRootRouteUrl() );
            } else {
                $this->setStatusCode( 404 );
            }
        }

        // Output Headers
        foreach ( $this->getResponseHeaders() as $value ) header( $value );

        // Output Body (unless we're redirecting)
        if ( !$this->isRedirecting() ) echo $this->_body;

        die;
    }


    public function processRequestForRoute( $route ) {
        $this->_route = $route;

        $this->beforeRequest();

        if ( !$this->isRedirecting() ) {
            $this->_body = $this->callRouteAction( $this->_route );
        }

        $this->afterRequest();
    }

    public function callRouteAction( array $route ) {
        return call_user_func( array( $this, $route["action"] ) );
    }

    public function beforeRequest() {
        $this->startUsingCustomErrorHandler();

        $this->loadSession();

        if ( !$this->isPasswordValid() ) {
            $verb_past_tense = $this->isPasswordConstantDefined() ? "changed" : "defined";
            $this->addFlash( "warning", "Login will remain DISABLED until the <code>WPDC_PASSWORD</code> constant is $verb_past_tense in the <code>wpdc/config.php</code> file." );
        }

        if ( $this->isRequestProtected() ) {
            if ( $this->isAuthenticated() ) {
                $this->renewAuthCookie();
            } else {
                $this->addFlash( "error", "Your session has expired, please login again." );
                return $this->redirectToAction( "login" );
            }
        }
    }

    public function beforeRender() {
        // Override if needed
    }


    public function afterRequest() {
        if ( !$this->isRedirecting() )  $this->clearFlash();
        if ( $this->getRequestVerb() == "POST" ) $this->setLastPostTo( $this->getRequestAction(), $this->getPost() );
        $this->saveSession();
        $this->stopUsingCustomErrorHandler();
    }

    public function isPasswordConstantDefined() {
        return defined( "WPDC_PASSWORD" );
    }

    public function isPasswordValid() {
        return $this->isPasswordConstantDefined() && WPDC_PASSWORD != "Replace-This-Password";
    }

    public function isRequestProtected() {
    	$route = $this->getRequestRoute();
        return $route["auth"];
    }

    public function render( $view_name ) {
        $this->beforeRender();

        $layout = new View( realpath( dirname( __FILE__ ) . "/../../views/_layout.php" ) );
        $view   = new View( realpath( dirname( __FILE__ ) . "/../../views/" . $view_name . ".php" ) );

        $this->data["current_user"] = (object) array(
            "authenticated" => $this->isAuthenticated(),
            "session"       => $this->getSession()
        );

        $this->data["request"] = (object) array(
            "url"              => $this->getRequestUrl(),
            "verb"             => $this->getRequestVerb(),
            "path"             => $this->getRequestPath(),
            "protected"        => $this->isRequestProtected(),
            "authenticated"    => $this->isAuthenticated(),
            "route"            => $this->getRequestRoute(),
            "assets_url"       => $this->getAssetsUrl(),
            "root_route_url"   => $this->getRootRouteUrl(),
            "base_url"         => $this->getBaseUrl(),
            "base_route_url"   => $this->getBaseRouteUrl(),
            "post"             => $this->getPost()
        );

        $this->data["flash"]   = $this->getFlash();
        $this->data["body"]    = $view->render( $this->data );
        $this->data["flash"]   = $this->getFlash(); // catch any flash messages added from view (usually an error)

        $html = $layout->render( $this->data );

        return $html;
    }

    public function redirectToAction( $action ) {
        $route = $this->getRouteWhere( array( "action" => $action ) );
        if ( $route ) {
            $this->setRedirectUrl( $this->getRouteUrl( $route ) );
        }
    }

    public function isAuthenticated() {
        // die("<pre>".print_r($this->getAuthCookie(), true));
        if ( $cookie = $this->getAuthCookie() ) {
            $not_expired = ( isset( $cookie["expiration"] ) && time() < $cookie["expiration"] ) ? true : false;
            $valid_token = ( isset( $cookie["token"] ) && $cookie["token"] == md5( WPDC_PASSWORD ) ) ? true : false;
            if ( $not_expired && $valid_token ) return true;
        }
        return false;
    }

    // == Flash Messages == //

    public function addFlash( $type, $message ) {
        if ( !in_array( $message, $this->_flash[$type] ) ) $this->_flash[$type][] = $message;
    }

    public function getFlash( $type = null ) {
        return $type ? $this->_flash[$type] : $this->_flash;
    }

    public function clearFlash() {
        $this->_flash = array(
            "error"   => array(),
            "success"  => array(),
            "warning" => array(),
            "info"    => array()
        );
    }

    public function unsetSessionCookie() {
        $this->unsetCookie( "session" );
    }

    public function saveSession() {
        $session = $this->session;
        $session["_flash"] = $this->_flash;

        $this->setCookieData( "session", $session, $this->session_ttl );

    }

    public function loadSession() {
        $this->session = $this->getCookieData( "session" );
        if ( array_key_exists( "_flash", $this->session ) ) {
            $this->_flash = $this->session["_flash"];
        }
    }

    public function getSession() {
        $this->session;
    }

    public function unsetAuthCookie() {
        $this->unsetCookie( "auth" );
    }

    public function renewAuthCookie() {
        $this->setAuthCookie();
    }

    public function setAuthCookie() {
        $data               = array();
        $data["token"]      = md5( WPDC_PASSWORD );

        $this->setCookieData( "auth", $data, $this->session_ttl );
    }

    public function getAuthCookie() {
        return $this->getCookieData( "auth" );
    }

    public function getLastPostTo( $action ) {
        return $this->cookieExists( "last_post_to_" . $action ) ? $this->getCookieData( "last_post_to_" . $action ) : false;
    }

    public function setLastPostTo( $action, $post ) {
        $post   = $post   ? $post   : $this->getPost();
        $action = $action ? $action : $this->getRequestAction();
        $this->setCookieData( "last_post_to_" . $action, $post, 5 );
    }

    public function setCookieData( $key, $data, $ttl = null ) {
        $ttl                = $ttl ? $ttl : ( 60 * 5 );
        $expiration         = time() + $ttl;
        $data["ttl"]        = $ttl;
        $data["expiration"] = $expiration;
        setcookie( "wpdc_" . $key, json_encode( $data ), $expiration, '/' );
    }

    public function getCookieData( $key ) {
        return $this->cookieExists( $key ) ? json_decode( $_COOKIE["wpdc_" . $key], true ) : array();
    }

    public function cookieExists( $key ) {
        return array_key_exists( "wpdc_" . $key, $_COOKIE );
    }

    public function unsetCookie( $key ) {
        setcookie( "wpdc_" . $key, "", time() - 3600, '/' );
    }

    public function getPost( $just_value_for_key = null ) {
        $data = array();
        foreach ( $GLOBALS["_POST"] as $key => $value ) {
            if ( get_magic_quotes_gpc() ) $value = stripslashes( trim( $value ) );
            $data[$key] = $value;
        }
        return $just_value_for_key ? $data[$just_value_for_key] : $data;
    }

    public function getRouteUrl( $route ) {
        return $this->getBaseRouteUrl() . '/'. $route["path"];
    }

    public function getActionUrl( $action ) {
        if ( $route = $this->getRouteWhere( array( "action" => $action ) ) ) {
            return $this->getBaseRouteUrl() . '/'. $route["path"];
        }
        return false;
    }

    // Credit: http://stackoverflow.com/a/8891890/493702
    public function getRequestUrl() {
        $s        = $_SERVER;
        $ssl      = ( !empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' ) ? true:false;
        $sp       = strtolower( $s['SERVER_PROTOCOL'] );
        $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
        $port     = $s['SERVER_PORT'];
        $port     = ( ( !$ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
        $host     = isset( $s['HTTP_X_FORWARDED_HOST'] ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
        $host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
        $url      = $protocol . '://' . $host . $s['REQUEST_URI'];
        return trim( $url, "/" );
    }

    public function getRoutesWhere( $criteria ) {
        $routes = array();
        foreach ( $this->_routes as $route ) {
            foreach ( $criteria as $key => $value ) {
                if ( $route[$key] != $value ) continue 2;
                $routes[] = $route;
                break 2;
            }
        }
        return $routes;
    }

    public function getRouteWhere( $criteria ) {
        $routes = $this->getRoutesWhere( $criteria );
        return ( count( $routes ) == 1 ) ? $routes[0] : false;
    }

    public function getRequestRoute() {
        return $this->getRouteWhere( array( "path" => $this->getRequestPath() ) );
    }

    public function getRequestAction() {
    	$route = $this->getRequestRoute();
        return $route["action"];
    }

    public function getRequestPath() {
        return array_key_exists( "PATH_INFO", $_SERVER ) ? substr( $_SERVER["PATH_INFO"], 1 ) : "";
    }

    public function getRequestVerb() {
        return strtoupper( $_SERVER['REQUEST_METHOD'] );
    }

    public function getBaseUrl() {
        if ( stripos( $this->getRequestUrl(), ".php" ) !== false ) {
        	$url_array = explode( ".php", $this->getRequestUrl() );
            return dirname( $url_array[0] );
        } else {
            return $this->getRequestUrl();
        }
    }

    public function getBaseRouteUrl() {
        return $this->getBaseUrl() . "/index.php";
    }

    public function getAssetsUrl() {
        return $this->getBaseUrl() . "/assets";
    }

    public function getRootRouteUrl() {
        if ( $route = $this->getRouteWhere( array( "root" => true ) ) ) {
            return $this->getRouteUrl( $route );
        }
        return false;
    }

    public function startUsingCustomErrorHandler() {
        set_error_handler( array( $this, "_customErrorHandler" ) );
    }

    public function stopUsingCustomErrorHandler() {
        restore_error_handler();
    }

    public function _customErrorHandler( $errno, $errstr, $errfile, $errline ) {
        $error = $this->_friendlyErrorType( $errno );
        $this->addFlash( "warning", "PHP Error: [$error] \"$errstr\" on line $errline in file $errfile" );
        return true;
    }

    public function _friendlyErrorType( $type ) {
        $return = "";
        if ( $type & E_ERROR )              $return .= '& E_ERROR ';              // 1 //
        if ( $type & E_WARNING )            $return .= '& E_WARNING ';            // 2 //
        if ( $type & E_PARSE )              $return .= '& E_PARSE ';              // 4 //
        if ( $type & E_NOTICE )             $return .= '& E_NOTICE ';             // 8 //
        if ( $type & E_CORE_ERROR )         $return .= '& E_CORE_ERROR ';         // 16 //
        if ( $type & E_CORE_WARNING )       $return .= '& E_CORE_WARNING ';       // 32 //
        if ( $type & E_COMPILE_ERROR )      $return .= '& E_COMPILE_ERROR ';      // 64 //
        if ( $type & E_COMPILE_WARNING )    $return .= '& E_COMPILE_WARNING ';    // 128 //
        if ( $type & E_USER_ERROR )         $return .= '& E_USER_ERROR ';         // 256 //
        if ( $type & E_USER_WARNING )       $return .= '& E_USER_WARNING ';       // 512 //
        if ( $type & E_USER_NOTICE )        $return .= '& E_USER_NOTICE ';        // 1024 //
        if ( $type & E_STRICT )             $return .= '& E_STRICT ';             // 2048 //
        if ( $type & E_RECOVERABLE_ERROR )  $return .= '& E_RECOVERABLE_ERROR ';  // 4096 //
        if ( $type & E_DEPRECATED )         $return .= '& E_DEPRECATED ';         // 8192 //
        if ( $type & E_USER_DEPRECATED )    $return .= '& E_USER_DEPRECATED ';    // 16384 //
        return trim( substr( $return, 2 ) );
    }
}
