<?php
namespace App\Core\Database;
use Exception;

class Connection
{
	private $host = null;
    private $dbname = null;
    private $user = null;
    private $pass = null;
    private $configfile = CONFIG_DIRECTORY . CONFIG_FILE;

	function __construct($name=null)
	{
		$this->getConfigData($name);
	}
	public function getConfigData($name=null) {
        
        if (file_exists($this->configfile)) {
            $xml = simplexml_load_file($this->configfile);
            if ($name) {
            	$dbdata = $xml->db->connections->{trim($name)};
            	if (is_null($dbdata)) {
            		throw new Exception('get config database error: '.$name.' no exists');
            	}
            }else{
            	$dbdata = $xml->db->connections->{(string)trim( $xml->db->default )};
            }
            $this->host = (string) $dbdata->host;
            $this->user = (string) $dbdata->username;
            $this->pass = (string) $dbdata->password;
            $this->dbname = (string) $dbdata->dbname;
            return $this;
        } else {
            throw new Exception('get config database error');
        }
    }
    public function __call($func, $args){
    	$method = strtolower($func);

		if (substr($method, 0, 3) === 'get') {
			$attr = substr($method, 3);
			if (isset($this->{$attr})) {
				return $this->{$attr};
			}
		}
		throw new Exception('method not exists');
    }

}
