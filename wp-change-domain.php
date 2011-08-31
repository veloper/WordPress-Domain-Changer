<?php
/**
 * Author: Daniel Doezema
 * Contributor: Alon Peer, Eric Butera
 * Author URI: http://dan.doezema.com
 * Version: 1.0 (Beta 2)
 * Description: This script was developed to help ease migration of WordPress sites from one domain to another.
 *
 * Copyright (c) 2010, Daniel Doezema
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in the
 *     documentation and/or other materials provided with the distribution.
 *   * The names of the contributors and/or copyright holder may not be
 *     used to endorse or promote products derived from this software without
 *     specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL DANIEL DOEZEMA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @copyright Copyright (c) 2010 Daniel Doezema. (http://dan.doezema.com)
 * @license http://dan.doezema.com/licenses/new-bsd New BSD License
 */

/* == CONFIG ======================================================== */

// Authentication Password
define('WPDC_PASSWORD', 'Replace-This-Password');

// Session Timeout (Default: 5 minutes)
define('WPDC_COOKIE_LIFETIME', 60 * 5);

// Show PHP Errors?
define('WPDC_DEBUG', false);

/* == NAMESPACE CLASS =============================================== */

class WPDC {

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
     * MySQLi Object
     *
     * @var string
     */
    private $mysqli = null;

    /**
     * Mu Table Names
     *
     * @var string
     */
    private $mu_tables = null;

    /**
     * Class Constructor
     *
     * @return void
     */
    public function __construct() {
        $this->loadConfigFile();
        if($this->isMultiSite()) {
            if(!$this->isRootDirWritable()) {
                $this->notices[] = 'The "'.dirname($this->getConfigFilePath()).'" directory is not writable.';
            }
            if(!$this->isConfigFileWritable()) {
                $this->notices[] = 'The "'.$this->getConfigFilePath().'" file is not writable.';
            }
        }
    }

    // == PUBLIC METHODS ===============================================

    /**
     * Creates a backup of the config file (if possible).
     *
     * @param mixed; string, the new file's basename; null, the default backup name.
     * @return bool;
     */
    public function createBackupConfigFile($new_file_name = null) {
        $new_file = $new_file_name !== null ? (string) $new_file_name : dirname($this->getConfigFilePath()).'/bak.'.microtime(true).'.wp-config.php';
        return copy($this->getConfigFilePath(), $new_file);
    }

    /**
     * Gets a constant's value from the wp-config.php file (if loaded). If the
     * the constant is found the returned value will ALWAYS be a string. Thus a
     * constant like define('MULTISITE', true); will be returned => (string) "true"
     *
     * @return mixed; false if not found.
     */
    public function getConfigConstant($constant) {
        if($this->isConfigLoaded()) {
            preg_match("/define\s*\(\s*[\'\"]{1}".$constant."[\'\"]{1}\s*,\s*[\'\"\-\.]*(.+?)'?\s*\);/", $this->config, $matches);
            //'/define\s*\(\s*[\'\"]DOMAIN_CURRENT_SITE[\'\"]\s*,\s*[\'\"\-\.\w]+\s*\)/i';
            return (isset($matches[1])) ? $matches[1] : false;
        }
        return false;
    }

    /**
     * Returns the full content of the config file.
     *
     * @return string.
     */
    public function getConfigContent() {
        return $this->config;
    }
    
