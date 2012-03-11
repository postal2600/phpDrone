function initphpDroneConsole()
{
    $('#drone_consoleBigBtn').bind('click',consoleBig);
    $('#drone_consoleSmallBtn').bind('click',consoleSmall);
    if (getLastConsoleState()=='1')
        consoleBig(null,0.001);
}

function getLastConsoleState()
{
    var myregexp = /phpDroneConsoleState=(\d)/;
    var match = myregexp.exec(document.cookie);
    if (match!=null)
        return match[1];    
}

function consoleBig(self,speed)
{
    if (speed==undefined)
        speed = 'medium'
    $('#droneConsoleBig').show(speed,BigEnd);
}

function BigEnd()
{
    $('#drone_consoleBigBtn').hide();
    $('#drone_consoleSmallBtn').show();
    document.cookie = "phpDroneConsoleState=1;"
}

function consoleSmall(self,speed)
{
    if (speed==undefined)
        speed = 'medium'
    $('#droneConsoleBig').hide(speed,SmallEnd);
}

function SmallEnd()
{
    $('#drone_consoleBigBtn').show();
    $('#drone_consoleSmallBtn').hide();
    document.cookie = "phpDroneConsoleState=0;"
}



initphpDroneConsole();
