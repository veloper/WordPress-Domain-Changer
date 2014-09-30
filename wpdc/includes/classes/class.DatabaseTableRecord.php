<?php
class DatabaseTableRecord {

  public $database;
  public $table;
  public $previous_attributes = array();
  public $attributes          = array();

  public function __construct( $database, $table, $result ) {
    $this->database = $database;
    $this->table    = $table;
    foreach ( $result as $key => $value ) if ( !ctype_digit( $key ) ) $this->previous_attributes[$key] = $this->attributes[$key] = $value;
  }

  public function getChangedAttributes() {
    return array_diff_assoc( $this->previous_attributes, $this->attributes );
  }

  public function getStringishAttributes() {
    $array = array();
    foreach ( $this->table->getStringishColumns() as $column ) $array[$column->name] = $this->attributes[$column->name];
    return $array;
  }

  public function getAlterationFor( $attribute, $find, $replace ) {
    $needle   = '/' . preg_quote( $find, '/' ) . '/';
    $haystack = $this->attributes[$attribute];
    return preg_match( $needle, $haystack ) ? new Alteration( $this, $attribute, $find, $replace ) : false;
  }

  public function getAlterations( $find, $replace ) {
    $alterations = array();
    foreach ( $this->getStringishAttributes() as $attribute => $value ) {
      if ( $alteration = $this->getAlterationFor( $attribute, $find, $replace ) ) $alterations[] = $alteration;
    }
    return $alterations;
  }

  public function getPrimaryKeyAttributes() {
    $primary_key_attributes = array();
    foreach ( $this->table->getPrimaryKeyColumns() as $column ) $primary_key_attributes[$column->name] = $this->attributes[$column->name];
    return $primary_key_attributes;
  }

  public function getSaveSql( $attributes = null ) {
    $tokens = array();

    $set = array();
    $changed_attributes = is_array( $attributes ) ? $attributes : $this->getChangedAttributes();
    foreach ( $changed_attributes as $column_name => $value ) {
      if ( is_array( $value ) && count( $value ) == 2 ) {
        list( $find, $replace ) = $value;
        $set[] = "`$column_name`=REPLACE(`$column_name`, ?, ?)";
        $tokens[] = $find;
        $tokens[] = $replace;
      } else {
        $set[] = "`$column_name`=?";
        $tokens[] = $value;
      }
    }
    $set = implode( ', ', $set );

    $where = array();
    foreach ( $this->getPrimaryKeyAttributes() as $column_name => $value ) {
      $where[] = "`$column_name`=?";
      $tokens[] = $value;
    }
    $where = implode( ' AND ', $where );

    $sql = $this->database->getPreparedSql( "UPDATE `{$this->table->name}` SET $set WHERE $where", $tokens );

    return $sql;
  }

}
