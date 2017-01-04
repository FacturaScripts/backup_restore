<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2016  Francesc Pineda Segarra     shawe.ewahs@gmail.com
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

require_once 'plugins/backup_restore/vendor/Artesanik/DatabaseManager.php';

use Artesanik\DatabaseManager;

class backup_restore extends fs_controller {

   const path = "sql_backups";

   public $files;
   public $fsvar;
   public $basepath;
   public $path;
   public $backup_file_now;
   public $backup_comando;
   public $restore_comando;
   public $backup_setup;
   public $db_version;
   public $results;
   public function __construct() {
      parent::__construct(__CLASS__, 'Copias de seguridad', 'admin', FALSE, TRUE);
   }

   protected function private_core() {
      $this->db_version = $this->db->version();
      $this->fsvar = new fs_var();
      //Si no existe el path lo creamos
      if (!file_exists(self::path)) {
         mkdir(self::path);
      }
      //Buscamos los binarios necesarios en las rutas normales
      $this->configurar();

      $this->backup_comando = $this->backup_setup['backup_comando'];
      $this->restore_comando = $this->backup_setup['restore_comando'];

      $this->basepath = dirname(dirname(dirname(__DIR__)));
      $this->path = self::path;

      // Interfaz para cargar 
      $dbInterface = ucfirst(strtolower(FS_DB_TYPE));
      require_once 'plugins/backup_restore/vendor/Artesanik/DBProcess/' . $dbInterface . 'Process.php';

      //Verificamos si existe un backup con la fecha actual para mostrarlo en el view
      $this->backup_file_now = file_exists(self::path.DIRECTORY_SEPARATOR.FS_DB_NAME."_".\date("Ymd").".zip");
      
      $accion = filter_input(INPUT_POST, 'accion');
      if($accion){
        $manager = new DatabaseManager([
             'dbms' => FS_DB_TYPE,
             'host' => FS_DB_HOST,
             'port' => FS_DB_PORT,
             'user' => FS_DB_USER,
             'pass' => FS_DB_PASS,
             'dbname' => FS_DB_NAME,
             'command' => ($accion=='agregar')?$this->backup_comando:$this->restore_comando,
             'root' => '/tmp',
             'backupdir' => $this->basepath . DIRECTORY_SEPARATOR . self::path
        ]);
        switch ($accion) {
           case "agregar":
               $this->template = false;
              try {
                 $backup = $manager->createBackup('full');
                 if (file_exists($backup)) {
                    header('Content-Type: application/json');
                    echo json_encode( array('success' => true, 'mensaje' => 'Backup realizado correctamente: ' . $backup) );
                 } else {
                    header('Content-Type: application/json');
                    echo json_encode( array('success' => false, 'mensaje' => 'Algo salió mal realizando el backup: ' . $backup) );
                 }
              } catch (Exception $e) {
                 header('Content-Type: application/json');
                echo json_encode( array('success' => false, 'mensaje' => 'Ocurrio un error interno al intentar crear el backup:' . $e->getMessage()) );
              }
              break;
           case "restaurar":
              $archivo = realpath(\filter_input(INPUT_POST, 'restore_file'));
              if(file_exists($archivo)){
                  $backup = $manager->restoreBackup($archivo);
                  if($backup){
                      $this->new_error_msg('Ocurrió un error al querer restaurar el backup: '.$backup);
                  }else{
                      $this->new_message('¡Backup restaurado con exito!');
                  }
              }else{
                  $this->new_error_msg('¡No se indicó un backup para realizar la restauración!');
              }
           case "configuracion":
              $this->configurar();
              break;
           default:
              break;
        }
      }



      if (isset($_GET['nueva'])) {
         $manager = new DatabaseManager([
             'dbms' => FS_DB_TYPE,
             'host' => FS_DB_HOST,
             'port' => FS_DB_PORT,
             'user' => FS_DB_USER,
             'pass' => FS_DB_PASS,
             'dbname' => FS_DB_NAME,
             'command' => $this->backup_comando,
             'root' => '/tmp',
             'backupdir' => $this->basepath . DIRECTORY_SEPARATOR . self::path
         ]);

         
      } else if (isset($_GET['nueva'])) {
         //restore
         //$manager->makeRestore()->run('local', 'tmp/sql_backups/backup_'.date('d-m-Y_H:i:s').'.sql.gz', 'production', 'gzip');
      }
      $this->results = array();
      $this->files = $this->getFiles(self::path);
   }

