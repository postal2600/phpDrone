<?php
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
        return $template->getBuffer();
    }

    function write_password($template)
    {
        if (array_key_exists($this->name,$_POST))
            $template->write("inputValue",$_POST[$this->name]);
        return $template->getBuffer();
    }

    function write_textarea($template)
    {
        if (array_key_exists($this->name,$_POST))
            $template->write("inputValue",$_POST[$this->name]);
        else
            $template->write("inputValue",$this->def);
        return $template->getBuffer();
    }

    function write_select($template)
    {
        if (gettype($this->validator['regExp'])=="array")
        {
            $values = array();
            $pas=0;
            $hasSelected = False;
            foreach ($this->validator['regExp'] as $key=>$value)
            {
                $values[$pas] = array();
                $values[$pas]["key"] = $key;
                $values[$pas]["value"] = $value;
                if ((array_key_exists($this->name,$_POST) && ($value==$_POST[$this->name])) || $value==$this->def)
                {
                    $values[$pas]["selected"] = "selected='selected'";
                    $hasSelected = True;
                }
                $pas++;
            }
            if (!$hasSelected)
                $values[0]["selected"] = "selected='selected'";
            $template->write("values",$values);
        }
        return $template->getBuffer();
    }

    function write_file($template)
    {
        // al what is needed for tyhis input, is handeled in the write() method
        // (the value can't be set for a file input). So we return the buffer back.
        return $template->getBuffer();
    }

    function write($upperTemplate="")
    {
        if (method_exists($this,"write_{$this->type}"))
        {
            $template = new Template("?form/input_{$this->type}.tmpl");
            $template->vars = $upperTemplate->vars;
            $template->write("inputLabel",$this->label);
            $template->write("inputName",$this->name);
            if ($this->error)
                $template->write("inputError",$this->error);

            eval("\$result .= \$this->write_{$this->type}(\$template);");
        }
        else
            die("<b>phpDrone error:</b> Unknown form input type: {$this->type}.");
        return $result;
    }

    function writeValueless($upperTemplate="")
    {
        $_POST[$this->name] = "";
        return $this->write($upperTemplate);
    }


    function setValidator($validator,$msg)
    {
        $this->validator=array('regExp'=>$validator,'message'=>$msg);
    }

    function validate()
    {
        if ($this->type=="select")
        {
            foreach ($this->validator['regExp'] as $key=>$value)
            {
                if ($_POST[$this->name]==$value)
                    return true;
            }
            if ($this->mandatory && $_POST[$this->name]=="")
            {
                $this->error = "Choose one";
                return false;
            }
            else
                return true;
            
        }
        if ($this->mandatory && strlen($_POST[$this->name])==0)
        {
            $this->error = "Can't be ampty";
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
?>
