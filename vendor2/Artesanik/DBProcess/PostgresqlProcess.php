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

    public $tablas;
    public $destino;
    public $origen;
    public $tempdir;
    public $file;
    public $filename;
    public $conn;
    public $query;
    public function __construct() {
        $this->tablas = array();
        //Obtenemos el directorio temporal para trabajar
        $this->tempdir = sys_get_temp_dir();
        $this->file = NULL;
        $this->filename = NULL;
        $this->destino = NULL;
        $this->origen = NULL;
        $this->conn = null;
        $this->uery = null;
    }

    /**
     * Conectamos utilizando PDO para obtener las carácteristicas de
     * mover información a archivos
     * @param type $db
     * @return \Artesanik\DBProcess\PDO
     */
    private function connectDB($db){
        $conn = new \PDO("pgsql:host=$db->host;port=$db->port;dbname=$db->dbname;user=$db->user;password=$db->pass");
        return $conn;
    }

    public function createSystemBackup($db){
        if($db->dbname){
            $this->destino = $db->backupdir.DIRECTORY_SEPARATOR.$db->dbname.'_'.$db->year.$db->month.$db->day.'.zip';
            $this->filename = $this->tempdir.$db->dbname.'_'.$db->year.$db->month.$db->day.'.sql';
            exec("export PGPASSWORD={$db->pass} | export PGUSER={$db->user} | {$db->command} -h {$db->host} -U {$db->user} {$db->dbname} -b > {$this->filename} 2>&1 | unset PGPASSWORD | unset PGUSER",$cmdout);
            //Comprimimos el Backup y lo mandamos a su detino
            $zip = new \ZipArchive();
            $zip->open($this->destino, \ZipArchive::CREATE);
            $zip->addFile($this->filename);
            $zip->close();
            unlink($this->filename);
            return $this->destino;
        }
    }

    public function createBackup($db,$type='full'){
        if($db->dbname){
            $this->conn = $this->connectDB($db);
            $this->destino = $db->backupdir.DIRECTORY_SEPARATOR.$db->dbname.'_'.$db->year.$db->month.$db->day.'.zip';
            $this->filename = $this->tempdir.$db->dbname.'_'.$db->year.$db->month.$db->day.'.sql';
            if($type=='full'){
                return $this->fullBackup();
            }elseif($type=='tablas'){
                $this->tablas = $db->tablas;
                $this->tableBackup();
            }
        }
    }

    public function fullBackup(){
        if(file_exists($this->filename))unlink($this->filename);
        $this->file = fopen($this->filename, "w");
        $lista = $this->tableList();
        if($lista){
            foreach($lista as $t){
                $listaColumnas = array();
                //Creamos la estructura de la tabla
                fputs($this->file, sprintf("CREATE TABLE IF NOT EXISTS %s (\n\r",$t['table_name']), 1024);
                foreach($this->tableColumns($t['table_name']) as $column){
                    $listaColumnas[] = $column['column_name'];
                    fputs($this->file, sprintf("%s",$this->constructColumn($column)), 1024);
                }
                fputs($this->file, sprintf(");\n\r"),1024);
                //Copiamos la información de la tabla
                fputs($this->file, sprintf("COPY %s (%s) FROM stdin;\n\r",$t['table_name'], implode(",",$listaColumnas)),1024);
                $datosTabla = $this->conn->pgsqlCopyToArray($t['table_name'],"\t","\\N",implode(",",$listaColumnas));
                foreach($datosTabla as $datos){
                    fputs($this->file, sprintf("%s\n\r",$datos),1024);
                }
                fputs($this->file, sprintf("\.\n\r"),1024);
            }
            fclose($this->file);
            //Comprimimos el Backup y lo mandamos a su detino
            $zip = new \ZipArchive();
            $zip->open($this->destino, \ZipArchive::CREATE);
            $zip->addFile($this->filename);
            $zip->close();
            unlink($this->filename);
            return $this->destino;
        }else{
            return false;
        }

    }

    public function tableBackup(){

    }

    public function tableList(){
        $query = $this->conn->query("SELECT * FROM information_schema.tables WHERE table_schema = 'public' order by table_name");
        $stm = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $stm;
    }

    public function tableColumns($tableName){
        $query = $this->conn->query("SELECT * FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '$tableName' ORDER BY ordinal_position;");
        $stm = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $stm;
    }

    public function constructColumn($c){
        $string = "";
        switch($c['data_type']){
            case "character varying":
                $string = sprintf("\t%s\t%s(%s) %s %s,\n\r",$c['column_name'],$c['data_type'],$c['character_maximum_length'],($c['is_nullable']=='YES')?null:"NOT NULL",($c['column_default']=='')?null:"DEFAULT ".$c['column_default']);
                break;
            case "double precision":
                $string = sprintf("\t%s\t%s(%s,%s) %s %s,\n\r",$c['column_name'],$c['data_type'],$c['numeric_precision'],$c['numeric_precision_radix'],($c['is_nullable']=='YES')?null:"NOT NULL",($c['column_default']=='')?null:"DEFAULT ".$c['column_default']);
                break;
            case "date":
            case "time without time zone":
            case "time with time zone":
            case "integer":
            case "boolean":
            case "text":
            default:
                $string = sprintf("\t%s\t%s %s %s,\n\r",$c['column_name'],$c['data_type'],($c['is_nullable']=='YES')?null:"NOT NULL",($c['column_default']=='')?null:"DEFAULT ".$c['column_default']);
                break;
        }
        return $string;
    }
}
