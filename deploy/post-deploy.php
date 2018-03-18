<?php
// File used to manage simple database migrations.
// This file should be executed just after deploy.
$dirName = realpath(dirname(__FILE__) . '/../');
require($dirName . '/vendor/autoload.php');
require_once($dirName . '/Includes/Config/Constants.php');
Lib\Bootstrap::bootstrap();

\Lib\BaseModel::migrate();