<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.4 beta
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

session_login.php
Create session with simple checking plain username/password example
*/

set_include_path("../classes");
include("stdforms.php");
include("stdcheck.php");
include("documents.php");

/*
Create login form if not logged, or redirect to homepage if success.
Use admin/password to login. Also set homepage (success redirect) url.
*/
class LoginDoc extends TSDocument {

    private $homepage = "session_home.php"; //set right uri here
    private $checker;

    function __construct($title) {
        parent::__construct($title,false);
        $selfname = basename(__FILE__);
        $this->checker = new TCheckLogin($this->body);
        if ($this->IsLogged()) {
            //session_login.php?do=out to close session
            if (isset($_GET["do"]) && ($_GET["do"]=="out")) {
                $this->session->Disconnect();
                $this->Redirect($selfname,2);
                $this->body->AddBlock("p")->Content("Logged out");
            } else {
                $this->Redirect($this->homepage,2);
                $this->body->AddBlock("p")->Content("Already logged in");
            }
        } else if ($this->checker->CheckIn()) {
            //Login and password hardcoded here.
            if ($this->checker->SimpleCheck("admin","password")) {
                echo $this->checker->userid.", ".$this->checker->login;
                $this->checker->SetSession($this->homepage,1);
            } else {
                $this->Redirect($selfname,3);
                $this->KillSession("Wrong login or password");
            }
        } else {
            $frm = new TStdLoginForm(null, "loginfrom", $selfname, 1);
            $frm->MakeForm();
            $this->body->AddControl($frm);
        }
    }

}

$doc = new LoginDoc("Please log in");
$doc->PrintAll(true);

?>