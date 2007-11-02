<?php
require_once ("Input.php"); //I know captcha already contains Input.php, but maby later I'll want to get it separated somewhow
require_once ("Captcha.php");
class Form
{

    function __construct($onSuccess,$defaults=NULL)
    {
        $this->onSuccess = $onSuccess;
        $this->inputs = array();
        $this->defaults =$defaults;
    }
    
    
    private function addInput_p($args)
    {
        if (count($args)>1)
        {
            $label = $args[0];
            $type = $args[1];
            $name = $args[2];
            $validator = $args[3];

            $len = count($this->inputs);
            if ($type!="captcha")
                $this->inputs[$len] = new Input($label,$type,$name);
            else
                $this->inputs[$len] = new Captcha($label,$name);

            if (isset($this->defaults[$name]))            
                $this->inputs[$len]->def = $this->defaults[$name];


            if ($validator!="")
                if (!is_array($validator) || function_exists($validator))
                    $this->inputs[$len]-> setValidator($validator,"Invalid ".strtolower($label));
                else
                    $this->inputs[$len]-> setValidator($validator,"Invalid value");
        }
        else
        if (count($args)==1)
        {
            $input = $args[0];

            $len = count($this->inputs);
            $this->inputs[$len] = $input;
            $input->addedLater = true;
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
        if (count($_POST)!=0)
        {
            foreach ($this->inputs as $item)
            {
                $result = $item->validate($_POST);
                if (!$result)
                    $isValid = false;
            }
            
            if ($isValid)
            {
                if ($this->onSuccess)
                {
                    $this->valueFlag = true;
                    $meth = $this->onSuccess;
                    $txtresult .= $meth();
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
