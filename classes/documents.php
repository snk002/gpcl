<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.3
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

class TDBDocumentHead extends TDocumentHead
{
    public $db;

    public function __construct($parent, $title)
    {
        parent::__construct($parent, $title);
        $this->db = $this->parentcontrol->db;
    }
}

class TDBDocumentBody extends TDocumentBody
{
    public $db;

    public function __construct($parent)
    {
        parent::__construct($parent);
        $this->db = $this->parentcontrol->db;
    }
}

class TDBDocument extends TDocument
{
    public $db;

    public function __construct($title, $checksrv = false, $usr = '', $pwd = '', $host = '', $database = '')
    {
        $this->db = new TDBH($usr, $pwd, $host, $database);
        $this->connect();
        parent::__construct($title, $checksrv);
        $this->FreeControl($this->head);
        $this->head = new TDBDocumentHead($this, $this->title);
        $this->AddControl($this->head);
        $this->FreeControl($this->body);
        $this->body = new TDBDocumentBody($this);
        $this->AddControl($this->body);
    }

    public function connect($strong = false)
    {
        $this->db->link(false, $strong);
    }
}

class TDBSDocument extends TDBDocument
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
