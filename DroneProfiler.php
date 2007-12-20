<?php
class DroneProfiler
{
    static $startTimes = array();
    static $endTimes = array();

    static function start($label)
    {
        self::$startTimes[$label] = microtime(true);
    }

    static function stop($label)
    {
        self::$endTimes[$label] = microtime(true);
    }

    static function getResults()
    {
        $result = array();
        foreach (self::$startTimes as $key=>$value)
        {
            if (self::$endTimes[$key])
                $result[$key] = self::$endTimes[$key] - self::$startTimes[$key];
            else
                $result[$key] = "Error: Timing started but not stoped!";
        }
        return $result;
    }

}
?>
