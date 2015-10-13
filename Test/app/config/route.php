<?php

namespace Ilex\Test\app\config;

/**
 * The following code will be included by Autoloader::resolve()
 * Whenever $Route::end() is invoked, $Route->settled will be TRUE,
 * so that the subsequent routes will fail.
 */

use \Ilex\Core\Loader;

/** @var \Ilex\Core\Route $Route */

$Route->get('/', function () {
    return ('Hello world!');
});

$Route->post('/user/(any)', function ($name) {
    $Input = Loader::model('sys/Input');
    return ('Hello ' . $Input->post('title', 'Guest') . ' ' . $name . '!');
});

$Route->get('/projects', 'Project'); // invoke ProjectController::index()
$Route->get('/project/(num)', 'Project', 'view'); // invoke ProjectController::view(num)
$Route->group('/planet', function ($Route) {
    $Route->get('/', function () {
        return ('Hello Cosmos!');
    });
    $Route->back();
});

$Route->controller('/about', 'About');

$Route->controller('/play', 'Play');

$Route->get('(all)', function ($url) {
    return ('Oops, 404! "' . $url . '" does not exist.');
});