<?php
/**
 * $this->setLayoutVar($key, $value)
 *
 * set layout use variables.
 **/
?>
<?php $this->setLayoutVar(array('title' => $this->t('models.post') )) ?>

<h1>posts</h1>

<table>
    <tr>
        <th>id</th>

        <?php
        /**
         * $this->t($string)
         *
         * translation function. need deictionary 'config/lang_name.yml'.
         **/
        ?>
        <th><?= $this->t('models.posts.name') ?></th>
        <th><?= $this->t('models.posts.body') ?></th>
        <th></th>
        <th></th>
        <th></th>
    </tr>
    <?php foreach( $posts as $key => $post ) { ?>
    <tr>
            <?php
            /**
             * h($string)
             *
             * html escape function
             **/
            ?>
        <td><?= h($post->id) ?></td>
        <td><?= h($post->name) ?></td>
        <td><?= h($post->body) ?></td>
        <td>

            <?php
            /**
             * link_to($name, $path, $method, $_token)
             *
             * generate ancher(http request is 'get') or form(http request is delete).
             * - $method default is 'get'.
             * - $_token is csrf token. if you set $method is 'delete', need it.
             **/
            ?>
            <?= link_to($this->t('show'), '/posts/'.h($post->id)) ?>

        </td>
        <td>
            <?= link_to($this->t('edit'), '/posts/'.h($post->id).'/edit') ?>
        </td>
        <td>

            <?php
            /**
             * delete method sample
             **/
            ?>
            <?= link_to($this->t('delete'),
                        "/posts/$post->id",
                        array( 'method' => 'delete',
                               '_token' => $_token)) ?>
        </td>
    </tr>
    <?php } ?>
</table>

<?= link_to($this->t('add'), '/posts/add') ?>

