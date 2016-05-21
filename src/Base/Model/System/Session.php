<?php

namespace Ilex\Base\Model\System;

use \Ilex\Base\Model\BaseModel;
use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;

/**
 * @todo: check calling final vs. normal methods using static:: vs. self::
 * Class Session
 * Encapsulation of session.
 * @package Ilex\Base\Model\System
 * 
 * @property const string LOGIN
 * @property const string SID
 * @property const string USERID
 * @property const string USERNAME
 *
 * @property private static boolean $booted
 * @property private static array   $fakeSession
 * 
 * @method final public                __construct()
 * @method final public static         assign(array $var_list)
 * @method final public static         boot()
 * @method final public static         forget()
 * @method final public static mixed   get(string|boolean $key = FALSE, mixed $default = FALSE)
 * @method final public static boolean has(string $key)
 * @method final public static         makeGuest()
 * @method final public static string  newSid()
 * @method final public static mixed   set(string $key, mixed $value)
 *
 * @method private static start()
 */
class Session extends BaseModel
{
    const LOGIN    = 'login';
    const SID      = 'sid';
    const USERID   = 'userId';
    const USERNAME = 'username';

    private static $booted = FALSE;
    private static $fakeSession; // @todo: use \Ilex\Lib\Container

    final public function __construct()
    {
        self::boot();
    }

    /**
     * Assigns $var_list to $_SESSION or $fakeSession.
     * @uses ENVIRONMENT
     * @param array $var_list
     */
    final public static function assign($var_list)
    {
        // @todo: use array_merge or '+' operator?
        $tmp = self::$fakeSession + $var_list;
        if (ENVIRONMENT !== 'TESTILEX') {
            $_SESSION = $tmp;
            self::$fakeSession = &$_SESSION;
        } else {
            self::$fakeSession = $tmp;
        }
    }

    /**
     * @uses ENVIRONMENT
     */
    final public static function boot()
    {
        if (FALSE === self::$booted) {
            self::start();
            self::$booted = TRUE;
            if ('TESTILEX' !== ENVIRONMENT) {
                self::$fakeSession = &$_SESSION;
            } else {
                self::$fakeSession = [];
            }
            if (FALSE === self::has(self::SID)) {
                self::newSid();
            }
            if (FALSE === self::has(self::USERID)) {
                self::makeGuest();
            }
        }
        /*
         * Now the following fields have been assigned:
         * self::LOGIN
         * self::SID
         * self::USERID
         * self::USERNAME
         */
    }

    /**
     * Resets status.
     * @uses ENVIRONMENT
     */
    final public static function forget()
    {
        if ('TESTILEX' !== ENVIRONMENT) {
            session_unset();
            session_destroy();
        }
        self::start();
        self::newSid();
        self::makeGuest();
    }

    /**
     * Starts the session.
     * @uses ENVIRONMENT, SYS_SESSNAME
     */
    final private static function start()
    {
        if ('TESTILEX' !== ENVIRONMENT) {
            session_name(SYS_SESSNAME); // Defined in \Ilex\Core\Constant.
            session_start();
        }
    }

    /**
     * Generates new sid.
     * @return string
     */
    final public static function newSid()
    {
        return self::set(self::SID, sha1(uniqid() . mt_rand()));
    }

    /**
     * Sets guest status.
     * @uses USERID, USERNAME, LOGIN
     */
    final public static function makeGuest()
    {
        self::set(self::LOGIN, FALSE);
        self::set(self::USERID, 0);
        self::set(self::USERNAME, 'Guest');
    }

    /**
     * Checks $key in $fakeSession.
     * @param string $key
     * @return boolean
     */
    final public static function has($key)
    {
        return isset(self::$fakeSession[$key]);
    }

    /**
     * @todo check the default value of params
     * Gets value from $fakeSession.
     * @param string|boolean $key
     * @param mixed          $default
     * @return mixed
     */
    final public static function get($key = FALSE, $default = FALSE)
    {
        return (FALSE !== $key) ?
            (TRUE === isset(self::$fakeSession[$key]) ? self::$fakeSession[$key] : $default) :
            (self::$fakeSession);
    }

    /**
     * Sets value into $fakeSession.
     * @param string $key
     * @param mixed  $value
     * @return mixed
     */
    final public static function set($key, $value)
    {
        return (self::$fakeSession[$key] = $value);
    }
}