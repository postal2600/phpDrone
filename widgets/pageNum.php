<?php
    switch ($_GET['action'])
    {
        case "setPage":
            $arr = parse_url($_SERVER['HTTP_REFERER']);
            $url = "{$arr['scheme']}://{$arr['host']}{$arr['path']}";
            header("Location: {$url}?page={$_GET['page']}");
            break;
        case "setPref":
            session_start();
            $_SESSION["drone_pn_{$_GET['prefName']}"] = $_GET['pref'];
            header("Location: {$_SERVER['HTTP_REFERER']}");
            break;
    }

?>

