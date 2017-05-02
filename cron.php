<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 * Copyright (C) 2017 Francesc Pineda Segarar <shawe.ewahs at gmail.com>
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
 * Se encarga de realizar backups de la base de datos y de los archivos de forma
 * programada, si es que el usuario ha indicado que los quiere realizar.
 * 
 * Más info: https://www.facturascripts.com/foro/configurar-el-cron-de-facturascripts-9.html
 *
 * @author Joe Nilson <joenilson at gmail.com>
 * @author Francesc Pineda Segarra <shawe.ewahs at gmail.com>
 */
use FacturaScripts\DatabaseManager;

class cron_backup {

   const backups_path = "backups";
   const sql_path = "sql";
   const fs_files_path = "archivos";
   const debug = TRUE;

   private $db;

   public function __construct(&$db) {

      $this->db = $db;

      $fsvar = new fs_var();
      $backup_vars = $fsvar->array_get(array(
          'backup_comando' => '',
          'restore_comando' => '',
          'restore_comando_data' => '',
          'backup_ultimo_proceso' => '',
          'backup_cron' => '',
          'backup_procesandose' => 'FALSE',
          'backup_usuario_procesando' => ''
              ), TRUE
      );

      if (self::debug) {
         echo "INFO: Modo debug activado.\n";
      } else {
         echo "INFO: Modo debug desactivado.\n";
      }

      /*
       * Por defecto queremos 1 backup diario de la base de datos 
       * y un backup semanal de los archivos, así que rellenamos las variables
       */
      echo "Ejecutando tareas iniciales para procesar backup...\n";

      $this->realizarBackup("DB");
      $this->realizarBackup("FILES");

      echo "Creación de backups finalizada...\n";
   }

   /**
    * Realización del backup según el tipo indicado.
    * 
    * @param type $type
    */
   public function realizarBackup($type) {
      $tmpPath = __DIR__ . "/../../tmp/" . FS_TMP_NAME;
      switch ($type) {
         case "DB":
            // Fecha de hoy
            $when = date("d-m-Y");
            $actual = $tmpPath . "backup-sql-" . $when;
            // Fecha de ayer
            $yesterday = date("d-m-Y", strtotime('-1 days'));
            $previous = $tmpPath . "backup-sql-" . $yesterday;
            // Texto que usaremos
            $typeString = "la base de datos";
            $whenString = "hoy";
            break;
         case "FILES":
            // Numero de esta semana
            $when = date("W");
            $actual = $tmpPath . "backup-files-" . $when;
            // Numero de la semana pasada
            $previousWeek = date("W") - 1;
            $previous = $tmpPath . "backup-files-" . $previousWeek;
            // Texto que usaremos
            $typeString = "los archivos";
            $whenString = "esta semana";
            break;
         default:
            echo "Formato no soportado\n";
            die();
            break;
      }

      /*
       * Sólo realizaremos backups sin repeticiones, ya que no sabemos la 
       * frecuencia con la que se ha configurado el cron de cada equipo.
       */
      if (!file_exists($actual)) {
         $this->checkPreviousBackup($previous, $typeString);
         switch ($type) {
            case "DB":
               $this->createDatabaseBackup($typeString, $when);
               break;
            case "FILES":
               $this->createFilesBackup($typeString, $when);
               break;
         }
         $this->checkActualBackup($actual, $typeString);
      } else {
         if (self::debug) {
            echo "   INFO: El backup de " . $typeString . " ya se ha realizado " . $whenString . ".\n";
         }
      }
   }

   /**
    * Comprobar si existe aviso del backup anterior que evita regenerar backups 
    * iguales, y si existe se borra el indicar del backup anterior, ya que no se 
    * va a sobreescribir y no necesitamos seguir teniendo este indicador.
    * 
    * @param type $previous
    * @param type $typeString
    */
   public function checkPreviousBackup($previous, $typeString) {
      if (file_exists($previous)) {
         if (!unlink($previous)) {
            echo "   ERROR: No se ha podido eliminar el indicador del anterior backup de " . $typeString . " en la carpeta temporal.\n";
         } elseif (self::debug) {
            echo "   INFO: Se ha eliminado el indicador del anterior backup de " . $typeString . " en la carpeta temporal.\n";
         }
      }
   }

   /**
    * Añadimos un indicador para controlar que ya hemos realizado este backup para 
    * evitar que se sobreescriba el backup si ya está hecho.
    * 
    * @param type $actual
    * @param type $typeString
    */
   public function checkActualBackup($actual, $typeString) {
      if (touch($actual)) {
         if (self::debug) {
            echo "   INFO: Se ha indicado que se ha realizado el backup de " . $typeString . " en la carpeta temporal.\n";
         }
      } else {
         echo "   ERROR: No se ha podido indicar que se ha realizado el backup de " . $typeString . " en la carpeta temporal.\n";
      }
   }

   public function createDatabaseBackup($typeString, $when) {
      echo "   Realizando el backup de " . $typeString . " para el día " . $when . ".\n";
      // TODO -Código para realizar el backup
   }

   public function createFilesBackup($typeString, $when) {
      echo "   Realizando el backup de " . $typeString . " para la semana " . $when . ".\n";
      // TODO -Código para realizar el backup
   }

}

//new cron_backup($db);
