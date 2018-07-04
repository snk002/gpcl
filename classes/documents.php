<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.4 beta
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

documents.php
Document classes.
 - TDBDocumentHead
 - TDBDocumentBody
 - TDBDocument
 - TDBSDocument
*/

include_once("basedoc.php");
include_once("db.php");
include_once("session.php");

class TDBDocument extends TDocument
{
    public $db;

    public function __construct($title, $checksrv = false, $usr = '', $pwd = '', $host = '', $database = '')
    {
        $this->db = new TDBH($usr, $pwd, $host, $database);
        $this->connect();
        parent::__construct($title, $checksrv);
        $this->FreeControl($this->head);
        $this->head = new TDocumentHead($this, $this->title);
        $this->AddControl($this->head);
        $this->FreeControl($this->body);
        $this->body = new TDocumentBody($this);
        $this->AddControl($this->body);
    }

    public function connect($strong = false)
    {
        return $this->db->link(false, $strong);
    }
}


interface iSessionDoc {
    public function IsLogged();
    public function KillSession($msg = "");
}

class TSDocument extends TDocument implements iSessionDoc
{

    public $session;

    public function __construct($title, $checkserv = false, $pvt = false, $redir = "")
    {
        $this->session = new TSession($pvt, $redir);
        parent::__construct($title, $checkserv);
    }

    public function IsLogged()
    {
        return intval($this->session->user) > 0;
    }

    public function KillSession($msg = "")
    {
        $this->session->Disconnect();
        if ($msg != "") echo "<p>$msg</p>\n";
        unset($this->session);
    }

}

class TDBSDocument extends TDBDocument implements iSessionDoc
{
    public $session;
    protected $isadmin;

    public function __construct($title, $checkserv = false, $pvt = false, $redir = "", $isadmin = false)
    {
        $this->session = new TSession($pvt, $redir);
        parent::__construct($title, $checkserv);
        if ($isadmin) {
            $this->isadmin = $this->session->GetIsAdmin();
            if (!$this->isadmin) {
                $this->KillSession(lc_unlogged);
            }
        }
    }

    public function KillSession($msg = "")
    {
        $this->session->Disconnect();
        if ($msg != "") echo "<p>$msg</p>\n";
        unset($this->session);
    }

    public function IsAdmin()
    {
        return $this->session->GetIsAdmin();
    }

    public function IsLogged()
    {
        return intval($this->session->user) > 0;
    }

    public function __destruct()
    {
        if (isset($this->session)) {
            $this->session->SaveStored(true);
        }
    }
}

?>
