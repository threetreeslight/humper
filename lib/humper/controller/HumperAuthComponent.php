<?php

/**
 * HumperAuthComponent
 */

class HumperAuthComponent {
    protected $root_dir = '/';

    /**
     * constructor
     *
     * @param HumperSession $session
     */
    public function __construct($application) {
        $this->session = $application->getSession();
    }

    /**
     * set Authrecation Status
     *
     * @param boolean
     */
    public function setAuthenticated($bool) {
        $this->session->set('_authenticated', (bool)$bool);
        $this->session->regenerate();
    }

    /**
     * valify current user is authricated
     *
     * @return boolean
     */
    public function isAuthenticated() {
        return $this->session->get('_authenticated', false);
    }

    public function setRootDir($dir) {
        $this->root_dir = $dir;
    }

    public function getRootDir($dir) {
        return $this->root_dir;
    }

    public function current_user() {
        return $this->session->get('current_user', false);
    }

}
