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

  public function getStringishAttributes()
  {
    $array = array();
    foreach($this->table->getStringishColumns() as $column) $array[$column->name] = $this->attributes[$column->name];
    return $array;
  }

  public function getAlterations($find, $replace)
  {
    $alterations = array();
    foreach($this->getStringishAttributes() as $attribute => $value) {
      if(mb_stripos($value, $find) !== false) {
        $alterations[] = new Alteration($this, $attribute, $find, $replace);
      }
    }
    return $alterations;
  }

  public function getPrettyPrimaryKeyAttributes()
  {
    $max_key_length = 0;
    foreach(array_keys($this->getPrimaryKeyAttributes()) as $key) if(strlen($key) > $max_key_length) $max_key_length = strlen($key);

    $return = array();
    foreach($this->getPrimaryKeyAttributes() as $key => $value) $return[] =  str_pad($key, $max_key_length, " ", STR_PAD_LEFT) . ": $value";
    return implode("\n", $return);
  }

  public function getPrimaryKeyAttributes()
  {
    $primary_key_attributes = array();
    foreach($this->table->getPrimaryKeyColumns() as $column) $primary_key_attributes[$column->name] = $this->attributes[$column->name];
    return $primary_key_attributes;
  }

  public function update()
  {
    $changed = $this->getChangedAttributes();

    $set = array();
    foreach($changed as $column_name => $value) $set[] = "$column_name=?";
    $set = implode(',', $set);

    $primary_key_attributes = $this->getPrimaryKeyAttributes();

    $where = array();
    foreach(array_keys($primary_key_attributes) as $column_name) $where[] = "{$column_name} = ?";
    $where = implode(' AND ', $where);

    $tokens = array_merge(array_values($changed), array_values($primary_key_attributes));

    $this->database->query("UPDATE {$this->table->name} SET $set WHERE $where", $tokens);
  }

  public function identity() {
    $identity = array();
    foreach ($this->table->getPrimaryKeyColumns() as $column) $identity[$column->name] = $this->attributes[$column->name];
    return $identity;
  }



}