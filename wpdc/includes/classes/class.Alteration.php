<?php

class Alteration {

  public $record  = null;
  public $field   = null;
  public $find    = null;
  public $replace = null;

  public function __construct($record, $field, $find, $replace)
  {
    $this->record  = $record;
    $this->field   = $field;
    $this->find    = $find;
    $this->replace = $replace;
  }


}