<?php
require_once 'PHPUnit.php';
require_once 'phpDrone/Form.php';

function do_success()
{
	
}

class FormTest extends PHPUnit_Framework_TestCase
{
	var $outputForm;
	var $expected;
	var $outputExpFile = "assertions/form.html";
	
    function setUp() 
    {
        $this->outputForm = new DroneForm(do_success);
		$this->outputForm->addInput("*Text","text","text_name");
		$this->outputForm->addInput("*Password","password","password_name");
		$this->outputForm->addInput("*Textarea","textarea","textarea_name");
		$this->outputForm->addInput("*Select","select","select_name");
		$this->outputForm->addInput("*File","file","file_name");
		$this->outputForm->addInput("*Radio","radio","radio_name");
		$this->outputForm->addInput("*Checbox","checkbox","checkbox_name");
		
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
?>