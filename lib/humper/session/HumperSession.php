<?php

/**
 * HumperSession.
 */
class HumperSession {
    protected static $sessionStarted = false;
    protected static $sessionIdRegenerated = false;

    /**
     * constructor
     *
     * start session
     *
     */
    public function __construct() {
        #TODO: when application framework migration finished, remove haveAlreadySession process and method
        $this->haveAlreadySession();

        if (!self::$sessionStarted) {
            session_start();

            self::$sessionStarted = true;
        }
    }

    /**
     * session exist check
     *
     * @param boolean $debug
     */
    #TODO: when application framework migration finished, remove haveAlreadySession process and method
    public function haveAlreadySession () {
        if ( !(session_id() == false) ) {
            self::$sessionStarted = true;
        }
    }

    /**
     * set value in session
     *
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value) {
        $_SESSION[$name] = $value;
    }

    /**
     * get value in session
     *
     * @param string $name
     * @param mixed $default (when key cant found, return this value)
     */
    public function get($name, $default = null) {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }
        return $default;
    }

    /**
     * remove value in session
     *
     * @param string $name
     */
    public function remove($name) {
        unset($_SESSION[$name]);
    }

    /**
     * clear session
     */
    public function clear() {
        $_SESSION = array();
    }

    /**
     * regenerate session id
     *
     * @param boolean $destroy (trueの場合は古いセッションを破棄する)
     */
    public function regenerate($destroy = true) {
        if (!self::$sessionIdRegenerated) {
            session_regenerate_id($destroy);
            self::$sessionIdRegenerated = true;
        }
    }

}
