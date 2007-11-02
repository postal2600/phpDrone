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
                $this->inputs[$len] = new Input($label,$type,$name);
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
    

    function getHTML($upperTemplate=False)
    {
        $isValid = true;
        $txtresult = "";
        if (count($this->request)!=0)
        {
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
        
        foreach ($this->inputs as $item)
            if (!$item->addedLater)
                if (isset($this->valueFlag))
                    $txtresult .= $item->writeValueless($upperTemplate);
                else
                    $txtresult .= $item->write($upperTemplate);
        return $txtresult;
    }
}


?>
