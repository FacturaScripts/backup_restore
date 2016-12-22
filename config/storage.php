<?php

require_once 'config.php';

return [
    'local' => [
        'type' => 'Local',
        'root' => '/tmp/' . FS_TMP_NAME . 'sql_backups',
    ],
];