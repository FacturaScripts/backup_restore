<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_once 'vendor/FacturaScripts/DatabaseManager.php';
/**
 * Description of cron
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
use FacturaScripts\DatabaseManager;

class cron_backup {
   const backups_path = "backups";
   const sql_path = "sql";
   const fs_files_path = "archivos";
   public function __construct(&$db) {
      $fsvar = new fs_var();
      $backup_vars = $fsvar->array_get( array(
         'backup_comando' => '',
         'restore_comando' => '',
         'restore_comando_data' => '',
         'backup_ultimo_proceso' => '',
         'backup_cron' => '',
         'backup_programado' => '',
         'backup_procesandose' => 'FALSE',
         'backup_usuario_procesando' => ''
         ), TRUE
      );  
      
      echo "Ejecutando tareas iniciales para procesar backup...";
    }
}