<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.3
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

mail.php
Mail classes and functions 
Classes:
 - TMailH - SMTP helper, provides attach support, html mail, etc. Can send via MTA or directly   
*/
include_once("const.php");

class TMailH
{
    public $server;
    public $localname;
    public $port;
    public $login;
    public $password;
    public $attachpath;
    public $charset;      //e.g. KOI-8R
    public $srccharset;   //e.g. Windows-1251
    public $UseMTA;       //if true, MTA (e.g. sendmail) will be used, otherwise send trougth sockets
    public $debug = false;
    public $nl;           //line break (only for sockets)
    protected $UseTLS;    //if true tls:// added. Set to true if port is 465 or 587
    protected $headers;
    protected $multipart;
    protected $body;
    protected $text;
    protected $parts;
    protected $mode;      //0=text/plain; 1=text/html; 2=multipart/mixed (attach); 3=multipart/related (html+images)
    protected $plainonly; //controls correct text part heading for multipart/mixed messages

    public function __construct($server = "", $port = 25)
    {
        $this->parts = array();
        $this->server = $server;
        $this->port = $port;
        $this->nl = "\r\n";
        $this->UseMTA = ($this->server == "");
        $this->UseTLS = (($this->port == 465) || ($this->port == 587));
        $this->attachpath = $_SERVER['DOCUMENT_ROOT'] . "/uploads/";
        $this->charset = CValues::$charset;
        $this->srccharset = CValues::$charset;  //if srccharset and charset are different (and not empty) then iconv is used
        $this->localname = "localhost";
        $this->mode = 0;
        $this->plainonly = true;
    }

    public function SetAuthData($login, $password)
    {
        $this->login = $login;
        $this->password = $password;
        $this->UseMTA = false;
    }

    public function ClearMessage()
    {
        $this->mode = 0;
        $this->headers = "";
        $this->text = "";
        $this->body = "";
        $this->multipart = "";
        unset($this->parts);
        $this->parts = array();
    }

    public function AddHTML($html = "")
    {
        if ($this->mode == 0) $this->mode = 1;
        $this->plainonly = false;
        $this->text .= $html;
    }

    public function AddText($text = "")
    {
        $this->text .= $text;
    }

    public function AddHTMLImg($name, $path = "", $ctype = "image/jpeg")
    {
        $this->mode = 3;
        return $this->AddAttach($name, $path, $ctype, "inline");
    }

    public function AddAttach($name, $path = "", $ctype = "application/octet-stream", $mtype = "attachment")
    {
        if (!file_exists($path . $name)) {
            if (($path == "") && ($this->attachpath != "")) $path = $this->attachpath;
            if (!file_exists($path . $name)) {
                print "File " . $path . $name . " does not exist.";
                return false;
            }
        }
        $fp = fopen($path . $name, "r");
        if (!$fp) {
            print "File $path.$name coudn't be read.";
            return false;
        }
        if ($this->mode < 2) $this->mode = 2;
        $file = fread($fp, filesize($path . $name));
        fclose($fp);
        $this->parts[] = array("body" => $file, "name" => $name, "ctype" => $ctype, "mtype" => $mtype);
        return true;
    }

    public function CreateMessage()
    {
        $this->headers .= "MIME-Version: 1.0\n";
        $this->headers .= "Content-Type: " . $this->GetCType();
        if ($this->mode <= 1) {
            $this->headers .= "; Charset=" . $this->charset . "\nContent-Transfer-Encoding: 8bit\n";
            $this->body = $this->PrepareCharset($this->text) . "\n\n";;
        } else {
            $boundary = "=_" . md5(uniqid(time()));
            $this->headers .= "; boundary=\"$boundary\"\n";
            $htmlpart = "This is a MIME encoded message.\n\n" . $this->PrepareHTML($boundary);
            for ($i = (count($this->parts) - 1); $i >= 0; $i--)
                $this->multipart .= "--$boundary\n" . $this->CreatePart($i);
            $this->body = $this->InsertInlines($htmlpart) . "$this->multipart--$boundary--\n";
        }
    }

    protected function GetCType()
    {
        if ($this->mode == 0) return "text/plain";
        else if ($this->mode == 1) return "text/html";
        else if ($this->mode == 3) return "multipart/related";
        else return "multipart/mixed";
    }

    protected function PrepareCharset($txt)
    {
        if (($this->charset != $this->srccharset) && ($this->srccharset != "") && ($this->charset != ""))
            $txt = iconv($this->srccharset, $this->charset, $txt);
        return $txt;
    }

    protected function PrepareHTML($orig_boundary)
    {
        $s = "--$orig_boundary\n";
        if ($this->plainonly) $ct = "text/plain"; else $ct = "text/html";
        $s .= "Content-Type: $ct; charset=$this->charset\n";
        $s .= "Content-Transfer-Encoding: Quot-Printed\n\n";
        $s .= $this->PrepareCharset($this->text) . "\n\n";
        return $s;
    }

