<?php
/*
 * Author: Daniel Doezema
 * Author URI: http://dan.doezema.com
 * Version: 0.2 (Beta)
 * Description: This script is a tool developed to help ease migration of WordPress sites from one domain to another.
 */

/**
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
 */

/* == CONFIG ======================================================= */

// Authentication Password
define('PASSWORD', 'ReplaceThisPassword');

// Cookie: Name: Authentication
define('COOKIE_NAME_AUTH', 'WPDC_COOKIE_AUTH');

// Cookie: Name: Expiration
define('COOKIE_NAME_EXPIRE', 'WPDC_COOKIE_EXPIRE');

// Cookie: Timeout (Default: 5 minutes)
define('COOKIE_LIFETIME', 60 * 5);

/* == NAMESPACE CLASS ============================================== */

// WordPress Domain Changer
class DDWordPressDomainChanger {
	
	public $actions = array();
	public $notices = array();
	public $errors = array();
	private $config = false;
	
	public function __construct() {
		$this->loadConfigFile();
	}
	public function getConfigConstant($constant) {
		if($this->isConfigLoaded()) {
			preg_match("!define\('".$constant."',[^']*'(.+?)'\);!", $this->config, $matches);
			return (isset($matches[1])) ? $matches[1] : false;
		}
		return false;
	}
	public function getConfigTablePrefix() {
		if($this->isConfigLoaded()) {
			preg_match("!table_prefix[^=]*=[^']*'(.+?)';!", $this->config, $matches);
			return (isset($matches[1])) ? $matches[1] : '';
		}
		return '';
	}
	public function getNewDomain() {
		$new_domain = str_replace('http://','', $_SERVER['SERVER_NAME']);
		if(isset($_SERVER['SERVER_PORT']) && strlen($_SERVER['SERVER_PORT']) > 0 && $_SERVER['SERVER_PORT'] != 80) {
			$new_domain .= ':'.$_SERVER['SERVER_PORT'];
		}
		return $new_domain;
	}
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
	public function isConfigLoaded() {
		return (strlen($this->config) > 0);
	}
	private function loadConfigFile() {
		$this->config = file_get_contents(dirname(__FILE__).'/wp-config.php');
		if(!$this->isConfigLoaded()) {
			$this->notices[] = 'Unable to find "wp-config.php" ... Make sure the '.basename(__FILE__).' file is in the root WordPress directory.';
		} else {
			$this->actions[] = 'wp-config.php file successfully loaded.';
		}
	}
}

/* == START PROCEDURAL CODE ============================================== */

// Config/Safety Check
if(PASSWORD == 'ReplaceThisPassword') die('This script will remain disabled until the default password is changed.');

// Password Check -> Cookie Set -> Redirect
if(isset($_POST['auth_password'])) {
	// Try and obstruct brute force attacks by making each login attempt take 5 seconds.
	sleep(5);
	if(md5($_POST['auth_password']) == md5(PASSWORD)) {
		$expire = time() + COOKIE_LIFETIME;
		setcookie(COOKIE_NAME_AUTH, md5(PASSWORD), $expire);
		setcookie(COOKIE_NAME_EXPIRE, $expire, $expire);
		die('<a id="redirect" href="'.basename(__FILE__).'">Click Here (Javascript Redirect Is Not Working)</a><script type="text/javascript">window.location = "'.basename(__FILE__).'";</script>');
	}	
}

// Check for auth cookie with proper password
$isAuthenticated = (isset($_COOKIE[COOKIE_NAME_AUTH]) && ($_COOKIE[COOKIE_NAME_AUTH] == md5(PASSWORD))) ? true : false;

