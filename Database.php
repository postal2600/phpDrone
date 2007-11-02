<?php
require('phpDrone/Utils.php');

class DBField
{
    function __construct($descArray)
    {
        foreach ($descArray as $key=>$value)
            eval("\$this->".$key." = ".$value.";");
        if (gettype($this->type)!="string")
            die("phpDrone error: The type of database field <b>{$this->name}</b> must be a string. Recieved: ".gettype($this->type));
        if ($this->null)
            $this->null = "NULL";
        else
            $this->null = "NOT NULL";
        //for the followinf ifs i must find another way of approach
        if ($this->unsigned)
            $this->unsigned = "UNSIGNED";
        if ($this->auto_increment)
            $this->auto_increment = "AUTO_INCREMENT";
        if ($this->primary)
            $this->primary = "PRIMARY KEY";
    }

    function getTextForString()
    {
        if (!$this->size)
            die("phpDrone error: Please supply a size argument for database field '<b>{$this->name}</b>'.");
        $template = new Template("?database/field_{$this->type}.tmpl");
        $template->write("fieldName",$this->name);
        $template->write("fieldSize",$this->size);
        $template->write("fieldNull",$this->null);
        $template->write("fieldDefault",$this->default);
        return $template->getBuffer();
    }

    function getTextForInt()
    {
        $template = new Template("?database/field_{$this->type}.tmpl");
        $template->write("fieldName",$this->name);
        $template->write("fieldSize",$this->size);
        $template->write("fieldNull",$this->null);
        $template->write("fieldDefault",$this->default);
        $template->write("fieldUnsigned",$this->unsigned);
        $template->write("fieldAutoIncrement",$this->auto_increment);
        $template->write("primary",$this->primary);
        return $template->getBuffer();
    }

    function getCreateText()
    {
        $this->type[0] = strtoupper($this->type[0]);
        if (method_exists($this,"getTextFor{$this->type}"))
            eval("\$result = \$this->getTextFor".$this->type."();");
        else
            die("phpDrone error: Unknown database field type: {$this->type}.");
        return $result;
    }
}

class Database
{
    function __construct($table)
    {
        require ("_droneSettings.php");
        if (isset($sqlEngine)&&$sqlEngine=="mysql")
        {
            $this->con = mysql_connect($sqlServer, $sqlUser, $sqlPassword) or die("<br />phpDrone error: Can't start connection to the database. SQL said: <b>".mysql_error()."</b><br />Please check your <b>_droneSettings.php</b> file.");
            mysql_select_db($sqlDatabase,$this->con) or die("phpDrone error: Can't find database <b>{$sqlDatabase}</b> on server <b>{$sqlServer}</b>.<br />Please check your <b>_droneSettings.php</b> file.");
            $this->tableName = $table;
            $this->tableExists = true;

            $qry =$this->exec_qry("show tables like '{$table}';");
            if (mysql_num_rows($qry)==1)
            {
                $qry = $this->exec_qry("SELECT * from {$table};");
                $numFields = mysql_num_fields($qry);

                $this->fields = array();

                for($f=0;$f<$numFields;$f++)
                {
                    if (preg_match('/primary_key/',mysql_field_flags($qry, $f)))
                        $this->id = mysql_field_name($qry, $f);
                    eval("\$this->types['".mysql_field_name($qry, $f)."'] = ".mysql_field_type($qry, $f).";");
                }
                if (!isset($this->id))
                    die("phpDrone error: Table <b>{$sqlServer}.{$sqlDatabase}.{$this->tableName}</b> has no primary key.");
                mysql_free_result($qry);
                $this->data = array();
            }
            else
                $this->tableExists = false;
            return $this->tableExists;
        }
        else
            die("phpDrone error: sqlEngine not defined in <b>_droneSettings.php</b>.");

    }

    private function exec_qry($query)
    {
        $qry =mysql_query($query,$this->con);
        if (!$qry)
            die("Querry was:<br /><b>".$query."</b><br /><br />phpDrone error: Database querry error:<br /> <b>".mysql_error()."</b>.");
        return $qry;
    }

    function save()
    {
        if ($this->tableExists)
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
        else
        {
            $txtFields = array();
            foreach($this->fields as $field)
                array_push($txtFields,$field->getCreateText());

            $template = new Template("?database/qry_create_table.tmpl");
            $template->write("tableName",$this->tableName);
            $template->write("tableFields",join(",",$txtFields));
            $this->exec_qry($template->getBuffer());
//             print $template->getBuffer();
        }
    }

    private function getUniqueKey($size)
    {
        $result = genRandomString($size);
        while (mysql_num_rows($this->exec_qry("SELECT * FROM {$this->tableName} WHERE {$this->id}='{$result}'"))>0)
            $result = genRandomString($size);
        return $result;
    }


    private function addField_p($args)
    {
        if (!$this->tableExists)
        {
            if (count($args)==1)
            {
                if (array_get("name",$args[0]))
                    $this->fields[array_get("name",$args[0])] = new DBField($args[0]);
                else
                    die("phpDrone error: You try to add an database field withouth a name.");
            }
            else
                if (count($args)==2)
                {
                    $this->fields[$args[0]] = new DBField(array("name"=>$args[0],"type"=>$args[1]));
                }
                else
                    die("phpDrone error: Method <b>{$method}</b> recienes 1 or 2 arguments. Recieved:".count($args));
        }
        else
            die("phpDrone error: You tried to add a field to existing table '<b>{$this->tableName}</b>'");
    }


    private function setData_p($args)
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
        return $id;
    }

    private function __call($method, $args)
    {
        if (method_exists($this,$method."_p"))
            eval("\$this->".$method."_p(\$args);");
        else
            die("phpDrone error: Call to undefined method <b>Database->".$method."()</b>");
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
