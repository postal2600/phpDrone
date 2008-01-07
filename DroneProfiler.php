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

    static function clear($label)
    {
        unset($_SESSION['droneProfilerTimes'][$label]);
    }

    static function buildResults()
    {
        $result = array();
        foreach (self::$startTimes as $key=>$value)
        {
            if (!isset($_SESSION['droneProfilerTimes'][$key]))
                $_SESSION['droneProfilerTimes'][$key] = array();
            if (self::$endTimes[$key])
                array_push($_SESSION['droneProfilerTimes'][$key],self::$endTimes[$key] - self::$startTimes[$key]);
            else
                array_push($_SESSION['droneProfilerTimes'][$key],-1);
        }
        return $result;
    }
}
?>
