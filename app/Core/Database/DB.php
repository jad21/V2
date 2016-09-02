<?php
namespace App\Core\Database;
use Exception;
use PDOException;
use PDO;
class DB {

    # @object, The PDO object
    private $pdo;
    # @object, PDO statement object
    private $PDOstatement;
    # @bool ,  Connected to the database
    private $bConnected = false;
    # @array, The parameters of the SQL query
    private $parameters = [];
    # @string, name conection
    private $name = null;

    public function __construct($name = null) {
        $this->name = $name;
        // $this->Connect();
    }
    private function Connect()
    {
        $name = $this->name;
        $con = new Connection($name);
        $dsn            = 'mysql:host=' . $con->getHost() . ';dbname=' . $con->getDbname();
        try {
            # Read settings from INI file, set UTF8
            $this->pdo = new PDO($dsn, $con->getUser(), $con->getPass(), array(
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            ));
            
            # We can now log any exceptions on Fatal error. 
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            # Disable emulation of prepared statements, use REAL prepared statements instead.
            // $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            # Connection succeeded, set the boolean to true.
            $this->bConnected = true;
        }
        catch (PDOException $e) {
            throw new Exception($e->getMessage(), -2);
        }
    }

    public function CloseConnection()
    {
        $this->pdo = null;
    }
    /**
     *  Every method which needs to execute a SQL query uses this method.
     *  
     *  1. If not connected, connect to the database.
     *  2. Prepare Query.
     *  3. Parameterize Query.
     *  4. Execute Query.   
     *  5. On exception : Write Exception into the log + SQL query.
     *  6. Reset the Parameters.
     */
    private function Init($query, $parameters = "")
    {
        # Connect to database
        if (!$this->bConnected) {
            $this->Connect();
        }
        try {
            # Prepare query
            $this->PDOstatement = $this->pdo->prepare($query);
            
            # Add parameters to the parameter array 
            $this->bindMore($parameters);
            
            # Bind parameters
            if (!empty($this->parameters)) {
                foreach ($this->parameters as $param => $value) {
                    
                    $type = PDO::PARAM_STR;
                    switch ($value[1]) {
                        case 0:
                            $type = PDO::PARAM_STR;
                            break;
                        case is_int($value[1]): 
                            $type = PDO::PARAM_INT; 
                            break;
                        case is_bool($value[1]):
                            $type = PDO::PARAM_BOOL;
                            break;
                        case is_null($value[1]):
                            $type = PDO::PARAM_NULL;
                            break;
                    }
                    // dd($query,$parameters);
                    // Add type when binding the values to the column
                    // $this->PDOstatement->bindValue($value[0], $value[1], $type);
                    $this->PDOstatement->bindParam($value[0], $value[1],$type);
                }
            }
            
            # Execute SQL 
            $this->PDOstatement->execute();
            # Reset the parameters
            $this->parameters = array();
        }
        catch (PDOException $e) {
            throw new Exception($e->getMessage(). " \ntrace: " .$e->getTraceAsString(), -3);
        }
        
    }  
    /**
     *  @void 
     *
     *  Add the parameter to the parameter array
     *  @param string $para  
     *  @param string $value 
     */
    public function bind($para, $value)
    {
        $this->parameters[sizeof($this->parameters)] = [":" . $para , $value];
    }
    /**
     *  @void
     *  
     *  Add more parameters to the parameter array
     *  @param array $parray
     */
    public function bindMore($parray)
    {
        if (empty($this->parameters) && is_array($parray)) {
            $columns = array_keys($parray);
            foreach ($columns as $i => &$column) {
                $this->bind($column, $parray[$column]);
            }
        }
    } 
    /**
     *  If the SQL query  contains a SELECT or SHOW statement it returns an array containing all of the result set row
     *  If the SQL statement is a DELETE, INSERT, or UPDATE statement it returns the number of affected rows
     *
     *      @param  string $query
     *  @param  array  $params
     *  @param  int    $fetchmode
     *  @return mixed
     */
    public function query($query, $params = null, $fetchmode = PDO::FETCH_OBJ)
    // public function query($query, $params = null, $fetchmode = PDO::FETCH_ASSOC)
    {
        $query = trim(str_replace("\r", " ", $query));
        
        $this->Init($query, $params);
        
        $rawStatement = explode(" ", preg_replace("/\s+|\t+|\n+/", " ", $query));
        
        # Which SQL statement is used 
        $statement = strtolower($rawStatement[0]);
        
        if ($statement === 'select' || $statement === 'show') {
            return $this->PDOstatement->fetchAll($fetchmode);
        } elseif ($statement === 'insert' || $statement === 'update' || $statement === 'delete') {
            return $this->PDOstatement->rowCount();
        } else {
            return NULL;
        }
    }
    
    /**
     *  Returns the last inserted id.
     *  @return string
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Starts the transaction
     * @return boolean, true on success or false on failure
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }
    
    /**
     *  Execute Transaction
     *  @return boolean, true on success or false on failure
     */
    public function executeTransaction()
    {
        return $this->pdo->commit();
    }
    
    /**
     *  Rollback of Transaction
     *  @return boolean, true on success or false on failure
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }
    
    /**
     *  Returns an array which represents a column from the result set 
     *
     *  @param  string $query
     *  @param  array  $params
     *  @return array
     */
    public function column($query, $params = null)
    {
        $this->Init($query, $params);
        $Columns = $this->PDOstatement->fetchAll(PDO::FETCH_NUM);
        
        $column = null;
        
        foreach ($Columns as $cells) {
            $column[] = $cells[0];
        }
        
        return $column;
        
    }
    /**
     *  Returns an array which represents a row from the result set 
     *
     *  @param  string $query
     *  @param  array  $params
     *      @param  int    $fetchmode
     *  @return array
     */
    public function row($query, $params = null, $fetchmode = PDO::FETCH_ASSOC)
    {
        $this->Init($query, $params);
        $result = $this->PDOstatement->fetch($fetchmode);
        $this->PDOstatement->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued,
        return $result;
    }
    /**
     *  Returns the value of one single field/column
     *
     *  @param  string $query
     *  @param  array  $params
     *  @return string
     */
    public function single($query, $params = null)
    {
        $this->Init($query, $params);
        $result = $this->PDOstatement->fetchColumn();
        $this->PDOstatement->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued
        return $result;
    }
    
}