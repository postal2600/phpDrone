<?php
class DroneCore
{
    static function handleSpecialURL()
    {
        if (preg_match('/(?:&|\\A)=phpDroneLogo2600/',$_SERVER['QUERY_STRING']))
        {

            if ($_GET['logo_size']=='large')
                self::serveDroneResources("images/logo.png");
            else
                self::serveDroneResources("images/powered.png");
        }

        if (preg_match('/(?:&|\\A)=droneadmin/',$_SERVER['QUERY_STRING']))
        {
            DroneAdmin::handle();
            die();
        }
    }
    
    static function serveDroneResources($resource=null)
    {
        $resource = isset($resource)?$resource:$_GET['phpDroneRequestResource'];
        if ($resource && !preg_match('/\\.\\.(?:\/|\\\\)/',$resource) && is_file(Utils::getDronePath()."/res/{$resource}"))
        {
            $mimes = array('gif'=>'image/gif',
                           'png'=>'image/x-png',
                           'css'=>'text/css',
                           'js'=>'text/javascript'
                          );
            $fileInfo = pathinfo($resource);
            $contentType = Utils::array_get($fileInfo['extension'],$mimes,'text/plain');

            $filename = Utils::getDronePath()."/res/{$resource}";
            $handle = fopen($filename,'rb');
            $content = fread($handle, filesize($filename));
            fclose($handle);

            header("Content-type: {$contentType}");
            ob_end_clean();
            die($content);
        }
        elseif (isset($resource) && !is_file(Utils::getDronePath())."/res/{$resource}")
            die('Resource not found.');
        
        return preg_match('/\\.\\.(?:\/|\\\\)/',$resource)?die('Invalid resource path.'):false;
    }
    
    static function throwDroneError($msg)
    {
        set_error_handler("DroneCore::handleDroneErrors");
        trigger_error($msg,E_USER_ERROR);
        restore_error_handler();
    }

    static function getStackTrace()
    {
        $stack = debug_backtrace();
        $result = "<br /><b>Traceback:</b><br />";
        foreach($stack as $item)
             if ($item['function']!="printStackTrace" &&
                 $item['function']!="handleDroneErrors" &&
                 $item['function']!="trigger_error" &&
                 $item['function']!="getStackTrace" &&
                 $item['function']!="throwDroneError")
                $result .= "<b>File:</b> {$item['file']}, <b>line</b> {$item['line']}, <b>in</b> {$item['function']}<br />";
        return $result;
    }

    static function handleDroneErrors($errno, $errstr, $errfile, $errline)
    {
        $template = new DroneTemplate("core/error.tmpl",true);
        $info = "<b>phpDrone error:</b> <br />";
        $info .= $errstr."<br />";

        $debugMode = DroneConfig::get('Main.debugMode');
        if ($debugMode)
            $info .= DroneCore::getStackTrace();
        $template->set('errorMessage',$info);
        $template->render();
        die(/*$info*/);
    }
}

DroneCore::handleSpecialURL();
DroneCore::serveDroneResources();
if ($_GET['phpDrone_captcha_action']=='regen' && $_GET['phpDrone_captcha_id']!="")
{
    $newValue = Captcha::generate(5,$_GET['phpDrone_captcha_id']);
    Captcha::draw($newValue);
}
if ($_GET['phpDrone_draw_captchaid'])
    Captcha::draw($_GET['phpDrone_draw_captchaid']);
?>
