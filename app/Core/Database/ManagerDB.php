<?php
namespace App\Core\Database;

class ManagerDB 
{
	const DB_DEFAULT = "_@main@	_";
    protected static $dbconections = [];
	
	public static function getConnection($name=self::DB_DEFAULT) {
        if ( !isset(self::$dbconections[$name]) ) {
            self::$dbconections[$name] = new DB($name);
        }
        return self::$dbconections[$name];
    }   
}