<?php

//generates a $size length random string
function genRandomString($size)
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
function getCurrentUrl()
{
    return "http".(($_SERVER['HTTPS']=="on")?"s://":"://").$_SERVER['SERVER_NAME'].(($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"])).$_SERVER['REQUEST_URI'];
}

//get the referer of the page only if it came from the same host as the current page.
//$paranoic argument tells if the referer is on the same scheme (http/https/ftp) as the current url
function getSafeReferer($getQueryString=True,$paranoic=False)
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
function array_get($key,$array,$default=NULL)
{
    if (gettype($array)=="array")
        if (array_key_exists($key, $array))
            return $array[$key];
        else
            if ($default)
                return $default;
}

function advHttpQuery($r_query="")
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

function throwDroneError($msg)
{
    set_error_handler("handleDroneErrors");
    trigger_error($msg,E_USER_ERROR);
    restore_error_handler();
}

function printStackTrace()
{
    $stack = debug_backtrace();
    foreach($stack as $item)
        if ($item['function']!="printStackTrace" &&
            $item['function']!="handleDroneErrors" &&
            $item['function']!="trigger_error" &&
            $item['function']!="throwDroneError")
            print "<b>File:</b> {$item['file']}, <b>line</b> {$item['line']}, <b>in</b> {$item['function']}<br />";
}

function handleDroneErrors($errno, $errstr, $errfile, $errline)
{
    print "<b>phpDrone error:</b> <br />";
    if (preg_match_all('/require\(_droneSettings\.php\)/',$errstr,$some))
    {
        print "Your project does not have a <b>_droneSettings.php</b> file.<br />";
        die();
    }
    else
        print $errstr."<br />";
    
    set_error_handler("handleDroneErrors");
    require("_droneSettings.php");
    restore_error_handler();
    
    if ($debugMode)
    {
        print "<br /><b>Traceback:</b><br />";
        printStackTrace();
    }
    die();
}

function silentDeath()
{
    return True;
}
?>
