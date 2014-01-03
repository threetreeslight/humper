<?php

require '../bootstrap.php';
require '../BlogApplication.php';

$app = new BlogApplication(true);
$app->run();

## if you want stop after process, uncomment here
#
# if ($app->getResponse()->getStatusCode() === 200 ) {
#     return false;
# }

