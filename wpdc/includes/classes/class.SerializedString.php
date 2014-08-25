<?php
class SerializedString {

    /**
     * The original raw string
     *
     * @var string
     */
    public $original_string = "";

    /**
     * The "working" string on which all operations are performed
     *
     * @var string
     */
    public $string = "";


    /**
     * Class Constructor
     *
     * @return void
     */
    public function __construct($string) {
        $this->original_string = $string;
        $this->string          = $string;
    }


    /**
     * Replace $find with $replace in a string segment and still keep the integrity of the PHP serialized string.
     *
     * Example:
     *   new SerializedString('s:13:"look a string"')->replace('string', 'function')->toString() => s:15:"look a function"
     *
     *
     * @param string;
     * @param string;
     * @param string;
     * @return string;
     */
    public function replace($find, $replace) {
        $length_diff    = strlen($replace) - strlen($find);
        $find_escaped   = self::preg_quote($find, '!');
        $encoded_string = self::encodeDoubleQuotes($this->string);
        if(preg_match_all('!s:([0-9]+):"([^"]*?' . $find_escaped . '{1}.*?)";!', $encoded_string, $encoded_matches)) {
            $matches     = array_map(array(__CLASS__, 'decodeDoubleQuotes'), $encoded_matches);
            $match_count = count($matches[0]);
            for($i = 0; $i < $match_count; $i++) {
                $new_string   = str_replace($find, $replace, $matches[2][$i], $replace_count);
                $new_length   = ((int) $matches[1][$i]) + ($length_diff * $replace_count);
                $this->string = str_replace($matches[0][$i], 's:'.$new_length.':"'.$new_string.'";', $this->string);
            }
        }
        return $this;
    }

    public function toString() {
        return $this->string;
    }

    public function __toString() {
        return $this->toString();
    }

    /**
     * Enhanced version of preg_quote() that works properly in PHP < 5.3
     *
     * @param string;
     * @param mixed; string, null default
     * @return string;
     */
    private function preg_quote($string, $delimiter = null) {
        $string = preg_quote($string, $delimiter);
        if(phpversion() < 5.3) $string = str_replace('-', '\-', $string);
        return $string;
    }

    /**
     * Replaces any occurrence of " (double quote character) within the value
     * of a serialized string segment with [DOUBLE_QUOTE]. This allows for RegExp
     * to properly capture string segment values in the replace() method.
     *
     * Example:
     *  ... s:13:"look "a" string"; ...
     *  encodeDoubleQuotes($serialized_string)
     *  ... s:13:"look [DOUBLE_QUOTE]a[DOUBLE_QUOTE] string"; ...
     *
     * @param string;
     * @return string;
     */
    private static function encodeDoubleQuotes($string) {
        if(preg_match_all('!s:[0-9]+:"(.+?)";!', $string, $matches)) {
            foreach($matches[1] as $match) {
                $string = str_replace($match, str_replace('"', '[DOUBLE_QUOTE]', $match), $string);
            }
        }
        return $string;
    }



    /**
     * Undoes the changes that self::encodeDoubleQuotes() made to a string.
     *
     * @see self::encodeDoubleQuotes();
     * @param string;
     * @return string;
     */
    private static function decodeDoubleQuotes($string) {
        return str_replace('[DOUBLE_QUOTE]', '"', $string);
    }

}
