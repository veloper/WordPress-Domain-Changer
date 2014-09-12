<?php
class Controller extends BaseController {

  private $wpdb_settings = array(
    "host"         => "localhost",
    "user"         => null,
    "password"     => null,
    "database"     => null,
    "table_prefix" => null
  );

  private $db = null;

  // == Routes

  public function routes() {
    $this->addRoute( "GET"  , "login"           , "login"          , array( "root" => true ) );
    $this->addRoute( "POST" , "login/submit"    , "loginSubmit" );
    $this->addRoute( "GET"  , "logout"          , "logout" );
    $this->addRoute( "GET"  , "database"        , "database"       , array( "auth" => true ) );
    $this->addRoute( "POST" , "database/submit" , "databaseSubmit" , array( "auth" => true ) );
    $this->addRoute( "GET"  , "change/setup"    , "changeSetup"    , array( "auth" => true, "db" => true ) );
    $this->addRoute( "POST" , "change/review"   , "changeReview"   , array( "auth" => true, "db" => true ) );
    $this->addRoute( "POST" , "change/apply"    , "changeApply"    , array( "auth" => true, "db" => true ) );
    $this->addRoute( "GET"  , "change/success"  , "changeSuccess"  , array( "auth" => true, "db" => true ) );
    $this->addRoute( "GET"  , "change/failure"  , "changeFailure"  , array( "auth" => true, "db" => true ) );
  }

  // == Actions

  public function login() {
    $this->data["form_path"] = $this->getActionUrl( "loginSubmit" );
    $this->data["disabled"]  = ( $this->isPasswordValid() == false );

    return $this->render( "login" );
  }

  public function loginSubmit() {
    if ( md5( $this->getPost( 'password' ) ) == md5( WPDC_PASSWORD ) ) {
      $this->setAuthCookie();
      $this->addFlash( "success", "You have logged-in successully!" );
      $this->redirectToAction( "database" );
    } else {
      $this->addFlash( "error", "Incorrect password, please try again." );
      $this->redirectToAction( "login" );
    }
  }

  public function logout() {
    $this->addFlash( "success", "You have logged-out successully!" );
    $this->unsetAuthCookie();
    $this->redirectToAction( "login" );
  }

  public function database() {
    $config = PhpFile::create( 'tests/support/wp-config.php' );

    $this->data["fields"] = array(
      array( "name" => "host"         , "label" => "Hostname"      , "value" => $config->getConstant( "DB_HOST" )      , "req" => true ),
      array( "name" => "user"         , "label" => "Username"      , "value" => $config->getConstant( "DB_USER" )      , "req" => true ),
      array( "name" => "password"     , "label" => "Password"      , "value" => $config->getConstant( "DB_PASSWORD" )  , "req" => true ),
      array( "name" => "database"     , "label" => "Database Name" , "value" => $config->getConstant( "DB_NAME" )      , "req" => true ),
      array( "name" => "table_prefix" , "label" => "Table Prefix"  , "value" => $config->getVariable( "table_prefix" ) , "req" => true )
    );

    if ( $last_post = $this->getLastPostTo( "databaseSubmit" ) ) {
      foreach ( $this->data["fields"] as $i => $field ) {
        $value = isset( $last_post[$field["name"]] ) ? $last_post[$field["name"]] : "";
        $this->data["fields"][$i]["value"] = $value;
      }
    }

    $this->data["form_path"] = $this->getActionUrl( "databaseSubmit" );

    $this->data["config"] = array( "file" => $config, "constants" => array(), "variables" => array() );
    foreach ( array( "DB_HOST", "DB_USER", "DB_PASSWORD", "DB_NAME" ) as $name )
      $this->data["config"]["constants"][$name] = $config->getConstant( $name );
    foreach ( array( "table_prefix" ) as $name )
      $this->data["config"]["variables"][$name] = $config->getVariable( $name );

    return $this->render( "database" );
  }

  public function databaseSubmit() {
    $post = $this->getPost();

    $this->wpdb_settings = array(
      "host"         => $post["host"],
      "user"         => $post["user"],
      "password"     => $post["password"],
      "database"     => $post["database"],
      "table_prefix" => $post["table_prefix"]
    );

    if ( !$this->db()->isConnected() ) {
      $this->addFlash( "error", "Database Error: Unable to connect using the settings provided." );
      return $this->redirectToAction( "database" );
    }

    if ( empty( $this->db()->getTables() ) ) {
      $this->addFlash( "error", 'Database Error: Could not find any tables that start with the "' . $post["table_prefix"] . '" prefix.' );
      return $this->redirectToAction( "database" );
    }

    $this->session["wpdb_settings"] = $this->wpdb_settings;
    $this->addFlash( "success", "Database connection was successful!" );

    return $this->redirectToAction( "changeSetup" );
  }

