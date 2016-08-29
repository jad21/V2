<?php
namespace App\Core\Logs;
use Exception;
abstract class Logger 
{
    public static function log($message, $filename = "logger",$prefix = "LOG") {
        
        $path = LOGS_DIRECTORY;
        $file = $path . $filename . '.log';
        if(is_file($file)){
            $bool = file_put_contents($file, date('Y-m-d H:i:s').' ' . $prefix . ' : ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
        }else{
            $bool = file_put_contents($file, date('Y-m-d H:i:s').' ' . $prefix .' : ' . $message);
        }
        if (!$bool) {
            $error = error_get_last();
            throw new Exception($error["message"], 1);
        }
        return $bool; 
    }

    public static function error($message, $filename = "logger",$prefix = "ERROR") 
    {
        return self::log($message, $filename, $prefix);
    }
    
    public static function info($message, $filename = "logger",$prefix = "INFO") 
    {
        return self::log($message, $filename, $prefix);
    }
}