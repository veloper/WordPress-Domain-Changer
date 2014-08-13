<?php
class WordPressDatabase {

    protected $host         = null;
    protected $user         = null;
    protected $password     = null;
    protected $database     = null;
    protected $port         = null;
    protected $table_prefix = null;

    protected $last_connection_error = false;
    protected $connection = null;

    public function __construct( $connection_info_array ) {
        extract($connection_info_array);
        $this->setHost( $host );
        $this->setUser( $user );
        $this->setPassword( $password );
        $this->setDatabase( $database );
        $this->setTablePrefix(isset($table_prefix) ? $table_prefix : "wp_" );
        if(isset($port)) $this->setPort($port);
    }

    public function isConnected() {
        return $this->last_connection_error ? false : true;
    }

    public function getLastConnectionError()
    {
        return "Failed to connect to MySQL: " . $this->last_connection_error;
    }

    public function mysqli()
    {
        $mysqli = null;
        if(function_exists("mysqli_report")) mysqli_report(MYSQLI_REPORT_STRICT);
        try {
            $mysqli = @new mysqli(
                $this->getHost(),
                $this->getUser(),
                $this->getPassword(),
                $this->getDatabase(),
                $this->getPort()
            );
            if(mysqli_connect_error()) throw Exception(mysqli_connect_errno() . " - " . mysqli_connect_error());
        } catch (Exception $e) {
            $this->last_connection_error = $e->getMessage();
        }
        return $mysqli;
    }

    public function getConnection() {
        $mysqli = $this->mysqli();
        return !$this->getLastConnectionError() ? $mysqli : false;
    }

    // Getter
    public function getHost() {
        return $this->host;
    }
    public function getUser() {
        return $this->user;
    }
    public function getPassword() {
        return $this->password;
    }
    public function getDatabase() {
        return $this->database;
    }
    public function getPort() {
        return $this->port;
    }
    public function getTablePrefix() {
        return $this->table_prefix;
    }

    // Setters
    public function setHost( $value ) {
        $value = str_replace("localhost", "127.0.0.1", $value);
        if(strpos($value, ':') !== false) {
            list($host, $port) = explode(":", $value);
            if( (int) $port > 0 ) {
                $this->setPort($port);
                $this->setHost($host);
            }
        } else {
            $this->host = $value;
        }
    }
    public function setUser( $value ) {
        $this->user = $value;
    }
    public function setPassword( $value ) {
        $this->password = $value;
    }
    public function setDatabase( $value ) {
        $this->database = $value;
    }
    public function setPort($value) {
        $this->port = (int) $value;
    }
    public function setTablePrefix( $value ) {
        $this->table_prefix = $value;
    }



    public function query( $query ) {
        $results = array();
        if ( $this->isConnected() ) {
            $result = $this->getConnection()->query( $query );
            if ( is_object( $result ) && ( $result->num_rows > 0 ) ) {
                while ( $row = $result->fetch_assoc() ) array_push( $row );
            }
        }
        return ( count( $results ) > 0 ) ? $results : false;
    }

    public function getTableName( $table ) {
        return $this->table_prefix . $table;
    }

    public function escape( $value ) {
        return $this->getConnection()->escape_string( $value );
    }

    public function option( $name ) {
        $query = 'SELECT * FROM ' . $this->getTableName( "options" ) . ' WHERE option_name="' . $this->escape( $name ) . '";';
        if ( $results = $this->getQueryResults( $query ) ) {
            return $results[0]['option_value'];
        }
        return null;
    }


}
