<?php
//workaround for captcha
$inputClassIsIncluded = True;
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
        if ($this->validator['regExp'])
        foreach (array_keys($this->validator['regExp']) as $item)
        {
            $template->write("inputValue","<option value='{$item}'");
            if ((array_key_exists($this->name,$_POST) && ($item==$_POST[$this->name])) || $item==$this->def)
                $template->write("inputValue","selected='selected'");
            $template->write("inputValue",">{$this->validator['regExp'][$item]}</option>");
        }
        return $template->getBuffer();
    }

    function write_file($template)
    {
        // al what is needed for tyhis input, is handeled in the write() method
        // (the value can't be set for a file input). So we return the buffer back.
        return $template->getBuffer();
    }

    function write()
    {
        $template = new Template("phpDrone/templates/form/input_{$this->type}.tmpl");
        $template->write("inputLabel",$this->label);
        $template->write("inputName",$this->name);
        if ($this->error)
            $template->write("inputError",$this->error);

        eval("\$result .= \$this->write_{$this->type}(\$template);");
        return $result;
    }

    function writeValueless()
    {
        $_POST[$this->name] = "";
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
