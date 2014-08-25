<?php
class WordPressDatabase extends Database {

    protected $table_prefix = null;

    public function __construct( $host, $user, $password, $database, $table_prefix ) {
        parent::__construct($host, $user, $password, $database);
        $this->table_prefix = $table_prefix;
    }

    public function getTables() {
        $tables = array();
        foreach(parent::getTables() as $table)
            if(stripos($name, $this->table_prefix) === 0)
                $tables[$table->name] = $table;
        return $tables;
    }

    public function getPrefixedTableName( $table ) {
        return $this->table_prefix . $table;
    }

    public function escape( $value ) {
        return $this->getConnection()->escape_string( $value );
    }

    public function getOption( $name ) {
        $results = $this->query('SELECT * FROM {$this->table_prefix}options WHERE option_name = ?;', $name);
        return !empty($results) ? (string) $results[0]['option_value'] : null;
    }


}
