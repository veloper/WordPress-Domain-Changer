<?php

class DDWordPressDomainChanger {

    /**
     * Actions that occurred during request.
     *
     * @var array
     */
    public $actions = array();

    /**
     * Notices that occurred during request.
     *
     * @var array
     */
    public $notices = array();

    /**
     * Errors that occurred during request.
     *
     * @var array
     */
    public $errors = array();

    /**
     * File contents of the wp-config.php file.
     *
     * @var string
     */
    private $config = '';

    /**
     * Class Constructor
     *
     * @return void
     */
    public function __construct() {
        $this->loadConfigFile();
    }

    /**
     * Gets a constant's value from the wp-config.php file (if loaded).
     *
     * @return mixed; false if not found.
     */
    public function getConfigConstant($constant) {
        if($this->isConfigLoaded()) {
            preg_match("!define\('".$constant."',[^']*'(.+?)'\);!", $this->config, $matches);
            return (isset($matches[1])) ? $matches[1] : false;
        }
        return false;
    }

    /**
     * Gets $table_prefix value from the wp-config.php file (if loaded).
     *
     * @return string;
     */
    public function getConfigTablePrefix() {
        if($this->isConfigLoaded()) {
            preg_match("!table_prefix[^=]*=[^']*'(.+?)';!", $this->config, $matches);
            return (isset($matches[1])) ? $matches[1] : '';
        }
        return '';
    }

    /**
     * Gets the best guess of the "New Domain" based on this files location at runtime.
     *
     * @return string;
     */
    public function getNewDomain() {
        $new_domain = str_replace('http://','', $_SERVER['SERVER_NAME']);
        if(isset($_SERVER['SERVER_PORT']) && strlen($_SERVER['SERVER_PORT']) > 0 && $_SERVER['SERVER_PORT'] != 80) {
            $new_domain .= ':'.$_SERVER['SERVER_PORT'];
        }
        return $new_domain;
    }

    /**
     * Gets the "siteurl" WordPress option (if possible).
     *
     * @return mixed; false if not found.
     */
    public function getOldDomain() {
        if($this->isConfigLoaded()) {
            $mysqli = @new mysqli($this->getConfigConstant('DB_HOST'), $this->getConfigConstant('DB_USER'), $this->getConfigConstant('DB_PASSWORD'), $this->getConfigConstant('DB_NAME'));
            if(mysqli_connect_error()) {
                $this->notices[] = 'Unable to connect to this server\'s database using the settings from wp-config.php; check that it\'s properly configured.';
            } else {
                $result = $mysqli->query('SELECT * FROM '.$this->getConfigTablePrefix().'options WHERE option_name="siteurl";');
                if(is_object($result) && ($result->num_rows > 0)) {
                    $row = $result->fetch_assoc();
                    return str_replace('http://','', $row['option_value']);
                } else {
                    $this->error[] = 'The WordPress option_name "siteurl" does not exist in the "'.$this->getConfigTablePrefix().'options" table!';
                }
            }
        }
        return false;
    }

    /**
     * Returns true if the wp-config.php file was loaded successfully.
     *
     * @return bool;
     */
    public function isConfigLoaded() {
        return (strlen($this->config) > 0);
    }

    /**
    * Replace $find with $replace in a string segment and still keep the integrity of the PHP serialized string.
    *
    * Example:
    *  ... s:13:"look a string"; ...
    *  serializedReplace('string', 'function', $serialized_string)
    *  ... s:15:"look a function"; ...
    *
    * @param string;
    * @param string;
    * @param string;
    * @return string;
    */
    public static function serializedStrReplace($find, $replace, $haystack) {
        $length_diff = strlen($replace) - strlen($find);
        $find_escaped = self::preg_quote($find, '!');
        if(preg_match_all('!s:([0-9]+):"([^"]*?'.$find_escaped.'{1}.*?)";!', self::regExpSerializeEncode($haystack), $matches)) {
            $matches = array_map(array(__CLASS__,'regExpSerializeDecode'), $matches);
            $match_count = count($matches[0]);
            for($i=0;$i<$match_count;$i++) {
                $new_string = str_replace($find, $replace, $matches[2][$i], $replace_count);
                $new_length = ((int) $matches[1][$i]) + ($length_diff * $replace_count);
                $haystack = str_replace($matches[0][$i], 's:'.$new_length.':"'.$new_string.'";', $haystack);
            }
        }
        return $haystack;
    }

    /**
    * Enhanced version of preg_quote() that works properly in PHP < 5.3
    *
    * @param string;
    * @param mixed; string, null default
    * @return string;
    */
    public static function preg_quote($string, $delimiter = null) {
        $string = preg_quote($string, $delimiter);
        if(phpversion() < 5.3) $string = str_replace('-', '\-', $string);
        return $string;
    }

    /**
     * Attempts to load the wp-config.php file into $this->config
     *
     * @return void;
     */
    private function loadConfigFile() {
        $this->config = file_get_contents(dirname(__FILE__).'/../../wp-config.php');
        if(!$this->isConfigLoaded()) {
            $this->notices[] = 'Unable to find "wp-config.php" ... Make sure the '.basename(__FILE__).' file is in the root WordPress directory.';
        } else {
            $this->actions[] = 'wp-config.php file successfully loaded.';
        }
    }


    /**
    * Replaces any occurrence of " (double quote character) within the value
    * of a serialized string segment with [DOUBLE_QUOTE]. This allows for RegExp
    * to properly capture string segment values in self::serializedStrReplace().
    *
    * Example:
    *  ... s:13:"look "a" string"; ...
    *  regExpSerializeEncode($serialized_string)
    *  ... s:13:"look [DOUBLE_QUOTE]a[DOUBLE_QUOTE] string"; ...
    *
    * @param string;
    * @return string;
    */
    private static function regExpSerializeEncode($string) {
        if(preg_match_all('!s:[0-9]+:"(.+?)";!', $string, $matches)) {
            foreach($matches[1] as $match) {
                $string = str_replace($match, str_replace('"', '[DOUBLE_QUOTE]', $match), $string);
            }
        }
        return $string;
    }

    /**
    * Undoes the changes that self::regExpSerializeEncode() made to a string.
    *
    * @see self::regExpSerializeEncode();
    * @param string;
    * @return string;
    */
    private static function regExpSerializeDecode($string) {
        return str_replace('[DOUBLE_QUOTE]', '"', $string);
    }
}
