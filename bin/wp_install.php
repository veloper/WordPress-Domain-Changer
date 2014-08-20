<?php
// ./php wp_setup.php /full/path/to/wordpress/directory \
//  DB_PREFIX=wp_ \
//  DB_USER=wordpress_test \
//  DB_PASSWORD=wordpress_test \
//  DB_NAME=wordpress_test \
//  DB_HOST=localhost:3306 \
error_reporting(0);

require_once 'class.LocalWebServer.php';

$arguments   = $_SERVER["argv"];

$env         = array();
$script_name = array_shift($arguments);
$target      = array_shift($arguments);
foreach($arguments as $argument) {
  list($k,$v) = explode('=', $argument);
  $env[$k] = $v;
}


$db_name     = array_key_exists("DB_NAME", $env)     ? $env["DB_NAME"]     : 'wordpress_test';
$db_user     = array_key_exists("DB_USER", $env)     ? $env["DB_USER"]     : 'wordpress_test';
$db_password = array_key_exists("DB_PASSWORD", $env) ? $env["DB_PASSWORD"] : 'wordpress_test';
$db_host     = array_key_exists("DB_HOST", $env)     ? $env["DB_HOST"]     : 'localhost:3306';
$tbl_prefix   = array_key_exists("DB_PREFIX", $env)   ? $env["DB_PREFIX"]   : 'wp_';

// Paths
$document_root = realpath($target);

// Sanity Check
if(!file_exists($document_root)) die("Error: The Path '" . $document_root . "' does not exist.");

require_once($document_root . '/wp-includes/version.php');
$tbl_prefix .= str_replace(".", "_", $wp_version) . "_";

$server = new LocalWebServer(array("port" => "8114", "docroot" => $document_root));

$server->restart();

$server->request("POST", 'wp-admin/setup-config.php?step=2', array(
  "dbname" => $db_name,
  "uname"  => $db_user,
  "pwd"    => $db_password,
  "dbhost" => $db_host,
  "prefix" => $tbl_prefix
));


$server->request("POST", 'wp-admin/install.php?step=2', array(
  "weblog_title"    => "test",
  "user_name"       => "test",
  "admin_password"  => "test",
  "admin_password2" => "test",
  "admin_email"     => "test@example.com",
  "blog_public"     => 1
));

echo "\n\n";
echo "==== [ PID ] ===> " . $server->pid();
echo "\n\n";
echo "==== [ LINK ] ===> " . $server->rootUrl();
echo "\n\n";

