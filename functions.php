<?php

/* Para desactivar que se puedan configurar los backups */
if (!defined('FS_DISABLE_CONFIGURE_BACKUP')) {
   define('FS_DISABLE_CONFIGURE_BACKUP', FALSE);
}

/* Para desactivar que se puedan eliminar los backups */
if (!defined('FS_DISABLE_DELETE_BACKUP')) {
   define('FS_DISABLE_DELETE_BACKUP', FALSE);
}

/* Para desactivar que se puedan descargar los backups */
if (!defined('FS_DISABLE_DOWNLOAD_BACKUP')) {
   define('FS_DISABLE_DOWNLOAD_BACKUP', FALSE);
}

/* Para desactivar que se puedan subir los backups */
if (!defined('FS_DISABLE_UPLOAD_BACKUP')) {
   define('FS_DISABLE_UPLOAD_BACKUP', FALSE);
}