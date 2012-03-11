<?php
require_once 'PHPUnit.php';
require_once 'phpDrone/phpDrone.php';

class FormTest extends PHPUnit_TestCase
{
	var $outputForm;
	var $expected;
	var $outputExpFile = "assertions/form.html";
	
    function FormTest($name) 
    {
       $this->PHPUnit_TestCase($name);
    }

    function setUp() 
    {
        $this->outputForm = new DroneForm(do_success);
		$this->outputForm->addInput("*User","text","username","/.*/");
		$this->outputForm->addInput("*Pass","password","password");
		
		
		$handle = fopen($this->outputExpFile, "r");
		$this->expected = fread($handle, filesize($this->outputExpFile));
		fclose($handle);
    }

    function tearDown() 
    {
    	unset($this->outputForm);
    }

    function testFormOutput()
    {
    	$result = $this->outputForm->getHTML();
        $this->assertEquals($result, $this->expected);
		// $handle = fopen($this->outputExpFile, "w");
		// fwrite($handle, $result);
		// fclose($handle);
    }
}

$suite  = new PHPUnit_TestSuite("FormTest");
$result = PHPUnit::run($suite);

echo $result -> toString();
?>