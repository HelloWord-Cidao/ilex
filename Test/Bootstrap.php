<?php

namespace Ilex\Test;

use \Ilex\Tester;
use \Ilex\RouteTest;

require_once(__DIR__ . '/../vendor/autoload.php');

Tester::boot(__DIR__ . '/../Test/app', __DIR__ . '/../Test/runtime');

include(__DIR__ . '/RouteTest.php');

$RouteTest = new RouteTest();
$RouteTest->testHelloWorld();
$RouteTest->testPost();
$RouteTest->testCallingController();
$RouteTest->testControllerIndex();
$RouteTest->testControllerFunction();
$RouteTest->testControllerResolve();
$RouteTest->testGroup();
var_dump('OK');
