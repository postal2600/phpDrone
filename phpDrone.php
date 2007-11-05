<?php
	$modules = array('database'=>'Database.php',
					 'form'=>'Form.php',
					 'template'=>'Template.php',
					 'widgets'=>'HTMLwidgets.php'
					);
	$excludeModules = array();
	
	// TODO: if in debug mode, check if the excluded module is valid
	function excludeModule($module)
	{
	    global $excludeModules;
	    array_push($excludeModules,$module);
	}
	
	require_once('Utils.php');
    set_error_handler("Utils::handleDroneErrors");
	require("drone/settings.php");
	restore_error_handler();

	if (version_compare(phpversion(),"5")>-1)
	{
	    foreach ($modules as $key=>$module)
	        if (!in_array($key,$excludeModules))
	    		require $module;
	}
	else
	{
	    die("<b>phpDrone error:</b> phpDrone runs only on php5 or above. Your php version is: ".phpversion());
	}
?>
