<?php

/**
 * HumperClassLoader
 *
 */
class HumperClassLoader {
    protected $dirs;

    /**
     * regsiter own class to autoload stack
     */
    public function register() {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * regist auto load target dir
     *
     * @param string $dir
     */
    public function registerDir($dir) {
        $this->dirs[] = $dir;
    }

    /**
     * require classes
     *
     * @param string $class
     */
    public function loadClass($class) {
        foreach ($this->dirs as $dir) {
            $file = $dir . '/' . $class . '.php';
            if (is_readable($file)) {
                require $file;
                return;
            }
        }
    }
}
