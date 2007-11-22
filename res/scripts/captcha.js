function regenerate(captchaId)
{
    captchaImage = document.getElementById("captchaImage")
    // the random at the end is to skip the cacheing the captcha
    captchaImage.src = "?phpDrone_captcha_action=regen&phpDrone_captcha_id="+captchaId+"&anti_cache="+Math.floor(Math.random()*999999)
}
