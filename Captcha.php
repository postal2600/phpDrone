<?php
require_once("Input.php");
    
class Captcha extends DroneInput
{
    const width = 160;
    const height = 50;
    const alphabet = "ABCDEFGHJKLMNPQRSTUVWXYZ";


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
            $result=$result.substr(Captcha::alphabet,rand(0,strlen(Captcha::alphabet)-1),1);
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

    static function gradient($image, $startColor, $endColor)
    {
        $curColor = array($startColor[0],$startColor[1],$startColor[2]);

        $redStep = ($endColor[0]-$startColor[0])/Captcha::width;
        $greenStep = ($endColor[1]-$startColor[1])/Captcha::width;
        $blueStep = ($endColor[2]-$startColor[2])/Captcha::width;
        
        for ($f=0;$f<Captcha::width;$f++)
        {
            $curColor[0] += $redStep;
            $curColor[1] += $greenStep;
            $curColor[2] += $blueStep;
            
            $color = imagecolorallocate($image, $curColor[0], $curColor[1], $curColor[2]);
            imageline($image, $f, 0,$f, Captcha::height, $color);
        }
    }

    static function draw($id)
    {
        $text = $_SESSION['done_captcha'][$id];
        if ($text!="")
        {
            header("Content-type: image/gif");
            header('Cache-control: no-cache, no-store');
            $noiseFile = Utils::getDronePath()."/res/images/captcha_noise.png";
            $noiseSource = imagecreatefrompng($noiseFile);
            $im     = imagecreatetruecolor(Captcha::width,Captcha::height);
            $noiseBkg     = imagecreate(Captcha::width,Captcha::height);

            $noiseSourceSize = getimagesize($noiseFile);
            imagecopy($noiseBkg, $noiseSource, 0, 0, rand(0,$noiseSourceSize[0]-Captcha::width), rand(0,$noiseSourceSize[1]-Captcha::height), Captcha::width, Captcha::height);

            $gradientImg  = imagecreatetruecolor(Captcha::width,Captcha::height);
            $rndNoiseColor1 = array(rand(100,255), rand(100,255), rand(100,255));
            $rndNoiseColor2 = array(rand(50,150), rand(50,150), rand(50,150));

            Captcha::gradient($gradientImg,$rndNoiseColor1, $rndNoiseColor2);

            imagecolormatch($gradientImg, $noiseBkg);

            imagecopy($im, $noiseBkg, 0, 0, 0, 0, Captcha::width, Captcha::height);


            $font = realpath(Utils::getDronePath()."/res/fonts/arialbd.ttf");
            $px=30;
            for ($f=0;$f<strlen($text);$f++)
            {
                $frgColor = imagecolorallocatealpha($im, rand(0,70), rand(0,70), rand(0,70),rand(30,70));
                $angle = rand(-45,45);
                $py = rand(30,40);
                imagettftext($im, 25, $angle, $px, $py, $frgColor, $font, substr($text,$f,1));
                $px += 20;
            }

            imagegif($im);
            imagedestroy($im);
            imagedestroy($noiseSource);
        }
        die();
    }
    
    function write()
    {
        $template = new DroneTemplate("form/input_captcha.tmpl",true);

        $template->set("captchaId",$this->generate(5));
        $result = $template->getBuffer(false);
        $_POST[$this->name] = "";
        $result .= parent::write();
        return $result;
    }

    function validate()
    {
        $captchaId = $_REQUEST['captchaId'];
        $corectValue = $_SESSION['done_captcha'][$captchaId];
        unset($_SESSION['done_captcha'][$captchaId]);
        $value = $this->request[$this->attributes['name']];
        if (strtolower($corectValue)==strtolower($value))
            return true;
        $this->error = dgettext("phpDrone","The text didn't match image");
        return false;
    }
}
?>
