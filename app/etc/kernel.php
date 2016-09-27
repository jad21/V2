<?php
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
define("DS", DIRECTORY_SEPARATOR);
define('SERVER_ROOT', realpath(__dir__ . DS . ".." . DS . "..") . DS);
define('CONFIG_FILE', 'config.xml');
define('CONFIG_DIRECTORY', SERVER_ROOT . 'app' . DS . 'etc' . DS);
define('VIEW_DIRECTORY', SERVER_ROOT . 'app' . DS . 'views' . DS);
define('VAR_DIRECTORY', SERVER_ROOT . 'app' . DS . 'var' . DS);
define('LOGS_DIRECTORY', VAR_DIRECTORY . 'logs' . DS);
define('REMOTE_IP', isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:"127.0.0.1");
define('MODULE_MAIN', "Main");
define('CTRL_MAIN', "Main");


/*timezone*/
date_default_timezone_set('America/La_Paz'); 

require SERVER_ROOT."vendor/autoload.php";