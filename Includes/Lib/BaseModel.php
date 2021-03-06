<?php
namespace Lib;

class BaseModel{

    /**
     * @var \PDO
     */
    protected static $_db;

    /**
     * @var array
     */
    protected static $_dbConfig; // for debugging.


    /**
     * @return \PDO
     */
    public static function getDb(){
        if(!isset(self::$_db)){
            $config = require('Config/Database.php');
            self::$_dbConfig = $config;
            self::$_db = new \PDO('mysql:host=' . $config['host']. ';port=' . $config['port'] . ';dbname=' .$config['database'], $config['username'], $config['password']);
            self::$_db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
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
     * Returns 1 row (just 1!)
     *
     * @param $query
     * @param array $arguments
     * @return array
     */
    public static function fetchRow($query, array $arguments=[]){
        $stmt = self::_query($query, $arguments);
        $row = $stmt->fetch();
        $stmt->closeCursor();
        return $row;
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
     * Becarefull, this method overrides all the constraints (foreign keys)
     * @param $table
     * @return int
     */
    public static function _truncate($table){
       $stmt = self::_query("SET FOREIGN_KEY_CHECKS = 0;  TRUNCATE $table; SET FOREIGN_KEY_CHECKS = 1;");
       $r = $stmt->rowCount();
       $stmt->closeCursor();
       return $r;

    }

    /**
     * @todo this should just rerun all migrations ??
     * @param bool $insertSystemRows
     * @throws \Exception
     */
    public static function _truncateAll($insertSystemRows=true){
        // Delete all records from db.
        BaseModel::_truncate('comments');
        BaseModel::_truncate('topics');
        BaseModel::_truncate('categories');
        BaseModel::_truncate('branches');
        BaseModel::_truncate('userTokens');
        BaseModel::_truncate('companies');
        BaseModel::_truncate('offices');
        BaseModel::_truncate('tokenAcls');

        if($insertSystemRows===true) {
            self::execFile(self::_getMigrationDirPath() . '/002_insert_system_records.sql');
        }
    }

    /**
     * Runs database migrations, handle with care!
     *
     */
    public static function migrate(){
        self::getDb();
        if(DEBUG===true) {
            echo 'Running migrations on: '  . "\n";
            var_dump(self::$_dbConfig);
            echo 'current directory   : ' . getcwd() . "\n";
            echo 'open_basedir set too: ' . ini_get('open_basedir') . "\n";
            echo 'include_path set too: ' . ini_get('include_path') . "\n";
        }else{
            echo 'Running migrations' . "\n";
        }
        try{
            try {
                $row = self::fetchRow('SELECT `index`, fileName FROM migrations ORDER BY migration_id DESC LIMIT 0, 1');
                $last_index = $row['index'];
            }catch(\Exception $e){
                if($e->getCode()=='42S02'){
                    $last_index = 0;
                    $row = ['index' => 0, 'fileName' => '---'];
                }else{
                    throw($e);
                }
            }
            $path = self::_getMigrationDirPath();

            $files = array_slice(scandir($path), 2);
            sort($files);
            echo 'Last migration index: ' . $row['index'] . ' (' . $row['fileName'] . ')'  . "\n";
            foreach($files as $file){
                $parts = explode('_', $file);
                if((int) $parts[0] > (int) $last_index){
                    echo 'Executing file:'  . $file . "\n";
                    self::execFile($path . '/' . $file);
                    self::_insert([
                        'index' => $parts[0],
                        'fileName' => $path . '/' . $file
                    ], 'migrations');
                }
            }

        }catch(\Exception $e){
            echo $e->getMessage();
            exit(1);
        }
    }

    /**
     * @param $filePath
     * @throws \Exception
     */
    public static function execFile($filePath){
        if(!is_file($filePath)){
            throw new \Exception('File "' . $filePath . '" does not exist"');
        }
        $db = require(realpath(dirname(__FILE__) . '/..') . '/Config/Database.php');
        $commands =[];
        $commands[]= 'echo "[mysql]             # NEEDED FOR RESTORE" >> ./.sqlpwd';
        $commands[]= 'echo "user=' . $db['username'] . '" >> ./.sqlpwd';
        $commands[]= 'echo "password=' . $db['password'] . '" >> ./.sqlpwd';
        $commands[]= 'mysql --defaults-extra-file=./.sqlpwd '.$db['database'].' < ' . $filePath;
        $commands[]= 'rm ./.sqlpwd';
        foreach($commands as $command){
            $out=[];
            $return=0;
            exec($command, $out, $return);
            if($return==1){
                echo 'FAILED: Recreating database:'  . "\n";
                echo $command  . "\n";
                var_dump($out);
                exec('rm ./.sqlpwd');
                exit(1);
            }
        }
    }

    protected static function _getMigrationDirPath(){
        $path = escapeshellarg(realpath(dirname(__FILE__) . '/../..' . MIGRATION_DIR));
        // some differences exists between the test server and the local vagrant..
        // this results in the full path working on test, but only the relative path working on dev.
        // hence this check.
        if(is_dir($path)===false){
            // use a relative path
            $path = str_replace('/', '', MIGRATION_DIR);
        }
        if(DEBUG===true){
            echo 'Migration dir: ' . $path  . "\n";
        }
        return $path;
    }


    /**
     * @param string $userTokenTablePrefix
     * @return string
     *
     * We can add forum type here!
     */
    protected static function getACLWhere($userTokenTablePrefix = 'u'){
        $forum_type = ' ' . $userTokenTablePrefix .'.forum_type="' . Dispatcher::getUserCredentials('forum_type') . '"';
        if(Dispatcher::getUserCredentials('office_restricted') === true){
            return $forum_type . ' AND ' . $userTokenTablePrefix . '.local_office_id=' . (int) Dispatcher::getUserCredentials('local_office_id');
         }
        if(Dispatcher::getUserCredentials('company_restricted') === true){
            return $forum_type . ' AND ' . $userTokenTablePrefix . '.local_company_id=' . (int) Dispatcher::getUserCredentials('local_company_id');
        }
        if(Dispatcher::getUserCredentials('branch_restricted') === true){
            return $forum_type . ' AND ' . $userTokenTablePrefix . '.local_branch_id=' . (int) Dispatcher::getUserCredentials('local_branch_id');
        }
        return $forum_type;
    }

}