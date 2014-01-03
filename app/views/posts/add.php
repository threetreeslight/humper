<?php $this->setLayoutVar(array('title' => $this->t('add'))) ?>

<h1>posts/add</h1>

<?php
/**
 * post method sample
 **/
?>
<form action='/posts/create' method='post'>
    <input type='hidden' name='_token' value="<?= $_token ?>">
    <?= $this->t('name') ?>:
    <input type='text' name='name' value="">
    <?= $this->t('post') ?>:
    <input type='text' name='body' value="">
    <input type='submit' value='<?= $this->t('create') ?>'>
</form>

<?= link_to($this->t('back'), '/posts') ?>
