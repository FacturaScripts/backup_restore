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
        $conn = new PDO('mysql:host='.$db->host.';port='.$db->port.';dbname='.$db->dbname, $db->user, $db->pass, array( PDO::ATTR_PERSISTENT => false, PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8' ));
        return $conn;
    }

    public function createSystemBackup($db){
        if($db->dbname){
            $this->destino = $db->backupdir.DIRECTORY_SEPARATOR.$db->dbms.'_'.$db->dbname.'_'.$db->year.$db->month.$db->day.'.zip';
            $this->filename = $this->tempdir.DIRECTORY_SEPARATOR.$db->dbms.'_'.$db->dbname.'_'.$db->year.$db->month.$db->day.'.sql';
            $access_file = $this->tempdir.DIRECTORY_SEPARATOR.'dbaccess.cnf';
            $fp = fopen($this->filename,"w");
            fputs($fp,sprintf("%s\n\r","SET AUTOCOMMIT=0;"));
            fputs($fp,sprintf("%s\n\r","SET FOREIGN_KEY_CHECKS=0;"));
            fclose($fp);
            $fp = fopen($access_file,"w+");
            fputs($fp, sprintf("%s","[client]\n"));
            fputs($fp, sprintf("user=%s",$db->user."\n"));
            fputs($fp, sprintf("password=%s",$db->pass."\n"));
            fclose($fp);
            exec("{$db->command} --defaults-extra-file={$access_file} -h {$db->host} --databases {$db->dbname} --add-drop-database --add-drop-table >> {$this->filename} 2>&1",$cmdout);
            if(empty($cmdout)){
                $fp = fopen($this->filename,"a");
                fputs($fp,sprintf("%s\n\r","SET FOREIGN_KEY_CHECKS=1;"));
                fputs($fp,sprintf("%s\n\r","COMMIT;"));
                fputs($fp,sprintf("%s\n\r","SET AUTOCOMMIT=1;"));
                fclose($fp);
                //Comprimimos el Backup y lo mandamos a su detino
                $zip = new \ZipArchive();
                $zip->open($this->destino, \ZipArchive::CREATE);
                $zip->addFile($this->filename);
                $zip->close();
                unlink($this->filename);
                unlink($access_file);
            }
            return (!empty($cmdout))?$cmdout[0]:$this->destino;
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
                    $listaColumnas[] = $column['Field'];
                    fputs($this->file, sprintf("%s",$this->constructColumn($column)), 1024);
                }
                fputs($this->file, sprintf(");\n\r"),1024);
                //Copiamos la información de la tabla
                fputs($this->file, sprintf("COPY %s (%s) FROM stdin;\n\r",$t['table_name'], implode(",",$listaColumnas)),1024);
                $datosTabla = $this->mysqlCopyToArray($t['table_name'],"\t","\\N",implode(",",$listaColumnas));
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

    public function mysqlCopyToArray($table,$separator,$null,$fields){
        $query = $this->conn->query("SELECT ($fields) from $table");
        $prestm = $query->fetchAll();
        $resultados = array();
        if($prestm){
            foreach($prestm as $item){
                $line = "";
                foreach($item as $column){
                    $column = (!empty($column))?$item:(isset($null))?$null:$column;
                    $line.=sprintf("%s%s",$column,$separator);
                }
                $line.="\n\r";
                $resultados[] = $line;
            }
        }
        return $resultados;
    }

    public function tableBackup(){

    }

    public function tableList(){
        $query = $this->conn->query("SHOW TABLES");
        $prestm = $query->fetchAll(\PDO::FETCH_COLUMN);
        $stm = array();
        if($prestm){
            foreach($prestm as $line){
                $stm['table_name'] = $line[0];
            }
        }
        return $stm;
    }

    public function tableColumns($tableName){
        $query = $this->conn->query("DESCRIBE $tableName");
        $stm = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $stm;
    }

    public function constructColumn($c){
        $string = sprintf("\t%s\t%s %s %s,\n\r",
                $c['Field'],$c['Type'],($c['Null']=='YES')?"NULL":"NOT NULL",($c['Default']=='NULL')?'':"DEFAULT ".$c['Default']);
        return $string;
    }

    public function restoreSystemBackup($db,$fileBackup){
        $file_info = $this->fileInfo($fileBackup);
        $tmp_file = '';
        $cmdout = null;
        if($file_info=='sql'){
            $tmp_file = $this->tempdir.DIRECTORY_SEPARATOR.$fileBackup;
            copy($fileBackup, $tmp_file);
        }elseif($file_info=='zip'){
            $zip = new \ZipArchive();
            if ($zip->open($fileBackup) === TRUE) {
                $backup = $zip->getNameIndex(0);
                $fileinfo = pathinfo($backup);
                $tmp_file = $this->tempdir.DIRECTORY_SEPARATOR.$fileinfo['basename'];
                copy("zip://".$fileBackup."#".$backup, $tmp_file);
                $zip->close();
            }
        }
        if(!empty($tmp_file)){
            //Creamos el archivo con el usuario y la clave temporalmente
            $access_file=$this->tempdir.DIRECTORY_SEPARATOR.'dbaccess.cnf';
            $fp = fopen($access_file,"w+");
            fputs($fp, sprintf("%s","[client]\n"));
            fputs($fp, sprintf("user=%s",$db->user."\n"));
            fputs($fp, sprintf("password=%s",$db->pass."\n"));
            fclose($fp);
            $launchparam = "{$db->command} --defaults-extra-file={$access_file} -h {$db->host} -D {$db->dbname} < {$tmp_file} 2>&1";
            exec($launchparam,$cmdout);
            if(file_exists($tmp_file)) {
               unlink($tmp_file);
               unlink($access_file);
            }
            return (!empty($cmdout))?$cmdout[0]:$cmdout;
        }else{
            return 'No se encuentra la ruta del archivo';
        }

    }

    private function fileInfo($file){
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $information = finfo_file($finfo, $file);
        finfo_close($finfo);
        if($information == 'text/plain'){
            return 'sql';
        }elseif($information == 'application/zip'){
            return 'zip';
        }else{
            return false;
        }
    }
}
