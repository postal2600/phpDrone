<?php
require_once 'PHPUnit.php';
require_once 'phpDrone/Settings.php';

class SettingsTest extends PHPUnit_Framework_TestCase
{
	var $settingsObject;
	
    function testGetSettings()
    {
		$this->assertEquals("Test value",DroneSettings::get("TEST_SETTING"));
    }

    function testGetSettingsWithDefault()
    {
		$this->assertEquals("Default value",DroneSettings::get("NON_EXISTING_SETTING", "Default value"));
    }
}
?>