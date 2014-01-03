<?php

/**
 * HumperHelperComponent
 */
class HumperHelperComponent {
    protected $application;
    protected $helpers = array(
                            'rest_view_helper',
                            'security_helper',
                            'i18n_helper'
                         );

    /**
     * constructor
     *
     * @param HumperApplication $application
     */
    public function __construct($application) {
        $this->application = $application;
    }

    /**
     * humper helper registration
     *
     * @param HumperApplication $application
     */
    public function humperHelperRegistration() { }

    /**
     * loadHelpers
     *
     * @param string $controller_name
     */
    public function loadHelper($controller_name) {
        $this->loadDefaultHelper();
        $this->loadControllerHelper($controller_name);
    }

    /**
     * load controller helper
     *
     * @param string $controller_name
     */
    public function loadControllerHelper($controller_name) {
        $_file = $this->application->getHelperDir().'/'. $controller_name .'_helper.php';
        if (is_readable($_file)) {
            require $_file;
        }
    }

    /**
     * load humper default helper
     *
     * @param string $controller_name
     */
    public function loadDefaultHelper() {
        $base_path = $this->application->getLibraryDir().'/humper/helper';
        foreach( $this->helpers as $helper ) {
            $_file = $base_path .'/'. $helper .'.php';
            if (is_readable($_file)) {
                require $_file;
            }
        }
    }

}
