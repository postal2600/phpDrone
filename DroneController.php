<?php
class DroneController
{
    static $args = array();

    static function handleMVCurl()
    {
        global $args;
        
        // .*index\.php/(?:(?<class>\w*)){0,1}(?:/(?<method>\w*)){0,1}(?:/(?<args>.*)){0,}
        if (preg_match('/.*index\\.php(?:\/(?:(?P<class>\\w+))){1}(?:\/(?P<method>\\w*)){0,1}(?:\/(?P<args>.*)){0,}/i', $_SERVER['PHP_SELF'], $urlParts))
        {
            $args = preg_split('/\//',$urlParts['args']);
            $class = $urlParts['class'];
            $method = $urlParts['method'];
            if ($method == "")
                $method = DroneConfig::get('Controller.defaultMethod','index');
        }
        else
        {
            $class = DroneConfig::get('Controller.defaultController','index');
            $method = DroneConfig::get('Controller.defaultMethod','index');
        }

        $controllerDir = DroneConfig::get('Main.controllerDir','controllers/');
        $controlerFile = $controllerDir.$class.'.php';
        if (!file_exists($controlerFile))
            DroneCore::throwDroneError("404: Controler file not found: {$controlerFile}");
        include ($controlerFile);
        if (!method_exists($class,$method))
            DroneCore::throwDroneError("404: Method <b>{$method}</b> not found in controller: {$controlerFile}");
        call_user_func_array(array(new $class(), $method),$args);
        die();

    }
    
    function getArg($index)
    {
        global $args;
        return $args[$index];
    }
}
?>
