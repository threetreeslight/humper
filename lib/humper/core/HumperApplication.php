<?php

/**
 * Application.
 */
abstract class HumperApplication {

    protected $debug = false;
    protected $request;
    protected $response;
    protected $session;
    protected $db_manager;
    protected $auth_component;
    protected $security_component;
    protected $i18n_component;
    protected $helper_component;

    /**
     * constractor
     *
     * @param boolean $debug
     */
    public function __construct($debug = false) {
        $this->setDebugMode($debug);
        $this->initialize();
        $this->configure();
    }

    /**
     * set debug mode
     *
     * @param boolean $debug
     */
    protected function setDebugMode($debug) {
        if ($debug) {
            $this->debug = true;
            ini_set('display_errors', 1);
            error_reporting(-1);
        } else {
            $this->debug = false;
            ini_set('display_errors', 0);
        }
    }

    /**
     * initialize application
     */
    protected function initialize() {
        $this->request            = new HumperRequest();
        $this->response           = new HumperResponse();
        $this->session            = new HumperSession();
        $this->security_component = new HumperSecurityComponent($this);
        $this->auth_component     = new HumperAuthComponent($this);
        $this->i18n_component     = new HumperI18nComponent($this);
        $this->helper_component   = new HumperHelperComponent($this);
        # $this->db_manager = new HumperDbManager();
        $this->router             = new HumperRouter($this->registerRoutes());
    }

    /**
     * set application configuration. ( e.g. db, log )
     */
    protected function configure() {
    }

    /**
     * get project root directory
     *
     * @return string absolute_path (e.g. '/var/www/app' )
     */
    abstract public function getRootDir();

    /**
     * Registration Routings
     *
     * @return array
     */
    abstract protected function registerRoutes();

    /**
     * check Mode of Debug
     *
     * @return boolean
     */
    public function isDebugMode() {
        return $this->debug;
    }

    /**
     * get HumperReqest Object
     *
     * @return HumperRequest
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * get HumperResponse object
     *
     * @return HumperResponse
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * get Humper Session object
     *
     * @return HumperSession
     */
    public function getSession() {
        return $this->session;
    }

    /**
     * get Humper security Component Object
     *
     * @return HumperRequest
     */
    public function getSecurityComponent() {
        return $this->security_component;
    }

    /**
     * get Humper Auth Component Object
     *
     * @return HumperAuthComponent
     */
    public function getAuthComponent() {
        return $this->auth_component;
    }

    /**
     * get Humper I18n Component Object
     *
     * @return HumperI18nComponent
     */
    public function getI18nComponent() {
        return $this->i18n_component;
    }

    /**
     * get Humper helper Component Object
     *
     * @return HumperHelperComponent
     */
    public function getHelperComponent() {
        return $this->helper_component;
    }

    # /**
    #  * DbManagerオブジェクトを取得
    #  *
    #  * @return DbManager
    #  */
    # public function getDbManager() {
    #     return $this->db_manager;
    # }

    /**
     * get Controllers Directory
     *
     * @return string
     */
    public function getControllerDir() {
        return $this->getRootDir() . '/controllers';
    }

    /**
     * get Controllers Directory
     *
     * @return string
     */
    public function getHelperDir() {
        return $this->getRootDir() . '/helpers';
    }

    /**
     * get config Directory
     *
     * @return string
     */
    public function getConfigDir() {
        return $this->getRootDir() . '/config';
    }

    /**
     * get Views directory path
     *
     * @return string
     */
    public function getViewDir() {
        return $this->getRootDir() . '/views';
    }

    /**
     * get Models Directry
     *
     * @return stirng
     */
    public function getModelDir() {
        return $this->getRootDir() . '/models';
    }

    # /**
    #  * ドキュメントルートへのパスを取得
    #  *
    #  * @return string
    #  */
    # public function getWebDir() {
    #     return $this->getRootDir() . '/web';
    # }

    /**
     * execute application
     *
     * @throws HttpNotFoundException when not found routes
     */
    public function run() {
        try {
            $params = $this->router->resolve($this->request->getPathInfo(), $this->request->method);
            if ($params === false) {
                throw new HttpNotFoundException('No route found for ' . $this->request->getPathInfo());
            }
            $controller = $params['controller'];
            $action = $params['action'];

            $this->runAction($controller, $action, $params);
        } catch (HttpNotFoundException $e) {
            $this->render404Page($e);
        } catch (UnauthorizedActionException $e) {
            # catch on basic, digest auth Exception
        }
        $this->response->send();
    }

    /**
     * execute action
     *
     * @param string $controller_name
     * @param string $action
     * @param array $params
     *
     * @throws HttpNotFoundException when not found Action
     */
    public function runAction($controller_name, $action, $params = array()) {
        $controller_class = ucfirst($controller_name) . 'Controller';

        $controller = $this->findController($controller_class);
        if ($controller === false) {
            throw new HttpNotFoundException($controller_class . ' controller is not found.');
        }

        $content = $controller->run($action, $params);

        $this->response->setContent($content);
    }

    /**
     * find and return controller object by controller name
     *
     * @param string $controller_class
     * @return Controller
     */
    protected function findController($controller_class) {
        if (!class_exists($controller_class)) {
            $controller_file = $this->getControllerDir() . '/' . $controller_class . '.php';
            if (!is_readable($controller_file)) {
                return false;
            } else {
                require_once $this->getControllerDir() . '/ApplicationController.php';
                require_once $controller_file;

                if (!class_exists($controller_class)) {
                    return false;
                }
            }
        }

        return new $controller_class($this);
    }

    /**
     * response 404 error setting
     *
     * @param Exception $e
     */
    protected function render404Page($e) {
        $this->response->setStatusCode(404, 'Not Found');
        $message = $this->isDebugMode() ? $e->getMessage() : 'Page not found.';
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        $this->response->setContent(<<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>404</title>
</head>
<body>
    {$message}
</body>
</html>
EOF
        );
    }
}
