<?php

/**
 * Post model
 */
class Post extends HumperModel {
    protected $model_name  = 'Post';
    protected $schema_name = 'blog';
    protected $attributes = array(
                                    'name',
                                    'body',
                                 );


    /**
     * always add these method
     *
     * - find: record find by id
     * - findAll: select all record
     * - findAll: select record query with where with limit = 1
     * - where: select record query with where
     */
    public static function where($args, $values = array(), $select = array()) {
        $obj = new self; return $obj->insWhere($args, $values, $select);
    }
    public static function findBy($args, $select = array()){
        $obj = new self; return $obj->insFindBy($args, $select);
    }
    public static function find($id){
        $obj = new self; return $obj->insFindBy(array(':id' => $id));
    }
    public static function findAll(){
        $obj = new self; return $obj->insFindAll();
    }
}
