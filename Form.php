<?php
require_once ("Input.php"); //I know captcha already contains Input.php, but maby later I'll want to get it separated somewhow
require_once ("Captcha.php");
class Form
{

    function __construct($onSuccess,$defaults=NULL,$method="both")
    {
        $this->onSuccess = $onSuccess;
        $this->inputs = array();
        $this->defaults =$defaults;
        $this->madatoryMarker = "*";
        $this->submitTriggers = array();
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
    }

	private function addFilesData(&$requestObj)
	{
		foreach ($_FILES as $key=>$item)
		    $requestObj[$key] = $item['name'];
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

//             $len = count($this->inputs);
            if ($type!="captcha")
                $this->inputs[$name] = new Input($label,$type,$name,$this->madatoryMarker);
            else
                $this->inputs[$name] = new Captcha($label,$name);

            if (isset($this->defaults[$name]))
                if ($type!="select")
                	$this->inputs[$name]->defaultValue = $this->defaults[$name];
				else
				    $this->inputs[$name]->initial = $this->defaults[$name];

            if ($validator!="")
                if (is_array($validator))
                {
                    $this->inputs[$name]->setValidator($validator[0],$validator[1]);
                }
                else
                    $this->inputs[$name]->setValidator($validator,"Invalid ".strtolower($label));
                    
                    
            if ($maxLen)
                $this->inputs[$name]->attributes['maxlength'] = intval($maxLen);

            $this->inputs[$name]->setRequestData($this->request);
            if  ($type=="submit")
                $this->submitTriggers[$this->inputs[$name]->attributes['name']]="";
//             return $len;
        }
        else
        if (count($args)==1)
        {
            $input = $args[0];

//             $len = count($this->inputs);
            $this->inputs[$name] = $input;
            $input->addedLater = true;
            $input->setRequestData($this->request);
//             return $len;
        }
        else
        {
            //this wil be replaced later with a nicer error
            Utils::throwDroneError("Method addInput takes at least one argument.");
        }
        
    }
    
    private function __call($method, $args)
    {
        if (method_exists($this,$method."_p"))
            eval("\$this->".$method."_p(\$args);");
        else
            //this wil be replaced later with a nicer error
            Utils::throwDroneError("Call to undefined method Form->".$method."()");
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
                $result = $item->validate();
                if (!$result)
                    $isValid = false;
            }

            if ($isValid)
            {
                if ($this->onSuccess)
                {
                    $this->valueFlag = true;
                    $meth = $this->onSuccess;
                    $txtresult .= $meth($this->request);
                }
            }
        }

        if (count($this->submitTriggers)==0)
        {
            $validate_trigger = new Input("needed for phpDrone form validation","hidden","droneSubmitTrigger");
            $validate_trigger->setValidator("required","required");
            array_push($this->inputs,$validate_trigger);
        }
    }


    function getHTML($upperTemplate=False)
    {
        $this->validateForm();
        $htmlResult = "";
        foreach ($this->inputs as $item)
            if (!$item->addedLater)
                if (isset($this->valueFlag))
                    $htmlResult .= $item->writeValueless($upperTemplate);
                else
                    $htmlResult .= $item->write($upperTemplate);
        return $htmlResult;
    }
    
    function getHTMLinputs($upperTemplate=False)
    {
        $this->validateForm();
        $arrayResult = array();
        foreach ($this->inputs as $item)
            if (!$item->addedLater)
                if (isset($this->valueFlag))
                    $arrayResult[$item->attributes['name']] = $item->writeValueless($upperTemplate);
                else
                    $arrayResult[$item->attributes['name']] = $item->write($upperTemplate);
        return $arrayResult;

    }
}


?>
