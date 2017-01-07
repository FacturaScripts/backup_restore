<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2016  Joe Nilson                  joenilson@gmail.com
 * Copyright (C) 2016  Francesc Pineda Segarra     shawe.ewahs@gmail.com
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

namespace FacturaScripts;

use FacturaScripts\DBProcess\MysqlProcess;
use FacturaScripts\DBProcess\PostgresqlProcess;

/**
 * Description of DatabaseManager
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class DatabaseManager {

   public $dbms;
   public $user;
   public $pass;
   public $host;
   public $port;
   public $dbname;
   public $backupdir;
   public $command;
   public $year;
   public $month;
   public $day;
   public $hour;
   public $min;
   public $onlydata;
   public $nodata;
   public $createdb;
   public $config_file;

   public function __construct(array $info) {
      $this->dbms = $info['dbms'];
      $this->user = $info['user'];
      $this->pass = $info['pass'];
      $this->host = $info['host'];
      $this->port = $info['port'];
      $this->dbname = $info['dbname'];
      $this->command = $info['command'];
      $this->backupdir = $info['backupdir'];
      $this->onlydata = false;
      $this->nodata = false;
      $this->createdb = false;

      $today = getdate();

      $this->day = $today['mday'];
      if ($this->day < 10) {
         $this->day = "0$this->day";
      }
      $this->month = $today['mon'];
      if ($this->month < 10) {
         $this->month = "0$this->month";
      }
      $this->year = $today['year'];
      $this->hour = $today['hours'];
      $this->min = $today['minutes'];
      $this->sec = "00";

      $this->config_file['configuracion']['dbms'] = $this->dbms;
      $this->config_file['configuracion']['type'] = ($this->onlydata)?"data":"full";
      $this->config_file['configuracion']['date_backup'] = $this->year.'-'.$this->month.'-'.$this->day;
   }

   public function createBackup($tipo = false) {
      $this->config_file['configuracion']['create_database'] = ($this->createdb)?true:false;
      $this->config_file['configuracion']['only_data'] = ($this->onlydata)?true:false;
      $this->config_file['configuracion']['no_data'] = ($this->nodata)?true:false;
      switch ($this->dbms) {
         case "MYSQL":
            $dbHandler = new MysqlProcess;
            break;
         case "POSTGRESQL":
            $dbHandler = new PostgresqlProcess;
            break;
         default:
            break;
      }
      $resultado = $dbHandler->createSystemBackup($this);
      return $resultado;
   }

   public function restoreBackup($fileBackup) {
      switch ($this->dbms) {
         case "MYSQL":
            $dbHandler = new MysqlProcess;
            break;
         case "POSTGRESQL":
            $dbHandler = new PostgresqlProcess;
            break;
         default:
            break;
      }
      $resultado = $dbHandler->restoreSystemBackup($this, $fileBackup);
      return $resultado;
   }

   public function removeFileBackup() {

   }

}
