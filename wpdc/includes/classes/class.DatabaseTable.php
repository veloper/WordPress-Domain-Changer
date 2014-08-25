<?php
class DatabaseTable {

  public $database;
  public $name;
  public $columns;


  public function __construct($database, $name)
  {
    $this->database = $database;
    $this->name     = $name;
  }

  public function getCount()
  {
    return $this->meta()->rows;
  }

  public function getPrimaryKeyColumns()
  {
    $columns = array();
    foreach($this->columns() as $column) if($column->is_primary_key) $columns[] = $column;
    return $columns;
  }

  public function getMeta()
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
        "Comment"         => "comment"
      );

      $meta = array();
      $record = current(reset($this->database->query("SHOW TABLE STATUS WHERE Name='?'", $this->name)));
      foreach ($mapping as $record_key => $mapped_key) $meta[$mapped_key] = $record->{$record_key};
      return (object) $meta;
  }

  public function getColumns()
  {
    if(!isset($this->columns)) {
        $this->columns = array();
        $mapping = array(
          "Field"   => "name",
          "Type"    => "type",
          "Null"    => "null",
          "Key"     => "key",
          "Default" => "default",
          "Extra"   => "extra"
        );
        $columns = array();
        foreach($this->database->query("DESCRIBE `%s`", $this->name) as $record) {
          $column = array();
          foreach ($mapping as $record_key => $mapped_key) $column[$mapped_key] = $record->{$record_key};
          $column["is_stringish"]   = (bool) preg_match("/(varchar|char|text)/", $column["type"]);
          $column["is_primary_key"] = ($column["key"] == "PRI");

          $columns[$column["name"]] = (object) $column;
          $this->columns = (object) $columns;
        }
      }
      return $this->columns;
  }

}