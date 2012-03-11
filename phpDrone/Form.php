<?php
require_once ("Input.php"); //I know captcha already contains Input.php, but maby later I'll want to get it separated somewhow
require_once ("Captcha.php");
class DroneForm
{
	const VALID_USERNAME = '/^[\\w_.$]*$/';
	const VALID_NUMBER = '/^[-+]?(?:\\b[0-9]+(?:\\.[0-9]*)?|\\.[0-9]+\\b)(?:[eE][-+]?[0-9]+\\b)?$/';
	const VALID_EMAIL = '/^[A-Z0-9._%-]+@[A-Z0-9.-]+\\.[A-Z]{2,4}$/i';
	const VALID_PASSCOMPLEX = '/\\A(?=[-_a-zA-Z0-9]*?[A-Z])(?=[-_a-zA-Z0-9]*?[a-z])(?=[-_a-zA-Z0-9]*?[0-9])\\S{6,}\\z/';
    const VALID_URL = '/^(ftp|http|https):\\\/\\\/(\\w+:{0,1}\\w*@)?(\\S+)(:[0-9]+)?(\\\/|\\\/([\\w#!:.?+=&%@!\\-\\\/]))?$/i';
    const VALID_IP = '/^\\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\b$/';
    const VALID_CC_AMERICANEXPRESS = '/^3[47][0-9]{13}$/';
    const VALID_CC_DISCOVER = '/^6011[0-9]{14}$/';
    const VALID_CC_MASTERCARD = '/^5[1-5][0-9]{14}$/';
    const VALID_CC_VISA = '/^4[0-9]{12}(?:[0-9]{3})?$/';

    function __construct($onSuccess,$defaults=NULL,$method="both")
    {
        $this->onSuccess = $onSuccess;
        $this->inputs = array();
        $this->defaults =$defaults;
        $this->madatoryMarker = "*";
        $this->submitTriggers = array();
        $this->isValid = false;
        $this->filter = array();
        $this->hasReq = array();
        
        switch ($method)
        {
            case "post":
                $this->request = $_POST;
                $this->addFilesData($this->request);
                break;
            case "get":
                $this->request = $_GET;
                break;
            default:
                $this->request = array_merge_recursive($_GET,$_POST);
                $this->addFilesData($this->request);
                break;
        }
        $this->cleanData($this->request);
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

	private function addFilesData(&$requestObj)
	{
		foreach ($_FILES as $key=>$item)
		    $requestObj[$key] = $item['name'];
	}

	private function cleanData(&$data)
	{
		foreach ($data as $key=>$value)
		    if (gettype($value)=="string")
				$data[$key] = stripcslashes($value);
			else if (gettype($value)=="array")
				$this->cleanData($data[$key]);
	}

    private function addInput_p($args)
    {
        if (count($args)>1)
        {
            $label = $args[0];
            $type = $args[1];
            $name = $args[2];
            $validator = $args[3];
            $maxLen = $args[4];

            if ($type!="captcha")
                $this->inputs[$name] = new DroneInput($label,$type,$name,$this->madatoryMarker);
            else
                $this->inputs[$name] = new Captcha($label,$name);

            if (isset($this->defaults[$name]))
                if ($type!="select" && $type!="radio")
                	$this->inputs[$name]->defaultValue = $this->defaults[$name];
				else
				    $this->inputs[$name]->initial = $this->defaults[$name];

            if ($validator!="")
                $this->inputs[$name]->setValidator($validator);
                    
                    
            if ($maxLen)
                $this->inputs[$name]->attributes['maxlength'] = intval($maxLen);

            $this->inputs[$name]->setRequestData($this->request);
            if  ($type=="submit")
                $this->submitTriggers[$this->inputs[$name]->attributes['name']]="";
            $this->inputs[$name]->setFilter($this->filter);
            $this->inputs[$name]->setParent($this);
        }
        else
        if (count($args)==1)
        {
            $input = $args[0];

            $input->addedLater = true;
            $input->setRequestData($this->request);
            $this->inputs[$name] = $input;
            $this->inputs[$name]->setFilter($this->filter);
            $this->inputs[$name]->setParent($this);
        }
        else
        {
            //this wil be replaced later with a nicer error
            DroneCore::throwDroneError("Method addInput takes at least one argument.");
        }
        
    }
    
    private function __call($method, $args)
    {
        if (method_exists($this,$method."_p"))
            eval("\$this->".$method."_p(\$args);");
        else
            //this wil be replaced later with a nicer error
            DroneCore::throwDroneError("Call to undefined method: Form->".$method."()");
    }
    
    function validateForm()
    {
        $isValid = true;
        
        $trigger = array_intersect_key($this->submitTriggers,$this->request);
        if (array_key_exists("droneSubmitTrigger",$this->request) || count($trigger)!=0)
        {
            unset($this->request['droneSubmitTrigger']);
            unset($_POST['droneSubmitTrigger']);
            unset($_GET['droneSubmitTrigger']);

            foreach ($this->inputs as $item)
            {
                $item->filterInput();
                $result = $item->validate();
                if (!$result)
                    $isValid = false;
            }

            if ($isValid)
            {
                if ($this->onSuccess)
                {
                    $this->valueFlag = true;
                    $meth = preg_split('/::/',$this->onSuccess);
                    if (count($meth)==1)
                    {
                        if (function_exists($meth[0]))
                            $txtresult .= $meth[0]($this->request);
                        else
                            DroneCore::throwDroneError("Form success method does not exist: <b>{$this->onSuccess}</b>");
                    }
                    else
                    {
                        if (method_exists($meth[0],$meth[1]))
                            eval("\$txtresult .= {$meth[0]}::{$meth[1]}(\$this->request);");
                        else
                            DroneCore::throwDroneError("Form success method does not exist: <b>{$this->onSuccess}</b>");
                    }
                }
            }
        }

        if (count($this->submitTriggers)==0)
        {
            $validate_trigger = new DroneInput("needed for phpDrone form validation","hidden","droneSubmitTrigger");
            $validate_trigger->setValidator("required");
            array_push($this->inputs,$validate_trigger);
        }
        $this->isValid = $isValid;
    }

	function isValid()
	{
		return $this->isValid;
	}

    function getHTML($upperTemplate=False)
    {
        $this->validateForm();
        $htmlResult = "";
        foreach ($this->inputs as $item)
        {
            $htmlResult .= $item->writeRequirements($upperTemplate);
            if (!$item->addedLater)
                if (isset($this->valueFlag))
                    $htmlResult .= $item->writeValueless($upperTemplate);
                else
                    $htmlResult .= $item->write($upperTemplate);
        }
        return $htmlResult;
    }
    
    function getHTMLinputs($upperTemplate=False)
    {
        $this->validateForm();
        $arrayResult = array();
        foreach ($this->inputs as $item)
        {
            if (!$item->addedLater)
                if (isset($this->valueFlag))
                    $arrayResult[$item->attributes['name']] = $item->writeValueless($upperTemplate);
                else
                {
                    $arrayResult[$item->attributes['name']] = $item->writeRequirements($upperTemplate);
                    $arrayResult[$item->attributes['name']] .= $item->write($upperTemplate);
                }
        }
        return $arrayResult;

    }
}


?>
