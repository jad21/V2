<?php
require "app/Core/kernel.php";
require "vendor/autoload.php";
use App\Core\Logs\Logger;
use App\Core\Utils\Result;

try {
    $app = new \App\Core\Application();
    exit($app->run());
} catch (Exception $e) {
    header('HTTP/1.1 500 Error Server');
    $body_exception =
    $e->getMessage() . " " .
    $e->getFile() . ":" . $e->getLine() . " \n" .
    $e->getTraceAsString();
    $response_str = Result::error($body_exception,null, $code = "ERROREXCEPTION");
    echo $response_str;
    Logger::error($body_exception);
}
