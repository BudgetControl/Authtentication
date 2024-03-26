<?php
// Autoload Composer dependencies

use \Illuminate\Support\Carbon as Date;
use Illuminate\Support\Facades\Facade;
use Monolog\Level;

require_once __DIR__ . '/../vendor/autoload.php';

// Set up your application configuration
// Initialize slim application
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Crea un'istanza del gestore del database (Capsule)
$capsule = new \Illuminate\Database\Capsule\Manager();

// Aggiungi la configurazione del database al Capsule
$connections = require_once __DIR__.'/../config/database.php';
$capsule->addConnection($connections['mysql']);

// Esegui il boot del Capsule
$capsule->bootEloquent();
$capsule->setAsGlobal();

$streamHandler = new \Monolog\Handler\StreamHandler(__DIR__.'/../storage/logs/log-'.date("Ymd").'.log', Level::Debug);
$logger = new \Monolog\Logger('app');
$formatter = new \Monolog\Formatter\SyslogFormatter();
$streamHandler->setFormatter($formatter);
$logger->pushHandler($streamHandler);

// Set up the Facade application
Facade::setFacadeApplication([
    'log' => $logger,
    'date' => new Date(),
    // 'cache' => $fileBasedStorage
]);