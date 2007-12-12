var drone_oldColors = new Array();
function countChar()
{
    if (drone_taMaxLength!=undefined)
    {
        drone_count = Math.max(0,drone_taMaxLength - $(this).val().length);
        $("#drone_display_"+$(this).attr("id")).attr("value",drone_count);
        if (drone_count==0)
        {
            if (drone_oldColors[$(this).attr("id")]==undefined)
                drone_oldColors[$(this).attr("id")] = $(counterDisplay).css('background-color');
            $("#drone_display_"+$(this).attr("id")).css('background-color','#ffc1c1');
        }
        else
            if (drone_oldColors[$(this).attr("id")]!=undefined)
                $("#drone_display_"+$(this).attr("id")).css('background-color',drone_oldColors[$(this).attr("id")]);
    }
    else
        $(counterDisplay).attr("value",$(this).val().length);
}

$('.drone_limited_textarea').bind('keyup',countChar);
