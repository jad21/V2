<?php 
namespace App\Core\Utils;
use Exception;

class Env
{
	private $configfile = CONFIG_DIRECTORY . CONFIG_FILE;
	public static $setting = null;
	public function __construct()
	{
		self::$setting = $this->getDataFile();
	}
	public static function getData($file=null)
	{
		if (is_not_null($file)) {
			$this->configfile = CONFIG_DIRECTORY . $file;
			return $this->getDataFile();
		}
		if (empty(self::$setting) OR is_null(self::$setting)) {
			new self;
		}
		return self::$setting;
	}
	private function getDataFile(){
		if (file_exists($this->configfile)) {
            $xml = simplexml_load_file($this->configfile);
            return $xml;
        } else {
            throw new Exception('get config file error');
        }
	}
}