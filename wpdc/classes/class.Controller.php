<?php
require_once dirname( __FILE__ ) . '/../config.php';
require_once 'classes/class.View.php';
class Controller {

    protected $actions = array(
        "default"             => array( "method" => "login"               , "verb" => "get"  , "auth" => false ),

        "login"               => array( "method" => "login"               , "verb" => "get"  , "auth" => false ),
        "loginHandler"        => array( "method" => "loginHandler"        , "verb" => "post" , "auth" => false ),

        "changeDomain"        => array( "method" => "changeDomain"        , "verb" => "get"  , "auth" => true ),
        "changeDomainHandler" => array( "method" => "changeDomainHandler" , "verb" => "post" , "auth" => true ),
        "changeDomainSuccess" => array( "method" => "changeDomainSuccess" , "verb" => "get"  , "auth" => true )
    );

    protected $data = array(); // view data

    protected $session = array();

    protected $flash = array(
        "error"   => array(),
        "notice"  => array(),
        "warning" => array(),
        "info"    => array()
    );


    protected $action  = null; // The action that will be processed
    protected $headers = array();
    protected $body    = "";


    public function respondAndDie() {
        // Output Headers
        foreach ( $this->headers as $key => $value ) header( $key . ":" . $value );

        // Output Body (unless we're redirecting)
        if ( !$this->willRedirect() ) {
            echo $this->body;
        }

        die;
    }

    public function processRequestForAction( $action ) {
        $this->action = $this->getAction( $action ) ? $this->getAction( $action ) : $this->getAction( "default" );

        $this->beforeRequest();

        if ( !$this->willRedirect() ) {
            $this->body = $this->runAction( $this->action );
        }

        $this->afterRequest();
    }

    public function runAction( array $action ) {
        $action_method = array( $this, $action["method"] );
        return call_user_func( $action_method );
    }

    public function beforeRequest()
    {
        $this->loadSession();

        if($this->isDefaultPasswordSet()) {
            $this->addFlash("warning", "Login is disabled until the <code>WPDC_PASSWORD</code> constant is changed in the <code>wpdc/config.php</code> file.");
        }

        if($this->isProtectedAction() && !$this->isAuthenticated()) {
            $this->addFlash("error", "Your session has expired, please login again.");
            $this->redirectToAction("login");
        }
    }


    public function afterRequest() {
        if ( !$this->willRedirect() )  $this->clearFlash();
        $this->saveSession();
    }



    public function isDefaultPasswordSet() {
        return WPDC_PASSWORD == "Replace-This-Password";
    }

    public function isProtectedAction() {
        return $this->action['auth'] == true;
    }

    public function getAction( $action ) {
        return array_key_exists( $action, $this->actions ) ? $this->actions[$action] : false;
    }


    // == ACTIONS ==

    public function login() {
        $this->data["form_path"]               = $this->pathToAction( "loginHandler" );
        $this->data["is_default_password_set"] = $this->isDefaultPasswordSet();

        return $this->render( "login" );
    }

    public function loginHandler() {
        if ( md5( $this->post( 'auth_password' ) ) == md5( DDWPDC_PASSWORD ) ) {
            $this->setAuthCookie();
            $this->redirectToAction( "changeDomain" );
        } else {
            $this->addFlash( "error", "Incorrect password, please try again." );
            $this->redirectToAction("login" );
        }
    }


    public function changeDomain() {

        $this->render( "form" );
    }


