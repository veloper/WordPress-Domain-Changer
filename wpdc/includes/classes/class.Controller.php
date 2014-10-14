<?php
class Controller extends BaseController {

  private $db   = null;
  private $wpdb = array( "host" => "localhost", "user" => null, "password" => null, "database" => null, "table_prefix" => null );


  // == Routes ==============================================================

  public function routes() {
    $this->addRoute( "GET"  , "login"           , "login"          , array( "root" => true ) );
    $this->addRoute( "POST" , "login/submit"    , "loginSubmit" );
    $this->addRoute( "GET"  , "logout"          , "logout" );

    $this->addRoute( "GET"  , "database"        , "database"       , array( "auth" => true ) );
    $this->addRoute( "POST" , "database/submit" , "databaseSubmit" , array( "auth" => true ) );

    $this->addRoute( "GET"  , "tables"          , "tables"         , array( "auth" => true, "db" => true ) );
    $this->addRoute( "POST" , "tables/submit"   , "tablesSubmit"   , array( "auth" => true, "db" => true ) );

    $this->addRoute( "GET"  , "change/setup"    , "changeSetup"    , array( "auth" => true, "db" => true, "tables" => true ) );
    $this->addRoute( "POST" , "change/review"   , "changeReview"   , array( "auth" => true, "db" => true, "tables" => true ) );
    $this->addRoute( "POST" , "change/submit"   , "changeSubmit"   , array( "auth" => true, "db" => true, "tables" => true ) );

    $this->addRoute( "GET"  , "success"         , "success"        , array( "auth" => true, "db" => true, "tables" => true ) );
  }

  // == Actions ==============================================================

  public function login() {
    $this->data["form_path"] = $this->getActionUrl( "loginSubmit" );
    $this->data["disabled"]  = ( $this->isPasswordValid() == false );  // TODO: better naming for isPasswordValid

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
    $config = PhpFile::readFromRelativePath( 'wp-config.php' );

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

    $this->wpdb = array(
      "host"         => $post["host"],
      "user"         => $post["user"],
      "password"     => $post["password"],
      "database"     => $post["database"],
      "table_prefix" => $post["table_prefix"]
    );

    if ( empty( $this->session["wpdb"] ) || $this->wpdb != $this->session["wpdb"] ) {
      $this->session["wpdb"] = $this->wpdb;
      $this->setSelectedTableNames( array() );
    }

    if ( !$this->db()->isConnectable() ) {
      $this->addFlash( "error", "Database Error: Unable to connect using the settings provided." );
      return $this->redirectToAction( "database" );
    }

    if ( empty( $this->db()->getTables() ) ) {
      $this->addFlash( "error", 'Database Error: Could not find any tables that start with the "' . $post["table_prefix"] . '" prefix.' );
      return $this->redirectToAction( "database" );
    }

    $this->session["wpdb"] = $this->wpdb;
    $this->addFlash( "success", "Database connection successful!" );

    return $this->redirectToAction( "tables" );
  }

  public function tables() {
    $this->data["form_path"] = $this->getActionUrl( "tablesSubmit" );
    $this->data["table_prefix"] = $this->wpdb["table_prefix"];
    $this->data["tables"] = $this->db()->getValidTables();
    $this->data["selected_table_names"] = $this->getSelectedTableNames();

    return $this->render( "tables" );
  }

  public function tablesSubmit() {
    $post = $this->getPost();

    // Selected Tables
    $valid_tables = $this->db()->getValidTables();
    $selected_tables = array();
    foreach ( array_keys( $post ) as $key ) {
      $table_name = str_replace( "table_", "", $key );
      if ( isset( $valid_tables[$table_name] ) ) $selected_tables[$table_name] = $valid_tables[$table_name];
    }

    $this->setSelectedTableNames( array_keys( $selected_tables ) );

    if ( !empty( $selected_tables ) ) {
      $this->addFlash( "success", "Table selections updated successully!" );
    }

    return $this->redirectToAction( "changeSetup" );
  }

  public function changeSetup() {
    $suggested_old_url = (string) str_replace( array( 'http://', 'https://' ), '', $siteurl = $this->db()->getOption( "siteurl" ) );
    $suggested_new_url = (string) str_replace( array( 'http://', 'https://', '/wpdc' ), '', $this->getBaseUrl() );

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
    $this->data["table_prefix"] = $this->wpdb["table_prefix"];
    $this->data["tables"] = $this->getSelectedTables();

    return $this->render( "changeSetup" );
  }

  public function changeReview() {
    $post = $this->getPost();

    // Alterations
    $find    = $post["old_url"];
    $replace = $post["new_url"];
    $results = $this->getAlterationsForTables( $this->getSelectedTables(), $find, $replace );

    // Session
    $this->session["find"]    = $find;
    $this->session["replace"] = $replace;

    // View
    $this->data["find"]      = $find;
    $this->data["results"]   = $results;
    $this->data["form_path"] = $this->getActionUrl( "changeSubmit" );

    return $this->render( "changeReview" );
  }

