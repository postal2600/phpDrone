<?php
class PageNum
{
    function __construct($itemCount,$itemLabel,$itemPageURL,$prefName,$prefOptionList,$maxLinks)
    {
        $this->itemCount = $itemCount;
        $this->itemLabel = $itemLabel;
        $this->itemPageURL = $itemPageURL;
        $this->prefName = $prefName;
        $this->prefOptionList = $prefOptionList;
        $this->maxLinks = $maxLinks;
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
        $template->write("currentPage",array_get('cvds_pn_page',$_SESSION,1));
        $itemsPerPage = array_get('cvds_pn_pref',$_SESSION,$this->prefOptionList[0]);
        $template->write("currentPerPage",$itemsPerPage);
        $template->write("prefs",$this->prefOptionList);
        $template->write("pages",(int)ceil($this->itemCount/$itemsPerPage)-1);
        return $template->getBuffer();
    }
}
?>
