# Humper

Light weight and Easy Use PHP MVC framework

we support

* REST API
* ActiveRecord like ORM
* Protect from forgery
* Auth
* I18n
* helper
* Pure PHP Template engine

require

* php > 5.1
* ruby > 1.9 ( for sass, coffeescript)

## contents

* Getting Started
* Model
* Routing
* Controller
  * Protect from forgery
  * Auth
* View
  * Pure PHP Template Engine
  * Helper
  * I18n
* Mail

## Getting Started

create blog application

## Database Migration

create 'blog' Database.

create 'posts' table.

column name | type |
--- | --- | ---
id | integer | auto increment
name | string |
body | text |

## Model

Humper Model is ActiveRecord like object-relational-mapper(ORM).

create post model.

```php
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
```

if your created table name is not based on ActiveRecord name rule.

(e.g. PostModel has Posts table)

set $table_name

```php
class Post extends HumperModel {
    protected $table_name  = 'country';

    ...

}
```

#### usage

select a record by id

```php
$post = Post::find('1');
```

select all record

```php
$post = Post::findAll();
```

select a record by name

```php
$post = Post::findBy(array('name' => 'foo'));

# with columns
$post = Post::findBy(array('name' => 'foo'), array('id, name as title'));
```

select any record by name

```php
# by array
$posts = Post::where(array('name' => 'foo'));

# by array with columns
$posts = Post::where(array('name' => 'foo'), array(), array('id, name as title'));

# by string array
$posts = Post::where('name like ?', array('%foo%'));

# with select column
$posts = Post::where('name like ?', array('%foo%'), array('id, name as title'));
```

insert

```php
$post = new Post;
$post->name = 'foo';
$post->body = 'bar';
$post->save();
```

update

```php
$post = Post::findBy(array(':name' => 'foo'));
$post->body = 'barbar';
$post->save();
```

delete

```php
$post = Post::findBy(array(':name' => 'foo'));
$post->destroy();
```

## Routing

we support REST API.

* HTTP Get, Post, Put , Delete method
* Unique URL Routing

```php
class BlogApplication extends HumperApplication {

    ...

    protected function registerRoutes() {
        return array(

            # show post list
            'GET;/posts'
                => array('controller' => 'posts', 'action' => 'index'),

            # show add post form
            'GET;/posts/add'
                => array('controller' => 'posts', 'action' => 'add'),

            # show post target id
            'GET;/posts/:id'
                => array('controller' => 'posts', 'action' => 'show'),

            # edit already post form
            'GET;/posts/:id/edit'
                => array('controller' => 'posts', 'action' => 'edit'),

            # create new post via 'GET;/posts/add'
            'POST;/posts/:id'
                => array('controller' => 'posts', 'action' => 'create'),

            # update post via 'GET;/posts/:id/edit'
            'PUT;/posts/:id'
                => array('controller' => 'posts', 'action' => 'update'),

            # delete post
            'DELETE;/posts/:id'
                => array('controller' => 'posts', 'action' => 'delete'),
        );
    }

    ...

}
```

## Controller

create post controller in 'controllers' directory.

```php
class PostsController extends ApplicationController {
    protected $auth_actions = array('');
    protected $protect_from_forgery_actions = array('');

    /**
     * index
     */
    function index() {
        $posts = Post::findAll();

        return $this->render(array(
            'posts'   => $posts,
        ));
    }

    /**
     * show
     */
    function show() {
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
        $post->name = $this->request->getPost('name', null);
        $post->post = $this->request->getPost('body', null);

        if( $post->create() ) {
            return $this->redirect('/posts/'.$post->id);
        } else {
            return $this->redirect('/posts/'.$post->id);
        }
    }

    /**
     * update
     */
    function update() {
        $post = Post::find($this->params['id']);

        $post->member_id = $this->request->getPost('member_id', null);
        $post->post     = $this->request->getPost('post', null);

        if( $post->update() ) {
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
```

and you can set beforeFilter/afterFilter.

these method call target method before/after.

```php
class PostsController extends ApplicationController {

    function beforeFilter() {
        parent::beforeFilter();

    }

    function afterFilter() {
        parent::beforeFilter();

    }

}

```

#### usage

get url parameter

```php
# access via /posts/5 as /posts/:id
$this->params['id']; // = 5
```

get $_GET, $_POST, $_SERVER paramter

```php
# $_GET
$this->request->getGet('key', default);

# $_POST
$this->request->getPost('key', default);

# $_SERVER
$this->request->getServer('key', default);
```

render template

```php
# default
# * temaplte_name: method name
# * layout_name: layout
$this->render(variables_array, template_name, layout_name);
```

redirect

