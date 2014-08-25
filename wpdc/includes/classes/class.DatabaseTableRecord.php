<?php
class DatabaseTableRecord {

  public $database;
  public $table;
  public $previous_attributes = array();
  public $attributes          = array();

  public function __construct($database, $table, $result)
  {
    $this->database = $database;
    $this->table    = $table;
    foreach ($result as $key => $value) if(!ctype_digit($key)) $this->previous_attributes[$key] = $this->attributes[$key] = $value;
  }

  public function getChangedAttributes()
  {
    return array_diff_assoc($this->previous_attributes, $this->attributes);
  }

  public function update()
  {
    $changed = $this->getChangedAttributes();

    $primary_key_to_value = array();
    foreach($this->table->getPrimaryKeyColumns() as $column) $primary_key_to_value[$column->name] = $this->attributes[$column->name];

    $set = array();
    foreach($changed as $column_name => $value) $set[] = "$column_name=?";
    $set = implode(',', $set);

    $where = array();
    foreach(array_keys($primary_key_to_value) as $column_name) $where[] = "{$column_name} = ?";
    $where = implode(' AND ', $where);

    $tokens = array_merge(array_values($changed), array_values($primary_key_to_value));

    $this->database->query("UPDATE {$this->table->name} SET $set WHERE $where", $tokens);
  }

  public function identity() {
    $identity = array();
    foreach ($this->table->getPrimaryKeyColumns() as $column) $identity[$column->name] = $this->attributes[$column->name];
    return $identity;
  }



}