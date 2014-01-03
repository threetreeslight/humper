<?php $this->setLayoutVar(array('title' => $this->t('post'))) ?>

<h1>post</h1>


<p>id : <?= h($post->id) ?></p>
<p><?= $this->t('name') ?>: <?= h($post->name) ?></p>
<p><?= $this->t('body') ?>: <?= h($post->body) ?></p>

<?= link_to($this->t('back'), '/posts') ?>
 | 
<?= link_to($this->t('edit'), '/posts/'.h($post->id).'/edit') ?>
