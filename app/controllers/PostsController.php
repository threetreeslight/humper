<?php

/**
 * PostsController
 */
class PostsController extends ApplicationController {
    /**
     * need authenticate actions
     *
     * @var boolean|array
     */
    protected $auth_actions = array('');
    /**
     * need csrf token check actions
     *
     * @var boolean|array
     */
    protected $protect_from_forgery_actions = array('create', 'update', 'delete');


    /**
     * index
     */
    function index() {
        $posts = Post::findAll();

        /**
         * $this->render($variables, $template, $layout)
         *
         * render templates
         * - $template default value is action name
         * - $layout default value is 'layout'
         */
        return $this->render(array(
            'posts'   => $posts,
        ));
    }

    /**
     * show
     */
    function show() {
        /**
         * $this->params[$key]
         *
         * uri included parameters
         */
        $post = Post::find($this->params['id']);

        return $this->render(array(
            'post' => $post,
        ));
    }

    /**
     * add
     */
    function add() {
        return $this->render();
    }

    /**
     * edit
     */
    function edit() {
        $post = Post::find($this->params['id']);

        return $this->render(array(
            'post'   => $post,
        ));
    }

    /**
     * create
     */
    function create() {
        $post = new Post();

        /**
         * $this->request->getGet($key, default_value)
         * $this->request->getPost($key, default_value)
         *
         * get $_GET/$_POST parameter
         */
        $post->name = $this->request->getPost('name', null);
        $post->body = $this->request->getPost('body', null);

        if( $post->save() ) {
            /**
             * $this->redirect
             *
             * redirect
             */
            return $this->redirect('/posts/'.$post->id);
        } else {
            # get PDO exception
            return $this->redirect('/posts/'.$post->id);
        }

    }

    /**
     * update
     */
    function update() {
        $post = Post::find($this->params['id']);

        $post->name = $this->request->getPost('name', null);
        $post->body = $this->request->getPost('body', null);

        if( $post->save() ) {
            return $this->redirect('/posts/'.$post->id);
        } else {
            # get PDO exception
            return $this->redirect('/posts/'.$post->id);
        }
    }

    /**
     * delete
     */
    function delete() {
        $post = Post::find($this->params['id']);

        if( $post->destroy() ) {
            return $this->redirect('/posts');
        } else {
            # get PDO exception
            return $this->redirect('/posts');
        }
    }


}
