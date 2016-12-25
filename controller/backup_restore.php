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

require_once 'plugins/backup_restore/vendor2/Artesanik/DatabaseManager.php';

use Artesanik\DatabaseManager;

class backup_restore extends fs_controller {

   const path = "sql_backups";
   public $files;
   public $fsvar;
   public $basepath;
   public $path;
   public $backup_comando;
   public $backup_setup;
   public $db_version;
   public function __construct() {
      parent::__construct(__CLASS__, 'Cópias de seguridad', 'admin', FALSE, TRUE);

   }

   protected function private_core() {
      $this->db_version = $this->db->version();
      $this->fsvar = new fs_var();
      //Inicializamos la configuracion
      $this->backup_setup = $this->fsvar->array_get(
          array(
             'backup_comando' => '',
          ), TRUE
      );
      //Si no existe el path lo creamos
      if (!file_exists(self::path)) {
         mkdir(self::path);
      }
      //Buscamos el comando de backup en las rutas normales
      $this->configurar();


      $accion = filter_input(INPUT_POST, 'accion');
      switch ($accion){
        case "agregar":

            break;
        case "restaurar":

            break;
        case "configuracion":
            $this->configurar();
            break;
        default:
            break;
      }

      $this->backup_comando = $this->backup_setup['backup_comando'];

      $this->basepath = dirname(dirname(dirname(__DIR__)));
      $this->path = self::path;

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
            'backupdir' => $this->basepath.DIRECTORY_SEPARATOR.self::path
        ]);

         try {
            // backup
            $dbInterface = ucfirst(strtolower(FS_DB_TYPE));
            require_once 'plugins/backup_restore/vendor2/Artesanik/DbProcess/'.$dbInterface.'Process.php';
            $backup = $manager->createBackup('full');
            $this->new_message('Backup realizado correctamente: '.$backup);
         } catch (Exception $e){
             $this->new_error_msg('Ocurrio un error interno al intentar crear el backup:');
             $this->new_error_msg($e->getMessage());
             $this->new_error_msg($e->getTraceAsString());
         }

      }

       //restore
       //$manager->makeRestore()->run('local', 'tmp/sql_backups/backup_'.date('d-m-Y_H:i:s').'.sql.gz', 'production', 'gzip');

      $this->files = $this->getFiles(self::path);
   }

   private function configurar(){
        $nombre = filter_input(INPUT_POST, 'backup_comando');
        $cmd = $this->buscarCmd($nombre);
        $comando = ($cmd)?trim($cmd):$this->backup_setup['backup_comando'];
        $backup_config = array(
            'backup_comando' => $comando
        );
        $this->fsvar->array_save($backup_config);
   }

   private function buscarCmd($comando){
      if(isset($comando)){
          $resultado = array();
          exec("$comando --version", $resultado);
          if(!empty($resultado[0])){
             return $comando;
          }else{
             return false;
          }
      }else{
          $paths = $this->osPath();

          foreach($paths as $cmd){
              exec("$cmd --version",$resultado);
              if(!empty($resultado[0])){
                  return $cmd;
              }
          }
      }
   }

   private function osPath(){
       $paths = array();
       $db_version = explode(" ",$this->db->version());
       $version = explode(".",$db_version[1]);
       if(PHP_OS == "WINNT"){
           $comando = (FS_DB_TYPE=='POSTGRESQL')?'pg_dump.exe':'mysqldump.exe';
           $paths[] = "c:\\Program Files\\". ucfirst(strtolower($db_version[0]))."\\".$db_version[1]."\\bin\\".$comando;
           $paths[] = "c:\\Program Files\\". ucfirst(strtolower($db_version[0]))."\\".ucfirst(strtolower($db_version[0]))." ".$db_version[1]."\\bin\\".$comando;
           $paths[] = "c:\\Program Files\\". ucfirst(strtolower($db_version[0]))."\\".ucfirst(strtolower($db_version[0]))." Server ".$db_version[1]."\\bin\\".$comando;
           $paths[] = "c:\\Program Files\\". ucfirst(strtolower($db_version[0]))."\\".$version[0].".".$version[1]."\\bin\\".$comando;
           $paths[] = "c:\\Program Files\\". ucfirst(strtolower($db_version[0]))."\\".$version[0].".".$version[1]."\\exe\\".$comando;
           $paths[] = "c:\\Program Files\(x86\)\\". ucfirst(strtolower($db_version[0]))."\\".$version[0].".".$version[1]."\\exe\\".$comando;
       }else{
           $comando = (FS_DB_TYPE=='POSTGRESQL')?'pg_dump':'mysqldump';
           $paths[] = "/usr/bin/".$comando;
           //$paths[] = "/usr/local/bin/".$comando;
       }
       return $paths;
   }

   private function getFiles($dir) {
      $result = array();
      foreach (new DirectoryIterator($dir) as $file) {
        if($file->isDot()){
            continue;
        }elseif($file->isFile()){
           $archivo = new stdClass();
           $archivo->filename = $file->getFilename();
           $archivo->size = filesize($file->getPathName());
           $archivo->date = date('Y-m-d',filemtime($file->getPathName()));
           $archivo->type = $file->getExtension();
           $result[] = $archivo;
        }else{
            $result[$file] = $this->getFiles($dir . DIRECTORY_SEPARATOR . $file);
        }
      }
      return $result;
   }

   public function url() {
      return parent::url();
   }

}