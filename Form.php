<?php
require ("Input.php"); //I know captcha already contains Input.php, but maby later I'll want to get it separated somewhow
require ("Captcha.php");
class Form
{

    function __construct($onSuccess,$defaults)
    {
        $this->onSuccess = $onSuccess;
        $this->inputs = array();
        $this->defaults =$defaults;
    }
    
    
    private function __call($method, $args)
    {
        if ($method=="addInput")
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

                if (array_key_exists($name,$this->defaults))
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
                //this wil be replaced later witha nicer error
                die("phpDrone error: addInput takes at least one argument.");
            }
        }
    }
    

    function getHTML()
    {
        $isValid = true;
        $txtresult = "";
        if (count($_POST)!=0)
        {
            foreach ($this->inputs as $item)
            {
                $result = $item->validate($_POST);
                if (!$result)-
                    $isValid = false;
            }
            
            if ($isValid)
            {
                $this->valueFlag = true;
                $meth = $this->onSuccess;
                $txtresult .= $meth();
            }
        }
        
        foreach ($this->inputs as $item)
            if (isset($this->valueFlag))
                $txtresult .= $item->writeValueless();
            else
                $txtresult .= $item->write();
        return $txtresult;
    }
}


?>
