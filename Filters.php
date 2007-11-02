<?php
function filter_trunc($input,$count=1)
{
    if (strlen($input)>$count)
        return substr($input, 0, $count)." ...";
    return $input;
}

function filter_toLower($input)
{
    return strtolower($input);
}

function filter_inc($input)
{
    return $input+=1;
}

function filter_obfuscate($input)
{
    $text = preg_split("//",$input);
    $text = implode("'+'",$text);
    $result = "<script type='text/javascript' src='phpDrone/res/scripts/obfuscater.js' /></script>\n";
    $result .= "<script type='text/javascript'>obfuscate('{$text}')</script>";
    return $result;
}

function filter_formatTime($input,$format)
{
    $input = (int) $input;
    return date($format,$input);
}
?>
