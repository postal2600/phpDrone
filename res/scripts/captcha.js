function regenerate(captchaId)
{
//     if (navigator.appName!="Microsoft Internet Explorer")
        captchaImage = document.getElementById("captchaImage")
    // the random at the end is to skip the cache issue
    captchaImage.src = "phpDrone/Captcha.php?action=regen&id="+captchaId+"&stuff="+Math.floor(Math.random()*999999)
}
