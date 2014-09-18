<?php
mb_internal_encoding('UTF-8');
class Database {

  protected $credentials = null;

  protected $connection = null;
  protected $is_connected = false;
  protected $last_error = null;

  public function __construct( $host, $user, $password, $database ) {
    list( $host, $port ) = array_pad( explode( ':', $host ), 2, 3306 );
    $this->credentials = (object) array(
      "host"     => str_replace( "localhost", "127.0.0.1", $host ),
      "port"     => $port,
      "user"     => $user,
      "password" => $password,
      "database" => $database
    );
    $this->getConnection();
  }

  public function getTables() {
    if (empty( $this->tables ) ) {
      $this->tables = array();
      foreach ( $this->query( "SHOW TABLES" ) as $row ) {
        $table = new DatabaseTable( $this, current( $row ) );
        $this->tables[$table->name] = $table;
      }
    }
    return $this->tables;
  }

  public function getTableByName($name)
  {
    return $this->getTables()[$name];
  }

  public function getTableNameFromSql( $sql ) {
    preg_match( "/from{1}.+?([^\s`'\"\-]+)(WHERE|AS|\s|){1}/ism", $sql, $matches );
    return trim($matches[1]);
  }

  public function getTableRecords( $query, $tokens = array() ) {
    $table = $this->getTables()[$this->getTableNameFromSql($query)];
    $records = array();
    foreach($this->query( $query, $tokens ) as $row) $records[] = new DatabaseTableRecord($this, $table, $row);
    return $records;
  }

  public function query( $query, $tokens = array() ) {
    $result = $this->getConnection()->query( $this->getPreparedSql( $query, $tokens ) );
    $rows = array();
    if ( is_object( $result ) && ( $result->num_rows > 0 ) ) while ( $row = $result->fetch_assoc() ) $rows[] = $row;
    return $rows;
  }

  public function multiQuery($queries = array())
  {
    $details = array();

    foreach($queries as $i => $query) {
      $result = $this->getConnection()->query($query);
      $details[$i] = array(
        'query'         => $query,
        'result'        => $result,
        'error'         => null,
        'affected_rows' => $this->getConnection()->affected_rows
      );
      if( $error = $this->getConnection()->error ) {
        $details[$i]["error"] = $this->last_error = "MySQL Error: $error | Query (" . ($i + 1) . "/" . count($queries) . "): '{$queries[$i]}'";
      }
    }
    return $details;
  }

  public function getPreparedSql( $query, $tokens = array() ) {
    if ( substr_count( $query, "?" ) != count( $tokens ) ) throw new Exception( "Database->getPreparedSql(): Token count missmatch." );
    $query = str_replace('?', '[________?________]', $query);
    foreach ( $tokens as $token ) $query = preg_replace( "/\[________\?________\]/", $this->getEscapedSqlFromValue( $token ), $query, 1 );
    return $query;
  }

  public function getEscapedSqlFromValue( $value ) {
    if ( is_numeric( $value ) ) {
      $replacement = ( intval( $value ) == $value ) ? intval( $value ) : floatval( $value );
    } elseif ( is_array( $value ) ) {
      $replacement = implode( ', ', array_map( array( __CLASS__, __METHOD__ ), $value ) );
    } elseif ( $value === null || stripos( (string) $value, "null" ) !== false ) {
      $replacement = "NULL";
    } else {
      $replacement = '"' . $this->escape( (string) $value ) . '"';
    }
    return $replacement;
  }

  public function isConnectable() {
    return ($this->getConnection() !== false);
  }

  public function getLastError() {
    return $this->last_error;
  }

  public function getConnection() {
    if(!$this->connection) {
      if ( function_exists( "mysqli_report" ) ) mysqli_report( MYSQLI_REPORT_STRICT );
      try {
        $this->connection = new mysqli(
          $this->credentials->host,
          $this->credentials->user,
          $this->credentials->password,
          $this->credentials->database,
          $this->credentials->port
        );
        // Connection Test
        if ( mysqli_connect_errno() ) {
          throw Exception( "(#" . mysqli_connect_errno() . ") " . mysqli_connect_error() );
        } else {
          $this->is_connected = true;
        }

        // Charset
        $this->connection->set_charset("utf8");
        if ( $this->connection->error ) throw Exception( "Error loading character set utf8: {$mysqli->error}" );

      } catch ( Exception $e ) {
        $this->last_error = $e->getMessage();
        $this->connection = false;
      }
    }
    return $this->connection;
  }

  public function escape( $value ) {
    return $this->getConnection()->escape_string( $value );
  }


}
