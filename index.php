<<<<<<< HEAD
<?php
require "app/etc/kernel.php";
use Exception;

try {
    $app = new \V2\Core\Application();
    exit($app->run());
} catch (Exception $e) {
    ErrorHandlerFaltal($e);
}
=======
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
>>>>>>> 163952770fcb0d5076006b87056a6636c495fd0a
