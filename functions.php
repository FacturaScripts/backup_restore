<?php

/* Para desactivar que se puedan descargar los backups */
if (!defined('FS_DISABLE_DOWNLOAD_BACKUP')) {
   define('FS_DISABLE_DOWNLOAD_BACKUP', FALSE);
   define('FS_DISABLE_DOWNLOAD_BACKUP_NOTSET', TRUE);
}

/* Para desactivar que se puedan subir los backups */
if (!defined('FS_DISABLE_UPLOAD_BACKUP')) {
   define('FS_DISABLE_UPLOAD_BACKUP', FALSE);
   define('FS_DISABLE_UPLOAD_BACKUP_NOTSET', TRUE);
}