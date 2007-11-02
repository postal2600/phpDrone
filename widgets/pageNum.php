<?php

    //till i find a way to include this from the Utils.php, i'll let it here. Very probably I'll have a function droneImport('library.php')
    function querySetVar($userUrl,$varName,$varValue)
    {
        $url = preg_replace("/".$varName."=(?:.*?(&))|".$varName."=(?:.*?(\\z))/",$varName."=".$varValue."\\1",$userUrl);
        if ($url==$userUrl)
            $url .=(strpos($url,"?")?"&":"?")."{$varName}={$varValue}";
        return $url;
    }

    switch ($_GET['action'])
    {
        case "setPage":
            $url = querySetVar($_SERVER['HTTP_REFERER'],"page",$_GET['page']);
            header("Location: {$url}");
            break;
        case "setPref":
            session_start();
            $_SESSION["drone_pn_{$_GET['prefName']}"] = $_GET['pref'];
            header("Location: {$_SERVER['HTTP_REFERER']}");
            break;
    }

?>

