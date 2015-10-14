<?php

namespace Ilex\Base\Model\sys;

use \Ilex\Base\Model\Base;
use \Ilex\Lib\Container;
use \Ilex\Lib\Kit;

/**
 * Class Session
 * Encapsulation of session.
 * @package Ilex\Base\Model\sys
 * 
 * @property private boolean $booted
 * @property private array   $fakeSession
 * 
 * @method public            __construct()
 * @method public    string  __toString()
 * @method public            boot()
 * @method public            forget()
 * @method public    string  newSid()
 * @method public            makeGuest()
 * @method public            assign(array $vars)
 * @method public    boolean has(string $key)
 * @method public    mixed   __get(string $key)
 * @method public    mixed   get(string|boolean $key = FALSE, mixed $default = FALSE)
 * @method public    mixed   __set(string $key, mixed $value)
 * @method public    mixed   set(string $key, mixed $value)
 * @method private           start()
 */
class Session extends Base
{
    const SID      = 'sid';
    const UID      = 'uid';
    const USERNAME = 'username';
    const LOGIN    = 'login';

    private $booted = FALSE;
    private $fakeSession; // @todo: use \Ilex\Lib\Container

    public function __construct()
    {
        $this->boot();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return Kit::toString([
            'booted'      => $this->booted,
            'fakeSession' => $this->fakeSession
        ]);
    }

    /**
     * @uses ENVIRONMENT
     */
    public function boot()
    {
        if (!$this->booted) {
            $this->start();
            $this->booted = TRUE;
            if (ENVIRONMENT !== 'TEST') {
                $this->fakeSession = &$_SESSION;
            } else {
                $this->fakeSession = [];
            }
            if (!$this->has($this->SID)) {
                $this->newSid();
            }
            if (!$this->has($this->UID)) {
                $this->makeGuest();
            }
        }
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
     * Generates new sid.
     * @return string
     */
    public function newSid()
    {
        return $this->set($this->SID, sha1(uniqid() . mt_rand()));
    }

    /**
     * Sets guest status.
     * @uses UID, USERNAME, LOGIN
     */
    public function makeGuest()
    {
        $this->set($this->UID, 0);
        $this->set($this->USERNAME, 'Guest');
        $this->set($this->LOGIN, FALSE);
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
     * Checks $key in $fakeSession.
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return isset($this->fakeSession[$key]);
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
     * Gets value from $fakeSession.
     * @param string|boolean $key
     * @param mixed          $default
     * @return $mixed
     */
    public function get($key = FALSE, $default = FALSE)
    {
        return $key ?
            (isset($this->fakeSession[$key]) ? $this->fakeSession[$key] : $default) :
            $this->fakeSession;
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
     * Sets value into $fakeSession.
     * @param string $key
     * @param mixed  $value
     * @return mixed
     */
    public function set($key, $value)
    {
        return $this->fakeSession[$key] = $value;
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