    public function changeDomainHandler() {
        try {
            // Start change process
            if ( isset( $_POST ) && is_array( $_POST ) && ( count( $_POST ) > 0 ) ) {
                // Clean up data & check for empty fields
                $POST = array();
                foreach ( $_POST as $key => $value ) {
                    $value = trim( $value );
                    if ( strlen( $value ) <= 0 ) throw new Exception( 'One or more of the fields was blank; all are required.' );
                    if ( get_magic_quotes_gpc() ) $value = stripslashes( $value );
                    $POST[$key] = $value;
                }

                // Check for "http://" in the new domain
                if ( stripos( $POST['new_domain'], 'http://' ) !== false ) {
                    // Let them correct this instead of assuming it's correct and removing the "http://".
                    throw new Exception( 'The "New Domain" field must not contain "http://"' );
                }

                // DB Connection
                $mysqli = @new mysqli( $POST['host'], $POST['username'], $POST['password'], $POST['database'] );
                if ( mysqli_connect_error() ) {
                    throw new Exception( 'nable to create database connection; most likely due to incorrect connection settings.' );
                }

                // Escape for Database
                $data = array();
                foreach ( $_POST as $key => $value ) {
                    $data[$key] = $mysqli->escape_string( $value );
                }

                /**
                 * Handle Serialized Values
                 *
                 * Before we update the options we need to find any option_values that have the
                 * old_domain stored within a serialized string.
                 */
                if ( !$result = $mysqli->query( 'SELECT * FROM '.$data['prefix'].'options WHERE option_value REGEXP "s:[0-9]+:\".*'
                        . $mysqli->escape_string( DDWordPressDomainChanger::preg_quote( $POST['old_domain'] ) ).'.*\";"' )
                ) {
                    throw new Exception( $mysqli->error);
                }
               $serialized_options = array();
                $options_to_exclude = '';
                if ( $result->num_rows > 0 ) {
                    // Build dataset
                    while ( is_array( $row = $result->fetch_assoc() ) ) $serialized_options[] = $row;

                    // Build Exclude SQL
                    foreach ( $serialized_options as $record ) $options_to_exclude .= $record['option_id'].',';
                    $options_to_exclude = ' WHERE option_id NOT IN('.rtrim( $options_to_exclude, ',' ).')';

                    // Update Serialized Options
                    foreach ( $serialized_options as $record ) {
                        $new_option_value = DDWordPressDomainChanger::serializedStrReplace( $data['old_domain'], $data['new_domain'], $record['option_value'] );
                        if ( !$mysqli->query( 'UPDATE '.$data['prefix'].'options SET option_value = "'.$mysqli->escape_string( $new_option_value ).'" WHERE option_id='.(int)$record['option_id'].';' ) ) {
                            throw new Exception( $mysqli->error);
                        }
                       $DDWPDC->actions[] = '[Serialize Replace] Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in option_name="'.$record['option_name'].'"';
                    }

                }

                // Update Options
                if ( !$mysqli->query( 'UPDATE '.$data['prefix'].'options SET option_value = REPLACE(option_value,"'.$data['old_domain'].'","'.$data['new_domain'].'")'.$options_to_exclude.';' ) ) {
                    throw new Exception( $mysqli->error);
                }
               $DDWPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'options.option_value';

                // Update Post Content
                $result = $mysqli->query( 'UPDATE '.$data['prefix'].'posts SET post_content = REPLACE(post_content,"'.$data['old_domain'].'","'.$data['new_domain'].'");' );
                if ( !$result ) {
                    throw new Exception( $mysqli->error);
                } else {
                   $DDWPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'posts.post_content';
                }

                // Update Post GUID
                $result = $mysqli->query( 'UPDATE '.$data['prefix'].'posts SET guid = REPLACE(guid,"'.$data['old_domain'].'","'.$data['new_domain'].'");' );
                if ( !$result ) {
                    throw new Exception( $mysqli->error);
                } else {
                   $DDWPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'posts.guid';
                }
                // Update post_meta
                $result = $mysqli->query( 'UPDATE '.$data['prefix'].'postmeta SET meta_value = REPLACE(meta_value,"'.$data['old_domain'].'","'.$data['new_domain'].'");' );
                if ( !$result ) {
                    throw new Exception( $mysqli->error);
                } else {
                   $DDWPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'postmeta.meta_value';
                }

                // Update "upload_path"
                $upload_dir = dirname( __FILE__ ).'/wp-content/uploads';
                $result = $mysqli->query( 'UPDATE '.$data['prefix'].'options SET option_value = "'.$upload_dir.'" WHERE option_name="upload_path";' );
                if ( !$result ) {
                    throw new Exception( $mysqli->error);
                } else {
                   $DDWPDC->actions[] = 'Option "upload_path" has been changed to "'.$upload_dir.'"';
                }
            }
        } catch ( Exception $exception ) {
            $DDWPDC->error[] = $exception->getMessage();
        }
    }


