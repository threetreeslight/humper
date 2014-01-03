<?php

/**
 * BlogApplication
 */
class BlogApplication extends HumperApplication {

    public function getRootDir() {
        # application root directory
        return dirname(__FILE__).'/app';
    }
    public function getLibraryDir() {
        # application Library directory
        return dirname(__FILE__).'/lib';
    }
    public function getConfigDir() {
        # application root directory
        return dirname(__FILE__).'/config';
    }

    protected function registerRoutes() {
        # define routing
        #
        # 'http_method;path'
        #       => array('controller' => 'controller_name', 'action' => 'method_name'),
        #
        # e.g.
        # show user list:
        #   'GET;/users' => array('controller' => 'users', 'action' => 'index'),
        #
        return array(
            # root path
            # 'GET;/'
            #     => array('controller' => 'posts', 'action' => 'index'),

            'GET;/'
                => array('controller' => 'posts', 'action' => 'index'),
            'GET;/posts'
                => array('controller' => 'posts', 'action' => 'index'),
            'GET;/posts/add'
                => array('controller' => 'posts', 'action' => 'add'),
            'GET;/posts/:id'
                => array('controller' => 'posts', 'action' => 'show'),
            'GET;/posts/:id/edit'
                => array('controller' => 'posts', 'action' => 'edit'),
            'POST;/posts/:id'
                => array('controller' => 'posts', 'action' => 'create'),
            'PUT;/posts/:id'
                => array('controller' => 'posts', 'action' => 'update'),
            'DELETE;/posts/:id'
                => array('controller' => 'posts', 'action' => 'delete'),

            # with language path
            'GET;/:lang/posts'
                => array('controller' => 'posts', 'action' => 'index'),
            'GET;/:lang/posts/add'
                => array('controller' => 'posts', 'action' => 'add'),
            'GET;/:lang/posts/:id'
                => array('controller' => 'posts', 'action' => 'show'),
            'GET;/:lang/posts/:id/edit'
                => array('controller' => 'posts', 'action' => 'edit'),
            'POST;/:lang/posts/:id'
                => array('controller' => 'posts', 'action' => 'create'),
            'PUT;/:lang/posts/:id'
                => array('controller' => 'posts', 'action' => 'update'),
            'DELETE;/:lang/posts/:id'
                => array('controller' => 'posts', 'action' => 'delete'),

        );
    }

    protected function configure() {

        # security component
        #
        # use for csrf generating key
        $this->security_component->setSecretKeyBase('d803d80e195dc30189203eeb0d0129b4f34ab9d26cc5014ddf752b9bc6274fb3675fec242b1b55052136ec56a9ee14dba288afe3cb8a39316828706664a6e1df');

        # i18n component
        #
        # set default language
        $this->i18n_component->setDefaultLanguage('en');

        # auth component setting
        #
        # redirect : 'url'
        # action   : ('controller_name', 'action_name')
        #
        $this->auth_component->setRootDir('/');

        # orm initialize
        #
        # logging
        #   ON : setLoggingMode(true, dirname(__FILE__).'/log');
        #   OFF: setLoggingMode(false);
        HumperDbConfig::setLoggingMode(false);
        HumperDbConfig::initialize(true);
        HumperDbConfig::setConnection(
            # database name
            'blog',
            array(
                'dsn'      => 'mysql:dbname=blog;host=localhost;unix_socket=/opt/boxen/data/mysql/socket',
                'user'     => 'root',
                'password' => 'root',
            )
        );

    }
}

