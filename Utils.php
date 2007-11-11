<?php
class Utils
{
	static function getVideoData($videourl)
	{
		//http://(?:www.|)(?<site>youtube)\.com/watch\?v=(?<vid>[^&]*)|http://(?:www.|)(?<site>metacafe)\.com/watch/(?<vid>\d*)/
		//youtube: http://img.youtube.com/vi/####/default.jpg
		//metacafe: http://metacafe.com/thumb/embed/####.jpg
		//daily motion: http://limelight-689.static.dailymotion.com/dyn/preview/320x240/####.jpg
		
		$sitePatterns = array("youtube"=>'/http:\/\/(?:www.|)(?P<site>youtube)\\.com\/watch\\?v=(?P<vid>[^&]*)/',
							  "metacafe"=>'/http:\/\/(?:www.|)(?P<site>metacafe)\\.com\/watch\/(?P<vid>\\d*)\/(?P<vid_a>.*)\//',
							  "dailymotion"=>'/http:\/\/www\\.dailymotion\\.com\//'
							 );
							 
		foreach($sitePatterns as $site=>$pattern)
		if (preg_match($pattern, $videourl, $cap))
		{
   			switch ($site)
   			{
				case "youtube":
				    return array("preview"=>"http://img.youtube.com/vi/{$cap['vid']}/default.jpg","embed"=>"http://www.youtube.com/v/{$cap['vid']}");
				    break;
				case "metacafe":
				    return array("preview"=>"http://metacafe.com/thumb/embed/{$cap['vid']}.jpg","embed"=>"http://www.metacafe.com/fplayer/{$cap['vid']}/{$cap['vid_a']}.swf");
				    break;
				case "dailymotion":
				    $handle = fopen($videourl, "rb");
				    $content = stream_get_contents($handle);
				    fclose($handle);
				    preg_match('/var WRP_CONTENT[ ]*=[ ]*\'(?P<vid>\\d*)\';/', $content, $prevCap);
				    preg_match('/http:\/\/www\\.dailymotion\\.com\/swf\/(?P<vid>[^&]*)&quot;&gt;&lt;/m', $content, $embedCap);
				    return array("preview"=>"http://limelight-689.static.dailymotion.com/dyn/preview/320x240/{$prevCap['vid']}.jpg","embed"=>"http://www.dailymotion.com/swf/{$embedCap['vid']}");
				    break;
				default:
				    return "Error parsing url. Please report url!";
				    break;
			}
		}
		return false;
	}

    static function querySetVar($userUrl,$varName,$varValue)
    {
        $wasThere = preg_match("/".$varName."=(?:.*?(&))|".$varName."=(?:.*?(\\z))/",$userUrl);
        $url = preg_replace("/".$varName."=(?:.*?(&))|".$varName."=(?:.*?(\\z))/",$varName."=".$varValue."\\1",$userUrl);
        if (!$wasThere)
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
        global $debugMode;
    
        print "<b>phpDrone error:</b> <br />";
        if (preg_match_all('/require\(drone\/settings\.php\)/',$errstr,$some))
        {
            print "Your project does not have a configuration file in the drone environment.<br />";
            die();
        }
        else
            print $errstr."<br />";

        if ($debugMode)
        {
            print "<br /><b>Traceback:</b><br />";
            printStackTrace();
        }
        die();
    }

	static function microTime()
	{
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}

    static function silentDeath()
    {
        return True;
    }
    
    static function getLanguage($localeDir)
    {
        global $droneLanguage;
        if (isset($droneLanguage))
            return $droneLanguage;
        elseif (isset($_SESSION['drone_language']))
            return $_SESSION['drone_language'];
        elseif (isset($_COOKIE['drone_language']))
            return $_COOKIE['drone_language'];
        elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        {
            $userLanguages = array();
            $langs = preg_split('/,/',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach($langs as $lang)
            {
                $quality = preg_split('/;(?:[ ]*|)q=/',$lang);
                if (count($quality)==1)
                    $userLanguages[trim($quality[0])] = 1.0;
                else
                    $userLanguages[trim($quality[0])] = floatval($quality[1]);
            }
            arsort($userLanguages);
            foreach($userLanguages as $language=>$quality)
                if (is_dir("$localeDir/{$language}"))
                    return $language;
        }

        return False;
    }

}
?>
