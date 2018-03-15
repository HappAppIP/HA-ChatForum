<?php
// File used to manage simple database migrations.
// This file should be executed just after deploy.

require('../vendor/autoload.php');
require_once('../Includes/Config/Constants.php');
Lib\Bootstrap::bootstrap();

\Lib\BaseModel::migrate();