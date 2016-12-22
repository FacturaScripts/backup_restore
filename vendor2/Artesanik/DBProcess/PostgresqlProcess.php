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
 * Description of PostgresqlProcess
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class PostgresqlProcess {
    public function __construct() {
        return '';
    }

    public function createBackup($db){
        if($db->dbname){
            try {
                putenv("PGPASSWORD=" . $db->pass);
                system(
                    sprintf(
                        'pg_dump -Fp -h %s -U %s %s | gzip > %s/%s/%s-%s%s%s-%s%s.gz',
                        $db->host,
                        $db->user,
                        $db->dbname,
                        $db->root,
                        $db->backupdir,
                        $db->dbname,
                        $db->year,
                        $db->month,
                        $db->day,
                        $db->hour,
                        $db->min
                    )
                );
                putenv("PGPASSWORD");

            }catch(Exception $e){
                return $e->getMessage();
            }
        }
    }
}
