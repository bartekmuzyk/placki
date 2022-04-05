<?php

define('PROJECT_ROOT', dirname(__DIR__));

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use App\App;

require_once 'vendor/autoload.php';

$app = new App(null);
$entityManager = $app->getDBManager()->getEntityManager();

return ConsoleRunner::createHelperSet($entityManager);
