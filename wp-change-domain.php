<?php
/**
 * Author: Daniel Doezema
 * Author URI: http://dan.doezema.com
 * Version: 0.2 (Beta)
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

/* == CONFIG ======================================================= */

// Authentication Password
define('DDWPDC_PASSWORD', 'Replace-This-Password');

// Cookie: Name: Authentication
define('DDWPDC_COOKIE_NAME_AUTH', 'DDWPDC_COOKIE_AUTH');

// Cookie: Name: Expiration
define('DDWPDC_COOKIE_NAME_EXPIRE', 'DDWPDC_COOKIE_EXPIRE');

// Cookie: Timeout (Default: 5 minutes)
define('DDWPDC_COOKIE_LIFETIME', 60 * 5);

/* == NAMESPACE CLASS ============================================== */

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
     * Attempts to load the wp-config.php file into $this->config
     *
     * @return void;
     */
    private function loadConfigFile() {
        $this->config = file_get_contents(dirname(__FILE__).'/wp-config.php');
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

/* == START PROCEDURAL CODE ============================================== */

// Config/Safety Check
if(DDWPDC_PASSWORD == 'Replace-This-Password') {
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
    if(md5($_POST['auth_password']) == md5(DDWPDC_PASSWORD)) {
        $expire = time() + DDWPDC_COOKIE_LIFETIME;
        setcookie(DDWPDC_COOKIE_NAME_AUTH, md5(DDWPDC_PASSWORD), $expire);
        setcookie(DDWPDC_COOKIE_NAME_EXPIRE, $expire, $expire);
        die('<a href="'.basename(__FILE__).'">Click Here</a><script type="text/javascript">window.location = "'.basename(__FILE__).'";</script>');
    }
}

// Authenticate
$is_authenticated = (isset($_COOKIE[DDWPDC_COOKIE_NAME_AUTH]) && ($_COOKIE[DDWPDC_COOKIE_NAME_AUTH] == md5(DDWPDC_PASSWORD))) ? true : false;

// Check if user is authenticated
if($is_authenticated) {
    $DDWPDC = new DDWordPressDomainChanger();
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

            // DB Connection
            $mysqli = @new mysqli($POST['host'], $POST['username'], $POST['password'], $POST['database']);
            if(mysqli_connect_error()) {
                throw new Exception('Unable to create database connection; most likely due to incorrect connection settings.');
            }

            // Escape for Database
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
            if(!$result = $mysqli->query('SELECT * FROM '.$data['prefix'].'options WHERE option_value REGEXP "s:[0-9]+:\".*'.$mysqli->escape_string(DDWordPressDomainChanger::preg_quote($POST['old_domain'])).'.*\";"')) {
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
                    $new_option_value = DDWordPressDomainChanger::serializedStrReplace($data['old_domain'], $data['new_domain'], $record['option_value']);
                    if(!$mysqli->query('UPDATE '.$data['prefix'].'options SET option_value = "'.$mysqli->escape_string($new_option_value).'" WHERE option_id='.(int)$record['option_id'].';')) {
                        throw new Exception($mysqli->error);
                    }
                    $DDWPDC->actions[] = '[Serialize Replace] Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in option_name="'.$record['option_name'].'"';
                }
                
            }
            
            // Update Options
            if(!$mysqli->query('UPDATE '.$data['prefix'].'options SET option_value = REPLACE(option_value,"'.$data['old_domain'].'","'.$data['new_domain'].'")'.$options_to_exclude.';')) {
                throw new Exception($mysqli->error);
            }
            $DDWPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'options.option_value';

            // Update Post Content
            $result = $mysqli->query('UPDATE '.$data['prefix'].'posts SET post_content = REPLACE(post_content,"'.$data['old_domain'].'","'.$data['new_domain'].'");');
            if(!$result) {
                throw new Exception($mysqli->error);
            } else {
                $DDWPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'posts.post_content';
            }

            // Update Post GUID
            $result = $mysqli->query('UPDATE '.$data['prefix'].'posts SET guid = REPLACE(guid,"'.$data['old_domain'].'","'.$data['new_domain'].'");');
            if(!$result) {
                throw new Exception($mysqli->error);
            } else {
                $DDWPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'posts.guid';
            }
            // Update post_meta
            $result = $mysqli->query('UPDATE '.$data['prefix'].'postmeta SET meta_value = REPLACE(meta_value,"'.$data['old_domain'].'","'.$data['new_domain'].'");');
            if(!$result) {
                throw new Exception($mysqli->error);
            } else {
                $DDWPDC->actions[] = 'Old domain ('.$data['old_domain'].') replaced with new domain ('.$data['new_domain'].') in '.$data['prefix'].'postmeta.meta_value';
            }

            // Update "upload_path"
            $upload_dir = dirname(__FILE__).'/wp-content/uploads';
            $result = $mysqli->query('UPDATE '.$data['prefix'].'options SET option_value = "'.$upload_dir.'" WHERE option_name="upload_path";');
            if(!$result) {
                throw new Exception($mysqli->error);
            } else {
                $DDWPDC->actions[] = 'Option "upload_path" has been changed to "'.$upload_dir.'"';
            }
        }
    } catch (Exception $exception) {
        $DDWPDC->errors[] = $exception->getMessage();
    }
}
?>
<html>
    <head>
        <title>WordPress Domain Changer by Daniel Doezema</title>
        <script type="text/javascript" language="Javascript">
            window.onload = function() {
                if(document.getElementById('seconds')) {
                    window.setInterval(function() {
                        var seconds_elem = document.getElementById('seconds');
                        var bar_elem     = document.getElementById('bar');
                        var seconds      = parseInt(seconds_elem.innerHTML);
                        var percentage   = Math.round(seconds / <?php echo DDWPDC_COOKIE_LIFETIME + 5; ?> * 100);
                        var bar_color    = '#00FF19';
                        if(percentage < 25) {
                            bar_color = 'red';
                        } else if (percentage < 75) {
                            bar_color = 'yellow';
                        }
                        if(seconds <= 0) window.location.reload();
                        bar_elem.style.width = percentage + '%';
                        bar_elem.style.backgroundColor = bar_color;
                        seconds_elem.innerHTML = --seconds;
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
            form div input {width:80%;}
            #left {width:35%;float:left;}
            #right {margin-top:5px;float:right; width:63%; text-align:left;}
            div.log {padding:5px 10px; margin:10px 0;}
            div.error { background-color:#FFF8F8; border:1px solid red;}
            div.notice { background-color:#FFFEF2; border:1px solid #FDC200;}
            div.action { background-color:#F5FFF6; border:1px solid #01BE14;}
            #timeout {padding:5px 10px 10px 10px; background-color:black; color:white; font-weight:bold;position:absolute;top:0;right:10px;}
            #bar {height:10px;margin:5px 0 0 0;}
        </style>
    </head>
    <body>
        <h1>WordPress Domain Changer</h1>
        <span>By <a href="http://dan.doezema.com" target="_blank">Daniel Doezema</a></span>
        <div class="body">
            <?php if($is_authenticated): ?>
                <div id="timeout">
                    <div>You have <span id="seconds"><?php echo ((int) $_COOKIE[DDWPDC_COOKIE_NAME_EXPIRE] + 5) - time(); ?></span> Seconds left in this session.</div>
                    <div id="bar"></div>
                </div>
                <div class="clear"></div>
                <div id="left">
                    <form method="post" action="<?php echo basename(__FILE__); ?>">
                        <h3>Database Connection Settings</h3>
                        <blockquote>
                            <?php
                            // Try to Auto-Detect Settings from wp-config.php file and pre-populate fields.
                            if($DDWPDC->isConfigLoaded()) $DDWPDC->actions[] = 'Attempting to auto-detect form field values.';
                            ?>
                            <label for="host">Host</label>
                            <div><input type="text" id="host" name="host" value="<?php echo $DDWPDC->getConfigConstant('DB_HOST'); ?>" /></div>

                            <label for="username">User</label>
                            <div><input type="text" id="username" name="username" value="<?php echo $DDWPDC->getConfigConstant('DB_USER'); ?>" /></div>

                            <label for="password">Password</label>
                            <div><input type="text" id="password" name="password" value="<?php echo $DDWPDC->getConfigConstant('DB_PASSWORD'); ?>" /></div>

                            <label for="database">Database Name</label>
                            <div><input type="text" id="database" name="database" value="<?php echo $DDWPDC->getConfigConstant('DB_NAME'); ?>" /></div>

                            <label for="prefix">Table Prefix</label>
                            <div><input type="text" id="prefix" name="prefix" value="<?php echo $DDWPDC->getConfigTablePrefix(); ?>" /></div>
                        </blockquote>

                        <label for="old_domain">Old Domain</label>
                        <div>http://<input type="text" id="old_domain" name="old_domain" value="<?php echo $DDWPDC->getOldDomain(); ?>" /></div>

                        <label for="new_domain">New Domain</label>
                        <div>http://<input type="text" id="new_domain" name="new_domain" value="<?php echo $DDWPDC->getNewDomain(); ?>" /></div>

                        <input type="submit" id="submit_button" name="submit_button" value="Change Domain!" />
                    </form>
                </div>
                <div id="right">
                    <?php if(count($DDWPDC->errors) > 0): foreach($DDWPDC->errors as $error): ?>
                        <div class="log error"><strong>Error:</strong> <?php echo $error; ?></div>
                    <?php endforeach; endif; ?>

                    <?php if(count($DDWPDC->notices) > 0): foreach($DDWPDC->notices as $notice): ?>
                        <div class="log notice"><strong>Notice:</strong> <?php echo $notice; ?></div>
                    <?php endforeach; endif; ?>

                    <?php if(count($DDWPDC->actions) > 0): foreach($DDWPDC->actions as $action): ?>
                        <div class="log action"><strong>Action: </strong><?php echo $action; ?></div>
                    <?php endforeach; endif; ?>
                </div>
            <?php else: ?>
                <?php if(isset($_POST['auth_password'])): ?>
                    <div class="log error"><strong>Error:</strong> Incorrect password, please try again.</div>
                <?php endif; ?>
                <form id="login" name="login" method="post" action="<?php echo basename(__FILE__); ?>">
                    <h3>Authenticate</h3>
                    <label for="auth_password">Password</label>
                    <input type="password" id="auth_password" name="auth_password" value="" />
                    <input type="submit" id="submit_button" name="submit_button" value="Submit!" />
                </form>
            <?php endif; ?>
        </div>
    </body>
</html>
