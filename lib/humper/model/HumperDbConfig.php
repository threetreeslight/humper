<?php

/**
 * Manages Configuration option for HumperDb
 *
 * <code>
 * require(PATHL.'simple_orm/bootstrap.php');
 * HumperDbConfig::initialize(true);
 * HumperDbConfig::setConnection(
 *     'test',
 *     array(
 *         'dsn'      => 'mysql:dbname=test;host=localhost;unix_socket=/tmp/mysql.sock',
 *         'user'     => 'root',
 *         'password' => 'root',
 *     )
 * );
 * </code>
 */
class HumperDbConfig {
    /**
     * Debug mode
     *
     * @var boolean
     */
    private static $debug = false;

    /**
     * Database Manager
     *
     * @var object DbManager
     */
    private static $humper_db_manager;

    /**
     * switch for logging
     *
     * @var object DbManager
     */
    private static $logging;

   /**
    * Contains a Logger object that must impelement a log() method.
    *
    * @var object
    */
    private static $logger;

    /**
     * initialize HumperDb
     *
     * @param boolean $debug
     * @return void
     */
    public static function initialize($debug = false){
        # self::setDebugMode($debug);
        self::$humper_db_manager = new HumperDbManager();
    }

    /**
     * set debug mode
     *
     * @param boolean $debug
     * @return void
     */
    protected static function setDebugMode($debug) {
        if ($debug) {
            self::$debug = true;
            ini_set('display_errors', 1);
            error_reporting(-1);
        } else {
            self::$debug = false;
            ini_set('display_errors', 0);
        }
    }

    /**
     * set logging mode
     *
     * @param boolean $logging
     * @return void
     */
    public static function setLoggingMode($logging, $log_dir = null) {
        if ($logging) {
            self::$logging = true;
            self::setLogger($log_dir);
        } else {
            self::$logging = false;
        }
    }

    /**
     * set logging mode
     *
     * @param boolean $logging
     * @return void
     */
    public static function getLoggingMode() {
        return self::$logging;
    }

    /**
     * set logger
     *
     * @param string $log_dir
     */
    private static function setLogger($log_dir = null){
        if (is_null($log_dir)) {
            $log_dir = '.';
        }

        self::$logger = &Log::singleton(
            'file',
            $log_dir.'/'.date("Ymd").'_simple_orm_query.log',
            'ident',
            array('mode' => 0664, 'timeFormat' =>  '%Y-%m-%d %H:%M:%S'),
            PEAR_LOG_DEBUG
        );
    }

    /**
     * get logger
     *
     * @return object Log
     */
    public static function getLogger(){
        return self::$logger;
    }

    /**
     * add db connection
     *
     * @param boolean $debug
     * @return void
     */
    public static function setConnection($name, $params){
        self::$humper_db_manager->connect($name, $params);
    }

    /**
     * get db connection
     *
     * @string $name
     * @return PDO
     */
    public static function getConnection($name){
        return self::$humper_db_manager->getConnection($name);
    }
}
