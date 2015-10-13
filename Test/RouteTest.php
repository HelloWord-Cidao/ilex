<?php

namespace Ilex\Test;

use \Ilex\Tester;

/**
 * Class RouteTest
 * @package \Ilex\Test
 *
 * @method public testHelloWorld()
 * @method public testPost()
 * @method public testCallingController()
 * @method public testControllerIndex()
 * @method public testControllerFunction()
 * @method public testControllerResolve()
 * @method public testGroup()
 */
class RouteTest extends PHPUnit_Framework_TestCase
{
    public function testHelloWorld()
    {
        $this->assertEquals('Hello world!', Tester::run(), 'Homepage does not come out as expected.');
    }

    public function testPost()
    {
        $this->assertEquals('Hello Guest Someone!', Tester::run('/user/Someone', 'POST'), 'Post with default fails.');
        $this->assertEquals('Hello Mr. Someone!', Tester::run('/user/Someone', 'POST', ['title' => 'Mr.']), 'Post fails.');
    }

    public function testCallingController()
    {
        $this->assertEquals('See all projects.', Tester::run('/projects'), 'Fail to visit the controller\'s index page.');
        $this->assertEquals('Oops, 404! "/project/oops" does not exist.', Tester::run('/project/oops'), 'Fail to report 404 for invalid url pattern.');
        $this->assertEquals('You\'re looking at Project-23', Tester::run('/project/23'), 'Fail to call one of the controller\'s functions.');
    }

    public function testControllerIndex()
    {
        $this->assertEquals('about', Tester::run('/about'), 'testControllerIndex fails.');
        $this->assertEquals('about', Tester::run('/about/'), 'testControllerIndex fails.');
        $this->assertEquals('about', Tester::run('/about//'), 'testControllerIndex fails.');
        $this->assertEquals('about', Tester::run('/about/index'), 'testControllerIndex fails.');
        $this->assertEquals('about', Tester::run('/about/index/'), 'testControllerIndex fails.');
        // $this->assertEquals('about', Tester::run('/about/index//'), 'testControllerIndex fails.');
    }

    public function testControllerFunction()
    {
        $this->assertEquals('Join tech!', Tester::run('/about/join'));
        $this->assertEquals('Join tech!', Tester::run('/about/join/'));
        $this->assertEquals('Join whatever!', Tester::run('/about/join/whatever'));
        // $this->assertEquals('Join whatever!', Tester::run('/about/join/whatever/'));
        // $this->assertEquals('Join whatever!', Tester::run('/about/join/whatever//'));
        // the default 'GET' method will go wrong!
        // $this->assertEquals('Welcome to whatever, Jack!', Tester::run('/about/join/whatever/', 'POST'));
        // $this->assertEquals('Welcome to whatever, John!', Tester::run('/about/join/whatever/', 'POST', ['name' => 'John']));
    }

    public function testControllerResolve()
    {
        // $this->assertEquals('Come and play!', Tester::run('/play'));
        // $this->assertEquals('Come and play!', Tester::run('/play/'));
        // $this->assertEquals('Play No.7?', Tester::run('/play/7'));
        // $this->assertEquals('Play No.7?', Tester::run('/play/play/7'));
        // $this->assertEquals('No back here...', Tester::run('/play/no-back'));
        // $this->assertEquals('No back here...', Tester::run('/play/no-back/'));
        // $this->assertEquals('', Tester::run('/play/no-back/no-back'));
        // $this->assertEquals('Sorry but "Mr.Rabbit" is not here. 404.', Tester::run('/play/Mr.Rabbit'));
        // $this->assertEquals('Sorry but "play/nobody" is not here. 404.', Tester::run('/play/play/nobody'));
    }

    public function testGroup()
    {
        // $this->assertEquals('Hello Cosmos!', Tester::run('/planet'));
        // $this->assertEquals('Hello Cosmos!', Tester::run('/planet/'));
        $this->assertEquals('Oops, 404! "/planet/mars" does not exist.', Tester::run('/planet/mars'));
    }

}