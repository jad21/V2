<?php
namespace App\Core\Models;
use App\Core\Database\DB;
use Exception;
use PDO;

abstract class CoreModel 
{
	protected $connection_name = null;
	protected $table;
	protected $primary_key = "id";
	protected $db;
	private $exception;
	private $sqlLastString;
	function __construct()
	{
		$this->db = DB::getConnection($this->connection_name);
	}
	public function save($data) {
        $sql = "";
        if (is_array($data)) {
            try {
                $fields = "";
                $values = "";
                $params = array();
                foreach ($data as $key => $value) {
                    $fields .="{$key},";
                    $values .=":{$key},";
                    $params[":{$key}"] = $value;
                }
                $fields = substr($fields, 0, -1);
                $values = substr($values, 0, -1);
                $sql .= "INSERT INTO {$this->table} ( {$fields} ) VALUES( {$values} );";
                $statement = $this->db->prepare($sql);
                foreach ($params as $key => $value) {
                    $statement->bindParam($key, $value, PDO::PARAM_STR);
                }
                $statement->execute($params);
                $this->setException($statement->errorInfo());
                $this->setLastQuery($sql);
                return true;
            } catch (Exception $exc) {
                $this->setException($exc->getMessage() . PHP_EOL . $exc->getTraceAsString());
                return FALSE;
            }
        } else {
            return false;
        }
    }

    public function update($data, $index) {
        $sql = "";
        if (!empty($data) && is_array($data) && is_array($index)) {
            try {
                $setFields = "";
                $params = array();
                $keyParams = array();
                $setKey = "";
                foreach ($data as $key => $value) {
                    $setFields .= "{$key} = :{$key},";
                    $params[":{$key}"] = $value;
                }
                $setFields = substr($setFields, 0, -1);
                foreach ($index as $iKey => $iValue) {
                    $setKey .= "{$iKey} = :{$iKey} AND ";
                    $keyParams[":{$iKey}"] = $iValue;
                }

                $setKey = substr($setKey, 0, -4);
                $sql .= "UPDATE {$this->table} SET $setFields WHERE $setKey ;";
                $statement = $this->db->prepare($sql);
                foreach ($params as $key => $value) {
                    $statement->bindParam($key, $value, PDO::PARAM_STR);
                }
                foreach ($keyParams as $key => $value) {
                    $statement->bindParam($key, $value, PDO::PARAM_STR);
                }
                $statement->execute($params);
                $this->setException($statement->errorInfo());
                $this->setLastQuery($sql);
                return true;
            } catch (Exception $exc) {
                 $this->setException($exc->getMessage() . PHP_EOL . $exc->getTraceAsString());
                return FALSE;
            }
        } else {
            return false;
        }
    }

    public function query($sql,$data=[])
    {
    	// try {
    		$this->setLastQuery($sql);
	    	$statement = $this->db->prepare($sql);
			$statement->execute($data);
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			return $statement->fetchAll();
    	// } catch (Exception $exc) {
    		// $this->setException($exc->getMessage() . PHP_EOL . $exc->getTraceAsString());
            // return FALSE;
    	// }
    }
    public function find($id)
    {
    	$sql = "SELECT * FROM {$this->table} WHERE id=:id";
    	$result = $this->query($sql ,["id"=>$id]);
    	if (isset($result) and count($result)>0) {
    		return $result[0];
    	}
    	return null;
    }
    public function all()
    {
    	$sql = "SELECT * FROM {$this->table}";
    	return $this->query($sql);
    }

    public function getLastQuery() {
        return $this->sqlLastString;
    }

    public function setLastQuery($sql) {
        $this->sqlLastString = $sql;
    }

    public function getException() {
        return $this->exception;
    }

    public function setException($message) {
        $this->exception = $message;
    }
}