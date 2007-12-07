<?php
class DroneAttach
{
    function __construct($file)
    {
        if (file_exists($file) && $handle = fopen($file, "rb"))
        {
            $this->filename = $file;
            $this->content = fread($handle, filesize($file));
            fclose($handle);
        }
        else
            DroneCore::throwDroneError(dgettext("phpDrone","Can't attach file").": {$file}");
    }
    
    function getContent()
    {
        return chunk_split(base64_encode($this->content));
    }
    
    function getHeaders()
    {
        $result = "Content-Type: text/plain \r\n";
        $result .= "Content-Transfer-Encoding: base64 \r\n";
        $fileName = basename($this->filename);
        $result .= "Content-Disposition: attachment; \r\nfilename= \"{$fileName}\" \r\n\r\n";
        return $result;
    }
}

class DroneMail
{
    const TYPE_TEXT = 0;
    const TYPE_HTML = 1;
    const TYPE_MULTIPART = 2;

    function __construct($server,$port,$timeout=30)
    {
        $this->headers = array("Content-type: text/plain; charset= {%charEncoding%} \r\n",
                               "Content-type: text/html; charset= {%charEncoding%} \r\n",
                               'Content-type: multipart/mixed; \r\nboundary= "{%binaryBoundary%}" \r\n\r\n'
                              );


        $this->con = fsockopen($server, $port, $errno, $errstr, $timeout);
        if(!$this->con)
        {
            print "SMTP connect error {$errno}: {$errstr}";
        	exit;
        }
        fgets($this->con,256);

        $this->type = self::TYPE_TEXT;
        $this->mimeVersion = "1.0";
        $this->charEncoding = "US-ASCII";
        $this->binaryBoundary = "phpDroneBoundaryForBinaryContent";
        
        $this->data = "To: {%to%}\r\nFrom: {%from%}\r\nSubject: {%subject%}\r\nMIME-Version: {%mimeVersion%}\r\n";
        
        $this->attach = array();
    }


    function addAttachment($file)
    {
        array_push($this->attach,new DroneAttach($file));
    }

    private function cmd($command,$okCode,$error)
    {
        fwrite($this->con,$command."\r\n");
        $res = fgets($this->con,256);
        preg_match('/(?P<code>\\d{3})[ -](?P<text>.*)/', $res, $capt);
        if ($capt['code']!=$okCode)
             DroneCore::throwDroneError("SMTP error: {$error}<br />Server responded: {$capt['text']}");
    }

    function setFrom($email,$name="")
    {
        $this->raw_from = array($name,$email);
    }

    function getFrom()
    {
        if ($this->raw_from[0])
            return "\"{$this->raw_from[0]}\" <{$this->raw_from[1]}>";
        return "<{$this->raw_from[1]}>";
    }

    function setTo($email,$name="")
    {
        $this->raw_to = array($name,$email);
    }

    function getTo()
    {
        if ($this->raw_to[0])
            return "\"{$this->raw_to[0]}\" <{$this->raw_to[1]}>";
        return "<{$this->raw_to[1]}>";
    }

    function setSubject($subject)
    {
        $this->subject = $subject;
    }

    function setMessage($msg)
    {
        $this->message = $msg;
    }

    function setType($type)
    {
        $this->type = $type;
    }

    private function getBoundary()
    {
        return "--{$this->binaryBoundary} \r\n";
    }

    private function prepareData()
    {
        if (!count($this->attach))
        {
            $this->data .= $this->headers[$this->type];
            $this->data .= "\r\n{%message%}";
        }
        else
        {
            $this->data .= $this->headers[self::TYPE_MULTIPART];
            $this->data .= $this->getBoundary();
            $this->data .= $this->headers[$this->type];
            $this->data .= "\r\n{%message%}\r\n\r\n";
            foreach ($this->attach as $attach)
            {
                $this->data .= $this->getBoundary();
                $this->data .= $attach->getHeaders();
                $this->data .= $attach->getContent();
            }
            $this->data .= "--{$this->binaryBoundary}-- \r\n";
        }
        $this->data = preg_replace('/{%([^\\d\\s]+?)%}/', '{$this->$1}', $this->data);
        eval('$this->data = "'.addcslashes($this->data,'"').'";');
    }

    function send()
    {
        $this->cmd("HELO phpDrone","250","HELO failed");
        $userName = DroneConfig::get('SMTP.username');
        $password = DroneConfig::get('SMTP.password');
        
        if ($userName)
        {
            $this->cmd("auth login","334","Authentication init failed");
            $this->cmd(base64_encode($userName),"334","Invalid username");
            $this->cmd(base64_encode($password),"235","Authentication failed");
        }
        
        $this->from = $this->getFrom();
        $this->to = $this->getTo();
        $this->prepareData();
        print $this->data."\r\n.";
        $this->cmd("MAIL FROM:{$this->raw_from[1]}","250","MAIL FROM failed");
        $this->cmd("RCPT TO:<{$this->raw_to[1]}>","250","RCPT TO failed");
        $this->cmd("DATA","354","DATA failed");
        $this->cmd($this->data."\r\n.","250","SET MESSAGE failed");
        $this->cmd("QUIT","221","QUIT failed");
    }

    function __destuct()
    {
        fclose($this->con);
    }
}
?>
