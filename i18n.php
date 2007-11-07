<?php
 	$droneLocale = isset($droneLocale)?$droneLocale:"en";
    putenv("LC_ALL={$droneLocale}");
	setlocale(LC_ALL, $droneLocale);
	bindtextdomain("phpDrone", dirname(__FILE__)."/locale");
	textdomain("phpDrone");
?>
