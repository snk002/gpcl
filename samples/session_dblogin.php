<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.3
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

session_dblogin.php
Create session with checking username/password given from database example
*/

set_include_path("../classes");
include("stdforms.php");
include("stdcheck.php");
include("stdsdocs.php");

/*
Create login form if not logged, or redirect to homepage if success.
Fill all $db... fields correctly by your database.
Do not forget to set db type, host, login and password in const.php!
Also set homepage (success redirect) url.
All other pages should inherit from TDBSDocument or classes inherited
from it, or have a TSession object.
*/
class LoginDoc extends TDBSDocument {
    
    private $dbtable    = "users";   //table name
    private $dbidfld    = "uid";     //id field (int)
    private $dbloginfld = "login";   //login field (str)
    private $dbpassfld  = "password";//password field (str)
    private $dbgroupfld = "groupid"; //group id field (vary, optional)
    private $dbnamefld  = "fullname";//full user name field (str, optional)
    private $homepage   = "session_home.php";//set right uri here
    private $checker;

    function __construct($title) {
        parent::__construct($title,false);
        $selfname = basename(__FILE__);
        $this->checker = new TCheckLogin($this->body);
        if ($this->IsLogged()) {
            //session_dblogin.php?do=out to close session
            if (isset($_GET["do"]) && ($_GET["do"]=="out")) {
                $this->session->Disconnect();
                $this->Redirect($selfname,2);
                $this->body->AddBlock("p")->Content("Logged out");
            } else {
                $this->Redirect($this->homepage,2);
                $this->body->AddBlock("p")->Content("Already logged in");
            }
        } else if ($this->checker->CheckIn()) {
            if ($this->checker->DBCheckMD5($this->dbtable,$this->dbloginfld,$this->dbpassfld,$this->dbidfld)) {
                $this->checker->SetSession($this->homepage,1);
                //comment line below if not required to load extra info (e.g. user preferences)
                $this->LoadPrefs();
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

    function LoadPrefs() {
        //You can add/remove user's extra stored preferences here
        $sql = "select * from $this->dbtable where $this->dbidfld = ".$this->session->GetUserId();
        $res = $this->db->Query($sql);
        if (!$res) return false;
        $usr = $this->db->FetchArray($res);
        $this->session->AddStoredPair("groupid", $usr[$this->dbgroupfld]);
        $this->session->AddStoredPair("fullname", $usr[$this->dbnamefld]);
        return true;
    }

}

$doc = new LoginDoc("Please log in");
$doc->PrintAll(true);

?>