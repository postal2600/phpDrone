function initphpDroneDebug()
{
    $('#drone_consoleBigBtn').bind('click',consoleBig);
    $('#drone_consoleSmallBtn').bind('click',consoleSmall);
}

function consoleBig()
{
    $('#droneConsoleBig').show('medium');
    $('#drone_consoleBigBtn').hide();
    $('#drone_consoleSmallBtn').show();
}

function consoleSmall()
{
    $('#droneConsoleBig').hide('medium');
    $('#drone_consoleBigBtn').show();
    $('#drone_consoleSmallBtn').hide();
}


initphpDroneDebug();
