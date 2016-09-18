<?php
namespace App\Core\Utils;

class Result
{
    public $code  = "";
    public $msg   = "";
    public $error = false;
    public $data  = [];
    public function __construct($data, $msg, $code, $error)
    {
        if (func_num_args()==4) {
            $this->make($data, $msg, $code, $error);
        }
    }
    public function make($data, $msg, $code, $error){
        $this->data  = $data;
        $this->code  = $code;
        $this->msg   = $msg;
        $this->error = $error;   
    }
    public static function success($data = [], $msg = "", $code = "OK")
    {
        return new self($data, $msg, $code, $error = false);
    }
    public static function error($msg = "", $data = [], $code = "ERROR")
    {
        return new self($data, $msg, $code, $error = true);
    }
    public function isBad($value = '')
    {
        return $this->error == true;
    }
    public function isError($value = '')
    {
        return $this->error == true;
    }
    public function isOk($value = '')
    {
        return $this->error != true;
    }
    public function isGood($value = '')
    {
        return $this->error != true;
    }
    public function getData()
    {
        return $this->data;
    }
    public function setData($arg1,$arg2=null)
    {
        if (is_not_null($arg2)) {
            if(is_array($this->data)){
                $this->data[$arg1] = $arg2;
            }
        }
    }
    public function getMessage()
    {
        return $this->msg;
    }
    public function toArray()
    {
        return [
            "code"  => $this->code,
            "msg"   => $this->msg,
            "error" => $this->error,
            "data"  => $this->data,
        ];
    }
    
    public function toJson()
    {
        return json_encode($this->toArray());
    }
    public function __tostring()
    {
        return $this->toJson();
    }
}
