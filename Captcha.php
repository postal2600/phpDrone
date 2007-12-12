<?php
require_once("Input.php");
    
class Captcha extends Input
{
    function __construct($label,$name)
    {
        if (extension_loaded("gd"))
        {
            parent::__construct($label,"text",$name);
        }
        else
            DroneCore::throwDroneError("GD extension for PHP must be loaded to use Captcha inputs.<br />Get it from <a href='http://www.libgd.org/'>http://www.libgd.org/</a>.");
    }
    
    static function generate($size,$id="")
    {
        $result = "";
        for ($f=0;$f<$size;$f++)
            $result=$result.chr(rand(65,90));
        if ($id=="")
        {
            $tmp_id = "";
            for ($f=0;$f<$size;$f++)
                $tmp_id=$tmp_id.chr(rand(0,255));
            $id = md5($tmp_id);
        }
        $time = time();
        $_SESSION['done_captcha'][$id]=$result;
        return $id;
    }

    static function draw($id)
    {
        $text = $_SESSION['done_captcha'][$id];
        if ($text!="")
        {
            header("Content-type: image/gif");
            $im     = imagecreate(160,50);
            $bkgColor = imagecolorallocate($im, 255, 255, 255);
            $frgColor = imagecolorallocate($im, 35, 52, 29);
            $font = realpath(Utils::getDronePath()."/res/fonts/captcha6.ttf");
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
        die();
    }
    
    function write()
    {
        $template = new Template("?form/input_captcha.tmpl");

        $template->set("captchaId",$this->generate(5));
        $result = $template->getBuffer(false);
        $_POST[$this->name] = "";
        $result .= parent::write();
        return $result;
    }

    function validate()
    {
        $value = $this->request[$this->attributes['name']];
        $captchaId = $_REQUEST['captchaId'];
        if (strtolower($_SESSION['done_captcha'][$captchaId])==strtolower($value))
        {
            unset($_SESSION['done_captcha'][$captchaId]);
            return true;
        }
        $this->error = dgettext("phpDrone","The text didn't match image");
        return false;
    }
}
?>
