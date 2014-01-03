<?php

/**
 * HumperController
 */
abstract class HumperController {
    protected $controller_name;
    protected $action_name;
    protected $application;
    protected $request;
    protected $response;
    protected $session;

    /**
     * Humper Helper Component
     *
     * @var object
     */
    protected $helper_component;

    /**
     * Humper i18n Component
     *
     * @var object
     */
    protected $i18n_component;

    /**
     * Humper Auth Component
     *
     * @var object
     */
    protected $auth_component;

    /**
     * Humper Security Component
     *
     * @var object
     */
    protected $security_component;

    /**
     * protect from forgery actions (csrf tokens)
     *
     * ture : always protect from forgery
     * array() : selected action protect from forgery
     *
     * @var array
     */
    protected $protect_from_forgery_actions = array();

    /**
     * authenticated user action
     *
     * @var array
     */
    protected $auth_actions = array();

    /**
     * uri included parameters
     *
     * @var String
     */
    protected $params = array();

    /**
     * layout template file name
     *
     * @var String
     */
    protected $layout = 'layout';

    /**
     * constructor
     *
     * @param Application $application
     */
    public function __construct($application) {
        $this->controller_name = strtolower(substr(get_class($this), 0, -10));

        $this->application        = $application;
        $this->request            = $application->getRequest();
        $this->response           = $application->getResponse();
        $this->session            = $application->getSession();
        $this->security_component = $application->getSecurityComponent();
        $this->auth_component     = $application->getAuthComponent();
        $this->i18n_component     = $application->getI18nComponent();
        $this->helper_component   = $application->getHelperComponent();
        # $this->db_manager  = $application->getDbManager();
    }

    /**
     * run action
     *
     * @param string $action
     * @param array $params
     * @return string response contents
     *
     * @throws UnauthorizedActionException 認証が必須なアクションに認証前にアクセスした場合
     */
    public function run($action, $params = array()) {
        try {
            $this->action_name = $action;
            $this->params = $params;

            if (!method_exists($this, $action)) {
                $this->forward404();
            }

            $this->beforeFilter();

            $content = $this->$action($params);

            $this->afterFilter();

            return $content;

        # } catch (HttpNotFoundException $e) {

        } catch (UnauthorizedActionException $e) {

            $root = $this->auth_component->getRootDir();

            if (is_array($root)) {
                list($controller, $action) = $root;
                # run action
                $this->runAction($controller, $action);

            } else {
                # redirect
                $this->redirect($root);
            }

        }
    }

    /**
     * filtering before run action
     *
     * @throws UnauthorizedActionException  when access csrf need action, csrf token cant valified
     * @throws HttpNotFoundException        unauthoraized user access need authorized action.
     */
    protected function beforeFilter() {
        if ($this->needsAuthentication($this->action_name) && !$this->auth_component->isAuthenticated()) {
            throw new UnauthorizedActionException();
        }
        if ($this->needsProtectFromForgery($this->action_name) && !$this->security_component->checkCsrfToken()) {
            throw new HttpNotFoundException("Can't verify CSRF token authenticity");
        }
    }

    /**
     * filtering after run action
     *
     */
    protected function afterFilter() {
    }

    /**
     * rendering view
     *
     * @param array $variables  template use variables(key value store)
     * @param string $template view file name (if null, use action name)
     * @param string $layout layout file name
     * @return string renderinged view file contents
     */
    protected function render($variables = array(), $template = null, $layout = null) {
        $defaults = array(
            'request'  => $this->request,
            'base_url' => $this->request->getBaseUrl(),
            'session'  => $this->session,
        );

        $view = new HumperView($this->application, $defaults);

        if (is_null($template)) {
            $template = $this->action_name;
        }

        $path = $this->controller_name . '/' .$template;
        $layout = 'layouts/'.(!!$layout ? $layout : $this->layout);

        # load view helper functions
        $this->helper_component->loadHelper($this->controller_name);

        return $view->render($path, $variables, $layout);
    }

    /**
     * forward 404 error views
     *
     * @throws HttpNotFoundException
     */
    protected function forward404() {
        throw new HttpNotFoundException('Forwarded 404 page from '
            . $this->controller_name . '/' . $this->action_name);
    }

    /**
     * redirect
     *
     * @param string $url
     */
    protected function redirect($url) {
        if (!preg_match('#https?://#', $url)) {
            $protocol = $this->request->isSsl() ? 'https://' : 'http://';
            $host = $this->request->getHost();
            $base_url = $this->request->getBaseUrl();

            $url = $protocol . $host . $base_url . $url;
        }

        $this->response->setStatusCode(302, 'Found');
        $this->response->setHttpHeader('Location', $url);
    }

    /**
     * check that target action need csrf valid
     *
     * @param string $action
     * @return boolean
     */
    protected function needsProtectFromForgery($action) {
        if ($this->protect_from_forgery_actions === true
            || (is_array($this->protect_from_forgery_actions) && in_array($action, $this->protect_from_forgery_actions))
        ) {
            return true;
        }

        return false;
    }

    /**
     * 指定されたアクションが認証済みでないとアクセスできないか判定
     *
     * @param string $action
     * @return boolean
     */
    protected function needsAuthentication($action) {
        if ($this->auth_actions === true
            || (is_array($this->auth_actions) && in_array($action, $this->auth_actions))
        ) {
            return true;
        }

        return false;
    }

}
