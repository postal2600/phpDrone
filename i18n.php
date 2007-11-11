<?php
Class i18n
{
    static function init()
    {
        $phpDrone_localeDir = bindtextdomain("phpDrone", dirname(__FILE__)."/locale");
        $phpDrone_Lang = Utils::getLanguage($phpDrone_localeDir);
        putenv("LC_ALL={$phpDrone_Lang}");
        setlocale(LC_ALL, $phpDrone_Lang);
    	textdomain("phpDrone");
	}
	
	static function changeLanguage($lang)
	{
        $_SESSION['drone_language'] = $lang;
        i18n::init();
    }
}

i18n::init();
?>
