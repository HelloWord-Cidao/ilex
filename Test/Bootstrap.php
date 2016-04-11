<?php

namespace Ilex\Test;

require_once(__DIR__ . '/../vendor/autoload.php');

use \Ilex\Tester;

// define('ENVIRONMENT', 'TEST');

Tester::boot(__DIR__ . '/../Test/app', __DIR__ . '/../Test/runtime', 'app');

require(__DIR__ . '/RouteTest.php');

use \Ilex\Test\RouteTest;

$RouteTest = new RouteTest();
$RouteTest->testHelloWorld();
$RouteTest->testPost();
$RouteTest->testCallingController();
$RouteTest->testControllerIndex();
$RouteTest->testControllerFunction();
$RouteTest->testControllerResolve();
$RouteTest->testGroup();
echo 'OK' . PHP_EOL;
