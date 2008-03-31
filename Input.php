<?php
class DroneInput
{
	static $safeChars = array('<'=>'&lt;','>'=>'&gt;','"'=>'&quot;',"'"=>'&#039;');

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
        $this->filter = array();
        $this->error = "";
        $this->initial = null;
        
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
        $this->request = &$reqData;
    }

    function setValue($defVal)
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

    function write_text($template)
    {
        if (array_key_exists($this->attributes['name'],$this->request))
            $template->set("inputValue",strtr($this->request[$this->attributes['name']],DroneInput::$safeChars));
        else
            $template->set("inputValue",strtr($this->defaultValue,DroneInput::$safeChars));
    }

    function write_password($template)
    {
        $this->write_text($template);
    }

    function write_textarea($template)
    {
        $this->write_text($template);
    }

    function write_count_textarea($template)
    {
        $this->attributes['class'] .= " drone_limited_textarea";
        $this->write_text($template);
    }

    function write_date($template)
    {
        $this->write_text($template);
    }

    function write_select($template)
    {
        if (isset($this->defaultValue))
        {
            $values = array();
            $pas=0;
            $hasSelected = False;
            foreach ($this->defaultValue as $item)
            {
                $values[$pas] = array();
                if (gettype($item)=="array")
                {
                    $values[$pas]["key"] = $item[0];
                    $values[$pas]["value"] = $item[1]; //htmlentities($item[1],ENT_QUOTES)
                }
                else
                {
                    $values[$pas]["key"] = $item;
                    $values[$pas]["value"] = $item; //htmlentities($item,ENT_QUOTES)
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
			
            $template->set("values",$values);
        }
    }

    function write_file($template)
    {
        // al what is needed for this input, is handeled in the write() method
        // (the value can't be set for a file input).
    }

    function write_checkbox($template)
    {
        if (isset($this->defaultValue))
        {
            $values = array();
            $pas=0;
            foreach ($this->defaultValue as $item)
            {
                $values[$pas] = array();
                if (gettype($item)=="array")
                {
                    $values[$pas]["key"] = $item[0];
                    $values[$pas]["value"] = htmlentities($item[1],ENT_QUOTES);
                }
                else
                {
                    $values[$pas]["key"] = $item;
                    $values[$pas]["value"] = htmlentities($item,ENT_QUOTES);
                }
                if ((array_key_exists($this->attributes['name'],$this->request) && ($values[$pas]["key"]==$this->request[$this->attributes['name']])) || (gettype($item)=="array" && isset($item[2]) && $item[2]))
                    $values[$pas]["selected"] = True;
                $pas++;
            }

            $template->set("values",$values);
        }
    }

    function write_radio($template)
    {
        if (isset($this->defaultValue))
        {
            $values = array();
            $pas=0;
            $hasSelected = False;
            foreach ($this->defaultValue as $item)
            {
                $values[$pas] = array();
                if (gettype($item)=="array")
                {
                    $values[$pas]["key"] = $item[0];
                    $values[$pas]["value"] = htmlentities($item[1],ENT_QUOTES);
                }
                else
                {
                    $values[$pas]["key"] = $item;
                    $values[$pas]["value"] = htmlentities($item,ENT_QUOTES);
                }

                if ((array_key_exists($this->attributes['name'],$this->request) && ($values[$pas]["key"]==$this->request[$this->attributes['name']])) || (gettype($item)=="array" && isset($item[2]) && $item[2]))
                {
                    $values[$pas]["selected"] = True;
                    $hasSelected = True;
                }
                $pas++;
            }

            $template->set("values",$values);
        }
    }

    function write_hidden($template)
    {
        $template->set("inputValue",htmlspecialchars($this->defaultValue,ENT_QUOTES));
    }

    function write_submit($template)
    {
        $template->set("inputValue",htmlspecialchars($this->label,ENT_QUOTES));
    }



    function write($upperTemplate="")
    {
        if (method_exists($this,"write_{$this->type}"))
        {
            $template = new DroneTemplate("form/input_{$this->type}.tmpl",true);
            $template->vars = $upperTemplate->vars;
            eval("\$this->write_{$this->type}(\$template);");
            $template->set("inputLabel",$this->label); //htmlentities($this->label,ENT_QUOTES)
            if ($this->type=='date')
                unset($this->attributes['format']);
            $template->set("attributes",$this->attributes);
            if ($this->mandatory)
                $template->set("mandatoryMarker",$this->mandatoryMarker);
            if ($this->error)
                $template->set("inputError",$this->error);
        }
        else
            DroneCore::throwDroneError("Unknown form input type: {$this->type}.");
        return $template->getBuffer();;
    }

    function writeValueless($upperTemplate="")
    {
//         $this->request[$this->attributes['name']] = "";
        return $this->write($upperTemplate);
    }


    function setValidator($validator,$msg=false)
    {
        if (!$msg)
            $msg = "";
        $this->validator=array('regExp'=>$validator,'message'=>$msg);
    }

    function addFilter($filter)
    {
        array_push($this->filter,$filter);
    }

    function setFilter($filter)
    {
        $this->filter = $filter;
    }

    function removeFilterRev($filter)
    {
        for ($f=count($this->filter)-1;$f>=0;$f--)
            if ($this->filter[$f]==$filter)
            {
                array_splice($this->filter,$f,1);
                return;
            }
    }

    function removeFilter($filter)
    {
        foreach($this->filter as $key=>$item)
            if ($item==$filter)
            {
                array_splice($this->filter,$key,1);
                return;
            }
    }


    function filterInput()
    {
        foreach ($this->filter as $filter)
            if (function_exists($filter))
                $this->request[$this->attributes['name']] = $filter($this->request,$this->attributes['name']);
    }

    function validate()
    {
        if ($this->type=="select" || $this->type=="checkbox" || $this->type=="radio")
        {

            if ($this->type=="select" || $this->type=="radio")
                if ($this->mandatory && $this->request[$this->attributes['name']]==$this->initial)
                {
                    $this->error = dgettext("phpDrone","Choose one");
                    return false;
                }

            $meth = $this->validator['regExp'];
            $validatorResult = True;
            
            if (function_exists($meth))
            {
                if ($meth($this->request,$this->attributes['name'])!=True)
                {
                    $this->error = $this->validator['message'];
                    $validatorResult = false;
                }
            }

            if (gettype($this->defaultValue)=="array")
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

            $this->error = dgettext("phpDrone","Invalid value");
            return false;
        }
        
        if ($this->mandatory && strlen($this->request[$this->attributes['name']])==0)
        {
            $this->error = dgettext("phpDrone","Can't be empty");
            return false;
        }
        
        if (!$this->mandatory && strlen($this->request[$this->attributes['name']])==0)
        {
            return true;
        }
        
        if (isset($this->attributes['maxlength']) && strlen($this->request[$this->attributes['name']])>$this->attributes['maxlength'])
        {
            $this->error = dgettext("phpDrone","Max length for input is")." {$this->attributes['maxlength']}";
            return false;
        }
        
        if ($this->type=='date')
        {
            $dateInfo=date_parse($this->request[$this->attributes['name']]);
            if ($dateInfo && !$dateInfo[errors])
            {
                $dateFormat = Utils::array_get('format',$this->attributes,'%Y-%m-%d %H:%M:%S');
                $this->request[$this->attributes['name']] = strftime($dateFormat,mktime($dateInfo["hour"],$dateInfo["minute"],$dateInfo["second"],$dateInfo["month"],$dateInfo["day"],$dateInfo["year"]));
                return true;
            }
            $this->error = dgettext("phpDrone","Invalid value");
            return false;
        }


        $meth = preg_split('/::/',$this->validator['regExp']);

        if (count($meth)==1 && function_exists($meth[0]))
        {
            if ($meth[0]($this->request,$this->attributes['name'])!=True)
            {
                $this->error = $this->validator['message'];
                return false;
            }
            return true;
        }
        elseif (method_exists($meth[0],$meth[1]))
        {
            eval("\$probe = {$meth[0]}::{$meth[1]}(\$this->request,\$this->attributes['name']);");
            if ($probe!=True)
            {
                $this->error = $this->validator['message'];
                return false;
            }
            return true;
        }
        elseif ($this->validator!=null && $this->type!="hidden" && $this->type!="submit")
            if (!preg_match ($this->validator['regExp'],$this->request[$this->attributes['name']]))
            {
                $this->error= $this->validator['message'];
                return false;
            }
        return true;
    }
}
?>
