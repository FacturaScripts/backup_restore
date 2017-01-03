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
namespace Artesanik;

use Artesanik\DBProcess\MysqlProcess;
use Artesanik\DBProcess\PostgresqlProcess;
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
    public $root;
    public $backupdir;
    public $command;
    public $year;
    public $month;
    public $day;
    public $hour;
    public $min;
    public function __construct(array $info) {
        $this->dbms = $info['dbms'];
        $this->user = $info['user'];
        $this->pass = $info['pass'];
        $this->host = $info['host'];
        $this->port = $info['port'];
        $this->dbname = $info['dbname'];
        $this->command = $info['command'];
        $this->root = $info['root'];
        $this->backupdir = $info['backupdir'];
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
    }

    public function createBackup($tipo=false){
        switch($this->dbms){
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

    public function restoreBackup($fileBackup){
        switch($this->dbms){
            case "MYSQL":
                $dbHandler = new MysqlProcess;
                break;
            case "POSTGRESQL":
                $dbHandler = new PostgresqlProcess;
                break;
            default:
                break;
        }
        $resultado = $dbHandler->restoreSystemBackup($this,$fileBackup);
        return $resultado;
    }

    public function removeFileBackup(){

    }
}
