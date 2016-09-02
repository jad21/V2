<?php 

error_reporting(E_ERROR);
// error_reporting(E_ALL);
ini_set('display_errors', TRUE);
define("DS", DIRECTORY_SEPARATOR);
define('SERVER_ROOT', __dir__.DS);
define('CONFIG_FILE', 'config.xml');
define('CONFIG_DIRECTORY', SERVER_ROOT .'app' . DS . 'etc' . DS);
define('VIEW_DIRECTORY', SERVER_ROOT .'app' . DS . 'views' . DS);
define('LOGS_DIRECTORY', SERVER_ROOT .'app' . DS . 'var' . DS . 'logs' . DS);
define('REMOTE_IP', $_SERVER['REMOTE_ADDR']);
define('MODULE_MAIN', "Main");
define('CTRL_MAIN', "Main");

function exception_handler($e) {
	header("HTTP/1.1 500");
	http_response_code(500);
	$body_exception = 
		$e->getMessage()." ".
		$e->getFile().":".$e->getLine()." \n".
		$e->getTraceAsString();
	echo $body_exception;
	die();
}
set_exception_handler("exception_handler");

require "vendor/autoload.php";

try {
	$app = new \App\Core\Application();
	exit($app->run());
} catch (ErrorException $e) {
	exception_handler($e);
}

