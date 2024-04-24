<?php

use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Cache\RedisStore;
use Predis\Command\RedisFactory;


// Configura il file store per il caching
$fs = new Filesystem();
$store = new FileStore($fs, env('CACHE_PATH', __DIR__ . '/../storage/cache'));

if(env('CACHE_DRIVER','file') == 'redis') {
    require_once 'redis-cache.php';
    $store = new RedisStore(
        new RedisCache()
    );
}

// Crea un'istanza del Repository del cache utilizzando il file store
$cache = new Repository($store);