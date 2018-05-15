<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.4 beta
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

session.php
File system classes and routenes, includes upload  
Classes:
- TSession - PHP session helper 
*/

include_once("const.php");

class TSession extends TObject
{
    protected $user;        //session user id
    protected $username;    //most used string value
    protected $storedprops; //array

    public function __construct($pvt = true, $redir = "", $user = 0, $username = "")
    {
        parent::__construct(null);
        session_start();
        $newsess = false;
        if (sizeof($_SESSION) > 0)
            $this->user = intval($_SESSION['userid']); else $newsess = true;
        if ($newsess) {
            $this->SetUserdata($user, $username);
        }
        if ($pvt) {
            if ((getenv('REQUEST_METHOD') != 'POST') && ($this->user < 1)) {
                if ($redir != "") {
                    $this->Redirect($redir, 2);
                    $s = "<a href=\"$redir\">login</a>";
                } else $s = "login";
                die("Please $s first");
            }
            if (($this->user < 1) || $newsess) {
                die('Session Error');
            }
        }
        if (sizeof($_SESSION) > 1) {
            $this->username = $_SESSION['username'];
            $this->LoadStored();
        }
    }

    public function SetUserdata($id = 0, $name = "")
    {  // use carefully, it replaces $_SESSION[] id values
        $this->user = $id;
        $this->username = $name;
        $_SESSION['userid'] = $this->user;
        $_SESSION['username'] = $this->username;
    }

    public function Redirect($uri, $delay = 0)
    {
        $delay = intval($delay);
        if ($delay == 0) header("Location: $uri");
        else header("Refresh: $delay; URL=$uri");
    }

    protected function LoadStored()
    {
        foreach ($_SESSION as $k => $v) {
            if (($k != 'userid') && ($k != '') && ($k != 'username')) {
                $this->AddStoredPair($k, $v);
            }
        }
    }

    public function AddStoredPair($name, $value)
    {
        $this->storedprops[$name] = $value;
    }

    public function SaveStored($inclusr = false)
    {
        if (isset($this->storedprops)) {
            foreach ($this->storedprops as $k => $v) {
                $_SESSION[$k] = $v;
            }
        }
        if ($inclusr) {
            $this->SetUserdata($this->user, $this->username);
        }
    }

    public function SetIsAdmin($haveadmin)
    {
        if ($haveadmin) $this->AddStoredPair('admin', 200);
        else $this->AddStoredPair('admin', 0);
    }

    public function GetIsAdmin()
    {
        return (intval($this->GetStoredValue('admin')) == 200);
    }

    public function IsActive()
    {
        return ($this->user > 0);
    }

    public function GetStoredValue($name)
    {
        if (isset($this->storedprops[$name])) return $this->storedprops[$name];
        else return false;
    }

    public function GetUserId() {
        return $this->user;
    }

    public function GetUserName() {
        return $this->username;
    }

    public function Disconnect()
    {
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        $this->user = 0;
        $this->username = "";
        session_unset();
        session_destroy();
    }
}

?>
