<?php
	class DroneSettings
	{
		static function get($name, $default=NULL) 
		{
			include "droneEnv/settings.php";
			$var_data = get_defined_vars();
			
			return $var_data[$name]?$var_data[$name]:$default;
		}
	}
?>