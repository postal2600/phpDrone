<?php
class Utils
{
    // get the preview image and the embed value for a internet movie (will be used in MovieWidget)
	static function getVideoData($videourl)
	{
		//http://(?:www\.|\w{2}\.|)(?<site>youtube)\.com/watch\?v=(?<vid>[^&]*)|http://(?:www.|)(?<site>metacafe)\.com/watch/(?<vid>\d*)/
		//youtube: http://img.youtube.com/vi/####/default.jpg
		//metacafe: http://metacafe.com/thumb/embed/####.jpg
		//daily motion: http://limelight-689.static.dailymotion.com/dyn/preview/320x240/####.jpg
		
		$sitePatterns = array("youtube"=>'/http:\/\/(?:www\\.|\\w{2}\\.|)(?P<site>youtube)\\.com\/watch\\?v=(?P<vid>[^&]*)/',
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

    static function loremIpsum($paragraphs = 4, $classicStart = true, $minWords = 20, $maxWords = 70)
    {
        $start = "Lorem ipsum dolor sit amet consectetuer";
        $ipsumText = "Lorem ipsum dolor sit amet consectetuer adipiscing elit donec id eros quisque elit nisl lacinia cursus lacinia tincidunt porttitor non leo nunc metus nibh semper ac interdum nec fringilla ut felis donec tempor semper ligula suspendisse ut ipsum quis est commodo dignissim suspendisse mauris neque convallis ac tincidunt id interdum porttitor urna sed orci turpis pretium id semper consequat venenatis et risus cras facilisis augue in ipsum praesent pellentesque diam sed est vestibulum nec justo curabitur lorem sed sed dui vivamus vitae dui at enim laoreet tincidunt phasellus nunc metus cursus eget ornare et pulvinar ut enim vivamus a turpis aenean condimentum nullam id nibh mauris quis ante in fermentum sed scelerisque velit ac justo morbi quis urna nam rhoncus orci quis laoreet dapibus libero orci suscipit dui ut hendrerit elit elit at tortor integer congue sem quis orci morbi pretium integer eu felis vestibulum mauris pellentesque mi tellus condimentum et nonummy non vestibulum ac lacus duis in wisi aliquam justo nam nulla cum sociis natoque penatibus et magnis dis parturient montes nascetur ridiculus mus sed nibh nibh sodales at tincidunt vitae congue id tortor proin et leo morbi odio nunc tempor vitae pretium auctor convallis quis nulla sed sollicitudin consectetuer neque vestibulum adipiscing maecenas ac magna aliquam quis velit donec tristique urna vitae convallis malesuada velit libero fringilla nunc sed euismod libero sem eget elit phasellus et neque curabitur vel ante a elit cursus porta curabitur interdum lorem ipsum dolor sit amet consectetuer adipiscing elit vestibulum consequat sapien id eros phasellus metus elit cursus et tempus vitae mattis ut massa in vel purus at arcu congue eleifend nullam sit amet dolor a risus mollis tempus ut eget lacus nulla eu lorem ut aliquet lacinia tellus nulla interdum sed pharetra semper tellus nam sapien pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas vestibulum sagittis nam id justo nec nibh gravida molestie nulla facilisi in volutpat quisque a augue vel mauris feugiat egestas curabitur accumsan nibh vel justo phasellus consequat wisi sed eros vestibulum tellus pede pellentesque et auctor non dictum et dui curabitur lobortis semper urna aenean aliquam in hac habitasse platea dictumst aenean rutrum pede vitae auctor consequat enim sem condimentum lacus vitae iaculis orci lorem et mauris pellentesque tempus volutpat orci curabitur at nunc maecenas pellentesque nulla eget tellus nam sagittis curabitur wisi leo lobortis eu congue ut hendrerit vel enim aenean pretium nisl pretium leo vestibulum pellentesque augue et faucibus adipiscing tortor ligula faucibus urna id gravida pede justo eget pede curabitur eu ligula nec magna convallis condimentum fusce malesuada laoreet felis nulla sed risus vivamus laoreet accumsan odio quisque ut nibh tincidunt ante tincidunt feugiat nunc est massa ultrices id ullamcorper id laoreet at quam";
        
        $result = "";
        $count = 0;
        
        if ($classicStart)
        {
            $result .= $start;
            $count += 6;
        }

        $wordCount = mt_rand($minWords,$maxWords);
        
        $pool = preg_split('/ /',$ipsumText);

        for ($f=0;$f<$paragraphs;$f++)
        {
            for ($count;$count<=$wordCount;$count++)
            {
                if ($count!=0)
                    $word = $pool[mt_rand(0,count($pool)-1)];
                else
                {
                    $lastSentEnd = $count;
                    $commaCount = 0;
                    $word = ucwords($pool[mt_rand(0,count($pool)-1)]);
                }
                $endSentance = mt_rand(1,100)<10 && $count - $lastSentEnd >= 5 && $wordCount - $count >= 5 && $count - $lastComma >= 3 || $count - $lastSentEnd > 20;
                $putComma = mt_rand(1,100)<10 && $commaCount < 2 && $count - $lastComma >= 3 && !$endSentance && $wordCount - $count >= 3;
                
                if ($count!=$wordCount && !$endSentance && $count!=0 && !$putComma)
                    $result .= " ";
                    
                if ($endSentance)
                {
                    $lastSentEnd = $count;
                    $commaCount = 0;
                    $word = ucwords($word);
                    $result .= ". ";
                }
                
                if ($putComma)
                {
                    $lastComma = $count;
                    $commaCount ++;
                    $result .= ", ";
                }
                
                $result .= $word;
            }
            $result .= ".\n";
            $count = 0;
        }
        return $result;
    }

    // set/overwrite a variable in the query string
    static function querySetVar($userUrl,$varName,$varValue)
    {
        $wasThere = preg_match("/".$varName."=(?:.*?(&))|".$varName."=(?:.*?(\\z))/",$userUrl);
        $url = preg_replace("/".$varName."=(?:.*?(&))|".$varName."=(?:.*?(\\z))/",$varName."=".$varValue."\\1",$userUrl);
        if (!$wasThere)
            $url .=(strpos($url,"?")?"&":"?")."{$varName}={$varValue}";
        return $url;
    }

    //generates a hexadecimal random string
    static function genRandomHex($len)
    {
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

    //get the language currently used by phpDrone
    static function getLanguage($localeDir)
    {
        $codeLanguage = DroneConfig::get('Main.codeLanguage','en');
        if (isset($_SESSION['drone_language']))
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
                if (is_dir("$localeDir/{$language}") || $language==$codeLanguage)
                    return $language;
        }

        return $droneLanguage;
    }

    //get the location of the temporary directory
    static function getTempDir() //from http://www.phpit.net
    {
        if (!empty($_ENV['TMP']))
            return $_ENV['TMP'];
            
        elseif (!empty($_ENV['TMPDIR']))
            return  $_ENV['TMPDIR'];
            
        elseif (!empty($_ENV['TEMP']))
            return  $_ENV['TEMP'];
            
        else
            return  dirname(tempnam('', 'na'));
            
        return false;
    }

    //return the path to phpDrone library
    static function getDronePath()
    {
        return dirname(__FILE__);
    }


    //calculates the size of the image of size ($picWidth,$picHeight) that will feet inside a box of ($maxWidth,$maxHeight)
    static function getThumbSize($picWidth,$picHeight,$maxWidth,$maxHeight)
    {
        $scaleFactor = 1.0;
        if ($picWidth>$maxWidth || $picHeight>$maxHeight)
            $scaleFactor = min((float) $maxWidth/$picWidth,(float) $maxHeight/$picHeight);
        return array($scaleFactor*$picWidth,$scaleFactor*$picHeight);
    }

}
?>
