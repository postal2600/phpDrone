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
        //used only for selects. determines what was the default value set, to check if changed in case of a mandatory field
        $this->initial = null;
    }

    function setRequestData(&$reqData)
    {
        $this->request = $reqData;
    }

    function setMaxSize($size)
    {
        $this->maxSize = intval($size);
    }

    function setDefault($defVal)
    {
        if (!isset($this->defaultValue))
        {
            if ($this->type=="select")
                foreach ($defVal as $item)
                    if (isset($item[2]) && $item[2])
                        $this->initial = $item[0];
            $this->defaultValue = $defVal;
        }
    }

    function write_text($template)
    {
        if (array_key_exists($this->name,$this->request))
            $template->write("inputValue",$this->request[$this->name]);
        else
            $template->write("inputValue",$this->defaultValue);
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
            $template->write("inputValue",$this->defaultValue);
        return $template->getBuffer();
    }

    function write_select($template)
    {
        if (isset($this->defaultValue))
        {
            $values = array();
            $pas=0;
            $hasSelected = False;
            $safeChars = get_html_translation_table(HTML_ENTITIES);
            foreach ($this->defaultValue as $item)
            {
                $values[$pas] = array();
                if (gettype($item)=="array")
                {
                    $values[$pas]["key"] = $item[0];
                    $values[$pas]["value"] = strtr($item[1],$safeChars);
                }
                else
                {
                    $values[$pas]["key"] = $item;
                    $values[$pas]["value"] = strtr($item,$safeChars);
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
        if (isset($this->defaultValue))
        {
            $values = array();
            $pas=0;
            $safeChars = get_html_translation_table(HTML_ENTITIES);
            foreach ($this->defaultValue as $item)
            {
                $values[$pas] = array();
                $values[$pas]["key"] = $item[0];
                $values[$pas]["value"] = strtr($item[1],$safeChars);
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
        if (isset($this->defaultValue))
        {
            $values = array();
            $pas=0;
            $hasSelected = False;
            $safeChars = get_html_translation_table(HTML_ENTITIES);
            foreach ($this->defaultValue as $item)
            {
                $values[$pas] = array();
                $values[$pas]["key"] = $item[0];
                $values[$pas]["value"] = strtr($item[1],$safeChars);
                if ((array_key_exists($this->name,$this->request) && ($values[$pas]["key"]==$this->request[$this->name])) || (isset($item[2]) && $item[2]))
                {
                    $values[$pas]["selected"] = True;
                    $hasSelected = True;
                }
                $pas++;
            }

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

            if ($this->type=="select")
                if ($this->mandatory && $this->request[$this->name]==$this->initial)
                {
                    $this->error = "Choose one";
                    return false;
                }

            $meth = $this->validator['regExp'];
            $validatorResult = True;
            
            if (function_exists($meth))
            {
                if ($meth($this->request)!=True)
                {
                    $this->error = $this->validator['message'];
                    $validatorResult = false;
                }
            }

            foreach ($this->defaultValue as $item)
            {
                if (gettype($item)=="array")
                    $key=$item[0];
                else
                    $key = $item;
                if (gettype($this->request[$this->name])=="array")
                {
                    if (in_array($key,$this->request[$this->name]) && $validatorResult)
                        return true;
                }
                else
                    if ($this->request[$this->name]==$key && $validatorResult)
                        return true;
            }
            
            $this->error = "Invalid value";
            return false;
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
            if ($meth($this->request)!=True)
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
