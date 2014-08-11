<?php
require_once "class.BaseController.php";
require_once "class.WordPressDatabase.php";
class Controller extends BaseController {

    public function routes() {
        $this->addRoute( "GET" , "login"          , "login"         , array( "root" => true ));
        $this->addRoute( "POST", "login/submit"   , "loginSubmit");
        $this->addRoute( "GET" , "database"       , "database"      , array( "auth" => false ) );
        $this->addRoute( "POST", "database/submit", "databaseSubmit", array( "auth" => true ) );
        $this->addRoute( "GET" , "changer"        , "changer"       , array( "auth" => true ) );
        $this->addRoute( "POST", "changer/submit" , "changerSubmit" , array( "auth" => true ) );
        $this->addRoute( "GET" , "changer/success", "changerSuccess", array( "auth" => true ) );
    }


    public function login() {
        $this->data["form_path"] = $this->getActionUrl( "loginSubmit" );
        $this->data["disabled"]  = ( $this->isPasswordValid() == false );

        return $this->render( "login" );
    }

    public function loginSubmit() {
        if ( md5( $this->getPost( 'password' ) ) == md5( WPDC_PASSWORD ) ) {
            $this->setAuthCookie();
            $this->addFlash( "success", "Login Successful!" );
            $this->redirectToAction( "database" );
        } else {
            $this->addFlash( "error", "Incorrect password, please try again." );
            $this->redirectToAction( "login" );
        }
    }

    public function database() {
        $config = PhpFile::create('tests/support/wp-config.php');

        $this->data["fields"] = array(
            array("name" => "host"         , "label" => "Hostname"      , "value" => $config->getConstant("DB_HOST")),
            array("name" => "user"         , "label" => "Username"      , "value" => $config->getConstant("DB_USER")),
            array("name" => "password"     , "label" => "Password"      , "value" => $config->getConstant("DB_PASSWORD")),
            array("name" => "database"     , "label" => "Database Name" , "value" => $config->getConstant("DB_NAME")),
            array("name" => "table_prefix" , "label" => "Table Prefix"  , "value" => $config->getVariable("table_prefix"))
        );

        $this->data["form_path"] = $this->getActionUrl( "databaseSubmit" );

        $this->data["config"] = array("file" => $config, "constants" => array(), "variables" => array());
        foreach ( array( "DB_NAME", "DB_USER", "DB_PASSWORD", "DB_HOST" ) as $name )
            $this->data["config"]["constants"][$name] = $config->getConstant( $name );
        foreach ( array( "table_prefix" ) as $name )
            $this->data["config"]["variables"][$name] = $config->getVariable( $name );

        return $this->render( "form" );
    }

    public function databaseSubmit()
    {
        $post = $this->getPost();
        $db = new WordPressDatabase(array(
            "host"         => $post["host"],
            "user"         => $post["user"],
            "password"     => $post["password"],
            "database"     => $post["database"],
            "table_prefix" => $post["table_prefix"]
        ));

        if(!$db->isConnected()) {
            $this->addFlash("error", "Database Error: Unable to connect using the settings provided.");
            $this->redirectToAction("database");
        }
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
                    throw new Exception( $mysqli->error );
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
                            throw new Exception( $mysqli->error );
                        }
                        $DDWPDC->actions[] = '[Serialize Replace] Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in option_name="'.$record['option_name'].'"';
                    }

                }

                // Update Options
                if ( !$mysqli->query( 'UPDATE '.$data['prefix'].'options SET option_value = REPLACE(option_value,"'.$data['old_domain'].'","'.$data['new_domain'].'")'.$options_to_exclude.';' ) ) {
                    throw new Exception( $mysqli->error );
                }
                $DDWPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'options.option_value';

                // Update Post Content
                $result = $mysqli->query( 'UPDATE '.$data['prefix'].'posts SET post_content = REPLACE(post_content,"'.$data['old_domain'].'","'.$data['new_domain'].'");' );
                if ( !$result ) {
                    throw new Exception( $mysqli->error );
                } else {
                    $DDWPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'posts.post_content';
                }

                // Update Post GUID
                $result = $mysqli->query( 'UPDATE '.$data['prefix'].'posts SET guid = REPLACE(guid,"'.$data['old_domain'].'","'.$data['new_domain'].'");' );
                if ( !$result ) {
                    throw new Exception( $mysqli->error );
                } else {
                    $DDWPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'posts.guid';
                }
                // Update post_meta
                $result = $mysqli->query( 'UPDATE '.$data['prefix'].'postmeta SET meta_value = REPLACE(meta_value,"'.$data['old_domain'].'","'.$data['new_domain'].'");' );
                if ( !$result ) {
                    throw new Exception( $mysqli->error );
                } else {
                    $DDWPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'postmeta.meta_value';
                }

                // Update "upload_path"
                $upload_dir = dirname( __FILE__ ).'/wp-content/uploads';
                $result = $mysqli->query( 'UPDATE '.$data['prefix'].'options SET option_value = "'.$upload_dir.'" WHERE option_name="upload_path";' );
                if ( !$result ) {
                    throw new Exception( $mysqli->error );
                } else {
                    $DDWPDC->actions[] = 'Option "upload_path" has been changed to "'.$upload_dir.'"';
                }
            }
        } catch ( Exception $exception ) {
            $DDWPDC->error[] = $exception->getMessage();
        }
    }



}
