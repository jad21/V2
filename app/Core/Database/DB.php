<?php
namespace App\Core\Database;
use Exception;
use PDOException;
use PDO;
class DB {

    const DB_DEFAULT = "_@main@_";
    protected static $dbconections = [];
    private function __construct($name) {
        try {
            if (DB::DB_DEFAULT==$name) {
                $con = new Connection();
            }else{
                $con = new Connection($name);
            }            
            self::$dbconections[$name] = new PDO('mysql:host=' . $con->getHost() . ';dbname=' . $con->getDbname(), 
                                $con->getUser(), $con->getPass());
            self::$dbconections[$name]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
    }

    public static function getConnection($name=DB::DB_DEFAULT) {
        if ( !isset(self::$dbconections[$name]) ) {
            new DB($name);
        }
        return self::$dbconections[$name];
    }
    
}