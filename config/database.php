<?php

require_once 'config.php';

return [
    'production' => [
        'type' => FS_DB_TYPE,
        'host' => FS_DB_HOST,
        'port' => FS_DB_PORT,
        'user' => FS_DB_USER,
        'pass' => FS_DB_PASS,
        'database' => FS_DB_NAME,
    ],
];
