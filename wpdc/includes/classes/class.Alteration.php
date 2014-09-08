<?php

class Alteration {

  public $record;
  public $attribute;
  public $find;
  public $replace;

  public $original_value;
  public $altered_value;

  protected $is_serialized;

  public function __construct( $record, $attribute, $find, $replace ) {
    $this->record    = $record;
    $this->attribute = $attribute;
    $this->find      = $find;
    $this->replace   = $replace;

    $this->original_value = $this->record->attributes[$this->attribute];

    $this->is_serialized = PhpSerializedString::detect( $this->original_value  );
  }

  public function getTableName() {
    return $this->record->table->name;
  }

  public function getColumnName() {
    return $this->attribute;
  }

  public function getOriginalValue() {
    return $this->original_value;
  }

  public function getAlteredValue() {
    if ( !$this->altered_value ) {
      $original = $this->getOriginalValue();

      if ( $this->is_serialized ) {
        $string = new PhpSerializedString( $original );
        $this->altered_value = $string->replace( $this->find, $this->replace )->toString();
      } else {
        $this->altered_value = str_ireplace( $this->find, $this->replace, $original );
      }
    }
    return $this->altered_value;
  }

  public function toSql() {
    if($this->is_serialized) {
      return $this->record->getSaveSql(array($this->attribute => $this->getAlteredValue()));
    } else {
      return $this->record->getSaveSql(array($this->attribute => array($this->find, $this->replace)));
    }
  }

}
