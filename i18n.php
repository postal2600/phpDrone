<?php
class i18n
{
    static function init()
    {
        bind_textdomain_codeset('phpDrone',"UTF-8");
        $phpDrone_localeDir = bindtextdomain("phpDrone", dirname(__FILE__)."/locale");
        $phpDrone_Lang = Utils::getLanguage($phpDrone_localeDir);
        putenv("LC_ALL={$phpDrone_Lang}");
        setlocale(LC_ALL, $phpDrone_Lang);
        textdomain("phpDrone");
	}

    static function setDomain($domainName,$encoding=null)
    {
        if (is_dir('./locale'))
        {
            if (isset($encoding))
                bind_textdomain_codeset($domainName,$encoding);
            $phpDrone_localeDir = bindtextdomain($domainName, './locale');
            textdomain($domainName);
            return $domainName;
        }
        else
            return false;
    }

	static function changeLanguage($lang)
	{
        $_SESSION['drone_language'] = $lang;
        i18n::init();
    }
}

i18n::init();
?>
