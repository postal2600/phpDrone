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
                break;
            case "get":
                $this->request = $_GET;
                break;
            default:
                $this->request = $_GET;
                break;
        }
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

            $len = count($this->inputs);
            if ($type!="captcha")
                $this->inputs[$len] = new Input($label,$type,$name,$this->madatoryMarker);
            else
                $this->inputs[$len] = new Captcha($label,$name);

            if (isset($this->defaults[$name]))            
                $this->inputs[$len]->def = $this->defaults[$name];


            if ($validator!="")
                if (!is_array($validator) || function_exists($validator))
                    $this->inputs[$len]->setValidator($validator,"Invalid ".strtolower($label));
                else
                    $this->inputs[$len]->setValidator($validator,"Invalid value");
            if ($maxLen)
                $this->inputs[$len]->setMaxSize($maxLen);

            $this->inputs[$len]->setRequestData($this->request);
            if  ($type=="submit")
                $this->submitTriggers[$this->inputs[$len]->name]="";
        }
        else
        if (count($args)==1)
        {
            $input = $args[0];

            $len = count($this->inputs);
            $this->inputs[$len] = $input;
            $input->addedLater = true;
            $input->setRequestData($this->request);
        }
        else
        {
            //this wil be replaced later with a nicer error
            throwDroneError("Method addInput takes at least one argument.");
        }
        
    }
    
    private function __call($method, $args)
    {
        if (method_exists($this,$method."_p"))
            eval("\$this->".$method."_p(\$args);");
        else
            //this wil be replaced later with a nicer error
            throwDroneError("Call to undefined method Form->".$method."()");
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
                    $arrayResult[$item->name] = $item->writeValueless($upperTemplate);
                else
                    $arrayResult[$item->name] = $item->write($upperTemplate);
        return $arrayResult;

    }
}


?>
