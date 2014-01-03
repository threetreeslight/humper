<?php

/**
 * ApplicationController
 *
 */
class ApplicationController extends HumperController {
    protected $current_user;

    function beforeFilter() {
        parent::beforeFilter();

        if (isset($this->params['lang'])) {
            $this->i18n_component->setLanguage($this->params['lang']);
        }

        # set current user
        if ($current_user = $this->auth_component->current_user()) {
            $this->current_user = $current_user;
        }

    }

}

