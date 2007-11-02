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
        $template = new Template('?htmlWidgets/login.tmpl');
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


class PageNum extends HTMLWidgets
{
    function __construct($itemCount,$itemLabel,$itemPageURL,$prefName,$prefOptionList,$maxLinks)
    {
        $this->itemCount = $itemCount;
        $this->itemLabel = $itemLabel;
        $this->itemPageURL = $itemPageURL;
        $this->prefName = $prefName;
        $this->prefOptionList = $prefOptionList;
        $this->maxLinks = $maxLinks;
        set_error_handler("handleDroneErrors");
        require("drone/settings.php");
        restore_error_handler();
        $this->droneURL = $droneURL;
    }

    function setCount($count)
    {
        $this->itemCount = $count;
    }

    function getHTML()
    {
        $template = new Template("?htmlWidgets/PageNum.tmpl");
        $template->write("itemCount",$this->itemCount);
        $template->write("itemLabel",$this->itemLabel);
        $template->write("currentPage",$this->getCurrentPage());
        $itemsPerPage = $this->getItemsPerPage();
        $template->write("currentPerPage",$itemsPerPage);
        $template->write("prefs",$this->prefOptionList);
        $template->write("prefName",$this->prefName);
        $template->write("pages",(int)ceil($this->itemCount/$itemsPerPage)-1);
        $template->write("phpDroneURL",$this->droneURL);
        return $template->getBuffer();
    }

    function getCurrentPage()
    {
        return array_get("page",$_GET,1);
    }

    function getItemsPerPage()
    {
        session_start();
        return array_get("drone_pn_{$this->prefName}",$_SESSION,$this->prefOptionList[0]);
    }
    
}


?>
