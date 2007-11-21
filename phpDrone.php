<?php
	if (version_compare(phpversion(),"5")>-1)
    {
    	$validModules = array('database'=>'Database.php',
    					      'form'=>'Form.php',
                              'template'=>'Template_new.php',
                              'mail'=>'DroneMail.php',
                              'widgets'=>'HTMLwidgets.php',
                              'utils'=>'Utils.php',
                              'i18n'=>'i18n.php',
                              'config'=>'DroneConfig.php',
    					);
        session_start();
        ob_start();
        
        require("DroneConfig.php");
        
        $loadModules = DroneConfig::getSection('Modules');
	    foreach ($loadModules as $key=>$loadIt)
	        if (array_key_exists($key,$validModules) && $loadIt)
	    		require $validModules[$key];
	    		
        @include 'droneEnv/drone.php';
	}
	else
	{
	    die("<b>phpDrone error:</b> phpDrone runs only on php5 or above. Your php version is: ".phpversion());
	}
?>
