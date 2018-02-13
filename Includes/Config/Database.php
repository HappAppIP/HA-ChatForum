<?php
if(defined('PHPUNIT_RUNNING') && PHPUNIT_RUNNING === true){
    return [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'forum_unittest',
        'database_prod' => 'forum',
        'username' => 'homestead',
        'password' => 'secret'

    ];
}
return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'forum',
    'username' => 'homestead',
    'password' => 'secret'

];