<?php

namespace app\config;

/**
 * The following code will be included by Autoloader::resolve()
 * Whenever $Route::end() is invoked, $Route->settled will be TRUE,
 * so that the subsequent routes will fail.
 */

use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;

/** @var \Ilex\Core\Route $Route */

Kit::log('#1 get /');
$Route->get('/', function () {
    return ('Hello world!');
});

Kit::log('#2 post /user/(any)');
$Route->post('/user/(any)', function ($name) {
    /**
     * Model 'sys/Input' has already been loaded in \Ilex\Tester::boot(),
     * and been updated in \Ilex\Tester::run(),
     * with the POST and data given by \Ilex\Test\RouteTest,
     * because the properties and methods of class Loader are all static,
     * and it is ensured that for each model only one entity is loaded.
     * Thus, the following assignment of $Input will not reload the model,
     * but just take out the loaded model from Loader::container,
     * and assign it to $Input.
     */
    $Input = Loader::model('sys/Input');
    Kit::log(['route.php', ['$Input' => $Input]]);
    return ('Hello ' . $Input->post('title', 'Guest') . ' ' . $name . '!');
});

Kit::log('#3 get /projects');
$Route->get('/projects', 'Project'); // invoke ProjectController::index()

Kit::log('#4 get /project/(num)');
$Route->get('/project/(num)', 'Project', 'view'); // invoke ProjectController::view(num)

Kit::log('#5 group /planet');
$Route->group('/planet', function ($Route) {
    Kit::log('#5.1 get /');
    $Route->get('/', function () {
        return ('Hello Cosmos!');
    });
    Kit::log('#5.2 back');
    $Route->back();
});

Kit::log('#6 controller /about');
$Route->controller('/about', 'About');

Kit::log('#7 controller /play');
$Route->controller('/play', 'Play');

Kit::log('#8 get (all)');
$Route->get('(all)', function ($url) {
    return ('Oops, 404! "' . $url . '" does not exist.');
});