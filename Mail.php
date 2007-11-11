<?php
class Mail
{
    function __construct($server,$port,$timeout=30)
    {
        $this->con = fsockopen($server, $port, $errno, $errstr, $timeout);
        if(!$this->con)
        {
            print "SMTP server error {$errno}: {$errstr}";
        	exit;
        }
    }

    function setAuth($user,$pass)
    {
        $this->username = $user;
        $this->password = $pass;
    }

    private function cmd($command)
    {
        fwrite($this->con,$command."\r\n");
//         print "<b>".htmlentities($command)."</b>: <br />".htmlentities(fgets($this->con),256)."<br /><br />";
    }

    function send($from,$to,$subject,$message)
    {
        $smtp_server = fsockopen("mail.cvds.ro", 587, $errno, $errstr, 30);

        $this->cmd("HELO phpDroneMailer");
        if (isset($this->username))
        {
            $this->cmd("auth login");
            $this->cmd(base64_encode($this->username));
            $this->cmd(base64_encode($this->password));
        }
        $this->cmd("MAIL FROM:<{$from}>");
        $this->cmd("RCPT TO:<{$to}>");
        $this->cmd("DATA");
        $this->cmd($message);
        $this->cmd(".");
        $this->cmd("quit");
    }

    function __destuct()
    {
        fclose($this->con);
    }
}
?>
