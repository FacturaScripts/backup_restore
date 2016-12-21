<?php
// path to composer autoloader
require FS_PATH.'plugins/backup_restore/vendor/autoload.php';
use BackupManager\Config\Config;
use BackupManager\Filesystems;
use BackupManager\Databases;
use BackupManager\Compressors;
use BackupManager\Manager;
// build providers
$filesystems = new Filesystems\FilesystemProvider(Config::fromPhpFile(FS_PATH.'plugins/backup_restore/config/storage.php'));
$filesystems->add(new Filesystems\LocalFilesystem);
$databases = new Databases\DatabaseProvider(Config::fromPhpFile(FS_PATH.'plugins/backup_restore/config/database.php'));
$databases->add(new Databases\MysqlDatabase);
$databases->add(new Databases\PostgresqlDatabase);
$compressors = new Compressors\CompressorProvider;
$compressors->add(new Compressors\GzipCompressor);
$compressors->add(new Compressors\NullCompressor);
// build manager
return new Manager($filesystems, $databases, $compressors);