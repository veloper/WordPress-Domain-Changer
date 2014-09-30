<?php
class Alteration {

    public $record;
    public $attribute;
    public $find;
    public $replace;

    public $original_value;
    public $altered_value;

    protected $is_serialized;

    public $elapsed_time;

    public function __construct( $record, $attribute, $find, $replace ) {
        $this->record    = $record;
        $this->attribute = $attribute;
        $this->find      = $find;
        $this->replace   = $replace;

        $this->original_value = $this->record->attributes[$this->attribute];

        $this->is_serialized = PhpSerializedString::detect( $this->original_value  );
    }

    public function isSerialized() {
        return $this->is_serialized;
    }

    public function getAlteredValue() {
        if ( !$this->altered_value ) {
            if ( $this->isSerialized() ) {
                $string = new PhpSerializedString( $this->original_value );
                $this->altered_value = $string->replace( $this->find, $this->replace )->toString();
            } else {
                $this->altered_value = str_ireplace( $this->find, $this->replace, $this->original_value );
            }
        }
        return $this->altered_value;
    }

    public function toSql() {
        $sql = "";
        if ( $this->isSerialized() ) {
            $sql = $this->record->getSaveSql( array( $this->attribute => $this->getAlteredValue() ) );
        } else {
            if ( empty( $this->find ) ) {
                $sql = $this->record->getSaveSql( array( $this->attribute => $this->replace ) );
            } else {
                $sql = $this->record->getSaveSql( array( $this->attribute => array( $this->find, $this->replace ) ) );
            }
        }
        return $sql;
    }

}
