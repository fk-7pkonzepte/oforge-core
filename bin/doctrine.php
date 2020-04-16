<?php
define('ROOT_PATH', dirname(__DIR__));
set_time_limit(0);

require_once ROOT_PATH . '/vendor/autoload.php';
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;

$smith = BlackSmith::getInstance();
$smith->forge(false);

/** @var EntityManager $entityManager */
$entityManager = Oforge()->DB()->getForgeEntityManager()->getEntityManager();
$helperSet     = ConsoleRunner::createHelperSet($entityManager);
$helperSet->set(new ConnectionHelper($entityManager->getConnection()), 'db');
$helperSet->set(new EntityManagerHelper($entityManager), 'em');
ConsoleRunner::run($helperSet, []);