```php
$this->redirect('url');
```

## protect from forgery

set key for generate CSRF token

```php
class BlogApplication extends HumperApplication {
    protected function configure() {
        ...
        $this->security_component->setSecretKeyBase('xxxxxxxxxxxxxxxxxxxx....');
        ...
    }
}
```

set action to controller's $protect_from_forgery_actions

```php
// set selected action
class PostsController extends ApplicationController {
    protected $protect_from_forgery_actions = array('create', 'delete', 'update');
}

// if you want set all action
class PostsController extends ApplicationController {
    protected $protect_from_forgery_actions = true;
}
```

## Auth

set auth back

```php
class BlogApplication extends HumperApplication {
    protected function configure() {
        ...
        $this->auth_component->setRootDir('/');
        ...
    }
}
```

set action to controller's $protect_from_forgery_actions

```php
// set selected action
class PostsController extends ApplicationController {
    protected $auth_actions = array('add', 'edit', 'create', 'delete', 'update');
}

// if you want set all action
class PostsController extends ApplicationController {
    protected $protect_from_forgery_actions = true;
}
```

#### usage

```php
# authed flag
$this->setAuthenticated(true);

# get auth
$this->isAuthenticated();
```

## I18n

if you want i18n and translate by your dictionary

set default language

```php
class BlogApplication extends HumperApplication {
    protected function configure() {
        ...
        $this->i18n_component->setDefaultLanguage('ja');
        ...
    }
}
```

create disctionay file `config/locale/ja.yml`

```php
ja:
  models:
    post: ポスト
    posts:
      name: 名前
      body: 内容
  add: 追加
  show: 表示
  update: 更新
  edit: 編集
  delete: 削除
```

change language use before filter

```php
class ApplicationController extends HumperController {
    function beforeFilter() {
        parent::beforeFilter();

        if (isset($this->params['lang'])) {
            $this->i18n_component->setLanguage($this->params['lang']);
        }
    }
}
```

## view helper

view helper method use in templates. and you can difine any helpers

e.g.

/helpers/posts_helper.php

#### default helper

link/form generate

```php
# get post link
link_to($post->name, '/posts/'$post->id)

# post delete link
link_to($this->t('delete'), '/posts/'.$post->id, 'delete', $_token)
```

html escape

```php
h() or $this->h()
```

translate (i18n)

```php
$this->t($key);
```

## Template

#### layout

default layout is views/layouts/layout.php

```php
<html>
   <head>
        <title><?php if (isset($title)): echo h($title) . ' - '; endif; ?>Blog</title>
    </head>
    <body>
        <?php echo $_content; ?>
    </body>
</html>
```

`$_content` is any template generated html include.

#### list

views/posts/index.php

```php
<?php $this->setLayoutVar(array('title', $this->t('models.post'))) ?>

<h1>posts</h1>

<table>
    <tr>
        <th>id</th>
        <th><?= $this->t('id') ?></th>
        <th><?= $this->t('models.posts.name') ?></th>
        <th><?= $this->t('models.posts.body') ?></th>
        <th></th>
        <th></th>
        <th></th>
    </tr>
    <?php foreach( $posts as $key => $post ) { ?>
    <tr>
        <td><?= h($post->id) ?></td>
        <td><?= h($post->name) ?></td>
        <td><?= h($post->body) ?></td>
        <td><?= link_to($this->t('show'),   '/posts/$post->id') ?></td>
        <td><?= link_to($this->t('edit'),   '/posts/$post->id/edit') ?></td>
        <td><?= link_to($this->t('delete'), '/posts/$post->id',
                                            array( 'method' => 'delete', 'token' => $_token)) ?></td>
    </tr>
    <?php } ?>
</table>

<?= link_to($this->t('add'), '/posts/add') ?>
```

#### edit

```php
<?php $this->setLayoutVar(array('title' => $this->t('edit'))) ?>

<h1>posts/edit</h1>

<form action='/posts/<?= $post->id ?>' method='post'>
    <input type='hidden' name='_token' value="<?= $_token ?>">
    <input type='hidden' name='_method' value="put">
    <?= $this->t('models.posts.name') ?>:
    <input type='text' name='name' value="<?= $post->name ?>">
    <?= $this->t('models.posts.body') ?>:
    <input type='text' name='body' value="<?= $post->body ?>">
    <input type='submit' value='<?= $this->t('update') ?> '>
</form>

<?= link_to($this->t('back'), '/posts') ?>
```

#### usage

set layout use variables

```php
$this->setLayoutVar(array('title' => 'list'))
```

csrf token

```php
$_token
```


# TODO

* implement Haml Tempalte Engine
* PHPUnit of controller

