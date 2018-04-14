#!/usr/bin/env bash
BASEDIR=$(dirname "$0")
echo "php -f $BASEDIR\"/post-deploy.php\""
php -f $BASEDIR"/post-deploy.php"