<?php
if (!isset($formIsLoaded))
    include("Form.php");
    
class Captcha extends Input
{
//     function __construct()
//     {
//         $this->captchaInput = new Input("*Code from image above","text","captcha");
//         $this->captchaInput->setValidator(validateCaptcha,"Wrong text from image");
//     }
    function generate($size)
    {
        session_start();
        $result = "";
        for ($f=0;$f<$size;$f++)
            $result=$result.chr(rand(65,90));
        $tmp_id = "";
        for ($f=0;$f<$size;$f++)
            $tmp_id=$tmp_id.chr(rand(0,255));
        $id = md5($tmp_id);
        $time = time();
        $_SESSION[$id]=$result;
        return $id;
    }

    function draw($id)
    {
        session_start();
        $text = $_SESSION[$id];
        if ($text!="")
        {
            header("Content-type: image/gif");
            $im     = imagecreate(160,50);
            $bkgColor = imagecolorallocate($im, 255, 255, 255);
            $frgColor = imagecolorallocate($im, 35, 52, 29);
            $font = realpath("res/captcha6.ttf");
            $px=10;
            for ($f=0;$f<strlen($text);$f++)
            {
                $angle = rand(-45,45);
                imagettftext($im, 25, $angle, $px, 40, $frgColor, $font, substr($text,$f,1));
                $px += 30;
            }
            for ($f=0;$f<1200;$f++)
                 imagesetpixel($im,rand(0,160),rand(0,50),$frgColor);
            for ($f=0;$f<1200;$f++)
                 imagesetpixel($im,rand(0,160),rand(0,50),$bkgColor);
            imagegif($im);
            imagedestroy($im);
        }
    }

    function write()
    {
        $template = new Template("phpDrone/templates/form/input_captcha.tmpl");

        $template->write("captchaId",$this->generate(5));
        $result = $template->getBuffer();
        $result .= $this->writeValueless();
        return $result;
    }

    function validate()
    {
        session_start();
        $value = $_REQUEST['captcha'];
        $captchaId = $_REQUEST['captchaId'];
        if (strtolower($_SESSION[$captchaId])==strtolower($value))
            return true;
        return false;
    }

}



// $tmp_captcha = new Captcha("*Code from image above","text","captcha");
// $tmp_captcha->draw($_GET['id']);

?>
