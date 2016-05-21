<?php

namespace Ilex\Test;

use PHPUnit_Framework_TestCase;
use \Ilex\Tester;
use \Ilex\Lib\Kit;

/**
 * Class RouterTester
 * @package \Ilex\Test
 *
 * @method final public static testHelloWorld()
 * @method final public static testPost()
 * @method final public static testCallingController()
 * @method final public static testControllerIndex()
 * @method final public static testControllerFunction()
 * @method final public static testControllerResolve()
 * @method final public static testGroup()
 */
final class RouterTester extends PHPUnit_Framework_TestCase
{
    final public static function testHelloWorld()
    {
        Kit::log('testHelloWorld #1');
        self::assertEquals('Hello world!', Tester::run(), 'Homepage does not come out as expected.');
    }

    final public static function testPost()
    {
        Kit::log('testPost #1');
        self::assertEquals('Hello Guest Someone!', Tester::run('/user/Someone', 'POST'), 'Post with default fails.');
        Kit::log('testPost #2');
        self::assertEquals('Hello Mr. Someone!', Tester::run('/user/Someone', 'POST', ['title' => 'Mr.']), 'Post fails.');
    }

    final public static function testCallingController()
    {
        Kit::log('testCallingController #1');
        self::assertEquals('See all projects.', Tester::run('/projects'), 'Fail to visit the controller\'s index page.');
        Kit::log('testCallingController #2');
        self::assertEquals('Oops, 404! "/project/oops" does not exist.', Tester::run('/project/oops'), 'Fail to report 404 for invalid url pattern.');
        Kit::log('testCallingController #3');
        self::assertEquals('You\'re looking at Project-23', Tester::run('/project/23'), 'Fail to call one of the controller\'s functions.');
    }

    final public static function testControllerIndex()
    {
        self::assertEquals('about', Tester::run('/about'), 'testControllerIndex fails.');
        self::assertEquals('about', Tester::run('/about/'), 'testControllerIndex fails.');
        self::assertEquals('about', Tester::run('/about//'), 'testControllerIndex fails.');
        self::assertEquals('about', Tester::run('/about/index'), 'testControllerIndex fails.');
        self::assertEquals('about', Tester::run('/about/index/'), 'testControllerIndex fails.');
        self::assertEquals('about', Tester::run('/about/index//'), 'testControllerIndex fails.');
    }

    final public static function testControllerFunction()
    {
        self::assertEquals('Join tech!', Tester::run('/about/join'));
        self::assertEquals('Join tech!', Tester::run('/about/join/'));
        self::assertEquals('Join whatever!', Tester::run('/about/join/whatever'));
        self::assertEquals('Join whatever!', Tester::run('/about/join/whatever/'));
        self::assertEquals('Join whatever!', Tester::run('/about/join/whatever//'));
        // @TODO: Guess the default 'GET' method will go wrong! Test it!
        self::assertEquals('Welcome to whatever, Jack!', Tester::run('/about/join/whatever/', 'POST'));
        self::assertEquals('Welcome to whatever, John!', Tester::run('/about/join/whatever/', 'POST', ['name' => 'John']));
    }

    final public static function testControllerResolve()
    {
        self::assertEquals('Come and play!', Tester::run('/play'));
        self::assertEquals('Come and play!', Tester::run('/play/'));
        self::assertEquals('Play No.7?', Tester::run('/play/7'));
        self::assertEquals('Play No.7?', Tester::run('/play/play/7'));
        self::assertEquals('No back here...', Tester::run('/play/no-back'));
        self::assertEquals('No back here...', Tester::run('/play/no-back/'));
        self::assertEquals('', Tester::run('/play/no-back/no-back'));
        self::assertEquals('Sorry but "Mr.Rabbit" is not here. 404.', Tester::run('/play/Mr.Rabbit'));
        self::assertEquals('Sorry but "play/nobody" is not here. 404.', Tester::run('/play/play/nobody'));
    }

    final public static function testGroup()
    {
        self::assertEquals('Hello Cosmos!', Tester::run('/planet'));
        self::assertEquals('Hello Cosmos!', Tester::run('/planet/'));
        self::assertEquals('Oops, 404! "/planet/mars" does not exist.', Tester::run('/planet/mars'));
    }
}