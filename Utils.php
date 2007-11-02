<?php
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

?>
