<?php

return [
    'channels' => [
        'main' => [
            'handler' => 'stream',
            'path' => 'storage/logs/app.log',
            'level' => 'debug',
        ],
    ],
];