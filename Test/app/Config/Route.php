<?php

namespace app\Config;

/**
 * The following code will be included by Autoloader::resolve()
 * Whenever $Router::end() is invoked, $Router->settled will be TRUE,
 * so that the subsequent routes will fail.
 */

use \Ilex\Core\Loader;
use \Ilex\Lib\Kit;

/** @var \Ilex\Core\Router $Router */

Kit::log('#1 get /');
$Router->get('/', function () {
    return ('Hello world!');
});

Kit::log('#2 post /user/(any)');
$Router->post('/user/(any)', function ($name) {
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
    Kit::log(['Route.php', ['$Input' => $Input]]);
    return ('Hello ' . $Input->post('title', 'Guest') . ' ' . $name . '!');
});

Kit::log('#3 get /projects');
$Router->get('/projects', 'Project'); // This will invoke ProjectController::index().

Kit::log('#4 get /project/(num)');
$Router->get('/project/(num)', 'Project', 'view'); // This will invoke ProjectController::view(num).

Kit::log('#5 group /planet');
$Router->group('/planet', function ($Router) {
    Kit::log('#5.1 get /');
    $Router->get('/', function () {
        return ('Hello Cosmos!');
    });
    Kit::log('#5.2 back');
    $Router->back();
});

Kit::log('#6 controller /about');
$Router->controller('/about', 'About');

Kit::log('#7 controller /play');
$Router->controller('/play', 'Play');

Kit::log('#8 get (all)');
$Router->get('(all)', function ($url) {
    return ('Oops, 404! "' . $url . '" does not exist.');
});