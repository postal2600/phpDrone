<?php
class DroneConfig
{
    static function get($key,$default=null)
    {
        if (is_file("droneEnv/settings.php"))
        {
            $parts = preg_split('/\./',$key);
            if (count($parts)==1)
            {
                $conf = parse_ini_file("droneEnv/settings.php");
                return Utils::array_get($key,$conf,$default);
            }
            else
            {
                $conf = parse_ini_file("droneEnv/settings.php",True);
                if (isset($conf[$parts[0]][$parts[1]]))
                    return $conf[$parts[0]][$parts[1]];
            }
        }
        return $default;
    }
    
    static function getSection($key,$default=null)
    {
        if (is_file("droneEnv/settings.php"))
        {
            $conf = parse_ini_file("droneEnv/settings.php",True);
            return $conf[$key];
        }
        return $default;
    }
    
    static function ensureConfig()
    {
        if (!@is_dir('droneEnv') && !@mkdir("droneEnv"))
            return false;
        if (!@is_file('droneEnv/settings.php') && !@copy(dirname(__FILE__).'/res/default_settings.php','droneEnv/settings.php'))
            return false;
        return true;
    }
}

DroneConfig::ensureConfig();
?>
