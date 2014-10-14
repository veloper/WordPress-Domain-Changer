<?php
class WordPressDatabase extends Database {

    protected $table_prefix = null;

    public function __construct( $host, $user, $password, $database, $table_prefix ) {
        parent::__construct( $host, $user, $password, $database );
        $this->table_prefix = $table_prefix;
    }

    public function getValidTables() {
        $tables = array();
        foreach ( $this->getTables() as $name => $table ) {
            if ( stripos( $name, $this->table_prefix ) === false ) continue;
            if ( $table->getRowCount() <= 0 ) continue;
            $stringish_columns = $table->getStringishColumns();
            if ( empty( $stringish_columns) ) continue;
            $tables[$name] = $table;
        }
        return $tables;
    }

    public function getOption( $name ) {
        $results = $this->query( "SELECT * FROM {$this->table_prefix}options WHERE option_name = ?", array( $name ) );
        return !empty( $results ) ? (string) $results[0]['option_value'] : null;
    }


}
