<?php

namespace Ilex\Base\Model\System;

use \Ilex\Base\Model\Base;
use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;

/**
 * Class Session
 * Encapsulation of session.
 * @package Ilex\Base\Model\System
 * 
 * @property const string LOGIN
 * @property const string SID
 * @property const string UID
 * @property const string USERNAME
 *
 * @property private boolean $booted
 * @property private array   $fakeSession
 * 
 * @method public         __construct()
 * @method public mixed   __get(string $key)
 * @method public mixed   __set(string $key, mixed $value)
 * @method public string  __toString()
 * @method public         assign(array $vars)
 * @method public         boot()
 * @method public         forget()
 * @method public mixed   get(string|boolean $key = FALSE, mixed $default = FALSE)
 * @method public boolean has(string $key)
 * @method public         makeGuest()
 * @method public string  newSid()
 * @method public mixed   set(string $key, mixed $value)
 *
 * @method private start()
 */
class Session extends Base
{
    const LOGIN    = 'login';
    const SID      = 'sid';
    const UID      = 'uid';
    const USERNAME = 'username';

    private $booted = FALSE;
    private $fakeSession; // @todo: use \Ilex\Lib\Container

    public function __construct()
    {
        $this->boot();
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @return mixed
     */
    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return Kit::toString([
            'booted'      => $this->booted,
            'fakeSession' => $this->fakeSession,
        ]);
    }

    /**
     * Assigns $vars to $_SESSION or $fakeSession.
     * @uses ENVIRONMENT
     * @param array $vars
     */
    public function assign($vars)
    {
        $tmp = array_merge($this->fakeSession, $vars);
        if (ENVIRONMENT !== 'TEST') {
            $_SESSION = $tmp;
            $this->fakeSession = &$_SESSION;
        } else {
            $this->fakeSession = $tmp;
        }
    }

    /**
     * @uses ENVIRONMENT
     */
    public function boot()
    {
        if ($this->booted === FALSE) {
            $this->start();
            $this->booted = TRUE;
            if (ENVIRONMENT !== 'TEST') {
                $this->fakeSession = &$_SESSION;
            } else {
                $this->fakeSession = [];
            }
            if ($this->has($this->SID) === FALSE) {
                $this->newSid();
            }
            if ($this->has($this->UID) === FALSE) {
                $this->makeGuest();
            }
        }
        /*
         * Now the following fields have been assigned:
         * $this->LOGIN
         * $this->SID
         * $this->UID
         * $this->USERNAME
         */
    }

    /**
     * Resets status.
     * @uses ENVIRONMENT
     */
    public function forget()
    {
        if (ENVIRONMENT !== 'TEST') {
            session_unset();
            session_destroy();
        }
        $this->start();
        $this->newSid();
        $this->makeGuest();
    }

    /**
     * @todo check the default value of params
     * Gets value from $fakeSession.
     * @param string|boolean $key
     * @param mixed          $default
     * @return mixed
     */
    public function get($key = FALSE, $default = FALSE)
    {
        return $key ?
            (isset($this->fakeSession[$key]) ? $this->fakeSession[$key] : $default) :
            $this->fakeSession;
    }

    /**
     * Checks $key in $fakeSession.
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return isset($this->fakeSession[$key]);
    }

    /**
     * Sets guest status.
     * @uses UID, USERNAME, LOGIN
     */
    public function makeGuest()
    {
        $this->set($this->LOGIN, FALSE);
        $this->set($this->UID, 0);
        $this->set($this->USERNAME, 'Guest');
    }

    /**
     * Generates new sid.
     * @return string
     */
    public function newSid()
    {
        return $this->set($this->SID, sha1(uniqid() . mt_rand()));
    }

    /**
     * Sets value into $fakeSession.
     * @param string $key
     * @param mixed  $value
     * @return mixed
     */
    public function set($key, $value)
    {
        return ($this->fakeSession[$key] = $value);
    }

    /**
     * Starts the session.
     * @uses ENVIRONMENT, SYS_SESSNAME
     */
    private function start()
    {
        if (ENVIRONMENT !== 'TEST') {
            session_name(SYS_SESSNAME); // Defined in \Ilex\Core\Constant.
            session_start();
        }
    }
}