  public function changeSetup() {
    $suggested_old_url = (string) str_replace( array( 'http://', 'https://' ), '', $siteurl = $this->db()->getOption( "siteurl" ) );
    $suggested_new_url = (string) str_replace( array( 'http://', 'https://' ), '', $this->getBaseUrl() );

    $this->data["fields"] = array(
      array( "name" => "old_url" , "label" => "Old URL"  , "value" => $suggested_old_url , "req" => true ),
      array( "name" => "new_url" , "label" => "New URL"  , "value" => $suggested_new_url , "req" => true ),
    );

    if ( $last_post = $this->getLastPostTo( "changeReview" ) ) {
      foreach ( $this->data["fields"] as $i => $field ) {
        $value = isset( $last_post[$field["name"]] ) ? $last_post[$field["name"]] : "";
        $this->data["fields"][$i]["value"] = $value;
      }
    }

    $this->data["form_path"] = $this->getActionUrl( "changeReview" );
    $this->data["table_prefix"] = $this->wpdb_settings["table_prefix"];
    $this->data["tables"] = $this->db()->getValidTables();

    return $this->render( "changeSetup" );
  }

  public function changeReview() {
    $post = $this->getPost();

    // Selected Tables
    $valid_tables = $this->db()->getValidTables();
    $selected_tables = array();
    foreach ( array_keys( $post ) as $key ) {
      $table_name = str_replace( "table_", "", $key );
      if ( isset( $valid_tables[$table_name] ) ) $selected_tables[$table_name] = $valid_tables[$table_name];
    }

    // Alterations
    $find    = $post["old_url"];
    $replace = $post["new_url"];
    $results = $this->_getAlterationsForTables( $selected_tables, $find, $replace );

    // Session
    $this->session["selected_table_names"] = array_keys( $selected_tables );
    $this->session["find"]    = $find;
    $this->session["replace"] = $replace;

    // View
    $this->data["results"] = $results;
    $this->data["back_path"] = $this->getActionUrl( "change" );
    $this->data["form_path"] = $this->getActionUrl( "changeApply" );

    return $this->render( "changeReview" );
  }

  public function changeApply() {
    $post = $this->getPost();

    // Selected Tables
    $selected_tables = array();
    $valid_tables = $this->db()->getValidTables();
    foreach ( $this->session["selected_table_names"] as $table_name ) {
      if ( isset( $valid_tables[$table_name] ) ) $selected_tables[$table_name] = $valid_tables[$table_name];
    }

    // Alterations
    $find    = $this->session["find"];
    $replace = $this->session["replace"];
    $tables_and_alterations = $this->_getAlterationsForTables( $selected_tables, $find, $replace );

    // Queries
    $queries = array();
    foreach($tables_and_alterations as $table_alterations) foreach($table_alterations['alterations'] as $alteration) $queries[] = $alteration->toSql();

    $multi_query = implode(";", $queries) . ";";


    TODO MULTI QUERY HERE

    // View
    $this->data["queries"] = $queries;

    return $this->render( "changeApply" );
  }

  public function _getAlterationsForTables( array $tables, $find, $replace ) {
    $results = array();
    foreach ( $tables as $table ) {
      $array = array(
        "table"       => $table,
        "alterations" => $table->getAlterations( $find, $replace )
      );

      if (preg_match('/options/', $table->name )) {
        foreach ( $table->getRecordsWhere( "option_name = ?", array( "upload_path" ) ) as $record ) {
          $old_upload = $record->attributes["option_value"];
          $new_upload = dirname( WP_ROOT_DIR ) . '/wp-content/uploads';
          if ( $alteration = $record->getAlterationFor( "option_value", $old_upload, $new_upload ) ) {
            $array["alterations"][] = $alteration;
          }
        }
      }

      if ( !empty( $array["alterations"] ) ) $results[] = $array;
    }

    $counts = array();
    foreach ( $results as $key => $row ) $counts[$key] = count( $row['alterations'] );
    array_multisort( $counts, SORT_DESC, $results );

    return $results;
  }


  public function changeSubmit() {
    // code...
  }

  // =========== Helpers

  public function getDatabaseChanges( $tables ) {

  }

  public function getWpConfigFile() {
    return $config = PhpFile::create( 'wp-config.php' );
  }

  public function db() {
    if ( $this->db === null || !$this->db->isConnected() ) {
      $this->db = new WordPressDatabase(
        $this->wpdb_settings["host"],
        $this->wpdb_settings["user"],
        $this->wpdb_settings["password"],
        $this->wpdb_settings["database"],
        $this->wpdb_settings["table_prefix"]
      );
    }
    return $this->db;
  }

  // Override
  public function beforeRequest() {
    parent::beforeRequest();

    if ( $this->isRedirecting() ) return null;


    if ( isset( $this->session["wpdb_settings"] ) ) {
      $this->wpdb_settings = $this->session["wpdb_settings"];
    }

    if ( $this->isDatabaseConnectionRequired() && !$this->db()->isConnected() ) {
      $this->addFlash( "error", "Unable to connect to database, please try again." );
      $this->addFlash( "warning", $this->db()->getLastError() );
      $this->redirectToAction( "database" );
    }

    $this->data["logout_path"] = $this->getActionUrl( "logout" );
  }

  public function isDatabaseConnectionRequired() {
    $options = $this->getRequestRoute()["options"];
    return isset( $options["db"] ) ? (bool) $options["db"] : false;
  }




}
