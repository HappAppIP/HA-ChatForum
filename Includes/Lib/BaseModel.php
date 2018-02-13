<?php
namespace Lib;

class BaseModel{

    /**
     * @var \PDO
     */
    protected static $_db;


    /**
     * @return \PDO
     */
    public static function getDb(){
        if(!isset(self::$_db)){
            $config = require('Config/Database.php');
            self::$_db = new \PDO('mysql:host=' . $config['host']. ';port=' . $config['port'] . ';dbname=' .$config['database'], $config['username'], $config['password']);
            self::$_db->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
            self::$_db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            self::$_db->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        }
        return self::$_db;
    }

    /**
     * @param $query
     * @param array $arguments
     * @return \PDOStatement
     */
    public static function _query($query, array $arguments=[]){
        $stmt = self::getDb()->prepare($query);
        $stmt->execute($arguments);
        return $stmt;
    }

    /**
     * @param array $data
     * @param $table
     * @throws \ErrorException
     * @return int
     */
    public static function _insert(array $data, $table){
        $db = self::getDb();
        $fields = '`' . implode('`,`', array_keys($data)) . '`';
        $placeholders = ':' . implode(', :', array_keys($data));
        $q= 'INSERT INTO ' . $table . '(' . $fields .')VALUES(' . $placeholders .')';
        $stmt = $db->prepare($q);
        foreach($data as $placeholder => &$value) {
            $placeholder = ':' . $placeholder;
            $stmt->bindParam($placeholder, $value);
        }
        if(!$stmt->execute()) {
            throw new \ErrorException('Could not execute query', 500);
        }
        if($stmt->rowCount() == 0) {
            throw new \ErrorException('Could not insert data into table. (' . $db->errorCode() . ')', 500);
        }
        $stmt->closeCursor();
        return $db->lastInsertId();
    }

    /**
     * @param $table
     * @param $whereField
     * @param $whereValue
     * @param array $updateData
     * @return int
     * @throws \ErrorException
     */
    public static function _update($table, $whereField, $whereValue, array $updateData){
        $db = self::getDb();
        $fields = [];
        foreach($updateData as $k => $v){
            $fields [] = '`' . $k . '`= :' . $k;
        }
        $q= 'UPDATE ' . $table . ' SET ' . implode(', ', $fields) . ' WHERE `' . $whereField . '`= :whereValue';
        $stmt = $db->prepare($q);
        foreach($updateData as $placeholder => &$value) {
            $placeholder = ':' . $placeholder;
            $stmt->bindParam($placeholder, $value);
        }
        $stmt->bindParam(':whereValue', $whereValue);
        if(!$stmt->execute()) {
            throw new \ErrorException('Could not execute query', 500);
        }
        $r= $stmt->rowCount();
        $stmt->closeCursor();
        return $r;
    }

    /**
     * @param $table
     * @param $fieldName
     * @param $fieldValue
     * @return int
     */
    public static function _delete($table, $fieldName, $fieldValue){
        $stmt =self::_query("DELETE FROM $table WHERE `$fieldName`=?", [$fieldValue]);
        $r = $stmt->rowCount();
        $stmt->closeCursor();
        return $r;
    }

    /**
     * @param $table
     * @return int
     */
    public static function _truncate($table){
       $stmt = self::_query("TRUNCATE $table;");
       $r = $stmt->rowCount();
       $stmt->closeCursor();
       return $r;

    }


}