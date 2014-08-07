<?php

class WordPressDatabase {


    public function __constructor( $host, $user, $password, $database, $table_prefix = "wp_" ) {
        $this->host         = $host;
        $this->user         = $user;
        $this->password     = $password;
        $this->database     = $database;
        $this->table_prefix = $table_prefix;
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
    public function getTablePrefix() {
        return $this->table_prefix;
    }

    // Setters
    public function setHost( $value ) {
        $this->host = $value;
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
    public function setTablePrefix( $value ) {
        $this->table_prefix = $value;
    }

    public function isConnected() {
        return mysqli_connect_error() ? false : true;
    }

    public function connection() {
        if ( !isset( $this->_conntection ) ) {
            $this->_conntection = new mysqli( $this->getHost(), $this->getUser(), $this->getPassword(), $this->getDatabase() );
        }
        return $this->_conntection;
    }

    public function query( $query ) {
        $results = array();
        if ( $this->isConnected() ) {
            $result = $this->connection()->query( $query );
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
        return $this->connection()->escape_string( $value );
    }

    public function option( $name ) {
        $query = 'SELECT * FROM ' . $this->getTableName( "options" ) . ' WHERE option_name="' . $this->escape( $name ) . '";';
        if ( $results = $this->getQueryResults( $query ) ) {
            return $results[0]['option_value'];
        }
        return null;
    }


}
