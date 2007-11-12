<?php
class DroneMail
{
    const MAIL_TYPE_TEXT = 1;
    const MAIL_TYPE_HTML = 2;

    function __construct($server,$port,$timeout=30)
    {
        $this->con = fsockopen($server, $port, $errno, $errstr, $timeout);
        if(!$this->con)
        {
            print "SMTP connect error {$errno}: {$errstr}";
        	exit;
        }
        fgets($this->con,256);
    }

    function setAuth($user,$pass)
    {
        $this->username = $user;
        $this->password = $pass;
    }


    private function cmd($command,$okCode,$error)
    {
        fwrite($this->con,$command."\r\n");
        $res = fgets($this->con,256);
        preg_match('/(?P<code>\\d{3})[ -](?P<text>.*)/', $res, $capt);
        if ($capt['code']!=$okCode)
             Utils::throwDroneError("SMTP error: {$error}<br />Server responded: {$capt['text']}");
    }

    function setFrom($email,$name="")
    {
        $this->from = array($name,$email);
    }

    function getFrom()
    {
        return "\"{$this->from[0]}\" <{$this->from[1]}>";
    }

    function setTo($email,$name="")
    {
        $this->to = array($name,$email);
    }

    function getTo()
    {
        return "\"{$this->to[0]}\" <{$this->to[1]}>";
    }

    function setSubject($subject)
    {
        $this->subject = $subject;
    }

    function setType($type)
    {
        $this->type = $type;
    }

    function setMessage($msg)
    {
        $this->message = $msg;
    }

    function send()
    {
        $this->cmd("HELO phpDrone","250","HELO failed");
        if (isset($this->username))
        {
            $this->cmd("auth login","334","Authentication init failed");
            $this->cmd(base64_encode($this->username),"334","Invalid username");
            $this->cmd(base64_encode($this->password),"235","Authentication failed");
        }
        $from = $this->getFrom();
        $to = $this->getTo();
        $this->cmd("MAIL FROM:{$this->from[1]}","250","MAIL FROM failed");
        $this->cmd("RCPT TO:<{$this->to[1]}>","250","RCPT TO failed");
        $this->cmd("DATA","354","DATA failed");
        $this->cmd("To: $to\r\nFrom: $from\r\nSubject: $this->subject\r\n$this->headers\r\n\r\n$this->message\r\n.","250","SET MESSAGE failed");
        $this->cmd("QUIT","221","QUIT failed");
    }

    function __destuct()
    {
        fclose($this->con);
    }
}
?>