   private function configurar() {
      //Inicializamos la configuracion
      $this->backup_setup = $this->fsvar->array_get(
              array(
          'backup_comando' => '',
          'restore_comando' => '',
              ), TRUE
      );
      
      $nombre = filter_input(INPUT_POST, 'backup_comando');
      $cmd = $this->buscarCmd($nombre, true);
      $comando_backup = ($cmd) ? trim($cmd) : $this->backup_setup['backup_comando'];
      
      $nombre = filter_input(INPUT_POST, 'restore_comando');
      $cmd = $this->buscarCmd($nombre, false);
      $comando_restore = ($cmd) ? trim($cmd) : $this->backup_setup['restore_comando'];
      
      $backup_config = array(
          'backup_comando' => $comando_backup ,
          'restore_comando' => $comando_restore
      );
      $this->fsvar->array_save($backup_config);
   }

   private function buscarCmd($comando, $backup = TRUE) {
      if (isset($comando)) {
         $resultado = array();
         exec("$comando --version", $resultado);
         if (!empty($resultado[0])) {
            return $comando;
         } else {
            return false;
         }
      } else {
         $paths = $this->osPath($backup);

         foreach ($paths as $cmd) {
            $lanza_comando = '"'."$cmd". '"' . " --version";
            exec($lanza_comando, $resultado);
            if (!empty($resultado[0])) {
               return $lanza_comando;
            }
         }
      }
   }

   private function osPath($backup = TRUE) {
      $paths = array();
      $db_version = explode(" ", $this->db->version());
      $version[0] = substr($db_version[1],0,1);
      $version[1] = intval(substr($db_version[1],1,2));
      if (PHP_OS == "WINNT") {
         $comando = (FS_DB_TYPE == 'POSTGRESQL') ? array('pg_dump.exe', 'pg_restore') : array('mysqldump.exe','mysql.exe');
         if ($backup == TRUE) {
            $comando = $comando[0];
         } else {
            $comando = $comando[1];
         }
         $base_dir = str_replace(" (x86)","",getenv("PROGRAMFILES")) . "\\";
         $base_dirx86 = getenv("PROGRAMFILES") . "\\";
         $paths[] = $base_dir . ucfirst(strtolower($db_version[0])) . "\\" . ucfirst(strtolower($db_version[0])). " Server " . $version[0] . "." . $version[1] . "\\bin\\" . $comando;
         $paths[] = $base_dirx86 . ucfirst(strtolower($db_version[0])) . "\\" . ucfirst(strtolower($db_version[0])). " Server " . $version[0] . "." . $version[1] . "\\bin\\" . $comando;
         $paths[] = $base_dir . ucfirst(strtolower($db_version[0])) . "\\" . ucfirst(strtolower($db_version[0])). " Server " . $version[0] . "." . $version[1] . "\\exe\\" . $comando;
         $paths[] = $base_dirx86 . ucfirst(strtolower($db_version[0])) . "\\" . ucfirst(strtolower($db_version[0])). " Server " . $version[0] . "." . $version[1] . "\\exe\\" . $comando;
      } else {
         $comando = (FS_DB_TYPE == 'POSTGRESQL') ? array('pg_dump', 'pg_restore') : array('mysqldump', 'mysql');
         if ($backup == TRUE) {
            $comando = $comando[0];
         } else {
            $comando = $comando[1];
         }
         $paths[] = "/usr/bin/" . $comando;
         //$paths[] = "/usr/local/bin/".$comando;
      }
      return $paths;
   }

   private function getFiles($dir) {
      foreach (new DirectoryIterator($dir) as $file) {
         if ($file->isDot()) {
            continue;
         } elseif ($file->isFile()) {
            $archivo = new stdClass();
            $archivo->filename = $file->getFilename();
            $archivo->path = $file->getPathName();
            $archivo->size = filesize($file->getPathName());
            $archivo->date = date('Y-m-d', filemtime($file->getPathName()));
            $archivo->type = $file->getExtension();
            $archivo->file = TRUE;
            $this->results[] = $archivo;
         } elseif($file->isDir()) {
            $this->getFiles($dir.DIRECTORY_SEPARATOR.$file);
         }
      }
      return $this->results;
   }

   public function url() {
      return parent::url();
   }

}
