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
    function __construct($itemCount,$itemLabel,$prefName,$prefOptionList,$maxLinks=9)
    {
        $this->itemCount = $itemCount;
        $this->itemLabel = $itemLabel;
        $this->prefName = $prefName;
        $this->prefOptionList = $prefOptionList;
        $this->maxLinks = $maxLinks;
        set_error_handler("Utils::handleDroneErrors");
        require("drone/settings.php");
        restore_error_handler();
        $this->droneURL = $droneURL;
        $this->showPages = true;
        $this->showPrefs = true;
        $this->showQuickJump = true;

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
        $totalPages = max(1,(int)ceil($this->itemCount/$itemsPerPage));
        $currentPage = $this->getCurrentPage();
        $allPages = range(1,$totalPages);
        
        if ($totalPages<=$this->maxLinks)
        {
        	$pages = $allPages;
		}
		else
  		{
			$lowerLimit = max(1, $currentPage-round($this->maxLinks/2));
			$upperLimit = min($totalPages, $currentPage + ($this->maxLinks-($currentPage-$lowerLimit)));
			if ($upperLimit-$lowerLimit<$this->maxLinks)
			    $lowerLimit -= $this->maxLinks - ($upperLimit-$lowerLimit);
            $pages = range($lowerLimit,$upperLimit);
            if ($lowerLimit!=1)
                $template->write("cutedLow","1");
            if ($upperLimit!=$totalPages)
                $template->write("cutedHigh","1");
		}

		$form = new Form(do_success);
		$form->addInput("","select","page");
		$form->inputs['page']->setDefault($allPages);
		$form->inputs['page']->setAttribute("onchange","location=\"{$this->droneURL}/widgets/pageNum.php?action=setPage&amp;page=\"+this.value");

        $template->write("quickJumpSelector",$form->getHTML());
        $template->write("pages",$pages);
        $template->write("phpDroneURL",$this->droneURL);
        $template->write("showPages",$this->showPages);
        $template->write("showPrefs",$this->showPrefs);
        $template->write("showQuickJump",$this->showQuickJump);
        return $template->getBuffer();
    }

    function getCurrentPage()
    {
        return Utils::array_get("page",$_GET,1);
    }

    function getItemsPerPage()
    {
        session_start();
        return Utils::array_get("drone_pn_{$this->prefName}",$_SESSION,$this->prefOptionList[0]);
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