// Check if Authenticated
if($isAuthenticated) {
	$DDWPDC = new DDWordPressDomainChanger();
	try {
		// Start Conversion Process
		if(isset($_POST) && is_array($_POST) && (count($_POST) > 0)) {
			// Clean up data & check for empty fields
			foreach($_POST as $key => $value) {
				$value = trim($value);
				if(strlen($value) <= 0) {
				    throw new Exception('One or more of the fields was blank; all are required.');
				}
				if(get_magic_quotes_gpc()) {
				  $value = stripslashes($value);
				}
				$_POST[$key] = $value;
			}
	
			// Check for "http://" in the new domain
			if(stripos($_POST['new_domain'], 'http://') !== false) {
				throw new Exception('The "New Domain" field must not contain "http://"');
			}
	
			// DB Connection	
			$mysqli = @new mysqli($_POST['host'], $_POST['username'], $_POST['password'], $_POST['database']);
			if(mysqli_connect_error()) {
				throw new Exception('Unable to create database connection; most likely due to incorrect connection settings.');
			}
	
			// Escape for Database
			$DATA = array();
			foreach($_POST as $key => $value) {
			    $DATA[$key] = $mysqli->escape_string($value);
	        }
	        
			// Update Options
			$result = $mysqli->query('UPDATE '.$DATA['prefix'].'options SET option_value = REPLACE(option_value,"'.$DATA['old_domain'].'","'.$DATA['new_domain'].'");');
			if(!$result) {
				throw new Exception($mysqli->error);
			} else {
				$DDWPDC->actions[] = 'Old domain ('.$DATA['old_domain'].') replaced with new domain ('.$DATA['new_domain'].') in '.$DATA['prefix'].'options.option_value';
			}
	
			// Update Post Content
			$result = $mysqli->query('UPDATE '.$DATA['prefix'].'posts SET post_content = REPLACE(post_content,"'.$DATA['old_domain'].'","'.$DATA['new_domain'].'");');
			if(!$result) {
				throw new Exception($mysqli->error);
			} else {
				$DDWPDC->actions[] = 'Old domain ('.$DATA['old_domain'].') replaced with new domain ('.$DATA['new_domain'].') in '.$DATA['prefix'].'posts.post_content';
			}
			
			// Update Post GUID
			$result = $mysqli->query('UPDATE '.$DATA['prefix'].'posts SET guid = REPLACE(guid,"'.$DATA['old_domain'].'","'.$DATA['new_domain'].'");');
			if(!$result) {
				throw new Exception($mysqli->error);
			} else {
				$DDWPDC->actions[] = 'Old domain ('.$DATA['old_domain'].') replaced with new domain ('.$DATA['new_domain'].') in '.$DATA['prefix'].'posts.guid';
			}

			// Update "upload_path"
			$upload_dir = dirname(__FILE__).'/wp-content/uploads';
			$result = $mysqli->query('UPDATE '.$DATA['prefix'].'options SET option_value = "'.$upload_dir.'" WHERE option_name="upload_path";');
			if(!$result) {
				throw new Exception($mysqli->error);
			} else {
				$DDWPDC->actions[] = 'Option "upload_path" has been changed to "'.$upload_dir.'"';
			}
	
			// Delete "recently_edited" option.
			$result = $mysqli->query('DELETE FROM '.$DATA['prefix'].'options WHERE option_name="recently_edited";');
			if(!$result) {
				throw new Exception($mysqli->error);
			} else {
				$DDWPDC->actions[] = 'Option "recently_edited" has been deleted.';
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
						var o = document.getElementById('seconds');
						var b = document.getElementById('bar');
						var s = parseInt(o.innerHTML);
						var p = Math.round(s / <?= COOKIE_LIFETIME + 5; ?> * 100);
						var c = '#00FF19';
						if(p < 25) {
							c = 'red';
						} else if (p < 75) {
							c = 'yellow';
						}
						if(s <= 0) window.location.reload();
						b.style.width = p + '%';
						b.style.backgroundColor = c;
						o.innerHTML = --s;
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
    		<?php if($isAuthenticated): ?>
    			<div id="timeout">
    				<div>You have <span id="seconds"><?= ((int) $_COOKIE[COOKIE_NAME_EXPIRE] + 5) - time();?></span> Seconds left in this session.</div>
    				<div id="bar"></div>
    			</div>
    			<div class="clear"></div>
    			<div id="left">
    				<form method="post" action="<?= basename(__FILE__);?>">
    					<h3>Database Connection Settings</h3>
    					<blockquote>
    						<?php
    						// Try to Auto-Detect Settings from wp-config.php file.
    						if($DDWPDC->isConfigLoaded())
    							$DDWPDC->actions[] = 'Attempting to auto-detect form field values.';
    						?>
    						<label>Host</label>
    						<div><input type="text" name="host" value="<?= $DDWPDC->getConfigConstant('DB_HOST'); ?>" /></div>
	
    						<label>User</label>
    						<div><input type="text" name="username" value="<?= $DDWPDC->getConfigConstant('DB_USER'); ?>" /></div>
	
    						<label>Password</label>
    						<div><input type="text" name="password" value="<?= $DDWPDC->getConfigConstant('DB_PASSWORD'); ?>" /></div>
					
    						<label>Database Name</label>
    						<div><input type="text" name="database"value="<?= $DDWPDC->getConfigConstant('DB_NAME'); ?>" /></div>
				
    						<label>Table Prefix</label>
    						<div><input type="text" name="prefix" value="<?= $DDWPDC->getConfigTablePrefix(); ?>" /></div>
    					</blockquote>
								
    					<label>Old Domain</label>
    					<div>http://<input type="text" name="old_domain" value="<?= $DDWPDC->getOldDomain(); ?>" /></div>
	
    					<label>New Domain</label>
    					<div>http://<input type="text" name="new_domain" value="<?= $DDWPDC->getNewDomain(); ?>" /></div>
	
    					<input type="submit" value="Submit!" />
    				</form>
    			</div>
    			<div id="right">
    				<?php if(count($DDWPDC->errors) > 0): foreach($DDWPDC->errors as $error):?>
    				<div class="log error"><strong>Error:</strong> <?=$error;?></div>
    				<?php endforeach; endif; ?>

    				<?php if(count($DDWPDC->notices) > 0): foreach($DDWPDC->notices as $notice):?>
    				<div class="log notice"><strong>Notice:</strong> <?=$notice;?></div>
    				<?php endforeach; endif; ?>

    				<?php if(count($DDWPDC->actions) > 0): foreach($DDWPDC->actions as $action):?>
    				<div class="log action"><strong>Action: </strong><?=$action;?></div>
    				<?php endforeach; endif; ?>
    			</div>
    		<?php else: ?>
    			<?if(isset($_POST['auth_password'])):?>
    				<div class="log error"><strong>Error:</strong> Incorrect password, please try again.</div>
    			<?endif;?>
    			<form method="post" action="<?= basename(__FILE__);?>">
    				<h3>Authenticate</h3>
    				<label>Password</label>
    				<input type="password" name="auth_password" value="" />
    				<input type="submit" value="Submit!" onclick="this.value='Loading...';this.disabled=true" />
    			</form>
    		<?php endif; ?>
    	</div>
	</body>
</html>