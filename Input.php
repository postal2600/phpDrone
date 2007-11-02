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
        $this->validator = null;
        $this->error = "";
        $this->initial = null;
        
        $this->allowHtml = False;
        
        $this->attributes = array("name"=>$name,"id"=>$name,"class"=>"textInput");
        if ($type=="submit")
            $this->setAttribute("class","buttonInput");
		elseif ($type=="radio")
		    $this->setAttribute("class","radioButton");
		elseif ($type=="checkbox")
		    $this->setAttribute("class","checkBox");
		elseif ($type=="select")
		    $this->setAttribute("class","selectInput");

    }

    function setRequestData(&$reqData)
    {
        $this->request = $reqData;
    }

    function setDefault($defVal)
    {
        if (!isset($this->defaultValue))
        {
            if ($this->type=="select" || $this->type=="radio")
            {
                $isSelected = false;
                for($f=0;$f<count($defVal);$f++)
                {
                    $valType = gettype($defVal[$f]);
					if ($valType=='array')
					    $defaultSetValue = $defVal[$f][0];
					else
					    $defaultSetValue = $defVal[$f];
	                if (isset($this->initial) && ($defaultSetValue==$this->initial))
	                {
	                    unset($this->initial);
	                    $isSelected = true;
	                    if ($valType=="array")
	                    {
	                        $s_value = Utils::array_get(0,$defVal[$f],"");
	                        $s_text = Utils::array_get(1,$defVal[$f],$s_value);
                        	$defVal[$f] = array($s_value,$s_text,True);
						}
						else
						{
						    $defVal[$f] = array($defVal[$f],$defVal[$f],True);
	  					}
					}
					elseif ($valType=="array" && isset($defVal[$f][2]) && $defVal[$f][2] && !$isSelected)
					    $coderDefaultPos = $f;
				}
			}
            if (isset($coderDefaultPos) && !$isSelected)
            {
                $valType = gettype($defVal[$coderDefaultPos]);
                if ($valType=="array")
                	$this->initial = $defVal[$coderDefaultPos][0];
				else
			    	$this->initial = $defVal[$coderDefaultPos];
			}
            $this->defaultValue = $defVal;
        }
    }

    function setAttribute($attr,$value)
    {
        if ($attr != "name")
        	$this->attributes[$attr] = $value;
    }

    function removeAttribute($attr)
    {
        if ($attr != "name")
        	unset($this->attributes[$attr]);
    }

	function allowHTML($bool=True)
	{
		$this->allowHtml = $bool;
	}

    function write_text($template)
    {
        if (array_key_exists($this->attributes['name'],$this->request))
            $template->write("inputValue",htmlspecialchars($this->request[$this->attributes['name']],ENT_QUOTES));
        else
            $template->write("inputValue",htmlspecialchars($this->defaultValue,ENT_QUOTES));
        return $template->getBuffer();
    }

    function write_password($template)
    {
        if (array_key_exists($this->attributes['name'],$this->request))
            $template->write("inputValue",htmlspecialchars($this->request[$this->attributes['name']],ENT_QUOTES));
        else
            $template->write("inputValue",htmlspecialchars($this->defaultValue,ENT_QUOTES));
        return $template->getBuffer();
    }

    function write_textarea($template)
    {
        if (array_key_exists($this->attributes['name'],$this->request))
            $template->write("inputValue",htmlspecialchars($this->request[$this->attributes['name']],ENT_QUOTES));
        else
            $template->write("inputValue",htmlspecialchars($this->defaultValue,ENT_QUOTES));
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
                    $values[$pas]["value"] = strtr(htmlspecialchars($item[1],ENT_QUOTES),$safeChars);
                }
                else
                {
                    $values[$pas]["key"] = $item;
                    $values[$pas]["value"] = strtr(htmlspecialchars($item,ENT_QUOTES),$safeChars);
                }
                if ((array_key_exists($this->attributes['name'],$this->request) && ($values[$pas]["key"]==$this->request[$this->attributes['name']])) || (gettype($item)=="array" && isset($item[2]) && $item[2]))
                {
                    if (!$hasSelected)
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
                if (gettype($item)=="array")
                {
                    $values[$pas]["key"] = $item[0];
                    $values[$pas]["value"] = strtr(htmlspecialchars($item[1],ENT_QUOTES),$safeChars);
                }
                else
                {
                    $values[$pas]["key"] = $item;
                    $values[$pas]["value"] = strtr(htmlspecialchars($item,ENT_QUOTES),$safeChars);
                }
                if ((array_key_exists($this->attributes['name'],$this->request) && ($values[$pas]["key"]==$this->request[$this->attributes['name']])) || (gettype($item)=="array" && isset($item[2]) && $item[2]))
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
                if (gettype($item)=="array")
                {
                    $values[$pas]["key"] = $item[0];
                    $values[$pas]["value"] = strtr(htmlspecialchars($item[1],ENT_QUOTES),$safeChars);
                }
                else
                {
                    $values[$pas]["key"] = $item;
                    $values[$pas]["value"] = strtr(htmlspecialchars($item,ENT_QUOTES),$safeChars);
                }

                if ((array_key_exists($this->attributes['name'],$this->request) && ($values[$pas]["key"]==$this->request[$this->attributes['name']])) || (gettype($item)=="array" && isset($item[2]) && $item[2]))
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
        $template->write("inputValue",htmlspecialchars($this->defaultValue,ENT_QUOTES));
        return $template->getBuffer();
    }

    function write_submit($template)
    {
        $template->write("inputValue",htmlspecialchars($this->defaultValue,ENT_QUOTES));
        return $template->getBuffer();
    }



    function write($upperTemplate="")
    {
        if (method_exists($this,"write_{$this->type}"))
        {
            $template = new Template("?form/input_{$this->type}.tmpl");
            $template->vars = $upperTemplate->vars;
            $safeChars = get_html_translation_table(HTML_ENTITIES);
            $template->write("inputLabel",strtr(htmlspecialchars($this->label,ENT_QUOTES),$safeChars));
            $template->write("attributes",$this->attributes);
            if ($this->mandatory)
                $template->write("mandatoryMarker",$this->mandatoryMarker);
            if ($this->error)
                $template->write("inputError",$this->error);

            eval("\$result .= \$this->write_{$this->type}(\$template);");
        }
        else
            Utils::throwDroneError("Unknown form input type: {$this->type}.");
        return $result;
    }

    function writeValueless($upperTemplate="")
    {
        $this->request[$this->attributes['name']] = "";
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

            if ($this->type=="select" || $this->type=="radio")
                if ($this->mandatory && $this->request[$this->attributes['name']]==$this->initial)
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
                if (gettype($this->request[$this->attributes['name']])=="array")
                {
                    if (in_array($key,$this->request[$this->attributes['name']]) && $validatorResult)
                        return true;
                }
                else
                    if ($this->request[$this->attributes['name']]==$key && $validatorResult)
                        return true;
            }
            
            $this->error = "Invalid value";
            return false;
        }
        
        if ($this->mandatory && strlen($this->request[$this->attributes['name']])==0)
        {
            $this->error = "Can't be empty";
            return false;
        }
        if (!$this->mandatory && strlen($this->request[$this->attributes['name']])==0)
        {
            return true;
        }
        if (isset($this->attributes['maxlength']) && strlen($this->request[$this->attributes['name']])>$this->attributes['maxlength'])
        {
            $this->error = "Max length for input is {$this->attributes['maxlength']}";
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
            if (!preg_match ($this->validator['regExp'],$this->request[$this->attributes['name']]))
            {
                $this->error= $this->validator['message'];
                return false;
            }
        return true;
    }
}
?>
