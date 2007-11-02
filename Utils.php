<?php
class Utils
{
	static function getVideoPreview($videourl)
	{
		//http://(?<site>youtube)\.com/watch\?v=(?<vid>\w*)|http://www.(?<site>metacafe).com/watch/(?<vid>807117/blues_guitar_lesson/)
	}

    static function querySetVar($userUrl,$varName,$varValue)
    {
        $url = preg_replace("/".$varName."=(?:.*?(&))|".$varName."=(?:.*?(\\z))/",$varName."=".$varValue."\\1",$userUrl);
        if ($url==$userUrl)
            $url .=(strpos($url,"?")?"&":"?")."{$varName}={$varValue}";
        return $url;
    }

    //generates a hexadecimal random string
    static function genRandomHex($len){
            $rhex="";
            for ($i = 1; $i <= $len/2; $i++) {
                $rhex=$rhex.chr(mt_rand(0, 255));
            }
            return bin2hex($rhex);
        }

    //generates a $size length random string
    static function genRandomString($size)
    {
        $result = "";
        $pas=0;
        while ($pas!=$size)
        {
            $rnd=58;
            while (($rnd>57 && $rnd<65) || ($rnd>90 && $rnd<97))
                $rnd = mt_rand(48, 122);
            $result = $result.chr($rnd);
            $pas++;
        }
        return $result;
    }

    //get the current url
    static function getCurrentUrl()
    {
        return "http".(($_SERVER['HTTPS']=="on")?"s://":"://").$_SERVER['SERVER_NAME'].(($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"])).$_SERVER['REQUEST_URI'];
    }

    //get the referer of the page only if it came from the same host as the current page.
    //$paranoic argument tells if the referer is on the same scheme (http/https/ftp) as the current url
    static function getSafeReferer($getQueryString=True,$paranoic=False)
    {
        $referer = $_SERVER['HTTP_REFERER'];
        $current = getCurrentUrl();

        $refComps = parse_url($referer);
        $curComps = parse_url($current);
        if ($paranoic && $refComps["scheme"]!=$curComps["scheme"])
            return false;
        if ($refComps["host"]==$curComps["host"])
            return $referer;
        else
            return false;
    }

    //extracts the value from an array from certain key.If the key is not found, it will return NULL or the $default argument.
    static function array_get($key,$array,$default=NULL)
    {
        if (gettype($array)=="array")
            if (array_key_exists($key, $array))
                return $array[$key];
            else
                if ($default)
                    return $default;
    }

    static function advHttpQuery($r_query="")
    {
        if ($r_query=="")
            $r_query = $_SERVER['QUERY_STRING'];

        $result = array();
        $query = $r_query."&";

        preg_match_all('/(?P<key>.*?)=(?P<value>.*?)&/', $query, $capt);
        $count = count($capt['key']);
        for ($f=0;$f<$count;$f++)
        {
            if (!isset($result[$capt['key'][$f]]))
                $result[$capt['key'][$f]] = array();
            $result[$capt['key'][$f]][count($result[$capt['key'][$f]])] = $capt['value'][$f];
        }
        return $result;
    }

    static function throwDroneError($msg)
    {
        set_error_handler("handleDroneErrors");
        trigger_error($msg,E_USER_ERROR);
        restore_error_handler();
    }

    static function printStackTrace()
    {
        $stack = debug_backtrace();
        foreach($stack as $item)
    //         if ($item['static function']!="printStackTrace" &&
    //             $item['static function']!="handleDroneErrors" &&
    //             $item['static function']!="trigger_error" &&
    //             $item['static function']!="throwDroneError")
                print "<b>File:</b> {$item['file']}, <b>line</b> {$item['line']}, <b>in</b> {$item['static function']}<br />";
    }

    static function handleDroneErrors($errno, $errstr, $errfile, $errline)
    {
        print "<b>phpDrone error:</b> <br />";
        if (preg_match_all('/require\(drone\/settings\.php\)/',$errstr,$some))
        {
            print "Your project does not have a <b>drone/settings.php</b> file.<br />";
            die();
        }
        else
            print $errstr."<br />";

        set_error_handler("handleDroneErrors");
        require("drone/settings.php");
        restore_error_handler();

        if ($debugMode)
        {
            print "<br /><b>Traceback:</b><br />";
            printStackTrace();
        }
        die();
    }

    static function silentDeath()
    {
        return True;
    }
}
?>
