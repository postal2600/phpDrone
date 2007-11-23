<?php
class DroneConfig
{
    static function get($key,$default=null)
    {
        if (is_file("droneEnv/settings.ini"))
        {
            $parts = preg_split('/\./',$key);
            if (count($parts)==1)
            {
                $conf = parse_ini_file("droneEnv/settings.ini");
                return Utils::array_get($key,$conf,$default);
            }
            else
            {
                $conf = parse_ini_file("droneEnv/settings.ini",True);
                foreach ($conf as $i_key=>$i_value)
                    if ($i_key==$parts[0])
                        foreach ($i_value as $item_key=>$item_value)
                            if ($item_key == $parts[1])
                                return $item_value;
            }
        }
        return $default;
    }
    
    static function getSection($key,$default=null)
    {
        if (is_file("droneEnv/settings.ini"))
        {
            $conf = parse_ini_file("droneEnv/settings.ini",True);
            foreach ($conf as $i_key=>$i_value)
                if ($i_key==$key)
                    return $i_value;
        }
        return $default;
    }
}
?>
