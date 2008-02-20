<?php
class Controller
{
    static $args;
    static function handleSEFurl()
    {
        // .*index\.php/(?:(?<class>\w*)){0,1}(?:/(?<method>\w*)){0,1}(?:/(?<args>.*)){0,}
        if (preg_match('/.*index\\.php(?:\/(?:(?P<class>\\w+))){1}(?:\/(?P<method>\\w*)){0,1}(?:\/(?P<args>.*)){0,}/i', $_SERVER['PHP_SELF'], $urlParts))
        {
            $args = preg_split('/\//',$urlParts['args']);
            
            die();
        }
    }
}
?>
