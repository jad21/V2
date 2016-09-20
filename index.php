<?php
require "app/etc/kernel.php";
use Exception;

try {
    $app = new \V2\Core\Application();
    exit($app->run());
} catch (Exception $e) {
    ErrorHandlerFaltal($e);
}
