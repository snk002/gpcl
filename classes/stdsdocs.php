<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.3
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

stddocs.php
Default document template. Useful for typical web pages. HTML5 compliant.
Classes:
 - TStdDBSDocument - DB-aware document with head, body and foot parts and session support
*/

include_once("stddbdocs.php");
include_once("session.php");

class TStdDBSDocument extends TDBSDocument
{
    public $header;
    public $bodyer;
    public $footer;

    function __construct($title, $closed = true)
    {
        parent::__construct($title, false, $closed, $redir = "index.php", $closed);
        if ($closed) {
            if (!$this->session->GetIsAdmin()) exit;
        }
        $this->header = new THeader();
        $this->body->AddControl($this->header);
        $this->bodyer = new TBodyer();
        $this->body->AddControl($this->bodyer);
        $this->footer = new TFooter();
        $this->body->AddControl($this->footer);
    }
}

?>
