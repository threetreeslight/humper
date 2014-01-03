<?php


/**
 * HumperSecurityCompoment
 */
class HumperSecurityComponent {
    protected $session;
    protected $request;
    protected $secret_key_base;

    /**
     * constructor
     *
     * @param HumperSession $session
     */
    public function __construct($application) {
        $this->request = $application->getRequest();
        $this->session = $application->getSession();
    }

    public function setSecretKeyBase($key) {
        $this->secret_key_base = $key;
    }

    public function getSecretKeyBase() {
        return $this->secret_key_base;
    }

    /**
     * generate CSRF token
     *
     * @param string $form_name
     * @return string $token
     */
    public function generateCsrfToken() {
        $key = $this->getSecretKeyBase();
        $tokens = $this->session->get($key, array());
        if (count($tokens) >= 10) {
            array_shift($tokens);
        }

        $token = hash('sha512', $key. session_id() . microtime());
        $tokens[] = $token;

        $this->session->set($key, $tokens);

        return $token;
    }

    /**
     * validate CSRF token
     *
     * @param string $form_name
     * @param string $token
     * @return boolean
     */
    public function checkCsrfToken() {
        $key = $this->getSecretKeyBase();
        $token = $this->request->getPost('_token');

        $tokens = $this->session->get($key, array());

        if (false !== ($pos = array_search($token, $tokens, true))) {
            unset($tokens[$pos]);
            $this->session->set($key, $tokens);

            return true;
        }

        return false;
    }

}

