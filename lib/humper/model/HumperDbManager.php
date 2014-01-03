<?php

/**
 * Manages DB connections
 *
 */
class HumperDbManager {
    protected $connections = array();
    protected $repository_connection_map = array();
    protected $repositories = array();

    /**
     * connection Database
     *
     * @param string $name
     * @param array $params
     */
    public function connect($name, $params) {
        $params = array_merge(array(
            'dsn'      => null,
            'user'     => '',
            'password' => '',
            'options'  => array(),
        ), $params);

        $con = new PDO(
            $params['dsn'],
            $params['user'],
            $params['password'],
            $params['options']
        );

        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        # TODO: ATTR_DEFAULT_FETCH_MODE is > php 5.2
        # $con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->connections[$name] = $con;
    }

    /**
     * get connected PDO instance
     *
     * @string $name
     * @return PDO
     */
    public function getConnection($name = null) {
        if (is_null($name)) {
            return current($this->connections);
        }

        return $this->connections[$name];
    }

    /**
     * set db connection setting
     *
     * @param string $repository_name
     * @param string $name
     */
    public function setRepositoryConnectionMap($repository_name, $name) {
        $this->repository_connection_map[$repository_name] = $name;
    }

    /**
     * get repository's connection setting
     *
     * @param string $repository_name
     * @return PDO
     */
    public function getConnectionForRepository($repository_name) {
        if (isset($this->repository_connection_map[$repository_name])) {
            $name = $this->repository_connection_map[$repository_name];
            $con = $this->getConnection($name);
        } else {
            $con = $this->getConnection();
        }

        return $con;
    }

    /**
     * destroy repository connection
     */
    public function __destruct() {
        foreach ($this->repositories as $repository) {
            unset($repository);
        }

        foreach ($this->connections as $con) {
            unset($con);
        }
    }
}
