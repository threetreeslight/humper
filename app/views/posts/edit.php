<?php $this->setLayoutVar(array('title' => $this->t('post'))) ?>

<h1>posts/edit</h1>

<?php
/**
 * put method sample
 **/
?>
<form action='/posts/<?= $post->id ?>' method='post'>
    <?php
    /**
     * $_token
     *
     * $_token is csrf token. and generated always.
     * you have to use in post, put, delete method.
     *
     **/
    ?>
    <input type='hidden' name='_token' value="<?= $_token ?>">
    <input type='hidden' name='_method' value="put">
    <?= $this->t('name') ?>:
    <input type='text' name='name' value="<?= $post->name ?>">
    <?= $this->t('body') ?>:
    <input type='text' name='body' value="<?= $post->body ?>">
    <input type='submit' value='<?= $this->t('update') ?> '>
</form>

<?= link_to($this->t('back'), '/posts') ?>
