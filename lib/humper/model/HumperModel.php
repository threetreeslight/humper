<?php

/**
 * Humper Model
 *
 */
abstract class HumperModel {

    /**
     * model class name
     *
     * @var String
     */
    # TODO:if you update php 5.3 over, this var remove. and use Late Static Bindings
    protected $model_name;

    /**
     * Database Connection
     *
     * @var object PDO
     */
    protected $connection;

    /**
     * Database Schema Name
     *
     * @var String
     */
    protected $schema_name;

    /**
     * Table Name
     *
     * When table name is not same class name + 's', Use this params.
     *
     * @var String
     */
    protected $table_name;

    /**
     * model class name
     *
     * @var String
     */
    protected $attributes = array();

    /**
     * logger
     *
     * @var object Log
     */
    private $logger;

    /**
     * constractor
     */
    public function __construct() {
        $this->setConnection();
        $this->setLogger();
    }

    /**
     * set logger
     *
     */
    private function setLogger(){
        $this->logger = HumperDbConfig::getLogger();
    }

    /**
     * find All (select All)
     *
     * @param integer $id
     * @retrun obj
     */
    public function insFindAll() {
        $sql = "SELECT * FROM ".$this->getTableName();
        return $this->fetchAll($sql);
    }

    /**
     * find (select query 1 record) on instance method
     *
     * @param array $args
     * @param array $pluck
     * @retrun obj
     */
    public function insFindBy($args, $select = array()) {
        $sql = $this->generateSelectPhrase($select);
        $sql .= $this->generateWherePhrase($args);

        return $this->fetch($sql, $args);
    }
 
    /**
     * where (select query) on instance method
     *
     * @param array|string $args
     * @param array $values
     * @retrun array|obj
     */
    public function insWhere($args, $values = array(), $pluck = array()) {
        if(is_array($args)) {
            $values = $args;
        }
        $sql = $this->generateSelectPhrase($pluck);
        $sql .= $this->generateWherePhrase($args);

        return $this->fetchAll($sql, $values);
    }

    /**
     * generate select path frase from $args
     *
     * @param array $pluck
     * @retrun string $sql
     */
    protected function generateSelectPhrase($select) {
        if(count($select) > 0) {
            $select = implode(',', $select);
            $sql = "SELECT ".$select." FROM ".$this->getTableName();
        } else {
            $sql = "SELECT * FROM ".$this->getTableName();
        }
        return $sql;
    }

    /**
     * generate where path frase from $args
     *
     * @param array|string $args
     * @param array $values
     * @retrun string $sql
     */
    protected function generateWherePhrase($args) {
        if(is_array($args)) {
            # generate sql from array
            foreach($args as $key => $val) {
               $query[] = trim($key,':').' = '.$key;
            }
            $query= implode(' AND ', $query);
            $sql = " WHERE ".$query;
        } elseif(is_string($args)) {
            # generate sql form string
            $sql = " WHERE ".$args;
        }

        return $sql;
    }

    /**
     * get logger
     *
     * @retrun object Log
     */
    public function getlogger(){
        return $this->logger;
    }

    /**
     * hundle create or update
     */
    public function save(){
        try {
            if(isset($this->id)){
                $this->update();
            }else{
                $this->create();
            }
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * create record
     */
    protected function create() {
        foreach( $this->attributes as $attr ) {
            $params[':'.$attr] = $this->$attr;
        }
        $sql =  "INSERT INTO "
                .$this->getTableName()
                ."(".implode(',', $this->attributes).")"
                ." VALUES(".implode(',', array_keys($params)).")";
        $this->execute($sql, $params);
        $this->id = $this->connection->lastInsertId();
    }

    /**
     * update record
     */
    protected function update(){
        foreach( $this->attributes as $attr ) {
            $keys[] = $attr.' = :'.$attr;
            $params[':'.$attr] = $this->$attr;
        }
        $sql = "UPDATE "
               .$this->getTableName()
               ." SET ".implode(',', $keys)
               ." WHERE id = :id";

        $params[':id'] = $this->id;
        return $this->execute($sql, $params);
    }

    /**
     * destroy record
     */
    public function destroy(){
        try {
            $sql = "DELETE FROM "
                   .$this->getTableName()
                   ." WHERE id = :id";

            $this->execute($sql, array( ':id' => $this->id ));
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * get table name
     *
     * @return string
     */
    protected function getTableName(){
        return is_null($this->table_name) ? strtolower($this->model_name.'s') : $this->table_name;
    }

    /**
     * set objcet's schema_names connection
     */
    protected function setConnection() {
        $this->connection = HumperDbConfig::getConnection($this->schema_name);
    }

    /**
     * execute query
     *
     * @param string $sql
     * @param array $params
     * @return PDOStatement $stmt
     */
    protected function execute($sql, $params = array()) {

        if ( !is_null($this->logger) ) {
            $this->logger->log($sql);
            if ( $params ) $this->logger->log($params);
        }

        $stmt = $this->connection->prepare($sql);
        $stmt = $this->setFetchMode($stmt);
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * set fetch return value as object
     *
     * @param object $stmt
     * @return object $stmt
     */
    protected function setFetchMode($stmt) {
        $stmt->setFetchMode(PDO::FETCH_CLASS, ucfirst($this->model_name));
        return $stmt;
    }

    /**
     * query execute and fetch 1 record
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    protected function fetch($sql, $params = array()) {
        return $this->execute($sql, $params)->fetch();
    }

    /**
     * query execute and fetch all record
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    protected function fetchAll($sql, $params = array()) {
        # return $this->execute($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
        return $this->execute($sql, $params)->fetchAll(PDO::FETCH_CLASS, ucfirst($this->model_name));
    }

}
