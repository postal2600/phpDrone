<?php
mysql_connect($server, $user, $password) or die("Can't start connection to the databe.");
mysql_select_db($database);

function genRandomKey($size)
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

function fetch_users($data)
{
  $result=array();
  while($row = mysql_fetch_array($data, MYSQL_NUM))
    { 
      $result[count($result)]=array(strtolower($row[0]),$row[1]);
      
    }
  return $result;
}

function fetch_all($data)
{
  $result=array();
  while($row = mysql_fetch_array($data, MYSQL_NUM))
    {
        $result[count($result)]=array($row[0],$row[1],$row[2],$row[3]);
    }
  return $result;
}

function dict($tuple)
{
    $result=array();
    foreach($tuple as $item)
        $result[$item[0]]=$item[1];
    return $result;
}

?>
