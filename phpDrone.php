<?php
if (version_compare(phpversion(),"5")>-1)
{
    require_once('Utils.php');
    include_once ("Database.php");
    include_once ("Form.php");
    include_once ("Template.php");
}
else
{
    die("<b>phpDrone error:</b> phpDrone runs only on php5 or above. Your php version is: ".phpversion());
}
?>