    /**
     * Gets the path/to/the/wp-config.php file.
     *
     * @return string;
     */
    public function getConfigFilePath() {
        return dirname(__FILE__).'/wp-config.php';
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
     * Attempts to lazy load a connection to the mysql database based on the config file.
     * If $this->getDatabase(...) has been called THAT mysqli object will be returned instead.
     *
     * @return mixed; MySQLi instance, false on failure to connect.
     */
    public function getDatabase() {
        if($this->mysqli === null) {
            if($this->isConfigLoaded()) {
                $this->mysqli = @new mysqli($this->getConfigConstant('DB_HOST'), $this->getConfigConstant('DB_USER'), $this->getConfigConstant('DB_PASSWORD'), $this->getConfigConstant('DB_NAME'));
                if(mysqli_connect_error()) {
                    $this->notices[] = 'Unable to connect to this server\'s database using the settings from wp-config.php. Please check that it\'s properly configured.';
                    $this->mysqli = false;
                }
            }
        }
        return ($this->mysqli instanceof mysqli) ? $this->mysqli : false;
    }

    /**
     * Returns a array of WordPress MU table names of the format: "[prefix]_[int]_*"
     *
     * @return mixed; array, false on failure.
     */
    public function getMUTableNames() {
        if (!isset($this->mu_tables)) {
            if($mysqli = $this->getDatabase()) {
                // Get any table matching (prefix.*_* => wp_1_posts, wp_10_posts)
                $sql_db      = $mysqli->escape_string($this->getConfigConstant('DB_NAME'));
                $sql_prefix  = str_replace(array('%', '_'), array('\%', '\_'), $mysqli->escape_string($this->getConfigTablePrefix()));
                $result      = $mysqli->query('SHOW TABLES FROM `'.$sql_db.'` LIKE "'.$sql_prefix.'%\_%"');
                if($result->num_rows > 0) {
                    $this->mu_tables = array();
                    while($row = $result->fetch_array()) {
                        $this->mu_tables[] = $row[0];
                    }
                } else {
                    $this->mu_tables = false;
                }
            }
        }
        return $this->mu_tables;
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
     * Checks if the WordPress root directory is writable.
     *
     * @return bool;
     */
    public function isMultiSite() {
        return $this->getConfigConstant('MULTISITE') == 'true' ? true : false;
    }

    /**
     * Checks if the WordPress root directory is writable.
     *
     * @return bool;
     */
    public function isConfigFileWritable() {
        return is_writable($this->getConfigFilePath());
    }

    /**
     * Checks if the wp-config.php file has been loaded successfully.
     *
     * @return bool;
     */
    public function isConfigLoaded() {
        return (strlen($this->config) > 0);
    }

    /**
     * Checks if the WordPress root directory is writable.
     *
     * @return bool;
     */
    public function isRootDirWritable() {
        return is_writable(dirname($this->getConfigFilePath()));
    }
    
    /**
     * Save any changes made self::$config back to the wp-config.php file.
     *
     * @return bool;
     */
    public function saveChangesToConfigFile() {
        return $this->writeToConfigFile($this->getConfigContent());
    }
        
    /**
     * Sets a new value to an EXISTING constant defined in wp-config.php
     *
     * NOTE: The changes made by this method are only to self::$config. 
     * When self::saveChangesToConfigFile() is call the changes get written 
     * to the wp-config.php file.
     *
     * @param string;
     * @param mixed; - Can't be an array, object, or resource type.  
     * @return bool;
     */ 
    public function setConfigConstant($constant, $value) {
        $count = 0;
        $value = $this->getConstantValue($value);
        if(is_string($value)) {
            $find       = "/define\s*\(\s*[\'\"]{1}" . $constant . "[\'\"]{1}\s*,\s*[\'\"\-\.]*(.+?)[\'\"]?\s*\)/";
            $replace    = "define('" . $constant . "', " . $value . " )";
            $new_config = preg_replace($find, $replace, $this->getConfigContent(), -1, $count);
            if($count > 0) {
                $this->setConfigContent($new_config);
            }
        }
        return ($count > 0) ? true : false;
    }
    
    /**
    * Sets the self::$config property's value.
    *
    * @param string;
    * @return void;
    */
    public function setConfigContent($string) {
        $this->config = $string;
    }
    
    /**
     * Sets a new string value to an EXISTING variable defined in wp-config.php
     *
     * NOTE: The changes made by this method are only to self::$config. 
     * When self::saveChangesToConfigFile() is call the changes get written 
     * to the wp-config.php file. 
     *
     * @param string;
     * @param string; 
     * @return bool;
     */ 
    public function setConfigVariable($variable, $string) {
        $count  = 0;
        $string = (string) $string;
        $find       = '/\$' . $variable . "[^=]*=\s*['\"\-\.]*(.+?)['\"]?\s*\;/";
        $replace    = "\$" . $variable . " = '" . $string . "';";
        $new_config = preg_replace($find, $replace, $this->getConfigContent(), -1, $count);
        if($count > 0) {
            $this->setConfigContent($new_config);
        }
        return ($count > 0) ? true : false;
    }
        
    /**
     * Overrides the class self::$mysqli property with a different MySQLi instance.
     * 
     * @param mysqli;
     * @return void;
     */
    public function setDatabase(mysqli $mysqli) {
        $this->mysqli = $mysqli;
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
                $new_string = str_replace($find, $replace, $matches[2][$i]);
                $new_length = ((int) $matches[1][$i]) + $length_diff;
                $haystack = str_replace($matches[0][$i], 's:'.$new_length.':"'.$new_string.'"', $haystack);
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
     * Attempts to overwrite the config file with $content.
     *
     * @return bool;
     */
    public function writeToConfigFile($content) {
        return (bool)file_put_contents($this->getConfigFilePath(), $content);
    }
       
    // == PRIVATE METHODS ===============================================
    
    /**
     * Attempts to convert a given value into its string representation.
     * 
     * @param mixed; - Can't be an array, object, or resource type. 
     * @return mixed; string on success, false on failure;
     */
    private function getConstantValue($mixed) {
        $value = false;
        if(is_float($mixed) || is_int($mixed)) {
            $value = (string) $mixed;
        } elseif(is_bool($mixed)) {
            $value = $mixed ? 'true' : 'false';
        } else if(is_string($mixed)) {
            $value = "'".$mixed."'";
        } else if(is_null($mixed)) {
            $value = "null";
        } else {
            $value = false;
        }
        return $value;
    }
           
    /**
     * Attempts to load the wp-config.php file into seld::$config
     *
     * @return void;
     */
    private function loadConfigFile() {
        $this->config = file_get_contents($this->getConfigFilePath());
        if(!$this->isConfigLoaded()) {
            $this->errors[] = 'Unable to find "wp-config.php" ... Make sure the '.basename(__FILE__).' file is in the root WordPress directory.';
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

/* == START PROCEDURAL CODE ============================================== */

// Debug Enabled?
ini_set( 'display_errors' , WPDC_DEBUG ? 1 : 0);
error_reporting( WPDC_DEBUG ? E_ALL : 0);


// Config/Safety Check
if(WPDC_PASSWORD == 'Replace-This-Password') {
    die('This script will remain disabled until the default password is changed.');
}

// Password Check -> Set Cookie -> Redirect
if(isset($_POST['auth_password'])) {
    /**
     * Try and obstruct brute force attacks by making each login attempt
     * take 5 seconds.This is total security-through-obscurity and can be
     * worked around fairly easily, it's just one more step.
     *
     * MAKE SURE you remove this script after the domain change is complete.
     */
    sleep(5);
    if(md5($_POST['auth_password']) == md5(WPDC_PASSWORD)) {
        $expire = time() + WPDC_COOKIE_LIFETIME;
        setcookie('wpdc_auth', md5(WPDC_PASSWORD), $expire);
        setcookie('wpdc_expire', $expire, $expire);
        die('<a href="'.basename(__FILE__).'">Click Here</a><script type="text/javascript">window.location = "'.basename(__FILE__).'";</script>');
    }
}

// Authenticate
$is_authenticated = (isset($_COOKIE['wpdc_auth']) && ($_COOKIE['wpdc_auth'] == md5(WPDC_PASSWORD))) ? true : false;

// Check if user is authenticated
if($is_authenticated) {
    $WPDC = new WPDC();
    try {
        // Start change process
        if(isset($_POST) && is_array($_POST) && (count($_POST) > 0)) {
            // Clean up data & check for empty fields
            $POST = array();
            foreach($_POST as $key => $value) {
                $value = trim($value);
                if(strlen($value) <= 0) throw new Exception('One or more of the fields was blank; all are required.');
                if(get_magic_quotes_gpc()) $value = stripslashes($value);
                $POST[$key] = $value;
            }

            // Check for "http://" in the new domain
            if(stripos($POST['new_domain'], 'http://') !== false) {
                // Let them correct this instead of assuming it's correct and removing the "http://".
                throw new Exception('The "New Domain" field must not contain "http://"');
            }

            // Trim and slahes off the new and old domain values.
            $POST['old_domain'] = trim($POST['old_domain'], '/');
            $POST['new_domain'] = trim($POST['new_domain'], '/');
            
            // DB Connection
            $mysqli = @new mysqli($POST['host'], $POST['username'], $POST['password'], $POST['database']);
            if(mysqli_connect_error()) {
                throw new Exception('Unable to create database connection; most likely due to incorrect connection settings.');
            }

            // Set the class database to this sucessfully connected mysqli instance.
            $WPDC->setDatabase($mysqli);

            // Escape $_POST data for sql statements.
            $data = array();
            foreach($_POST as $key => $value) {
                $data[$key] = $mysqli->escape_string($value);
            }
            
            /**
            * Handle Serialized Values
            *
            * Before we update the options we need to find any option_values that have the
            * old_domain stored within a serialized string.
            */
            if(!$result = $mysqli->query('SELECT * FROM '.$data['prefix'].'options WHERE option_value REGEXP "s:[0-9]+:\".*'.$mysqli->escape_string(WPDC::preg_quote($POST['old_domain'])).'.*\";"')) {
                throw new Exception($mysqli->error);
            }
            $serialized_options = array();
            $options_to_exclude = '';
            if($result->num_rows > 0) {
                // Build dataset
                while(is_array($row = $result->fetch_assoc())) $serialized_options[] = $row;
                
                // Build Exclude SQL
                foreach($serialized_options as $record) $options_to_exclude .= $record['option_id'].',';
                $options_to_exclude = ' WHERE option_id NOT IN('.rtrim($options_to_exclude, ',').')';
             
                // Update Serialized Options
                foreach($serialized_options as $record) {
                    $new_option_value = WPDC::serializedStrReplace($data['old_domain'], $data['new_domain'], $record['option_value']);
                    if(!$mysqli->query('UPDATE '.$data['prefix'].'options SET option_value = "'.$mysqli->escape_string($new_option_value).'" WHERE option_id='.(int)$record['option_id'].';')) {
                        throw new Exception($mysqli->error);
                    }
                    $WPDC->actions[] = '[Serialize Replace] Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in option_name="'.$record['option_name'].'"';
                }
                
            }
            
            // Update Options
            if(!$mysqli->query('UPDATE '.$data['prefix'].'options SET option_value = REPLACE(option_value,"'.$data['old_domain'].'","'.$data['new_domain'].'")'.$options_to_exclude.';')) {
                throw new Exception($mysqli->error);
            }
            $WPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'options.option_value';
            
            // Update Post GUID
            if(!$mysqli->query('UPDATE '.$data['prefix'].'posts SET guid = REPLACE(guid,"'.$data['old_domain'].'","'.$data['new_domain'].'");')) {
                throw new Exception($mysqli->error);
            }
            $WPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'posts.guid';

            // Update Post Content
            if(!$mysqli->query('UPDATE '.$data['prefix'].'posts SET post_content = REPLACE(post_content,"'.$data['old_domain'].'","'.$data['new_domain'].'");')) {
                throw new Exception($mysqli->error);
            }
            $WPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'posts.post_content';

            // Update "upload_path"
            $upload_dir = dirname(__FILE__).'/wp-content/uploads';
            if(!$mysqli->query('UPDATE '.$data['prefix'].'options SET option_value = "'.$upload_dir.'" WHERE option_name="upload_path";')) {
                throw new Exception($mysqli->error);
            }
            $WPDC->actions[] = 'Option "upload_path" has been changed to "'.$upload_dir.'"';

            // Update User Meta
            if(!$mysqli->query('UPDATE '.$data['prefix'].'usermeta SET meta_value = REPLACE(meta_value,"'.$data['old_domain'].'","'.$data['new_domain'].'");')) {
                throw new Exception($mysqli->error);
            }
            $WPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'usermeta.meta_value';


            // Updates for MU websites
            if (isset($data['multisite']) && ($data['multisite'] == '1')) {
                /**
                 * The Multi-Site tables "blogs" and "site" store the domain as "domain" and "path." Thus we 
                 * need to so some magic here and split up the replace values.
                 *
                 * Case:
                 *   Old Domain: "localhost/wordpress"
                 *   New Domain: "localhost/blog"
                 */
                 
                // Set the default values.
                $old_domain_base = $data['old_domain'];
                $new_domain_base = $data['new_domain'];
                $old_domain_path = '/';
                $new_domain_path = '/';
                
                // Check if the old_domain value looks something like this: "www.example.com/blog"
                if(count($old_domain_parts = explode('/', $data['old_domain'])) > 1) {
                    $old_domain_base = array_shift($old_domain_parts);
                    $old_domain_path = '/'.implode('/', $old_domain_parts).'/';
                }
                // Check if the new_domain value looks something like this: "www.example.com/blog"
                if(count($new_domain_parts = explode('/', $data['new_domain'])) > 1) {
                    $new_domain_base = array_shift($new_domain_parts);
                    $new_domain_path = '/'.implode('/', $new_domain_parts).'/';
                }
                
                // Update Blogs Domain
                if(!$mysqli->query('UPDATE '.$data['prefix'].'blogs SET domain = REPLACE(domain,"'.$old_domain_base.'","'.$new_domain_base.'");')) {
                    throw new Exception($mysqli->error);
                }
                $WPDC->actions[] = '[Multi-Site] Old domain ('.$old_domain_base.') replaced with new domain ('.$new_domain_base.') in '.$data['prefix'].'blogs.domain';
                
                // Update Blogs Path
                $sql_blog_path_set = ($old_domain_path == '/') ? 'CONCAT("'.rtrim($new_domain_path, '/').'", path)' : 'REPLACE(path,"'.$old_domain_path.'","'.$new_domain_path.'")';
                if(!$mysqli->query('UPDATE '.$data['prefix'].'blogs SET path = '.$sql_blog_path_set.';')) {
                    throw new Exception($mysqli->error);
                }
                $WPDC->actions[] = '[Multi-Site] Old path ('.$old_domain_path.') replaced with new path ('.$new_domain_path.') in '.$data['prefix'].'blogs.path';
                
                // Update Site Domain
                if(!$mysqli->query('UPDATE '.$data['prefix'].'site SET domain = REPLACE(domain,"'.$data['old_domain'].'","'.$data['new_domain'].'");')) {
                    throw new Exception($mysqli->error);
                }
                $WPDC->actions[] = '[Multi-Site] Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'site.domain';

                // Update Site Path
                $sql_site_path_set = ($old_domain_path == '/') ? 'CONCAT("'.rtrim($new_domain_path, '/').'", path)' : 'REPLACE(path,"'.$old_domain_path.'","'.$new_domain_path.'")';
                if(!$mysqli->query('UPDATE '.$data['prefix'].'site SET path = '.$sql_site_path_set.';')) {
                    throw new Exception($mysqli->error);
                }
                $WPDC->actions[] = '[Multi-Site] Old path ('.$old_domain_path.') replaced with new path ('.$new_domain_path.') in '.$data['prefix'].'site.path';                    

                // Update Site Meta
                if(!$mysqli->query('UPDATE '.$data['prefix'].'sitemeta SET meta_value = REPLACE(meta_value,"'.$data['old_domain'].'","'.$data['new_domain'].'");')) {
                    throw new Exception($mysqli->error);
                }
                $WPDC->actions[] = '[Multi-Site] Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'sitemeta.meta_value';
                                
                // Update [prefix]_[int]_* Tables
                if(is_array($mu_tables = $WPDC->getMUTableNames())) {
                    foreach($mu_tables as $mu_table) {
                        if(!preg_match('/^[a-z0-9]+\_([0-9])+\_(.+)/i', $mu_table, $mu_matches)) {
                            continue;
                        }
                        $mu_table_type = $mu_matches[2];
                        $mu_table_number = $mu_matches[1];
                        switch($mu_matches[2]) {
                            case 'options':
                                // Update Options
                                if(!$mysqli->query('UPDATE '.$mu_table.' SET option_value = REPLACE(option_value,"'.$data['old_domain'].'","'.$data['new_domain'].'");')) {
                                    throw new Exception($mysqli->error);
                                }
                                $WPDC->actions[] = '[Multi-Site #'.$mu_table_number.'] Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$mu_table.'.option_value';
                                break;
                            case 'postmeta':
                                // Update Post Meta
                                if(!$mysqli->query('UPDATE '.$mu_table.' SET meta_value = REPLACE(meta_value,"'.$data['old_domain'].'","'.$data['new_domain'].'");')) {
                                    throw new Exception($mysqli->error);
                                }
                                $WPDC->actions[] = '[Multi-Site #'.$mu_table_number.'] Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$mu_table.'.meta_value';
                                break;
                            case 'posts':
                                // Update Post's GUID
                                if(!$mysqli->query('UPDATE '.$mu_table.' SET guid = REPLACE(guid,"'.$data['old_domain'].'","'.$data['new_domain'].'");')) {
                                    throw new Exception($mysqli->error);
                                }
                                $WPDC->actions[] = '[Multi-Site #'.$mu_table_number.'] Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$mu_table.'.guid';
                                
                                // Update Posts Content
                                if(!$mysqli->query('UPDATE '.$mu_table.' SET post_content = REPLACE(post_content,"'.$data['old_domain'].'","'.$data['new_domain'].'");')) {
                                    throw new Exception($mysqli->error);
                                }
                                $WPDC->actions[] = '[Multi-Site #'.$mu_table_number.'] Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$mu_table.'.post_content';
                            default:
                                continue;
                        }
                    }
                }
                
                // Create a backup of the current wp-config.php file.
                $WPDC->actions[] = 'Backing up the wp-config.php file before edit attempt.';
                if($WPDC->createBackupConfigFile()) {
                    // Attempt to updated the wp-config.php file.
                    if($WPDC->setConfigConstant('DOMAIN_CURRENT_SITE', $new_domain_base)) {
                        $WPDC->actions[] = '[Multi-Site] "DOMAIN_CURRENT_SITE" constant value changed from ('.$old_domain_base.') to ('.$new_domain_base.') in the config file.';
                    }
                    if($WPDC->setConfigConstant('PATH_CURRENT_SITE', $new_domain_path)) {
                        $WPDC->actions[] = '[Multi-Site] "DOMAIN_CURRENT_PATH" constant value changed from ('.$old_domain_path.') to ('.$new_domain_path.') in the config file.';
                    }
                    if($WPDC->setConfigVariable('base', $new_domain_path)) {
                        $WPDC->actions[] = '[Multi-Site] "$base" variable value changed from ('.$old_domain_path.') to ('.$new_domain_path.') in the config file.';
                    }
                    if ($WPDC->saveChangesToConfigFile()) {
                        $WPDC->actions[] = '[Multi-Site] Changes successfully written to the wp-config.php file.';
                    } else {
                        $WPDC->notices[] = '[Multi-Site] Unable to write to the wp-config.php file. Please manually edit the wp-config.php file and update the following constants and variables:  "DOMAIN_CURRENT_SITE" => "'.$new_domain_base.'" -- "DOMAIN_CURRENT_PATH" => "'.$new_domain_path.'" -- "$base" => "'.$new_domain_path.'"';
                    }
                } else {
                    $WPDC->notices[] = '[Multi-Site] Unable to create a back up of the wp-config.php file because of file permissions. Please manually edit the wp-config.php file and update the following constants and variables: "DOMAIN_CURRENT_SITE" => "'.$new_domain_base.'" -- "DOMAIN_CURRENT_PATH" => "'.$new_domain_path.'" -- "$base" => "'.$new_domain_path.'"';
                }
                
                // TODO: Put some of this block into the namespace object.
                // Update .htaccess file
                $WPDC->actions[] = '[Multi-Site] Backing up the .htaccess file before edit attempt.';
                if(file_exists($htaccess_path = dirname(__FILE__).'/.htaccess')) {
                    if(copy($htaccess_path, dirname($htaccess_path).'/bak.'.microtime(true).'.htaccess')) {
                        if(($htaccess_content = file_get_contents($htaccess_path)) !== false){
                            $htaccess_content = preg_replace('/RewriteBase\s+.+?\n/', 'RewriteBase '.$new_domain_path."\n", $htaccess_content, -1, $count);
                            if($count > 0) {
                                $WPDC->actions[] = '[Multi-Site] "RewriteBase '.$old_domain_path.'" changed to "RewriteBase '.$new_domain_path.'" in the .htaccess file.';
                                if(file_put_contents($htaccess_path, $htaccess_content)) {
                                    $WPDC->actions[] = '[Multi-Site] Changes successfully written to the .htaccess file.';
                                } else {
                                    $WPDC->notices[] = '[Multi-Site] Unable to write to the .htaccess file. Please manually edit the .htaccess file and change "RewriteBase '.$old_domain_path.'" to "RewriteBase '.$new_domain_path.'".';
                                }
                            } else {
                                $WPDC->notices[] = '[Multi-Site] The .htaccess file does not contained a "RewriteBase" directive. While this might not be an <em>error</em>, issues can still arise with your WordPress install.';
                            }
                        } else {
                            $WPDC->notices[] = '[Multi-Site] Unable to read the .htaccess file because of file permissions. Please manually edit the .htaccess file and change "RewriteBase '.$old_domain_path.'" to "RewriteBase '.$new_domain_path.'".';
                        }
                    } else {
                        $WPDC->notices[] = '[Multi-Site] Unable to create a back up of the .htaccess file because of file permissions. Please manually edit the .htaccess file and change "RewriteBase '.$old_domain_path.'" to "RewriteBase '.$new_domain_path.'".';
                    }
                } else {
                    $WPDC->notices[] = '[Multi-Site] The .htaccess file does not exist. While this might not be an error, issues can still arise with your WordPress install.';
                }
            }
        }
    } catch (Exception $exception) {
        $WPDC->errors[] = $exception->getMessage();
    }
}
?>
<html>
    <head>
        <title>WordPress Domain Changer by Daniel Doezema &amp; Collaborators</title>
        <script type="text/javascript" language="Javascript">
            window.onload = function() {
                if(document.getElementById('seconds')) {
                    window.setInterval(function() {
                        var seconds_elem = document.getElementById('seconds');
                        var seconds      = parseInt(seconds_elem.value);
                        
                        // Note: Calling window.location.reload() will resend <form> data -- not desired.
                        if(seconds <= 0) window.location = window.location;
                        
                        var minutes_elem = document.getElementById('minutes');
                        var bar_elem     = document.getElementById('bar');
                        var percentage   = Math.round(seconds / <?= WPDC_COOKIE_LIFETIME + 5; ?> * 100);
                        var bar_color    = '#00FF19';
                        if(percentage < 25) {
                            bar_color = 'red';
                        } else if (percentage < 75) {
                            bar_color = 'yellow';
                        }
                        bar_elem.style.width = percentage + '%';
                        bar_elem.style.backgroundColor = bar_color;
                        seconds_elem.value = --seconds;
                        minutes_elem.innerHTML = Math.ceil(seconds_elem.value / 60);
                    }, 1000);
                }
            }
        </script>
        <style type="text/css">
            body {font:14px Tahoma, Arial;}
            div.clear {clear:both;}
            h1 {padding:0; margin:0;}
            h2, h3 {padding:0; margin:0 0 15px 0;}
            form { display:block; padding:10px; margin-top:15px; background-color:#FCFCFC; border:1px solid gray;}
            form label {font-weight:bold;}
            form div {margin:0 15px 15px 0;}
            form div input[type="text"] {width:80%;}
            form p {margin:0 0 10px 0; padding:0;}
            #left {width:35%;float:left;}
            #right {margin-top:5px;float:right; width:63%; text-align:left;}
            div.log {padding:5px 10px; margin:10px 0;}
            div.error { background-color:#FFF8F8; border:1px solid red;}
            div.notice { background-color:#FFFEF2; border:1px solid #FDC200;}
            div.action { background-color:#F5FFF6; border:1px solid #01BE14;}
            #timeout {padding:5px 10px 10px 10px; background-color:black; color:white; font-weight:bold;position:absolute;top:0;right:10px;}
            #bar {height:10px;margin:5px 0 0 0;}
            label em {color:gray;border-bottom:1px dotted gray;font-weight:normal;}
            div.drilldown {float:left;border-left:1px dotted gray;border-bottom:1px dotted gray;width:10px;height:10px;margin:0 5px 0 5px;}
            div.eg {margin:-10px 0 10px 0;padding-left:25px;color:gray;position:relative;font-size:0.8em;}
            div.eg div.drilldown {position:absolute;top:0;left:0;}
        </style>
    </head>
    <body>
        <h1>WordPress Domain Changer</h1>
        <span>By <a href="http://dan.doezema.com" target="_blank">Daniel Doezema</a> &amp; <a href="http://github.com/veloper/WordPress-Domain-Changer/network" target="_blank">Collaborators</a></span>
        <div class="body">
            <?php if($is_authenticated): ?>
                <div id="timeout">
                    <input type="hidden" id="seconds" name="seconds" value="<?= ((int) $_COOKIE['wpdc_expire'] + 5) - time();?>">
                    <div>You have about <span id="minutes">...</span> Minutes left in this session.</div>
                    <div id="bar"></div>
                </div>
                <div class="clear"></div>
                <div id="left">
                    <form method="post" action="<?= basename(__FILE__);?>" onsubmit="return confirm('Are you sure that you want to change the domain using these settings?');">
                        <?php if($WPDC->isConfigLoaded()): ?>
                        <p><strong>Note:</strong> The fields below were populated using a combination of data obtained from your wp-config.php file and this script's current environment.</p>
                        <p style="text-align:center;text-decoration:underline">It's important that all values below are accurate.</p>
                        <?php endif; ?>
                        <h3>Database Connection Settings</h3>
                        <blockquote>
                            <label for="host">Host</label>
                            <div><input type="text" id="host" name="host" value="<?= $WPDC->getConfigConstant('DB_HOST'); ?>" /></div>

                            <label for="username">User</label>
                            <div><input type="text" id="username" name="username" value="<?= $WPDC->getConfigConstant('DB_USER'); ?>" /></div>

                            <label for="password">Password</label>
                            <div><input type="text" id="password" name="password" value="<?= $WPDC->getConfigConstant('DB_PASSWORD'); ?>" /></div>

                            <label for="database">Database Name</label>
                            <div><input type="text" id="database" name="database" value="<?= $WPDC->getConfigConstant('DB_NAME'); ?>" /></div>

                            <label for="prefix">Table Prefix</label>
                            <div><input type="text" id="prefix" name="prefix" value="<?= $WPDC->getConfigTablePrefix(); ?>" /></div>
                        </blockquote>

                        <label for="old_domain">Old Domain</label>
                        <div>http://<input type="text" id="old_domain" name="old_domain" value="<?= $WPDC->getOldDomain(); ?>" /></div>
                        <div class="eg"><div class="drilldown"></div>(e.g., "www.example.com", "www.example.com/blog", "blog.example.com")</div>
                        <label for="new_domain">New Domain</label>
                        <div>http://<input type="text" id="new_domain" name="new_domain" value="<?= $WPDC->getNewDomain(); ?>" /></div>
                        <div class="eg"><div class="drilldown"></div>(e.g., "www.example.com", "www.example.com/blog", "blog.example.com")</div>
                        <?php if($WPDC->isMultiSite()): ?>
                            <p>Based on your wp-config.php file it looks like you're running a WordPress Multi-Site install.</p>
                            <div><div class="drilldown"></div><input type="checkbox" id="multisite" name="multisite" value="1" checked /><label for="multisite">Apply domain change to all sites.</label></div>
                        <?php endif; ?>
                        <input type="submit" id="submit_button" name="submit_button" value="Change Domain!" />
                    </form>
                </div>
                <div id="right">
                    <?php if(count($WPDC->errors) > 0): foreach($WPDC->errors as $error):?>
                        <div class="log error"><strong>Error:</strong> <?=htmlspecialchars($error);?></div>
                    <?php endforeach; endif; ?>

                    <?php if(count($WPDC->notices) > 0): foreach($WPDC->notices as $notice):?>
                        <div class="log notice"><strong>Notice:</strong> <?=htmlspecialchars($notice);?></div>
                    <?php endforeach; endif; ?>

                    <?php if(count($WPDC->actions) > 0): foreach($WPDC->actions as $action):?>
                        <div class="log action"><strong>Action: </strong><?=htmlspecialchars($action);?></div>
                    <?php endforeach; endif; ?>
                </div>
            <?php else: ?>
                <?if(isset($_POST['auth_password'])):?>
                    <div class="log error"><strong>Error:</strong> Incorrect password, please try again.</div>
                <?endif;?>
                <form id="login" name="login" method="post" action="<?= basename(__FILE__);?>">
                    <h3>Authenticate</h3>
                    <label for="auth_password">Password</label>
                    <input type="password" id="auth_password" name="auth_password" value="" />
                    <input type="submit" id="submit_button" name="submit_button" value="Submit!" />
                </form>
            <?php endif; ?>
        </div>
    </body>
</html>
