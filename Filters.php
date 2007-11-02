<?php
function filter_trunc($input)
{
    if (strlen($input)>70)
        return substr($input, 0, 70)." ...";
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
?>
