<?php
	if (version_compare(phpversion(),"5")>-1)
    {
    	$validModules = array('database'=>'phpDrone/Database.php',
                              'form'=>'phpDrone/Form.php',
                              'template'=>'phpDrone/Template.php',
                              'mail'=>'phpDrone/DroneMail.php',
                              'widgets'=>'phpDrone/HTMLwidgets.php',
                              'utils'=>'phpDrone/Utils.php',
                              'i18n'=>'phpDrone/i18n.php',
                              'admin'=>'phpDrone/DroneAdmin.php',
                              'profiler'=>'phpDrone/DroneProfiler.php',
                              'controller'=>'phpDrone/DroneController.php'
    					     );
        session_start();
        ob_start();
        
        require("DroneConfig.php");
        
        $customModules = DroneConfig::getSection('Modules');
        if ($customModules)
        {
    	    foreach ($customModules as $key=>$loadIt)
    	        if (array_key_exists($key,$validModules) && $loadIt)
    	    		require $validModules[$key];
        }
        else
    	    foreach ($validModules as $module)
    	    		require $module;
        require("DroneCore.php");
        if (is_file('droneEnv/drone.php'))
            include 'droneEnv/drone.php';
        if (DroneConfig::get('Modules.controller',false))
            DroneController::handleMVCurl();

	}
	else
	{
	    die("<b>phpDrone error:</b> phpDrone runs only on php5 or above. Your php version is: ".phpversion());
	}
?>
