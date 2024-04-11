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

//setup log level from env
switch(env('APP_LOG_LEVEL','debug')) {
    case 'debug':
        $logLevel = Level::DEBUG;
        break;
    case 'info':
        $logLevel = Level::INFO;
        break;
    case 'notice':
        $logLevel = Level::NOTICE;
        break;
    case 'warning':
        $logLevel = Level::WARNING;
        break;
    case 'error':
        $logLevel = Level::ERROR;
        break;
    case 'critical':
        $logLevel = Level::CRITICAL;
        break;
    case 'alert':
        $logLevel = Level::ALERT;
        break;
    case 'emergency':
        $logLevel = Level::EMERGENCY;
        break;
    default:
        $logLevel = Level::DEBUG;
}

$logPath = env('APP_LOG_PATH',__DIR__.'/../storage/logs/log-'.date("Ymd").'.log');
$streamHandler = new \Monolog\Handler\StreamHandler($logPath, $logLevel);
$logger = new \Monolog\Logger('MS-AUTHTENTICATION');
$formatter = new \Monolog\Formatter\SyslogFormatter();
$streamHandler->setFormatter($formatter);
$logger->pushHandler($streamHandler);

// Set up the Facade application
Facade::setFacadeApplication([
    'log' => $logger,
    'date' => new Date(),
    // 'cache' => $fileBasedStorage
]);
