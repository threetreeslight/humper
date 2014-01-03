<?php

/**
 * Request.
 */
class HumperRequest {
    public $method = null;

    /**
     * constractor
     */
    public function __construct() {
        $this->setMethod();
    }


    /**
     * set Http Method by request
     *
     * @return string
     */
    public function setMethod() {
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['_method']) ) {
            $this->method = $_SERVER['REQUEST_METHOD'];
        } elseif ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $this->method = (string)strtoupper($_POST['_method']);
        } else {
            $this->method = 'GET';
        }
    }

    /**
     * get SERVER parameter
     *
     * @param string $name
     * @param mixed $default ( not exist key's value. retrun this value )
     * @return mixed
     */
    public function getSERVER($name, $default = null) {
        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }
        return $default;
    }


    /**
     * get GET parameters
     *
     * @param string $name
     * @param mixed $default ( not exist key's value. retrun this value )
     * @return mixed
     */
    public function getGet($name, $default = null) {
        if (isset($_GET[$name])) {
            return $_GET[$name];
        }
        return $default;
    }

    /**
     * get POST parameter
     *
     * @param string $name
     * @param mixed $default ( not exist key's value. retrun this value )
     * @return mixed
     */
    public function getPost($name, $default = null) {
        if (isset($_POST[$name])) {
            return $_POST[$name];
        }
        return $default;
    }

    /**
     * get host name
     *
     * @return string
     */
    public function getHost() {
        if (!empty($_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];
        }
        return $_SERVER['SERVER_NAME'];
    }

    /**
     * check request is ssl
     *
     * @return boolean
     */
    public function isSsl() {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            return true;
        }
        return false;
    }

    /**
     * get request uri
     *
     * @return string
     */
    public function getRequestUri() {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * get base url
     *
     * @return string
     */
    public function getBaseUrl() {
        $script_name = $_SERVER['SCRIPT_NAME'];
        $request_uri = $this->getRequestUri();

        if (0 === strpos($request_uri, $script_name)) {
            return $script_name;
        } else if (0 === strpos($request_uri, dirname($script_name))) {
            return rtrim(dirname($script_name), '/');
        }
        return '';
    }

    /**
     * get path info
     *
     * @return string
     */
    public function getPathInfo() {
        $base_url = $this->getBaseUrl();
        $request_uri = $this->getRequestUri();

        if (false !== ($pos = strpos($request_uri, '?'))) {
            $request_uri = substr($request_uri, 0, $pos);
        }

        $path_info = (string)substr($request_uri, strlen($base_url));

        return $path_info;
    }

}
