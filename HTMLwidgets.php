<?php

class HTMLWidgets
{
    function __construct($initialBuffer="")
    {
        $this->buffer = $initialBuffer;
    }

    function execute()
    {
        //MUST be implemented in the child class
    }

    function getHTML()
    {
        $this->execute();
        return $this->buffer;
    }
}

class LoginWidget extends HTMLWidgets
{
    function __construct($template)
    {
        $this->template = $template;
        $this->execute();
    }

    function execute()
    {
        $template = new Template('?widgets/login.tmpl');
        $loginForm = new Form("");
        $loginForm->addInput("*User","text","username");
        $loginForm->addInput("*Password","password","password");
        
        $template->write("WidgetLoginForm",$loginForm->getHTML());
        $template->write("WidgetLoginPage",$_SERVER['PHP_SELF']);


        $loginResult = include ("widgets/login.php");

        if ($loginResult)
            if (substr($loginResult,0,5)!="user:")
                $template->write("loginFormStatusMessage",$loginResult);
            else
                $this->template->write("phpDroneLoggedUser",substr($loginResult,5));

        $this->template->write("loginWidget",$template->getBuffer());
    }
}
?>
