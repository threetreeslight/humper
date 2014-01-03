<a href='/ja<?= $current_url ?>'>日本語</a>
<a href='/en<?= $current_url ?>'>English</a>

<span> | </span>

<?php if(!$this->auth_component->isAuthenticated()) { ?>
    <?= link_to($this->t('signin'), '/signin') ?>
<?php } else { ?>
    <?= link_to($this->t('logout'), '/logout') ?>
<?php } ?>


