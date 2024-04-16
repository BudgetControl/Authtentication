<?php

use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Filesystem\Filesystem;

// Configura il file store per il caching
$fs = new Filesystem();
$fileStore = new FileStore($fs, env('CACHE_PATH', __DIR__ . '/../storage/cache'));

// Crea un'istanza del Repository del cache utilizzando il file store
$cache = new Repository($fileStore);