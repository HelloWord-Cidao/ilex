<?php

use \Ilex\Autoloader;

require_once(__DIR__ . '/../../vendor/autoload.php');

Autoloader::run(__DIR__ . '/../app/', __DIR__ . '/../runtime/');