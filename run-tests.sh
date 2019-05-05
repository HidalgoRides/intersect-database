#!/bin/sh

PHP=`which php`
$PHP vendor/bin/phpunit --testsuite "mysql"
$PHP vendor/bin/phpunit --testsuite "postgres"