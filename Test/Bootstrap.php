<?php

namespace Ilex\Test;

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', 1);
ini_set('html_errors', 0);
session_cache_expire(240);

require_once(__DIR__ . '/../../../autoload.php');
require_once(__DIR__ . '/ValidatorTester.php');

use \Exception;
use \Ilex\Tester;
use \Ilex\Core\Debug;
use \Ilex\Lib\Kit;
use \Ilex\Test\ValidatorTester as VT;

try {
    Tester::boot(__DIR__ . '/app', __DIR__ . '/runtime', 'app');
    // VT::test('countCollection');
    // echo 'Validator Test Passed.' . PHP_EOL;
} catch (Exception $e) {
    echo Kit::j(Debug::extractException($e));
}