    public function render( $view_name ) {
        $layout = new View( realpath( dirname( __FILE__ ) . "/../views/_layout.php" ) );
        $view   = new View( realpath( dirname( __FILE__ ) . "/../views/" . $view_name . ".php" ) );

        $this->data["post"]    = $this->post();
        $this->data["flash"]   = $this->getFlash();
        $this->data["body"]    = $view->render( $this->data );

        $html = $layout->render( $this->data );

        return $html;
    }

    public function pathToAction( $action ) {
        return "index.php?action=" . $action;
    }

    public function redirectToAction( $action_method, $flash = null ) {
        if(is_array($flash) && count($flash) == 2) $this->addFlash($flash[0], $flash[1]);
        if($this->action["method"] == $action_method) return;
        $this->headers["Location"] = $this->pathToAction( $action_method );
    }

    public function willRedirect() {
        return array_key_exists( "Location", $this->headers );
    }

    public function isAuthenticated() {
        if ( $cookie = $this->getAuthCookie() ) {
            $not_expired = ( isset( $cookie["expires_at"] ) && time() < $cookie["expires_at"] ) ? true : false;
            $valid_token = ( isset( $cookie["token"] ) && $cookie["token"] == md5( DDWPDC_PASSWORD + $cookie["expires_at"] ) ) ? true : false;
            if ( $not_expired && $valid_token ) return true;
        }
        return false;
    }

    public function addFlash( $type, $message ) {
        if(!in_array($message, $this->flash[$type])) $this->flash[$type][] = $message;
    }

    public function getFlash( $type = null ) {
        return $type ? $this->flash[$type] : $this->flash;
    }

    public function clearFlash() {
        $this->flash = array(
            "error"   => array(),
            "notice"  => array(),
            "warning" => array(),
            "info"    => array()
        );
    }


    public function destroySession() {
        $this->unsetCookie( "session" );
    }

    public function saveSession() {
        $ttl = ( 5 * 60 );

        $session = $this->session;
        $session["_flash"] = $this->flash;

        $this->setCookieData( "session", $session, $ttl );
    }

    public function loadSession() {
        $this->session = $this->getCookieData( "session" );
        if( array_key_exists("_flash", $this->session)) {
            $this->flash = $this->session["_flash"];
        }
    }


    public function unsetAuthCookie() {
        $this->unsetCookie( "auth" );
    }

    public function setAuthCookie() {
        $data               = array();
        $data["expires_at"] = time() + $ttl;
        $data["token"]      = md5( DDWPDC_PASSWORD + $data["expires_at"] );

        $this->setCookie( "auth", $data, $ttl);
    }

    public function getAuthCookie() {
        return $this->getCookieData( "auth" );
    }

    // Cookie Helpers

    public function setCookieData( $key, $data, $ttl = 300 ) {
        setcookie( "wpdc_" . $key, serialize( $data ), ( time() + $ttl ) );
    }

    public function getCookieData( $key ) {
        return $this->cookieExists( $key ) ? unserialize( $_COOKIE["wpdc_" . $key] ) : array();
    }

    public function cookieExists( $key ) {
        return array_key_exists( "wpdc_" . $key, $_COOKIE );
    }

    public function unsetCookie( $key ) {
        setcookie( "wpdc_" . $key, "", time() - 3600 );
    }

    // Post Helper

    public function post( $key = null ) {
        $data = array();
        foreach ( $_POST as $key => $value ) {
            if ( get_magic_quotes_gpc() ) $value = stripslashes( trim( $value ) );
            $data[$key] = $value;
        }
        return ( $key != null ) ? $data[$key] : $data;
    }

}
