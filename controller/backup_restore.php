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

require_once 'plugins/backup_restore/vendor/autoload.php';

use BackupManager\Filesystems\Destination;

class backup_restore extends fs_controller {

   const path = "tmp/" . FS_TMP_NAME . "sql_backups";

   public $files;
   public $fileSize;

   public function __construct() {
      parent::__construct(__CLASS__, 'Cópias de seguridad', 'admin', FALSE, TRUE);
   }

   protected function private_core() {
      if (!file_exists(self::path)) {
         mkdir(self::path);
      }

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
         }
      }
   }

   public function url() {
      return parent::url();
   }

   private function getFiles($dir) {
      $result = array();
      $cdir = scandir($dir);
      foreach ($cdir as $key => $value) {
         if (!in_array($value, array(".", ".."))) {
            if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
               $result[$value] = getFiles($dir . DIRECTORY_SEPARATOR . $value);
            } else {
               $result[] = $value;
            }
         }
      }
      return $result;
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

}
