<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.4 beta
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

stdforms.php                         
Stadard useful forms        
Classes:
- TStdLoginForm - default login form  
*/

include_once("const.php");
include_once("forms.php");
include_once(CValues::$lang . ".inc");

class TStdLoginForm extends TForm
{
    public $frame;
    protected $loginfld;
    protected $passwfld;
    protected $descrtxt;
    protected $logintxt;
    protected $passwtxt;
    protected $entertxt;
    protected $captcha = false;
    protected $captchatxt;
    protected $captchafld;
    protected $captchaimg;
    private $maked = false;

    public function __construct($parent, $name = "loginform", $script = "", $type = 0, $autocreate = false)
    {
        if ($type > 9) {
            $type = $type - 10;
            $this->captcha = true;
        }
        parent::__construct($parent, $name, $script, $type);
        if ($autocreate) {
            $this->loginfld = "elogin";
            $this->passwfld = "epasswd";
            $this->CreateControls();
        }
    }

    private function CreateControls()
    {
        $this->FreeControl($this->frame); //reset form (e.g. if autocreated)
        if ($this->logintxt == "") $this->logintxt = lc_login;
        if ($this->passwtxt == "") $this->passwtxt = lc_password;
        if ($this->descrtxt != "") $this->frame = $this->AddFieldset($this->descrtxt); else $this->frame = $this;
        if ($this->entertxt == "") $this->entertxt = lc_enter;
        $this->logined = $this->frame->AddLabeledEdit($this->loginfld, $this->logintxt, 10);
        //if ($this->breakln) $this->frame->AddBR();
        $this->frame->AddLabel($this->passwfld, $this->passwtxt);
        $this->passwded = $this->frame->AddPassword($this->passwfld, 10);
        //if ($this->breakln) $this->frame->AddBR();
        if ($this->captcha) {
            if ($this->captchatxt == "") $this->captchatxt = lc_captcha;
            if ($this->captchafld == "") $this->captchafld = "captcha";
            if ($this->captchaimg == "") $this->captchaimg = "captcha_image.php";
            //if ($this->breakln) $this->frame->AddBR();
            $this->captchaed = $this->frame->AddLabeledEdit("captcha", $this->captchatxt, 10);
            $this->captchaim = $this->frame->AddImage($this->captchaimg, "CAPTCHA");
        }
        $this->frame->AddSubmit("submit", lc_enter);
        $this->maked = true;
    }

    public function SetCaptcha($captchaimg = "", $captchatxt = "", $captchafld = "")
    {
        $this->captcha = ($captchaimg !== false); //call SetCaptcha(false) to disable captcha
        if ($this->captcha) {
            $this->captchatxt = $captchatxt;
            $this->captchafld = $captchafld;
            $this->captchaimg = $captchaimg;
        }
    }

    public function MakeForm($descrtxt = "", $loginfld = "elogin", $passwfld = "epasswd")
    {
        $this->loginfld = $loginfld;
        $this->passwfld = $passwfld;
        $this->descrtxt = $descrtxt;
        $this->CreateControls();
    }

    public function GetComplete($offset)
    {
        if (!$this->maked) $this->CreateControls();
        return parent::GetComplete($offset);
    }
}

?>
