<?php

/**
 * HumperResponse.
 */
class HumperResponse {
    protected $content;
    protected $status_code = 200;
    protected $status_text = 'OK';
    protected $http_headers = array();

    /**
     * send Response
     */
    public function send() {
        ini_set("default_charset", 'UTF-8');
        header('HTTP/1.1 ' . $this->status_code . ' ' . $this->status_text);
        foreach ($this->http_headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->content;
    }

    /**
     * set contents
     *
     * @param string $content
     */
    public function setContent($content) {
        $this->content = $content;
    }

    /**
     * set status code
     *
     * @param integer $status_code
     * @param string $status_text
     */
    public function setStatusCode($status_code, $status_text = '') {
        $this->status_code = $status_code;
        $this->status_text = $status_text;
    }

    /**
     * get status code
     *
     * @return array $status_code
     */
    public function getStatusCode() {
        return $this->status_code;
    }

    /**
     * set http response header
     *
     * @param string $name
     * @param mixed $value
     */
    public function setHttpHeader($name, $value) {
        $this->http_headers[$name] = $value;
    }
}
