<?php
class DroneAdmin
{
    static function handle()
    {
        global $tmpl;
        $tmpl = new Template();
        if (isset($_SESSION['drone_admin_user']))
            self::processRequest();
        else
            self::login();
    }

    static function verify_credentials($data)
    {
        $user = DroneConfig::get('Admin.user');
        $pass = DroneConfig::get('Admin.pass');
        $hash = DroneConfig::get('Admin.passHash');

        if (!isset($user) || $user=="")
            DroneCore::throwDroneError("To use the Admin module, you must set a user in droneEnv/settings.php");
        if (!isset($pass) && !isset($hash))
            DroneCore::throwDroneError("To use the Admin module, you must set a password or a password hash in droneEnv/settings.php");

        if (strtolower($user) == strtolower($data['user']))
        {
            if ((isset($pass) && $pass==$data['pass']) || (isset($hash) && $hash==md5($data['pass'])))
                return true;
        }
        return false;
    }
    
    static function processRequest()
    {
        $request = isset($_GET['section'])?$_GET['section']:"index";
        if (method_exists("DroneAdmin",$request))
            eval("DroneAdmin::{$request}();");
    }
    
    static function login_success($data)
    {
        if (isset($data))
        {
            $_SESSION['drone_admin_user'] = DroneConfig::get('Admin.user');
            header("?=droneadmin&section={$_GET['section']}");
        }

    }
    
    static function index()
    {
        global $tmpl;
        $tmpl->set('section',"main");
        $tmpl->render("?admin/login.tmpl");
    }
    
    static function login()
    {
        global $tmpl;
        $form = new Form("DroneAdmin::login_success");
        $form->addInput('*'.dgettext('phpDrone','User'),'text',"user",array("DroneAdmin::verify_credentials",dgettext('phpDrone',"Invalid user/password")));
        $form->addInput('*'.dgettext('phpDrone','Password'),'password',"pass");
        $form->addInput('*'.dgettext('phpDrone','Login'),'submit',"action");

        $tmpl->set('form',$form->getHTML());
        $tmpl->set('next',$_GET['section']);
        $tmpl->render("?admin/login.tmpl");
    }


    static function i18n()
    {
        global $tmpl;
        $tmpl->set('section',"i18n");
        $tmpl->render("?admin/login.tmpl");
    }
    
}
?>
