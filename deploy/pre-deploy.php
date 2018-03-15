<?php
// build the needed config files.

$unittest['host'] = getenv("JENKINS_PHPUNIT_DB_HOST");
$unittest['port'] = getenv("JENKINS_PHPUNIT_DB_PORT");
$unittest['database'] = getenv("JENKINS_PHPUNIT_DB_DATABASE");
$unittest['username'] = getenv("JENKINS_PHPUNIT_DB_USER");
$unittest['password'] = getenv("JENKINS_PHPUNIT_DB_PASSWORD");

$test['host'] = getenv("JENKINS_DB_HOST");
$test['port'] = getenv("JENKINS_DB_PORT");
$test['database'] = getenv("JENKINS_DB_DATABASE");
$test['username'] = getenv("JENKINS_DB_USER");
$test['password'] = getenv("JENKINS_DB_PASSWORD");


$fileName = '../Includes/Config/Database.php';
$configData = <<<EOS
<?php
if(defined('PHPUNIT_RUNNING') && PHPUNIT_RUNNING === true){
    return [
        'host' => '{$unittest['host']}',
        'port' => {$unittest['port']},
        'database' => '{$unittest['database']}',
        'database_prod' => '{$test['database']}',
        'username' => '{$unittest['username']}',
        'password' => '{$unittest['password']}'

    ];
}
return [
    'host' => '{$test['host']}',
    'port' => {$test['port']},
    'database' => '{$test['database']}',
    'username' => '{$test['username']}',
    'password' => '{$test['password']}'

];
EOS;

file_put_contents($fileName, $configData);
