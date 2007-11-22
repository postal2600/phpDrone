<?php
class DroneCore
{
    static function sendLogo()
    {
        if (preg_match('/(?:&|\\A)=phpDroneLogo2600/',$_SERVER['QUERY_STRING']))
        {

            http_send_content_disposition("logo.png", true);
            http_send_content_type('image/x-png');
            if ($_GET['logo_size']=='large')
                http_send_file(Utils::getDronePath()."/res/images/logo.png");
            else
                http_send_file(Utils::getDronePath()."/res/images/powered.png");
            die();
        }
    }
    
    static function serveDroneResources()
    {
        $resource = $_GET['phpDroneRequestResource'];
        self::sendLogo();
        if ($resource && !preg_match('/\\.\\.(?:\/|\\\\)/',$resource))
        {
            $mimes = array('gif'=>'image/gif',
                           'png'=>'image/x-png',
                           'css'=>'text/css',
                           'js'=>'text/javascript'
                          );
            $fileInfo = pathinfo($resource);
            http_send_content_disposition("logo.png", true);
            http_send_content_type(Utils::array_get($fileInfo['extension'],$mimes,'text/plain'));
            http_send_file(Utils::getDronePath()."/res/{$resource}");
            die();
        }

        return preg_match('/\\.\\.(?:\/|\\\\)/',$resource)?die('Invalid resource file'):false;
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
                 $item['function']!="throwDroneError")
                $result .= "<b>File:</b> {$item['file']}, <b>line</b> {$item['line']}, <b>in</b> {$item['function']}<br />";
        return $result;
    }

    static function handleDroneErrors($errno, $errstr, $errfile, $errline)
    {
        $template = new Template("?core/error.tmpl");
        $info = "<b>phpDrone error:</b> <br />";
        $info .= $errstr."<br />";

        $debugMode = DroneConfig::get('Main.debugMode');
        if ($debugMode)
            $info .= DroneCore::getStackTrace();
        $template->write('errorMessage',$info);
        $template->render();
        die();
    }
}

DroneCore::serveDroneResources();
if ($_GET['phpDrone_captcha_action']=='regen' && $_GET['phpDrone_captcha_id']!="")
    {
        $newValue = Captcha::generate(5,$_GET['phpDrone_captcha_id']);
        Captcha::draw($newValue);
    }
if ($_GET['phpDrone_draw_captchaid'])
    Captcha::draw($_GET['phpDrone_draw_captchaid']);

?>
