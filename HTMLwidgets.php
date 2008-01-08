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
        $template = new Template('htmlWidgets/login.tmpl',true);
        $loginForm = new Form("");
        $loginForm->addInput("*".dgettext("phpDrone","User"),"text","username");
        $loginForm->addInput("*".dgettext("phpDrone","Password"),"password","password");
        
        $template->set("WidgetLoginForm",$loginForm->getHTML());
        $template->set("WidgetLoginPage",$_SERVER['PHP_SELF']);


        $loginResult = include ("widgets/login.php");

        if ($loginResult)
            if (substr($loginResult,0,5)!="user:")
                $template->set("loginFormStatusMessage",$loginResult);
            else
                $this->template->set("phpDroneLoggedUser",substr($loginResult,5));

        $this->template->set("loginWidget",$template->getBuffer());
    }
}


class PageNum extends HTMLWidgets
{
    function __construct($itemCount,$itemLabel,$prefName,$prefOptionList,$maxLinks=NULL)
    {
        if (isset($_GET['action']))
            $this->handleAction();
        $this->itemCount = $itemCount;
        $this->itemLabel = $itemLabel;
        $this->prefName = $prefName;
        $this->prefOptionList = $prefOptionList;
        $this->maxLinks = $maxLinks;
        $this->droneURL = DroneConfig::get('Main.droneURL');
        $this->showPages = true;
        $this->showPrefs = true;
        $this->showQuickJump = true;
        $this->totalPages = max(1,(int)ceil($this->itemCount/$this->getItemsPerPage()));
    }

    function setCount($count)
    {
        $this->itemCount = $count;
    }

    function handleAction()
    {
        switch ($_GET['action'])
        {
            case "setPage":
                $url = Utils::querySetVar($_SERVER['HTTP_REFERER'],"page",$_GET['page']);
                header("Location: {$url}");
                break;
            case "setPref":
                session_start();
                $_SESSION["drone_pn_{$_GET['prefName']}"] = $_GET['pref'];
                $url = Utils::querySetVar($_SERVER['HTTP_REFERER'],"page",1);
                header("Location: {$url}");
                break;
        }
    }

    function getHTML()
    {
        $template = new Template("htmlWidgets/PageNum.tmpl",true);
        $template->set("itemCount",$this->itemCount);
        $template->set("itemLabel",$this->itemLabel);
        
        $itemsPerPage = $this->getItemsPerPage();
        $template->set("currentPerPage",$itemsPerPage);
        $template->set("prefs",$this->prefOptionList);
        $template->set("prefName",$this->prefName);
        $currentPage = $this->getCurrentPage();
        $allPages = range(1,$this->totalPages);
    	$pages = $allPages;
        
        if (isset($this->maxLinks))
	        if ($this->totalPages>$this->maxLinks)
	  		{
				$lowerLimit = max(1, $currentPage-round($this->maxLinks/2));
				$upperLimit = min($this->totalPages, $currentPage + ($this->maxLinks-($currentPage-$lowerLimit)));
				if ($upperLimit-$lowerLimit<$this->maxLinks)
				    $lowerLimit -= $this->maxLinks - ($upperLimit-$lowerLimit);
	            $pages = range($lowerLimit,$upperLimit);
	            if ($lowerLimit!=1)
	                $template->set("cutedLow","1");
	            if ($upperLimit!=$this->totalPages)
	                $template->set("cutedHigh","1");
			}
		
        $template->set("currentPage",$this->getCurrentPage());

		$form = new Form(do_success);
		$form->addInput("","select","page");
		$form->inputs['page']->setValue($allPages);
		$form->inputs['page']->setAttribute("onchange","window.location=\"?action=setPage&amp;page=\"+this.value");

        $template->set("quickJumpSelector",$form->getHTML());
        $template->set("pages",$pages);
        $template->set("phpDroneURL",$this->droneURL);
        $template->set("showPages",$this->showPages);
        $template->set("showPrefs",$this->showPrefs);
        $template->set("showQuickJump",$this->showQuickJump);
        return $template->getBuffer();
    }

    function getCurrentPage()
    {
        return max(1,min($this->totalPages,intval(Utils::array_get("page",$_GET,1))));
    }

    function getItemsPerPage()
    {
        return in_array(intval(Utils::array_get("drone_pn_{$this->prefName}",$_SESSION,$this->prefOptionList[0])),$this->prefOptionList)?intval(Utils::array_get("drone_pn_{$this->prefName}",$_SESSION,$this->prefOptionList[0])):$this->prefOptionList[0];
    }

	function hidePages($bool=true)
	{
		$this->showPages = !$bool;
	}

	function hidePrefs($bool=true)
	{
		$this->showPrefs = !$bool;
	}

	function hideQuickJump($bool=true)
	{
		$this->showQuickJump = !$bool;
	}

}


?>