  public function changeSubmit() {
    $post = $this->getPost();

    // Selected Tables
    $selected_tables = $this->getSelectedTables();

    // Alterations
    $find    = $this->session["find"];
    $replace = $this->session["replace"];
    $tables_and_alterations = $this->getAlterationsForTables( $selected_tables, $find, $replace );

    // Queries
    $queries = array();
    foreach ( $tables_and_alterations as $table_alterations )
      foreach ( $table_alterations['alterations'] as $alteration )
        $queries[] = $alteration->toSql();

      // Execute
      $results = $this->db()->multiQuery( $queries );

    // Errors ?
    $errors = array();
    foreach ( $results as $result ) if ( $result["error"] ) $errors[] = $result["error"];
      if ( count( $errors ) > 0 ) {
        foreach ( $errors as $msg ) {
          $this->addFlash( "error", $msg );
        }
        return $this->redirectToAction( "changeSetup" );
      }

    return $this->redirectToAction( "success" );
  }

  public function success() {
    $this->addFlash( "success", "All database queries executed successully!" );

    $this->data["find"]    = $this->session["find"];
    $this->data["replace"] = $this->session["replace"];

    return $this->render( "success" );
  }

  // == Helpers ==============================================================

  private function getAlterationsForTables( array $tables, $find, $replace ) {
    $results = array();
    foreach ( $tables as $table ) {
      $array = array(
        "table"       => $table,
        "alterations" => $table->getAlterations( $find, $replace )
      );

      if ( preg_match( '/options/', $table->name ) ) {
        foreach ( $table->getRecordsWhere( "option_name = ?", array( "upload_path" ) ) as $record ) {
          $old_upload = $record->attributes["option_value"];
          $new_upload = dirname( WP_ROOT_DIR ) . '/wp-content/uploads';
          if ( $old_upload != $new_upload && $alteration = $record->getAlterationFor( "option_value", $old_upload, $new_upload ) ) {
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

  private function getSelectedTables() {
    $selected = array();
    $valid    = $this->db()->getValidTables();
    foreach ( $this->getSelectedTableNames() as $name ) if ( isset( $valid[$name] ) ) $tables[$name] = $valid[$name];
      return $tables;
  }

  private function getSelectedTableNames() {
    return isset( $this->session["selected_table_names"] ) ? $this->session["selected_table_names"] : array();
  }

  private function setSelectedTableNames( array $table_names ) {
    $this->session["selected_table_names"] = $table_names;
  }

  private function getWpConfigFile() {
    return $config = PhpFile::create( 'wp-config.php' );
  }

  private function db() {
    if ( ( $this->db instanceof WordPressDatabase ) === false ) {
      $this->db = new WordPressDatabase(
        $this->wpdb["host"],
        $this->wpdb["user"],
        $this->wpdb["password"],
        $this->wpdb["database"],
        $this->wpdb["table_prefix"]
      );
    }
    return $this->db;
  }

  // Override
  public function beforeRequest() {
    parent::beforeRequest();

    if ( $this->isRedirecting() ) return null;

    if ( isset( $this->session["wpdb"] ) ) $this->wpdb = $this->session["wpdb"];

    if ( $this->isDatabaseConnectionRequired() && !$this->db()->isConnectable() ) {
      $this->addFlash( "error", "Unable to connect to database, please try again." );
      $this->addFlash( "warning", $this->db()->getLastError() );
      $this->redirectToAction( "database" );
    }

    if ( $this->isTableSelectionsRequired() && empty( $this->getSelectedTableNames() ) ) {
      $this->addFlash( "error", "At least one table must be selected." );
      $this->redirectToAction( "tables" );
    }

  }

  // Override
  public function beforeRender() {
    parent::beforeRender();

    $this->data["logout_path"] = $this->getActionUrl( "logout" );

    $this->data["nav"] = array(
      "database" => array(
        "path"     => $this->getActionUrl( "database" ),
        "disabled" => false,
        "valid" => $this->db()->isConnectable()
      ),
      "tables" => array(
        "path"     => $this->getActionUrl( "tables" ),
        "disabled" => !$this->db()->isConnectable(),
        "count"    => count( $this->getSelectedTableNames() )
      ),
      "change" => array(
        "path"     => $this->getActionUrl( "changeSetup" ),
        "disabled" => empty( $this->getSelectedTableNames() )
      )
    );
  }

  public function isDatabaseConnectionRequired() {
    $options = $this->getRequestRoute()["options"];
    return isset( $options["db"] ) ? (bool) $options["db"] : false;
  }

  public function isTableSelectionsRequired() {
    $options = $this->getRequestRoute()["options"];
    return isset( $options["tables"] ) ? (bool) $options["tables"] : false;
  }

}
