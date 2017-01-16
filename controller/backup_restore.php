<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2016  Francesc Pineda Segarra     shawe.ewahs@gmail.com
 * Copyright (C) 2016  Joe Nilson                  joenilson@gmail.com
 * Copyright (C) 2016  Rafael Salas Venero         rsalas.match@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'plugins/backup_restore/vendor/FacturaScripts/DatabaseManager.php';

use FacturaScripts\DatabaseManager;

class backup_restore extends fs_controller {

<<<<<<< HEAD
   const path = "tmp/" . FS_TMP_NAME . "sql_backups";

   public $files;
   public $fileSize;
=======
   const backups_path = "backups";
   const sql_path = "sql";
   const fs_files_path = "archivos";

   public $files;
   public $fsvar;
   public $basepath;
   public $path;
   public $backupdb_file_now;
   public $backupfs_file_now;
   public $backup_comando;
   public $restore_comando;
   public $restore_comando_data;
   public $backup_setup;
   public $db_version;
>>>>>>> joenilson

   public function __construct() {
      parent::__construct(__CLASS__, 'Copias de seguridad', 'admin', FALSE, TRUE);
   }

   protected function private_core() {
      $this->db_version = $this->db->version();
      $this->fsvar = new fs_var();

      //Si no existe el backups_path lo creamos
      if (!file_exists(self::backups_path)) {
         mkdir(self::backups_path);
      }

      //Si no existe el backups_path/sql_path lo creamos
      if (!file_exists(self::backups_path . DIRECTORY_SEPARATOR . self::sql_path)) {
         mkdir(self::backups_path . DIRECTORY_SEPARATOR . self::sql_path);
      }

<<<<<<< HEAD
      if (substr(sprintf('%o', fileperms(self::path)), -4) != "0777") {
         if (!chmod(self::path, 0777)) {
            $this->new_error_msg('La carpeta ' . self::path . ' necesita permisos 777 y no se han podido aplicar automáticamente.');
         }
      }

      if (FS_DB_TYPE == "MYSQL") {
         $this->new_advice('DEBUG: Estás utilizando MySQL.');
         $this->new_advice($this->command_exists('mysqldump')? 'DEBUG: mysqldump disponible' : 'DEBUG: mysqldump no disponible');
      } else if (FS_DB_TYPE == "POSTGRESQL") {
         $this->new_advice('DEBUG: Estás utilizando PostgreSQL.');
         $this->new_advice($this->command_exists('pg_dump')? 'DEBUG: pg_dump disponible' : 'DEBUG: pg_dump no disponible');
      }

      $files = $this->getFiles(self::path);
      //$fileSize = $this->getSize($files);

      if (isset($_GET['nueva']) AND ! empty($_GET['nueva'])) {
         $manager = require 'plugins/backup_restore/config/bootstrap.php';
         $file = self::path . '/backup_' . date('d-m-Y_H-i-s') . '.sql';
         $manager->makeBackup()->run('production', [
             new Destination('local', $file)
                 ], 'gzip');

         if (file_exists($file)) {
            $this->new_message("Copia '" . $file . "' creada correctamente.");
         } else {
            $this->new_error_msg("No se ha podido realizar la copia '" . $file . "'");
         }

         if (file_exists($file . '.gz')) {
            $this->new_message("Copia '" . $file . ".gz' creada correctamente.");
         } else {
            $this->new_error_msg("No se ha podido realizar la copia '" . $file . ".gz'");
         }
      } else if (isset($_GET['restaurar']) AND ! empty($_GET['restaurar'])) {
         if (file_exists($_GET['restaurar'])) {
            //restore
            //$manager->makeRestore()->run('local', 'tmp/sql_backups/backup_'.date('d-m-Y_H:i:s').'.sql.gz', 'production', 'gzip');
         } else {
            // El archivo no existe
=======
      //Si no existe el backups_path/fs_files_path lo creamos
      if (!file_exists(self::backups_path . DIRECTORY_SEPARATOR . self::fs_files_path)) {
         mkdir(self::backups_path . DIRECTORY_SEPARATOR . self::fs_files_path);
      }

      //Buscamos los binarios necesarios en las rutas normales
      $this->configurar();

      $this->backup_comando = $this->backup_setup['backup_comando'];
      $this->restore_comando = $this->backup_setup['restore_comando'];
      $this->restore_comando_data = $this->backup_setup['restore_comando_data'];

      $this->basepath = dirname(dirname(dirname(__DIR__)));
      $this->path = self::backups_path;

      // Interfaz para cargar
      $dbInterface = ucfirst(strtolower(FS_DB_TYPE));
      require_once 'plugins/backup_restore/vendor/FacturaScripts/DBProcess/' . $dbInterface . 'Process.php';

      //Verificamos si existe un backup con la fecha actual para mostrarlo en el view
      $this->backupdb_file_now = file_exists(self::backups_path . DIRECTORY_SEPARATOR . self::sql_path . DIRECTORY_SEPARATOR . FS_DB_TYPE.'_'.FS_DB_NAME. "_" . \date("Ymd") . ".zip");
      $this->backupfs_file_now = file_exists(self::backups_path . DIRECTORY_SEPARATOR . self::fs_files_path . DIRECTORY_SEPARATOR . "FS_" . \date("Ymd") . ".zip");

      $accion = filter_input(INPUT_POST, 'accion');
      if ($accion) {
         $manager = new DatabaseManager([
             'dbms' => FS_DB_TYPE,
             'host' => FS_DB_HOST,
             'port' => FS_DB_PORT,
             'user' => FS_DB_USER,
             'pass' => FS_DB_PASS,
             'dbname' => FS_DB_NAME,
             'command' => ($accion == 'backupdb') ? $this->backup_comando : $this->restore_comando,
             'backupdir' => $this->basepath . DIRECTORY_SEPARATOR . self::backups_path . DIRECTORY_SEPARATOR . self::sql_path
         ]);
         switch ($accion) {
            case "backupdb":
               $this->template = false;
               $crear_db = filter_input(INPUT_POST, 'crear_db');
               $estructura = filter_input(INPUT_POST, 'estructura');
               $solo_datos = filter_input(INPUT_POST, 'solo_datos');
               //Colocamos en el DatabaseManager las variables específicas para hacer el backup
               $manager->createdb = ($crear_db)?true:false;
               $manager->onlydata = ($estructura)?false:true;
               $manager->nodata = ($solo_datos)?false:true;
               try {
                  $backup = $manager->createBackup('full');
                  if (file_exists($backup)) {
                     header('Content-Type: application/json');
                     echo json_encode(array('success' => true, 'mensaje' => 'Backup de base de datos realizado correctamente: ' . $backup));
                  } else {
                     header('Content-Type: application/json');
                     echo json_encode(array('success' => false, 'mensaje' => 'Algo salió mal realizando el backup de base de datos: ' . $backup));
                  }
               } catch (Exception $e) {
                  header('Content-Type: application/json');
                  echo json_encode(array('success' => false, 'mensaje' => 'Ocurrio un error interno al intentar crear el backup:' . $e->getMessage()));
               }
               break;
            case "restaurardb":
               $archivo = realpath(\filter_input(INPUT_POST, 'restore_file'));
               if (file_exists($archivo)) {
                  $fichero = new SplFileInfo($archivo);
                  $dir = $fichero->getPath();
                  $informacion = $this->getConfigFromFile($dir,$fichero);
                  $manager->createdb = $informacion->configuracion->{'create_database'};
                  if(!$manager->createdb) {
                     $manager->command = $this->restore_comando_data;
                  }
                  $backup = $manager->restoreBackup($archivo, $informacion->configuracion);
                  if ($backup) {
                     $this->new_error_msg('Ocurrió un error al querer restaurar el backup de base de datos: ' . $backup);
                  } else {
                     $this->new_message('¡Backup de base de datos restaurado con exito!');
                  }
               } else {
                  $this->new_error_msg('¡No se indicó un backup de base de datos para realizar la restauración!');
               }
            case "configuracion":
               $this->configurar();
               break;
            case "backupfs":
               $this->file = self::backups_path . DIRECTORY_SEPARATOR . self::fs_files_path . DIRECTORY_SEPARATOR . 'FS_' . date("Ymd") . '.zip';
               $this->destino = $this->basepath . DIRECTORY_SEPARATOR . $this->file;
               $zip = new \ZipArchive();

               if ($zip->open($this->destino, \ZipArchive::CREATE) !== TRUE) {
                  echo json_encode(array('success' => false, 'mensaje' => "No se puede escribir el archivo " . $this->destino));
               } else {
                  $zip->open($this->destino, \ZipArchive::CREATE);
                  self::folderToZip($this->basepath, $zip, strlen("$this->basepath/"));
                  $zip->close();

                  $this->template = false;
                  header('Content-Type: application/json');
                  if (file_exists($this->destino)) {
                     echo json_encode(array('success' => true, 'mensaje' => "Backup de archivos realizado correctamente: " . $this->file));
                  } else {
                     echo json_encode(array('success' => false, 'mensaje' => "Backup de archivos no realizado!"));
                  }
               }
               break;
            case "restaurarfs":
               $archivo = realpath(\filter_input(INPUT_POST, 'restore_file'));
               if (file_exists($archivo)) {
                  // Es necesario eliminar algo antes de restaurar??
                  $zip = new ZipArchive;
                  if ($zip->open($archivo) === TRUE) {
                     $zip->extractTo($this->basepath);
                     $zip->close();

                     $this->new_message('¡Backup de archivos de restaurado con exito!');
                  } else {
                     $this->new_error_msg('Ocurrió un error al querer restaurar el backup de archivos');
                  }
               } else {
                  $this->new_error_msg('¡No se indicó un backup de archivos para realizar la restauración!');
               }
               break;
            case "eliminar":
               $archivo = realpath(\filter_input(INPUT_POST, 'delete_file'));
               if (file_exists($archivo)) {
                  if (is_dir($archivo)) {
                     $this->new_error_msg('No se puede eliminar ' . $archivo . ' porque es un directorio!');
                  } else {
                     if (unlink($archivo)) {
                        $this->new_message('Archivo ' . $archivo . ' eliminado con exito!');
                     } else {
                        $this->new_error_msg('Ocurrió un error al intentar eliminar el archivo ' . $archivo);
                     }
                  }
               } else {
                  $this->new_error_msg('El archivo ' . $archivo . ' no existe!');
               }
               break;
            default:
               break;
>>>>>>> joenilson
         }
      }
   }

<<<<<<< HEAD
   public function url() {
      return parent::url();
=======
      $this->sql_backup_files = $this->getFiles(self::backups_path . DIRECTORY_SEPARATOR . self::sql_path);
      $this->fs_backup_files = $this->getFiles(self::backups_path . DIRECTORY_SEPARATOR . self::fs_files_path);
>>>>>>> joenilson
   }

   private function configurar() {
      //Inicializamos la configuracion
      $this->backup_setup = $this->fsvar->array_get(
              array(
          'backup_comando' => '',
          'restore_comando' => '',
          'restore_comando_data' => '',
              ), TRUE
      );

      $nombre = filter_input(INPUT_POST, 'backup_comando');
      $cmd = $this->buscarCmd($nombre, true, false);
      $comando_backup = ($cmd) ? trim($cmd) : $this->backup_setup['backup_comando'];

      $nombre = filter_input(INPUT_POST, 'restore_comando');
      $cmd = $this->buscarCmd($nombre, false, false);
      $comando_restore = ($cmd) ? trim($cmd) : $this->backup_setup['restore_comando'];

      $nombre = filter_input(INPUT_POST, 'restore_comando_data');
      $cmd = $this->buscarCmd($nombre, false, true);
      $comando_restore_data = ($cmd) ? trim($cmd) : $this->backup_setup['restore_comando_data'];

      $backup_config = array(
          'backup_comando' => $comando_backup,
          'restore_comando' => $comando_restore,
          'restore_comando_data' => $comando_restore_data
      );
      $this->fsvar->array_save($backup_config);
   }

   private function buscarCmd($comando, $backup = TRUE, $onlydata = FALSE) {
      if (isset($comando)) {
         $resultado = array();
         exec("$comando --version", $resultado);
         if (!empty($resultado[0])) {
            return $comando;
         } else {
            return false;
         }
      } else {
         $paths = $this->osPath($backup, $onlydata);

         foreach ($paths as $cmd) {
            $lanza_comando = '"' . "$cmd" . '"' . " --version";
            exec($lanza_comando, $resultado);
            if (!empty($resultado[0])) {
               return '"' . "$cmd" . '"';
            }
         }
      }
   }

   private function osPath($backup = TRUE, $onlydata = FALSE) {
      $paths = array();
      $db_version = explode(" ", $this->db->version());
      $version[0] = substr($db_version[1], 0, 1);
      $version[1] = intval(substr($db_version[1], 1, 2));
      if (PHP_OS == "WINNT") {
         $comando = (FS_DB_TYPE == 'POSTGRESQL') ? array('pg_dump.exe', 'pg_restore.exe', 'pg_restore.exe') : array('mysqldump.exe', 'mysql.exe', 'mysqlimport.exe');
         if ($backup == TRUE) {
            $comando = $comando[0];
         } else {
            if($onlydata){
               $comando = $comando[2];
            } else {
               $comando = $comando[1];
            }
         }
         $base_dir = str_replace(" (x86)", "", getenv("PROGRAMFILES")) . "\\";
         $base_dirx86 = getenv("PROGRAMFILES") . "\\";
         $paths[] = $base_dir . ucfirst(strtolower($db_version[0])) . "\\" . ucfirst(strtolower($db_version[0])) . " Server " . $version[0] . "." . $version[1] . "\\bin\\" . $comando;
         $paths[] = $base_dirx86 . ucfirst(strtolower($db_version[0])) . "\\" . ucfirst(strtolower($db_version[0])) . " Server " . $version[0] . "." . $version[1] . "\\bin\\" . $comando;
         $paths[] = $base_dir . ucfirst(strtolower($db_version[0])) . "\\" . ucfirst(strtolower($db_version[0])) . " Server " . $version[0] . "." . $version[1] . "\\exe\\" . $comando;
         $paths[] = $base_dirx86 . ucfirst(strtolower($db_version[0])) . "\\" . ucfirst(strtolower($db_version[0])) . " Server " . $version[0] . "." . $version[1] . "\\exe\\" . $comando;
      } else {
         $comando = (FS_DB_TYPE == 'POSTGRESQL') ? array('pg_dump', 'pg_restore', 'pg_restore') : array('mysqldump', 'mysql', 'mysqlimport');
         if ($backup == TRUE) {
            $comando = $comando[0];
         } else {
            if($onlydata){
               $comando = $comando[2];
            } else {
               $comando = $comando[1];
            }
         }
         $paths[] = "/usr/bin/" . $comando;
      }
      return $paths;
   }

   private function getFiles($dir) {
      $results = array();
      $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
      foreach ($it as $file) {
         if ($file->isFile()) {
            //verificamos si el archivo ez un zip y si tiene un config.json
            $informacion = $this->getConfigFromFile($dir,$file);
            $archivo = new stdClass();
            $archivo->filename = $file->getFilename();
            $archivo->path = $file->getPathName();
            // FIXME Revisar para no tener que pasar el valor por duplicado
            $archivo->escaped_path = addslashes($file->getPathName());
            $archivo->size = self::tamano(filesize($file->getPathName()));
            $archivo->date = date('Y-m-d', filemtime($file->getPathName()));
            $archivo->type = $file->getExtension();
            $archivo->file = TRUE;
            $archivo->conf = $informacion;
            $results[] = $archivo;
         } else {
             continue;
         }
      }
      $ordenable = Array();
      foreach($results as &$columnaorden)
        $ordenable[] = &$columnaorden->date;
      array_multisort($ordenable, SORT_DESC, SORT_STRING, $results);
      return $results;
   }

   private function getSize($files) {
      $result = array();
      foreach ($files as $file) {
         $bytes = filesize($file);
         $decimals = 2;
         $sz = array('B', 'K', 'M', 'G', 'T', 'P');
         $factor = floor((strlen($bytes) - 1) / 3);
         $result[] = sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $sz[$factor];
      }

      return $result;
   }

   private function command_exists($command) {
      $whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';

      $process = proc_open(
              "$whereIsCommand $command", array(
          0 => array("pipe", "r"), //STDIN
          1 => array("pipe", "w"), //STDOUT
          2 => array("pipe", "w"), //STDERR
              ), $pipes
      );
      if ($process !== false) {
         $stdout = stream_get_contents($pipes[1]);
         $stderr = stream_get_contents($pipes[2]);
         fclose($pipes[1]);
         fclose($pipes[2]);
         proc_close($process);

         return $stdout != '';
      }

      return false;
   }

   public function tamano($tamano) {
      /* https://es.wikipedia.org/wiki/Mebibyte */
      $bytes = $tamano;
      $decimals = 2;
      $sz = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
      $factor = floor((strlen($bytes) - 1) / 3);
      return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $sz[$factor];
   }

   private function getConfigFromFile($dir, $file){
      if($file->getExtension()=='zip'){
         $z = new ZipArchive();
         if ($z->open($dir.'/'.$file->getFilename())) {
            $contents = '';
            $fp = $z->getStream('config.json');
            if($fp){
               while (!feof($fp)) {
                  $contents .= fread($fp, 2);
               }
               fclose($fp);
               return json_decode($contents);
            }
         }else{
            return false;
         }
      }else{
         return false;
      }
   }

   private static function folderToZip($folder, &$zipFile, $exclusiveLength) {
      $handle = opendir($folder);
      while (false !== $f = readdir($handle)) {
         if ($f != '.' && $f != '..') {
            $filePath = "$folder/$f";
            // Remove prefix from file path before add to zip.
            $localPath = substr($filePath, $exclusiveLength);
            if (is_file($filePath)) {
               $zipFile->addFile($filePath, $localPath);
            } elseif (is_dir($filePath)) {
               if (strpos($localPath, self::fs_files_path) !== false) {
                  // Contiene self::fs_files_path
                  // No queremos backup de backups, pero si queremos backups de sql
               } else {
                  // Add sub-directory.
                  $zipFile->addEmptyDir($localPath);
                  self::folderToZip($filePath, $zipFile, $exclusiveLength);
               }
            }
         }
      }
      closedir($handle);
   }

}