    protected function CreatePart($i)
    {
        $message_part = "";
        $message_part .= "Content-Type: " . $this->parts[$i]["ctype"];
        if ($this->parts[$i]["name"] != "")
            $message_part .= "; name = \"" . $this->parts[$i]["name"] . "\"\n";
        else $message_part .= "\n";
        $message_part .= "Content-Transfer-Encoding: base64\n";

        if ($this->parts[$i]["mtype"] == "inline") {
            $cid = "part$i.1234567890";
            $message_part .= "Content-ID: <$cid>\n";
            $this->parts[$i]["cid"] = $cid;
        }
        $message_part .= "Content-Disposition: " . $this->parts[$i]["mtype"] . "; filename = \"" . $this->parts[$i]["name"] . "\"\n\n";
        $message_part .= chunk_split(base64_encode($this->parts[$i]["body"])) . "\n";
        return $message_part;
    }

    protected function InsertInlines($s)
    {
        if ($this->mode == 3) {
            foreach ($this->parts as $part) {
                if ($part["mtype"] == "inline") {
                    $s = str_replace($part["name"], "cid:" . $part["cid"], $s);
                }
            }
        }
        return $s;
    }

    public function Send($to, $from, $subject = "", $headers = "")
    {
        if ($this->UseMTA) {
            $headers = "Reply-to: $from{$this->nl}" . $this->headers . "{$this->nl}$headers";
            return mail($to, $subject, $this->body, $headers);
        } else {
            $headers = $this->headers . "To: $to{$this->nl}From: $from{$this->nl}Subject: $subject{$this->nl}X-Mailer: GPCL Mail " . CConst::version(false) . "{$this->nl}$headers";
            if ($this->debug) echo "Begin!..\n";
            if ($this->server != "") {
                if ($this->UseTLS) $pref = "tls://"; else $pref = "";
                $smtpc = fsockopen($pref . $this->server, $this->port, $errno, $errstr, 15);
                if (!$smtpc) {
                    if ($this->debug) echo "Server $this->server. Connection failed: ($errno) $errstr<br />";
                    return false;
                }
            }
            if (!$this->CheckSuccess($smtpc)) return false;
            if ($this->debug) echo "EHLO $this->localname\n";
            fputs($smtpc, "HELO $this->localname{$this->nl}");
            if (!$this->CheckSuccess($smtpc)) return false;
            if (($this->login != "") && ($this->password != "")) {
                if ($this->debug) echo "AUTH LOGIN\n";
                fputs($smtpc, "AUTH LOGIN{$this->nl}");
                if (!$this->CheckSuccess($smtpc)) return false;
                if ($this->debug) echo "LOGIN\n";
                fputs($smtpc, base64_encode($this->login) . "{$this->nl}");
                if (!$this->CheckSuccess($smtpc)) return false;
                if ($this->debug) echo "PASS\n";
                fputs($smtpc, base64_encode($this->password) . "{$this->nl}");
                if (!$this->CheckSuccess($smtpc)) return false;
            }
            if ($this->debug) echo "MAIL FROM: $from\n";
            fputs($smtpc, "MAIL FROM: $from{$this->nl}");
            if (!$this->CheckSuccess($smtpc)) return false;
            if ($this->debug) echo "RCPT TO: $to\n";
            fputs($smtpc, "RCPT TO: $to{$this->nl}");
            if (!$this->CheckSuccess($smtpc)) return false;
            if ($this->debug) echo "DATA\n";
            fputs($smtpc, "DATA{$this->nl}");
            if (!$this->CheckSuccess($smtpc)) return false;
            if ($this->debug) echo "HEADERS: $headers\n";
            fputs($smtpc, $headers . "{$this->nl}");
            if ($this->debug) echo "BODY: $this->body";
            fputs($smtpc, $this->body);
            if ($this->debug) echo "END DATA: \n.\n";
            fputs($smtpc, "{$this->nl}.{$this->nl}");
            if (!$this->CheckSuccess($smtpc)) return false;
            if ($this->debug) echo "QUIT\n";
            fputs($smtpc, "QUIT{$this->nl}");
            fclose($smtpc);
            return true;
        }
    }

    protected function CheckSuccess($smtpc)
    {
        $data = fgets($smtpc, 4096);
        $code = intval(substr($data, 0, 3));
        if (in_array($code, array(220, 221, 235, 250, 251, 252, 334, 354))) {
            if ($this->debug) echo "Transfer ok: $data\n";
            return true;
        } else {
            if ($this->debug) echo "Transfer error ($code) $data\n";
            if ($code > 0) {
                fputs($smtpc, "\n.\nQUIT\n");
                fclose($smtpc);
                return false;
            }
            return true;
        }
    }
}

?>
