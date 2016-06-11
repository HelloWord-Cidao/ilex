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

Kit::log('#1 get / func');
$Router->get('/', function () {
    return ('Hello world!');
});

Kit::log('#2 post /user/(uid:any) func');
$Router->post('/user/(uid:any)', function ($name) {
    /**
     * Model 'System/Input' has already been loaded in \Ilex\Tester::boot(),
     * and been updated in \Ilex\Tester::run(),
     * with the POST and data given by \Ilex\Test\RouteTest,
     * because the properties and methods of class Loader are all static,
     * and it is ensured that for each model only one entity is loaded.
     * Thus, the following assignment of $Input will not reload the model,
     * but just take out the loaded model from Loader::container,
     * and assign it to $Input.
     */
    $Input = Loader::model('System/Input');
    Kit::log(['Route.php', ['$Input' => $Input]]);
    return ('Hello ' . $Input->post('title', 'Guest') . ' ' . $name . '!');
});

Kit::log('#3 get /projects Project');
$Router->get('/projects', 'Project'); // This will invoke ProjectController::index().

Kit::log('#4 get /project/(pid:num) Project view');
$Router->get('/project/(pid:num)', 'Project', 'view'); // This will invoke ProjectController::view(num).

// @todo: what is the situation of the usage of the route of type: group??
Kit::log('#5 group /planet func');
$Router->group('/planet', function ($Router) {
    Kit::log('#5.1 get / func');
    $Router->get('/', function () {
        return ('Hello Cosmos!');
    });
    Kit::log('#5.2 back');
    $Router->back();
    return $Router->result();
});

Kit::log('#6 controller /about About');
$Router->controller('/about', 'About'); // This will invoke AboutController.

Kit::log('#7 controller /play Play');
$Router->controller('/play', 'Play'); // This will invoke PlayController.

Kit::log('#8 get (uri:all) func');
$Router->get('(uri:all)', function ($url) {
    return ('Oops, 404! "' . $url . '" does not exist.');
});