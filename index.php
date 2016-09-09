<?php 

// error_reporting(E_ALL);
ini_set('display_errors', TRUE);
define("DS", DIRECTORY_SEPARATOR);
define('SERVER_ROOT', __dir__.DS);
define('CONFIG_FILE', 'config.xml');
define('CONFIG_DIRECTORY', SERVER_ROOT .'app' . DS . 'etc' . DS);
define('VIEW_DIRECTORY', SERVER_ROOT .'app' . DS . 'views' . DS);
define('VAR_DIRECTORY', SERVER_ROOT .'app' . DS . 'var' . DS );
define('LOGS_DIRECTORY', VAR_DIRECTORY . 'logs' . DS);
define('REMOTE_IP', $_SERVER['REMOTE_ADDR']);
define('MODULE_MAIN', "Main");
define('CTRL_MAIN', "Main");	

require "vendor/autoload.php";
use App\Core\Logs\Logger;

try {
	$app = new \App\Core\Application();
	exit($app->run());
} catch (Exception $e) {
	header("HTTP/1.1 500");
	echo date("Y-m-d H:i:s")."\n".$e->getTraceAsString();
	Logger::error($e->getTraceAsString());
}

