<?php

/**
 * HumperRouter.
 */
class HumperRouter {
    protected $routes;

    /**
     * constractor
     *
     * @param array $definitions
     */
    public function __construct($definitions) {
        $this->routes = $this->compileRoutes($definitions);
    }

    /**
     * convert routing array to inner usefull
     *
     * @param array $definitions
     * @return array
     */
    public function compileRoutes($definitions) {
        $routes = array();

        foreach ($definitions as $url => $params) {
            $tokens = explode('/', ltrim($url, '/'));
            foreach ($tokens as $i => $token) {
                if (0 === strpos($token, ':')) {
                    $name = substr($token, 1);
                    $token = '(?P<' . $name . '>[^/]+)';
                }
                $tokens[$i] = $token;
            }

            # $pattern = '/'.implode('/', $tokens);
            $pattern = implode('/', $tokens);
            $routes[$pattern] = $params;
        }
        return $routes;
    }

    /**
     * find routing params based on PATH_INFO
     *
     * @param string $path_info
     * @return array|false
     */
    public function resolve($path_info, $method_type) {
        # trim end of '/'
        if ('/' === substr($path_info, -1, 1)) {
            $path_info = substr($path_info, 0, -1);
        }
        # generate 'method;path'
        $path_info = $method_type.';'.$path_info;

        foreach ($this->routes as $pattern => $params) {
            if (preg_match('#^' . $pattern . '$#', $path_info, $matches)) {
                $params = array_merge($params, $matches);
                return $params;
            }
        }

        return false;
    }
}
