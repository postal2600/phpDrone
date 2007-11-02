<?php
require('phpDrone/Utils.php');
class Database
{
    function __construct($table="")
    {
        require ("_droneSettings.php");
        if (isset($sqlEngine)&&$sqlEngine=="mysql")
        {
            $this->con = mysql_connect($sqlServer, $sqlUser, $sqlPassword) or die("<br />phpDrone error: Can't start connection to the database. SQL said: <b>".mysql_error()."</b><br />Please check your <b>_droneSettings.php</b> file.");
            mysql_select_db($sqlDatabase,$this->con) or die("phpDrone error: Can't find database <b>{$sqlDatabase}</b> on server <b>{$sqlServer}</b>.<br />Please check your <b>_droneSettings.php</b> file.");
            $this->tableName = $table;
            $this->tableExists = true;
            if ($table!="")
            {
                $qry =$this->exec_qry("show tables like '{$table}';");
                if (mysql_num_rows($qry)==1)
                {
                    $qry = $this->exec_qry("SELECT * from {$table};");
                    $numFields = mysql_num_fields($qry);
                    for($f=0;$f<$numFields;$f++)
                        if (preg_match('/primary_key/',mysql_field_flags($qry, $f)))
                            $this->id = mysql_field_name($qry, $f);
                        else
                            eval("\$this->types['".mysql_field_name($qry, $f)."'] = ".mysql_field_type($qry, $f).";");
                    mysql_free_result($qry);
                    $this->data = array();
                }
                else
                    $this->tableExists = false;
            }
        }
        else
            die("phpDrone error: sqlEngine not defined in <b>_droneSettings.php</b>.");

    }

    function addField($name,$type,$flags)
    {

    }

    private function exec_qry($query)
    {
        $qry =mysql_query($query,$this->con) or die("phpDrone error: Database querry error: <b>".mysql_error()."</b>.");
        return $qry;
    }

    function save()
    {
        $tableData = $this->getData();
        $toInsert = array_diff_key($this->data,$tableData);
        $toUpdate = array_intersect_key($this->data,$tableData);
        
        foreach (array_keys($toInsert) as $item)
        {
            $insertKeys = join(",",array_keys($toInsert[$item]));
            $insertDataString = "('{$item}','".join("','",$toInsert[$item])."')";
            $this->exec_qry("INSERT INTO {$this->tableName} ({$this->id},{$insertKeys}) VALUES {$insertDataString};");
        }
        
        

//         //UPDATE `test` SET `test1` = 'Duis porttitor elita',`test2` = 'Integer fringilla. In a' WHERE CONVERT( `id` USING utf8 ) = 'aa' LIMIT 1 ;  n times
//         $this->exec_qry("UPDATE {$this->tableName} SET {$updateDataString} WHERE {$this->id}='{$id}' LIMIT 1;");
    }

    private function __call($method, $args)
    {
        if ($method=="setData")
        {
            if (count($args)==2)
            {
                $key = $args[0];
                $value = $args[1];
            }
            else
            if (count($args)==3)
            {
                $id = $args[0];
                $key = $args[1];
                $value = $args[2];
            }
            else
                die("phpDrone error: Method Database->".$method."() recieves only 2 or 3 arguments. Supplied: ".count($args).".");

            if (!isset($id))
            {
                $id = genRandomString(22);
            }
            
            if (isset($this->types[$key]))
                $this->data[$id][$key] = $value;
            else
                die("phpDrone error: Trying to set unknown field <b>{$key}</b> on table <b>{$this->tableName}</b>!");
        }
        else
            die("phpDrone error: Call to undefined method Database->".$method."()");
    }

    function getData($filter="",$sql="")
    {
        $result = array();
        if ($filter)
            $whereFilter = "WHERE ".$filter;
        else
            $whereFilter = "";

        if ($sql)
            $query = $sql;
        else
            $query = "SELECT * FROM {$this->tableName} {$whereFilter};";

        $qry =$this->exec_qry($query);
        while ($row = mysql_fetch_assoc($qry))
        {
            if (isset($this->id))
                $index = $row[$this->id];
            else
                $index = count($result);
            $result[$index] = $row;
        }
        mysql_free_result($qry);
        return $result;
    }

    function __destruct()
    {
        mysql_close($this->con);
    }

}


?>
