<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.4 beta
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

    function __construct($title, $closed = true, $indiv = false, $redir = "index.php")
    {
        parent::__construct($title, false, $closed, $redir, $closed);
        if ($closed) {
            if (!$this->session->GetIsAdmin()) exit;
        }
        if ($indiv) $parent = $this->body->AddBlock("div", "body");
        else $parent = $this->body;
        $this->header = new THeader();
        $parent->AddControl($this->header);
        $this->bodyer = new TBodyer();
        $parent->AddControl($this->bodyer);
        $this->footer = new TFooter();
        $parent->AddControl($this->footer);
    }
}

?>
