<?php

namespace Ilex\Base\Model\System;

use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;

/**
 * @todo: method arg type validate
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
 * @method public                __construct()
 * @method public static         assign(array $var_list)
 * @method public static         boot()
 * @method public static         forget()
 * @method public static mixed   get(string|boolean $key = FALSE, mixed $default = FALSE)
 * @method public static boolean has(string $key)
 * @method public static         makeGuest()
 * @method public static string  newSid()
 * @method public static mixed   set(string $key, mixed $value)
 *
 * @method private static start()
 */
final class Session
{
    const LOGIN    = 'login';
    const SID      = 'sid';
    const USERID   = 'userId';
    const USERNAME = 'username';

    private static $booted = FALSE;
    private static $fakeSession; // @todo: use \Ilex\Lib\Container

    public function __construct()
    {
        self::boot();
    }

    /**
     * Assigns $var_list to $_SESSION or $fakeSession.
     * @param array $var_list
     */
    public static function assign($var_list)
    {
        $tmp = array_merge(self::$fakeSession, $var_list);
        if (ENVIRONMENT !== 'TESTILEX') { // @TODO: change it
            $_SESSION = $tmp;
            self::$fakeSession = &$_SESSION;
        } else {
            self::$fakeSession = $tmp;
        }
    }

    public static function boot()
    {
        if (FALSE === self::$booted) {
            self::start();
            self::$booted = TRUE;
            if ('TESTILEX' !== ENVIRONMENT) { // @TODO: change it
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
     */
    public static function forget()
    {
        if ('TESTILEX' !== ENVIRONMENT) { // @TODO: change it
            session_unset();
            session_destroy();
        }
        self::start();
        self::newSid();
        self::makeGuest();
    }

    /**
     * Starts the session.
     * @uses SYS_SESSNAME
     */
    private static function start()
    {
        if ('TESTILEX' !== ENVIRONMENT) { // @TODO: change it
            session_name(SYS_SESSNAME); // Defined in \Ilex\Core\Constant.
            session_start();
        }
    }

    /**
     * Generates new sid.
     * @return string
     */
    public static function newSid()
    {
        return self::set(self::SID, sha1(uniqid() . mt_rand()));
    }

    /**
     * Sets guest status.
     * @uses USERID, USERNAME, LOGIN
     */
    public static function makeGuest()
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
    public static function has($key)
    {
        return isset(self::$fakeSession[$key]);
    }

    /**
     * @todo check the default value of args
     * Gets value from $fakeSession.
     * @param string|boolean $key
     * @param mixed          $default
     * @return mixed
     */
    public static function get($key = FALSE, $default = FALSE)
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
    public static function set($key, $value)
    {
        return (self::$fakeSession[$key] = $value);
    }
}