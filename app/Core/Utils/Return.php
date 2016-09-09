<?php 
namespace App\Core\Utils;
use Exception;

class Return
{
	$code = "";
	$msg = "";
	$error = false;
	$data = [];
	public static function success($data=[],$code="OK",$msg="")
	{
		new self;
		$this->code
		return self::$setting;
	}
	public static function error($value='')
	{
		# code...
	}
	public function isBad($value='')
	{
		return $this->error==true;
	}
	public function isOk($value='')
	{
		return $this->error!=true;	
	}
	public function __tostring()
	{
		return json_encode([
			"code"=>$this->code,
			"msg"=>$this->msg,
			"error"=>$this->error,
			"data"=>$data
		]);
	}
}