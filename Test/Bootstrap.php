<?php

require_once(__DIR__ . '/../vendor/autoload.php');

\Ilex\Test::boot(__DIR__ . '/../Test/app', __DIR__ . '/../Test/runtime');

include(__DIR__ . '/RouteTest.php');

$RouteTest = new RouteTest();
$RouteTest->testHelloWorld();
$RouteTest->testPost();
$RouteTest->testCallingController();
$RouteTest->testControllerIndex();
$RouteTest->testControllerFunction();
$RouteTest->testControllerResolve();
$RouteTest->testGroup();