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
                    {
                        if (preg_match('/primary_key/',mysql_field_flags($qry, $f)))
                            $this->id = mysql_field_name($qry, $f);
                        eval("\$this->types['".mysql_field_name($qry, $f)."'] = ".mysql_field_type($qry, $f).";");
                    }
                    if (!isset($this->id))
                        die("phpDrone error: Table <b>{$sqlServer}.{$this->tableName}</b> has no primary key.");
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
        
        foreach (array_keys($toUpdate) as $item)
        {
            $toBuild = array();
            foreach ($toUpdate[$item] as $key => $value)
            {
                array_push($toBuild,"{$key} = '{$value}'");
            }
            $updateDataString = join(",",$toBuild);
            $this->exec_qry("UPDATE {$this->tableName} SET {$updateDataString} WHERE {$this->id}='{$item}' LIMIT 1;");
        }
    }

    private function getUniqueKey($size)
    {
        $result = genRandomString($size);
        while (mysql_num_rows($this->exec_qry("SELECT * FROM {$this->tableName} WHERE {$this->id}='{$result}'"))>0)
            $result = genRandomString($size);
        return $result;
    }

    private function __call($method, $args)
    {
        if ($method=="setData")
        {
            $vars = array();
            foreach($args as $item)
            {
                $data = explode("=",$item);
                if (count($data)!=2)
                    die("phpDrone error: Illegal argument supplied to <b>setData</b>: {$item}");
                else
                    $vars[trim($data[0])] = trim($data[1]);
            }

            if (!isset($vars[$this->id]))
                $id = $this->getUniqueKey(22);
            else
            {
                $id = $vars['id'];
                unset($vars['id']);
            }
            
            foreach ($vars as $key => $value)
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
