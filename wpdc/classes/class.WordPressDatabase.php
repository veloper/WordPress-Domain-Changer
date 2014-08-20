<?php
class WordPressDatabase {

    protected $host         = null;
    protected $user         = null;
    protected $password     = null;
    protected $database     = null;
    protected $port         = null;
    protected $table_prefix = null;

    protected $db = null;
    protected $last_error = null;

    public function __construct( $connection_info_array ) {
        extract($connection_info_array);
        $this->setHost( $host );
        $this->setUser( $user );
        $this->setPassword( $password );
        $this->setDatabase( $database );
        $this->setTablePrefix(isset($table_prefix) ? $table_prefix : "wp_" );
        if(isset($port)) $this->setPort($port);
        $this->connect();
    }

    public function connect()
    {
        $this->db();
    }

    public function isConnected() {
        return $this->getLastError() ? false : true;
    }

    public function getLastError()
    {
        if($this->last_error) {
            return "Failed to connect to MySQL: " . $this->last_error;
        }
        return null;
    }

    public function db()
    {
        if(function_exists("mysqli_report")) mysqli_report(MYSQLI_REPORT_STRICT);
        try {
            $this->db = new mysqli(
                $this->getHost(),
                $this->getUser(),
                $this->getPassword(),
                $this->getDatabase(),
                $this->getPort()
            );
            if(mysqli_connect_error()) throw Exception(mysqli_connect_errno() . " - " . mysqli_connect_error());
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            $this->db = null;
        }
        return $this->db;
    }

    public function getConnection() {
        $db = $this->db();
        return !$this->getLastError() ? $db : false;
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

    public function getShowTablesStatus()
    {
        $mapping = array(
            "Name"            => "name",
            "Engine"          => "engine",
            "Version"         => "version",
            "Row_format"      => "row_format",
            "Rows"            => "rows",
            "Avg_row_length"  => "avg_row_length",
            "Data_length"     => "data_length",
            "Max_data_length" => "max_data_length",
            "Index_length"    => "index_length",
            "Data_free"       => "data_free",
            "Auto_increment"  => "auto_increment",
            "Create_time"     => "create_time",
            "Update_time"     => "update_time",
            "Check_time"      => "check_time",
            "Collation"       => "collation",
            "Checksum"        => "checksum",
            "Create_options"  => "create_options",
            "Comment"         => "comment",
        );

        // Tables
        $tables = array();
        foreach($this->query("SHOW TABLE STATUS") as $row) {
            $table = array();
            foreach ($mapping as $original => $new) {
                $table[$new] = $row[$original];
            }
            $tables[$table["name"]] = $table;
        }

        return $tables;
    }

    public function getDescribeTable($table_name)
    {
        $mapping = array(
            "Field"           => "field",
            "Type"            => "type",
            "Null"            => "null",
            "Key"             => "key",
            "Default"         => "default",
            "Extra"           => "extra"
        );
        $fields = array();
        foreach($this->query("DESCRIBE `%s`", array($table_name)) as $row) {
            $field = array();
            foreach ($mapping as $original => $new) $field[$new] = $row[$original];
            $field["is_stringish"] = (bool) preg_match("/(varchar|char|text)/", $field["type"]);

            $fields[$field["field"]] = $field;
        }
        return $fields;
    }

    public function getAllTables() {
        $tables = $this->getShowTablesStatus();
        foreach ($tables as $name => $details) $tables[$name]["description"] = $this->getDescribeTable($name);
        return $tables;
    }


    public function getTables() {
        $tables = array();
        foreach($this->getAllTables() as $name => $details) {
            if(stripos($name, $this->getTablePrefix()) === 0) $tables[$name] = $details;
        }
        return $tables;
    }

    public function query( $query, $tokens = array() ) {
        if(is_array($tokens) && count($tokens) > 0) {
            $args = array_map(array($this, "escape"), $tokens);
            array_unshift($args, $query);
            $query = call_user_func_array('sprintf', $args);
        }
        $results = array();
        if ( $this->isConnected() ) {
            $result = $this->getConnection()->query( $query );
            if ( is_object( $result ) && ( $result->num_rows > 0 ) ) {
                while ( $row = $result->fetch_array(MYSQLI_BOTH) ) $results[] = $row;
            }
        }
        return $results;
    }

    public function getPrefixedTableName( $table ) {
        return $this->table_prefix . $table;
    }

    public function escape( $value ) {
        return $this->getConnection()->escape_string( $value );
    }

    public function option( $name ) {
        $query = 'SELECT * FROM ' . $this->getPrefixedTableName( "options" ) . ' WHERE option_name="' . $this->escape( $name ) . '";';
        if ( $results = $this->getQueryResults( $query ) ) {
            return $results[0]['option_value'];
        }
        return null;
    }


}
