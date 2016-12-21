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

   const path = "tmp/" . FS_TMP_NAME . "/sql_backups";

   public $files;

   public function __construct() {
      parent::__construct(__CLASS__, 'Cópias de seguridad', 'admin', FALSE, TRUE);
   }

   protected function private_core() {
      if (!file_exists(self::path)) {
         mkdir(self::path);
      }

      $this->files = $this->getFiles(self::path);

      if (isset($_GET['nueva'])) {
         $manager = require 'plugins/backup_restore/config/bootstrap.php';
         try {
            // backup
            $manager->makeBackup()->run(
               'production',
               [new Destination('local', self::path . '/backup_' . date('d-m-Y_H:i:s') . '.sql')],
               'gzip'
            );
         } catch (Exception $e){
             $this->new_error_msg('Ocurrio un error interno al intentar crear el backup:');
             $this->new_error_msg($e->getMessage());
             $this->new_error_msg($e->getTraceAsString());
         }

      }

      //restore
//      $manager->makeRestore()->run('local', 'tmp/sql_backups/backup_'.date('d-m-Y_H:i:s').'.sql.gz', 'production', 'gzip');
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

   public function url() {
      return parent::url();
   }

}
