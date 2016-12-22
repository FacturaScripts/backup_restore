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
namespace Artesanik\DBProcess;
/**
 * Description of MysqlProcess
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class MysqlProcess {
    public function __construct() {

    }

    public function createBackup($db){
        if($db->dbname){
            try {
                system(
                    sprintf(
                        'mysqldump --opt -h %s -u %s -p%s %s | gzip > %s/%s/%s-%s%s%s-%s%s.gz',
                        $db->host,
                        $db->user,
                        $db->pass,
                        $db->dbname,
                        getenv('DOCUMENT_ROOT'),
                        "sql_backups",
                        $db->dbname,
                        $db->year,
                        $db->month,
                        $db->day,
                        $db->hour,
                        $db->min
                    )
                );
                return true;
            }catch(Exception $e){
                return $e->getMessage();
            }
        }
    }
}
