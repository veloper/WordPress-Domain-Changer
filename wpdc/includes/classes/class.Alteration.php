<?php

class Alteration {

  public $record    = null;
  public $attribute = null;
  public $find      = null;
  public $replace   = null;

  public function __construct($record, $attribute, $find, $replace)
  {
    $this->record    = $record;
    $this->attribute = $attribute;
    $this->find      = $find;
    $this->replace   = $replace;
  }

  public function getTableName() {
    return $this->record->table->name;
  }

  public function getColumnName() {
    return $this->attribute;
  }

  public function getOriginalValue()
  {
    return $this->record->attributes[$this->attribute];
  }


  public function getAlteredValue()
  {
    $altered  = "";
    $original = $this->getOriginalValue();

    if(PhpSerializedString::test($original)) {
      $string = new PhpSerializedString($original);
      $altered = $string->replace($this->find, $this->replace)->toString();
    } else {
      $altered = str_ireplace($this->find, $this->replace, $original);
    }

    return $altered;
  }

  public function getDiff()
  {
    $diff = new diff_match_patch();
    $main_diff = $diff->diff_main($this->getOriginalValue(), $this->getAlteredValue());
    $diff->diff_cleanupSemantic($main_diff);
    return $diff->diff_prettyHtml($main_diff);
  }

}