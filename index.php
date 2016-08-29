<?php 
ini_set('max_execution_time', 300);
//error_reporting(E_ALL);
ini_set('display_errors', TRUE);
define("DS", DIRECTORY_SEPARATOR);
define('SERVER_ROOT', str_replace('index.php', '', $_SERVER['SCRIPT_FILENAME']));
define('CONFIG_FILE', 'config.xml');
define('CONFIG_DIRECTORY', SERVER_ROOT .'app' . DS . 'Etc' . DS);
define('LOGS_DIRECTORY', SERVER_ROOT .'app' . DS . 'var' . DS . 'logs' . DS);
define('REMOTE_IP', $_SERVER['REMOTE_ADDR']);
define('MODULE_MAIN', "Main");

require "vendor/autoload.php";

$app = new \App\Core\Application();
exit($app->run());

