<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
   <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">

        <title><?php if (isset($title)): echo h($title) . ' - '; endif; ?>blog</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" href="/stylesheets/application.css">
    </head>
    <body>
        <!-- navigation -->
        <?= $this->render('application/navigation',
                          array('current_url' => i18n_path_info($this->request->getPathInfo(), $this->i18n_component->getLanguage()))
            ) ?>

        <!-- errors -->
        <?php if (isset($errors) && count($errors) > 0): ?>
            <?= $this->render('application/notification', array('errors' => $errors)); ?>
        <?php endif; ?>

        <!-- contents -->
        <?php echo $_content; ?>

        <script src="/javascripts/application.js"></script>
    </body>
</html>
