<?php
namespace Lib;
// removing this results in the deletion of the production database!!!
define('PHPUNIT_RUNNING', true);  // do not remove!!!


// Re-create a empty test database.
$db = require('Includes/Config/Database.php');
$commands =[];
$commands[]= 'echo "[mysqldump]             # NEEDED FOR DUMP" > ./.sqlpwd';
$commands[]= 'echo "user=' . $db['username'] . '" >> ./.sqlpwd';
$commands[]= 'echo "password=' . $db['password'] . '" >> .sqlpwd';
$commands[]= 'echo "[mysql]             # NEEDED FOR RESTORE" >> ./.sqlpwd';
$commands[]= 'echo "user=' . $db['username'] . '" >> ./.sqlpwd';
$commands[]= 'echo "password=' . $db['password'] . '" >> ./.sqlpwd';
$commands[]= 'mysqldump --defaults-extra-file=./.sqlpwd  --no-data '.$db['database_prod'].' > ./schema.sql';
$commands[]= 'mysql --defaults-extra-file=./.sqlpwd -e "DROP DATABASE IF EXISTS ' . $db['database'] . '"';
$commands[]= 'mysql --defaults-extra-file=./.sqlpwd  -e "CREATE DATABASE ' . $db['database'] . '"';
$commands[]= 'mysql --defaults-extra-file=./.sqlpwd '.$db['database'].' < ./schema.sql';
$commands[]= 'rm ./.sqlpwd schema.sql';

foreach($commands as $command){
    $out=[];
    $return=0;
    exec($command, $out, $return);
    if($return==1){
        echo 'FAILED: Recreating database:';
        echo $command;
        var_dump($out);
        exit;
    }
}

require_once('Includes/Config/Constants.php');
require __DIR__ . '/../../vendor/autoload.php';
ini_set('open_basedir', '/');

Bootstrap::bootstrap();
BaseModel::migrate();
