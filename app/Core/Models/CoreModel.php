<?php
namespace App\Core\Models;

use App\Core\Database\ManagerDB;
use Exception;

abstract class CoreModel
{
    protected $connection_name = null;
    protected $table;
    protected $primary_key = "id";
    protected $db;
    private $sqlLastString;
    private $_query_filter = [];
    public $variables;

    public function __construct($data = array())
    {
        $this->db        = ManagerDB::getConnection($this->connection_name);
        $this->variables = $data;
    }
    public function __set($name, $value)
    {
        if (strtolower($name) === $this->primary_key) {
            $this->variables[$this->primary_key] = $value;
        } else {
            $this->variables[$name] = $value;
        }
    }
    public function __get($name)
    {
        if (is_array($this->variables)) {
            if (array_key_exists($name, $this->variables)) {
                return $this->variables[$name];
            }
        }
        return null;
    }
    public function save($id = "0")
    {
        $this->variables[$this->primary_key] = (empty($this->variables[$this->primary_key])) ? $id : $this->variables[$this->primary_key];
        if ($this->variables[$this->primary_key] === 0) {
            return $this->create();
        }else{
            $fieldsvals                          = '';
            $columns                             = array_keys($this->variables);
            foreach ($columns as $column) {
                if ($column !== $this->primary_key) {
                    $fieldsvals .= $column . " = :" . $column . ",";
                }

            }
            $fieldsvals = substr_replace($fieldsvals, '', -1);
            if (count($columns) > 1) {
                $sql = "UPDATE " . $this->table . " SET " . $fieldsvals . " WHERE " . $this->primary_key . "= :" . $this->primary_key;
                if ($id === "0" && $this->variables[$this->primary_key] === "0") {
                    throw new Exception("Solo se puede actualizar si tiene un {$this->primary_key} Model:" . self::class, 1);
                }
                return $this->exec($sql);
            }
            return null;
        }
    }
    public function create($data = false)
    {
        if ($data and is_array($data)) {
            $this->variables = $data;
        }
        $bindings = $this->variables;
        if (!empty($bindings)) {
            $fields     = array_keys($bindings);
            $fieldsvals = array(implode(",", $fields), ":" . implode(",:", $fields));
            $sql        = "INSERT INTO " . $this->table . " (" . $fieldsvals[0] . ") VALUES (" . $fieldsvals[1] . ")";
        } else {
            throw new Exception("No hay valores para crear en " . self::class, -3);
        }
        return $this->exec($sql);
    }
    public function delete($id = "")
    {
        $id = (empty($this->variables[$this->primary_key])) ? $id : $this->variables[$this->primary_key];
        if (!empty($id)) {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primary_key}= :{$this->primary_key} LIMIT 1";
            return $this->exec($sql, array($this->primary_key => $id));
        }
    }
    public function find($id = "")
    {
        $id = (empty($this->variables[$this->primary_key])) ? $id : $this->variables[$this->primary_key];
        if (!empty($id)) {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primary_key}= :{$this->primary_key} LIMIT 1";

            $result          = $this->db->row($sql, array($this->primary_key => $id));
            $this->variables = ($result != false) ? $result : null;
        }
        return $this;
    }
    public function all()
    {
        return $this->db->query("SELECT * FROM " . $this->table);
    }
    public function min($field)
    {
        if ($field) {
            return $this->db->single("SELECT min(" . $field . ")" . " FROM " . $this->table);
        }

    }
    public function max($field)
    {
        if ($field) {
            return $this->db->single("SELECT max(" . $field . ")" . " FROM " . $this->table);
        }

    }
    public function avg($field)
    {
        if ($field) {
            return $this->db->single("SELECT avg(" . $field . ")" . " FROM " . $this->table);
        }

    }
    public function sum($field)
    {
        if ($field) {
            return $this->db->single("SELECT sum(" . $field . ")" . " FROM " . $this->table);
        }

    }
    public function count($field)
    {
        if ($field) {
            return $this->db->single("SELECT count(" . $field . ")" . " FROM " . $this->table);
        }

    }

    /**
     * @param array $fields.
     * @param array $sort.
     * @return array of Collection.
     * Example: $user = new User;
     * $found_user_array = $user->search(array('sex' => 'Male', 'age' => '18'), array('dob' => 'DESC'));
     * // Will produce: SELECT * FROM {$this->table_name} WHERE sex = :sex AND age = :age ORDER BY dob DESC;
     * // And rest is binding those params with the Query. Which will return an array.
     * // Now we can use for each on $found_user_array.
     * Other functionalities ex: Support for LIKE, >, <, >=, <= ... Are not yet supported.
     */
    public function search($fields = array(), $sort = array())
    {
        $bindings = empty($fields) ? $this->variables : $fields;
        $sql      = "SELECT * FROM " . $this->table;
        if (!empty($bindings)) {
            $fieldsvals = array();
            $columns    = array_keys($bindings);
            foreach ($columns as $column) {
                $fieldsvals[] = $column . " = :" . $column;
            }
            $sql .= " WHERE " . implode(" AND ", $fieldsvals);
        }

        if (!empty($sort)) {
            $sortvals = array();
            foreach ($sort as $key => $value) {
                $sortvals[] = $key . " " . $value;
            }
            $sql .= " ORDER BY " . implode(", ", $sortvals);
        }
        return $this->exec($sql);
    }

    public function getLastQuery()
    {
        return $this->sqlLastString;
    }

    public function setLastQuery($sql)
    {
        $this->sqlLastString = $sql;
    }

    public function where($key, $condicional, $value = null, $operador = "AND")
    {
        $_key  = "";
        $_cond = "=";
        $_val  = "";
        if (func_num_args() == 2) {
            $_key = $key;
            $_val = $condicional;
        } else if (func_num_args() >= 3) {
            $_key  = $key;
            $_cond = $condicional;
            $_val  = $value;
        } else {
            throw new Exception("Error en el where " . self::class, 1);
        }

        // $this->_query_filter .= " {$operador} {$this->table}.{$_key}{$_cond}{$_val} ";
        $this->_query_filter[] = [
            "operador" => $operador,
            "key"      => $_key,
            "cond"     => $_cond,
            "val"      => $_val,
        ];

        return $this;
    }
    public function first()
    {
        $sql           = "SELECT * FROM {$this->table} WHERE 1 ";
        $_query_filter = "";
        $bindParams    = [];
        $cont = 0;
        foreach ($this->_query_filter as $ikey => $ivalue) {
            $_query_filter .= " {$ivalue['operador']} {$this->table}.{$ivalue['key']}{$ivalue['cond']} :{$cont}{$ivalue['key']} ";
            $bindParams[$cont.$ivalue['key']] = $ivalue['val'];
            $cont++;
        }
        $sql .= $_query_filter;
        $sql .= " LIMIT 1";
        $res                 = $this->db->row($sql, $bindParams, \PDO::FETCH_OBJ);
        $this->_query_filter = [];
        if (!empty($res)) {
            return $res;
        }
        return null;
    }
    public function get()
    {
        $sql           = "SELECT * FROM {$this->table} WHERE 1 ";
        $_query_filter = "";
        $bindParams    = [];
        $cont = 0;
        foreach ($this->_query_filter as $ikey => $ivalue) {
            $_query_filter .= " {$ivalue['operador']} {$this->table}.{$ivalue['key']}{$ivalue['cond']} :{$cont}{$ivalue['key']} ";
            $bindParams["{$cont}{$ivalue['key']}"] = $ivalue['val'];
            $cont++;
        }
        $sql .= $_query_filter;
        $res                 = $this->exec($sql, $bindParams);
        $this->_query_filter = [];
        return $res;
    }
    public function table($table = '')
    {
        $this->table = $table;
        return $this;
    }
    public function update($data)
    {
        $sql = "";
        if (!empty($data) && is_array($data) && is_array($this->_query_filter) and count($this->_query_filter) > 0) {
            try {
                $setFields     = "";
                $paramsSet     = array();
                $paramsWhere   = array();
                $setKey        = "";
                $_query_filter = "";
                foreach ($data as $key => $value) {
                    $setFields .= "{$key} = :a_{$key},";
                    $paramsSet["a_{$key}"] = $value;
                }
                $setFields = substr($setFields, 0, -1);
                foreach ($this->_query_filter as $ikey => $ivalue) {
                    $_query_filter .= " {$ivalue['operador']} {$this->table}.{$ivalue['key']}{$ivalue['cond']} :b_{$ivalue['key']} ";
                    $paramsWhere["b_{$ivalue['key']}"] = $ivalue['val'];
                }

                $sql .= "UPDATE {$this->table} SET $setFields WHERE 1  $_query_filter;";

                return $this->exec($sql, array_merge($paramsSet, $paramsWhere));
            } catch (Exception $exc) {
                return [
                    "code"       => "ERRORDB",
                    "msg"        => $exc->getMessage() . PHP_EOL . $exc->getTraceAsString(),
                    "last_query" => $this->getLastQuery(),
                    "args"       => func_get_args(),
                ];
            }
        } else {
            throw new Exception("Faltan parametros en el update", 1);

        }
    }

    private function exec($sql, $array = null)
    {
        $this->setLastQuery($sql);
        if ($array !== null) {
            // Get result with the DB object
            $result = $this->db->query($sql, $array);
        } else {
            // Get result with the DB object
            $result = $this->db->query($sql, $this->variables);
        }

        // Empty bindings
        $this->variables = array();
        return $result;
    }
}
