<?php

/**
 * HumperI18nComponent
 */
class HumperI18nComponent {
    protected $request;
    protected $session;
    protected $default_language = 'en';
    protected $dict_dir = null;
    protected $dict_data = null;
    protected $locale = null;

    /**
     * constructor
     *
     * @param HumperApplication $application
     */
    public function __construct($application) {
        $this->request = $application->getRequest();
        $this->session = $application->getSession();
        $this->dict_dir = $application->getConfigDir().'/locales';
        $this->generateDictionary();
    }

    /**
     * generate dictionary
     *
     * @return boolean
     */
    public function generateDictionary() {
        $this->dict_data = $this->findDictionary($this->getLanguage());
    }

    /**
     * find dictionary
     *
     * @return boolean
     */
    protected function findDictionary($lang) {
        $_file = $this->dict_dir .'/'. $lang .'.yml';

        if (!is_readable($_file)) {
            return false;
        }

        require_once "spyc/Spyc.php";
        return Spyc::YAMLLoad($_file);
    }

    /**
     * compile Accept Languages
     *
     * @return boolean
     */
    public function compileAcceptLanguages() {
        $accept_languages = explode(',',$this->request->getSERVER('HTTP_ACCEPT_LANGUAGE', false));

        if (!$accept_languages) { return null; }

        foreach( $accept_languages as $language ) {
            preg_match_all('/(?P<langcode>[a-zA-Z-]+)(?:;q=[0-9\.]+)?/', $language, $matches);

            # parse ja-JP to jp
            $compiled_lang[] = substr($matches['langcode'][0], 0, 2);
        }
        return $compiled_lang;
    }

    /**
     * translate keyword
     *
     * @params string $key
     * @return mixed
     */
    public function t($key) {
        if (!isset($this->dict_data) || !isset($this->dict_data[$this->getLanguage()][$key])) {
            return (string)$key;
        }
        return $this->dict_data[$this->getLanguage()][$key];
    }

    /**
     * set language
     */
    public function setLanguage($lang = null) {
        $this->session->set('_language', (string)$lang);
        $this->generateDictionary();
    }

    /**
     * get language
     *
     * @return string lang
     */
    public function getLanguage() {
        return $this->session->get('_language', $this->default_language);
    }

    /**
     * set default_language
     */
    public function setDefaultLanguage($lang = null) {
        $this->default_language = $lang;
    }

    /**
     * get default language
     *
     * @return string lang
     */
    public function getDefaultLanguage() {
        return $this->default_language;
    }

    /**
     * set locale
     *
     * @return locale
     */
    public function setLocale($locale = null) {
        $this->locale = $locale;
    }

    /**
     * get locale
     *
     * @return locale
     */
    public function getLocale() {
        return $this->locale;
    }
}
