<?php
//workaround for captha
$formIsLoaded = True;
class Input
{
    function __construct($label,$type,$name)
    {
        if (substr($label,0,1)=="*")
        {
            $this->label = "<span class='mandatoryMarker'>*</span>".substr($label,1);
            $this->mandatory = true;
        }
        else
        {
            $this->label = $label;
            $this->mandatory = false;
        }
        $this->type = $type;
        $this->name = $name;
        $this->validator = null;
        $this->error = "";
        $this->def="";
    }
    
    function write_text($template)
    {
        if (array_key_exists($this->name,$_POST))
            $template->write("inputValue",$_POST[$this->name]);
        else
            $template->write("inputValue",$this->def);
        return $template->getBuffer(true);
    }

    function write_password($template)
    {
        if (array_key_exists($this->name,$_POST))
            $template->write("inputValue",$_POST[$this->name]);
        return $template->getBuffer(true);
    }

    function write_textarea()
    {
        $result = "";
        $result .= "<textarea rows='5' cols='9' id='{$this->name}' name='{$this->name}' class='textInput'>";
        if (array_key_exists($this->name,$_POST))
            $result .= $_POST[$this->name];
        else
            $result .= $this->def;
        $result .= "</textarea>";
        return $result;
    }

    function write_select()
    {        
        $result = "";
        $result .= "<select id='{$this->name}' name='{$this->name}' class='select'>";
//         $keys =array_keys($this->valueDict);
        foreach (array_keys($this->validator['regExp']) as $item)
        {
            $result .= "<option value='{$item}'";
            if ((array_key_exists($this->name,$_POST) && ($item==$_POST[$this->name])) || $item==$this->def)
                $result .= "selected='selected'";
            $result .= ">{$this->validator['regExp'][$item]}</option>";
        }
        $result .= "</select>";
        return $result;
    }

    function write_file()
    {
        return "<input class='textInput' type='file' id='{$this->name}' name='{$this->name}' />";
    }

    function write()
    {
        $template = new Template("phpDrone/templates/form/input_{$this->type}.tmpl");
        $template->write("inputLabel",$this->label);
        $template->write("inputName",$this->name);
        $template->write("inputType",$this->type);
        if ($this->error)
            $template->write("inputError","<div class='error'><span>Error:</span> {$this->error}</div>");
        
        eval("\$result .= \$this->write_{$this->type}(\$template);");
        return $result;
    }

    function writeValueless()
    {
        $this->valueDict[$this->name] = "";
        return $this->write();
    }


    function setValidator($validator,$msg)
    {
        $this->validator=array('regExp'=>$validator,'message'=>$msg);
    }
    
    function validate()
    {
        if ($this->type=="select")
        {
            foreach (array_keys($this->validator['regExp']) as $item)
            {
                if ($_POST[$this->name]==$item )
                    return true;
            }
            $this->error = $this->validator['message'];
            return false;
        }
        if ($this->mandatory && strlen($_POST[$this->name])==0)
        {
            $this->error = "Nu poate fi gol";
            return false;
        }
        if (!$this->mandatory && strlen($_POST[$this->name])==0)
        {
            return true;
        }
        $meth = $this->validator['regExp'];
        
        if (function_exists($meth))
        {            
            if ($meth()!=True)
            {                                
                $this->error = $this->validator['message'];
                return false;
            }            
            return true;
        }
        else 
        if ($this->validator!=null)            
            if (!preg_match ($this->validator['regExp'],$_POST[$this->name]))
            {
                $this->error= $this->validator['message'];
                return false;
            }
        return true;
    }
}

class Form
{

    function __construct($onSuccess,$defaults)
    {
        $this->onSuccess = $onSuccess;
        $this->inputs = array();
        $this->defaults =$defaults;
    }
    
    
    function addInput($label,$type,$name,$validator)
    {
        $len = count($this->inputs);
        $this->inputs[$len] = new Input($label,$type,$name);
        
        if (array_key_exists($name,$this->defaults))
            $this->inputs[$len]->def = $this->defaults[$name];


        if ($validator!="")
            if (!is_array($validator) || function_exists($validator))
                $this->inputs[$len]-> setValidator($validator,"Invalid ".strtolower($label));
            else
                $this->inputs[$len]-> setValidator($validator,"Invalid value");
    }
    
    function addCreatedInput($input)
    {
        $len = count($this->inputs);
        $this->inputs[$len] = $input;
        $input->addedLater = true;        
    }


    function printIt()
    {
        $isValid = true;
        $txtresult = "";
        if (count($_POST)!=0)
        {
            foreach ($this->inputs as $item)
            {
                $result = $item->validate($_POST);
                if (!$result)-
                    $isValid = false;
            }
            
            if ($isValid)
            {
                $this->valueFlag = true;
                $meth = $this->onSuccess;
                $txtresult .= $meth();
            }
        }
        
        foreach ($this->inputs as $item)
            if (isset($this->valueFlag))
                $txtresult .= $item->writeValueless();
            else
                $txtresult .= $item->write();
        return $txtresult;
    }


    function argPrepare() //neimplementata corect
    {
        $result = array();
        $recived = $_SERVER['QUERY_STRING'];
        $parr = explode('&', $recived);
        foreach ($parr as $param)
        {
            list($key, $val) = explode('=', $param);
            if (!isset($result[$key]))
            {
                $result[$key]=array();
                $result[$key][0]=$val;
            }
            else
                $result[$key][count($result[$key])]=$val;
        }
    return $result;

    }
}


?>
