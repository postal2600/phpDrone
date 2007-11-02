<?php
class Input
{
    function __construct($label,$type,$name,$mandatoryMarker="*")
    {
        if (substr($label,0,strlen($mandatoryMarker))==$mandatoryMarker)
        {
            $this->label = substr($label,strlen($mandatoryMarker));
            $this->mandatory = true;
            
            $this->mandatoryMarker = $mandatoryMarker;
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

    function setRequestData(&$reqData)
    {
        $this->request = $reqData;
    }

    function setMaxSize($size)
    {
        $this->maxSize = intval($size);
    }

    function write_text($template)
    {
        if (array_key_exists($this->name,$this->request))
            $template->write("inputValue",$this->request[$this->name]);
        else
            $template->write("inputValue",$this->def);
        if (isset($this->maxSize))
            $template->write("maxlength",$this->maxSize);
        return $template->getBuffer();
    }

    function write_password($template)
    {
        if (array_key_exists($this->name,$this->request))
            $template->write("inputValue",$this->request[$this->name]);
        if (isset($this->maxSize))
            $template->write("maxlength",$this->maxSize);
        return $template->getBuffer();
    }

    function write_textarea($template)
    {
        if (array_key_exists($this->name,$this->request))
            $template->write("inputValue",$this->request[$this->name]);
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
            foreach ($this->validator['regExp'] as $item)
            {
                $values[$pas] = array();
                if (gettype($item)=="array")
                {
                    $values[$pas]["key"] = $item[0];
                    $values[$pas]["value"] = $item[1];
                }
                else
                {
                    $values[$pas]["key"] = $item;
                    $values[$pas]["value"] = $item;
                }
                if ((array_key_exists($this->name,$this->request) && ($values[$pas]["key"]==$this->request[$this->name])) || (gettype($item)=="array" && isset($item[2]) && $item[2]))
                {
                    $values[$pas]["selected"] = True;
                    $hasSelected = True;
                }
                $pas++;
            }

            if (!$hasSelected)
                $values[0]["selected"] = True;
                
            $template->write("values",$values);
        }
        return $template->getBuffer();
    }

    function write_file($template)
    {
        // al what is needed for this input, is handeled in the write() method
        // (the value can't be set for a file input). So we return the buffer back.
        return $template->getBuffer();
    }

    function write_checkbox($template)
    {
        if (gettype($this->validator['regExp'])=="array")
        {
            $values = array();
            $pas=0;
            foreach ($this->validator['regExp'] as $item)
            {
                $values[$pas] = array();
                $values[$pas]["key"] = $item[0];
                $values[$pas]["value"] = $item[1];
                if (array_key_exists($this->name,$this->request) && in_array($values[$pas]["key"],$this->request[$this->name]) || isset($item[2]) && $item[2])
                    $values[$pas]["selected"] = True;
                $pas++;
            }
            
            $template->write("values",$values);
        }
        return $template->getBuffer();
    }

    function write_radio($template)
    {
        if (gettype($this->validator['regExp'])=="array")
        {
            $values = array();
            $pas=0;
            $hasSelected = False;
            foreach ($this->validator['regExp'] as $item)
            {
                $values[$pas] = array();
                $values[$pas]["key"] = $item[0];
                $values[$pas]["value"] = $item[1];
                if ((array_key_exists($this->name,$this->request) && ($values[$pas]["key"]==$this->request[$this->name])) || (isset($item[2]) && $item[2]))
                {
                    $values[$pas]["selected"] = True;
                    $hasSelected = True;
                }
                $pas++;
            }

            if (!$hasSelected)
                $values[0]["selected"] = True;

            $template->write("values",$values);
        }
        return $template->getBuffer();
    }

    function write_hidden($template)
    {
        $template->write("inputValue",$this->validator['regExp']);
        return $template->getBuffer();
    }

    function write_submit($template)
    {
        $template->write("inputValue",$this->validator['regExp']);
        return $template->getBuffer();
    }



    function write($upperTemplate="")
    {
        if (method_exists($this,"write_{$this->type}"))
        {
            $template = new Template("?form/input_{$this->type}.tmpl");
            $template->vars = $upperTemplate->vars;
            $safeChars = get_html_translation_table(HTML_ENTITIES);
            $template->write("inputLabel",strtr($this->label,$safeChars));
            $template->write("inputName",$this->name);
            if ($this->mandatory)
                $template->write("mandatoryMarker",$this->mandatoryMarker);
            if ($this->error)
                $template->write("inputError",$this->error);

            eval("\$result .= \$this->write_{$this->type}(\$template);");
        }
        else
            throwDroneError("Unknown form input type: {$this->type}.");
        return $result;
    }

    function writeValueless($upperTemplate="")
    {
        $this->request[$this->name] = "";
        return $this->write($upperTemplate);
    }


    function setValidator($validator,$msg=false)
    {
        if (!$msg)
            $msg = "";
        $this->validator=array('regExp'=>$validator,'message'=>$msg);
    }

    function validate()
    {
        if ($this->type=="select" || $this->type=="checkbox" || $this->type=="radio")
        {
            foreach ($this->validator['regExp'] as $key=>$value)
            {
                if ($this->request[$this->name]==$value)
                    return true;
            }
            if ($this->mandatory && $this->request[$this->name]=="")
            {
                $this->error = "Choose one";
                return false;
            }
            else
                return true;
            
        }
        if ($this->mandatory && strlen($this->request[$this->name])==0)
        {
            $this->error = "Can't be empty";
            return false;
        }
        if (!$this->mandatory && strlen($this->request[$this->name])==0)
        {
            return true;
        }
        if (isset($this->maxSize) && strlen($this->request[$this->name])>$this->maxSize)
        {
            $this->error = "Max length for input is {$this->maxSize}";
            return false;
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
        if ($this->validator!=null && $this->type!="hidden" && $this->type!="submit")
            if (!preg_match ($this->validator['regExp'],$this->request[$this->name]))
            {
                $this->error= $this->validator['message'];
                return false;
            }
        return true;
    }
}
?>
