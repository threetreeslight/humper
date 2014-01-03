<?php

require dirname(__FILE__).'/lib/humper/utility/HumperClassLoader.php';

$loader = new HumperClassLoader();

$dirs = array('utility',
              'error',
              'core',
              'session',
              'network',
              'routing',
              'model',
              'controller',
              'view' );

foreach( $dirs as $dir){
    $loader->registerDir(dirname(__FILE__).'/lib/humper/'.$dir);
}

$loader->registerDir(dirname(__FILE__).'/app/models');
$loader->register();

