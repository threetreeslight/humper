<?php
 
/**
 * HumperView.
 */
class HumperView {
    protected $request;
    protected $i18n_component;
    protected $auth_component;
    protected $security_component;

    /**
     * application root dir
     *
     * @var string
     */
    protected $root_dir;

    /**
     * views root dir
     *
     * @var string
     */
    protected $base_dir;

    /**
     * controller parameters
     *
     * @var array
     */
    protected $defaults;

    /**
     * layout use variables
     *
     * @var array
     */
    protected $layout_variables = array();

    /**
     * Constructor
     *
     * @param string $base_dir
     * @param array $defaults
     */
    public function __construct($application, $defaults = array()) {
        $this->root_dir           = $application->getRootDir();
        $this->base_dir           = $application->getViewDir();
        $this->request            = $application->getRequest();
        $this->i18n_component     = $application->getI18nComponent();
        $this->security_component = $application->getSecurityComponent();
        $this->auth_component     = $application->getAuthComponent();
        $this->defaults           = $defaults;
    }

    /**
     * set layout value
     *
     * @param string $name
     * @param mixed $value
     */
    public function setLayoutVar($params = array()) {
        foreach( $params as $key => $value ){
            $this->layout_variables[$key] = $value;
        }
    }

    /**
     * rendering view files
     *
     * @param string $_path
     * @param array $_variables
     * @param mixed $_layout
     * @throws HttpNotFoundException
     * @return string
     */
    public function render($_path, $_variables = array(), $_layout = false) {
        list($_file, $extention) = $this->findTemplate($_path);

        if ($_file === false) {
            throw new HttpNotFoundException($_file. ' template is not found.');
        }

        $_variables['_token'] = $this->security_component->generateCsrfToken();

        $_method = $extention.'_render';
        $content = $this->$_method($_file, $_variables, $_layout);

        return $content;
    }

    /**
     * find template file
     *
     * @return mixed $_file and extention
     */
    protected function findTemplate($_path) {
        #TODO: revise refer configuration files to read template extentions.
        $extentions = array('php', 'haml');

        foreach($extentions as $extention) {
            $_file = $this->base_dir . '/' . $_path . '.'.$extention;
            if (is_readable($_file)) {
                return array($_file, $extention);
            }
        }
        return false;
    }

    /**
     * php template render
     *
     * @param string $_path
     * @param array $_variables
     * @param mixed $_layout
     * @throws HttpNotFoundException
     * @return string
     */
    protected function php_render($_file, $_variables = array(), $_layout = false) {
        extract(array_merge($this->defaults, $_variables));

        // render contents
        ob_start();
        ob_implicit_flush(0);

        require $_file;

        $content = ob_get_clean();

        // render_layouts
        if ($_layout) {
            $content = $this->render($_layout,
                array_merge($this->layout_variables, array(
                    '_content' => $content,
                )
            ));
        }
        return $content;
    }

    /**
     * haml template render
     *
     * @param string $_path
     * @param array $_variables
     * @param mixed $_layout
     * @throws HttpNotFoundException
     * @return string
     */
    protected function haml_render($_file, $_variables = array(), $_layout = false) {

        require_once('phphaml/includes/haml/HamlParser.class.php');

        extract(array_merge($this->defaults, $_variables));

        // render contents
        ob_start();
        ob_implicit_flush(0);

        # $parser = new HamlParser($this->base_dir.'/packages', $this->base_dir.'/packages');

        display_haml($_file, array(), $this->base_dir.'/packages');

        #TODO: requireのタイミングで必要な変数をロードできない。
        ## scopeに変数が収まっていないため。
        ## HamlParser->render()
        ## HamlParser->execute() # ここのスコープに収まっていない
        # echo $parser->setFile($_file);

        # require('/vagrant/share/tenso_develop/lib/views/packages/79f799eb567c5aefe1c4dc0ded384259.hphp');

        $content = ob_get_clean();
        var_dump($content);

        return $content;

    }


    /**
     * html escape string
     *
     * @param string $string
     * @return string
     */
    public function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * translation $key
     *
     * @param string $string
     * @return string
     */
    public function t($key) {
        return $this->escape($this->i18n_component->t($key));
    }

}
