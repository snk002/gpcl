<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.3
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

stddocs.php
Default document template. Useful for typical web pages. HTML5 compliant.
Classes:
 - TStdBlock - TBlockControl-base class represents standard document block
 - THeader - Upper (head) body part 
 - TBodyer - Central (main) body part
 - TFooter - Lower (bottom) body part
 - TStdDocument - document with head, body and foot parts
 - TStdDBDocument - DB-aware clone of TStdDocument
*/

include_once("dbcontrols.php");
include_once("documents.php");

class TStdBlock extends TDBBlockControl
{
    public function __construct($parent, $class = "", $tag = "div")
    {
        parent::__construct($parent, $tag);
        if ($class != "") $this->SetAttr('class', $class);
    }
}

class THeader extends TStdBlock
{
    public $logo;
    public $menu;
    public $home;

    public function __construct($parent = NULL)
    {
        if (CValues::$doctype == "HTML5") $tag = "header"; else $tag = "div";
        parent::__construct($parent, "header", $tag);
        if (CValues::$doctype == "HTML5") $tag = "section"; else $tag = "div";
        $this->logo = new TStdBlock($this, "logo", $tag);
        $this->AddControl($this->logo);
        if (CValues::$doctype == "HTML5") $tag = "hgroup"; else $tag = "div";
        $this->home = new TStdBlock($this, "home", $tag);
        $this->AddControl($this->home);
        if (CValues::$doctype == "HTML5") $tag = "nav"; else $tag = "div";
        $this->menu = new TStdBlock($this, "menu", $tag);
        $this->AddControl($this->menu);
    }
}

class TBodyer extends TStdBlock
{
    public $main;
    public $tools;
    public $menu;

    public function __construct($parent = NULL)
    {
        parent::__construct($parent, "bodyer");
        if (CValues::$doctype == "HTML5") $tag = "nav"; else $tag = "div";
        $this->menu = new TStdBlock($this, "menu", $tag);
        $this->AddControl($this->menu);
        if (CValues::$doctype == "HTML5") $tag = "article"; else $tag = "div";
        $this->main = new TStdBlock($this, "main", $tag);
        $this->AddControl($this->main);
        if (CValues::$doctype == "HTML5") $tag = "aside"; else $tag = "div";
        $this->tools = new TStdBlock($this, "tools", $tag);
        $this->AddControl($this->tools);
    }
}

class TFooter extends TStdBlock
{
    public $about;
    public $menu;

    public function __construct($parent = NULL)
    {
        if (CValues::$doctype == "HTML5") $tag = "footer"; else $tag = "div";
        parent::__construct($parent, "footer", $tag);
        if (CValues::$doctype == "HTML5") $tag = "nav"; else $tag = "div";
        $this->menu = $this->AddControl(new TStdBlock($this, "menu", $tag), true);
        if (CValues::$doctype == "HTML5") $tag = "section"; else $tag = "div";
        $this->about = $this->AddControl(new TStdBlock($this, "about", $tag), true);
    }
}

class TStdDBDocument extends TDBDocument
{
    public $header;
    public $bodyer;
    public $footer;

    function __construct($title)
    {
        parent::__construct($title);
        $this->header = $this->body->AddControl(new THeader(), true);
        $this->bodyer = $this->body->AddControl(new TBodyer(), true);
        $this->footer = $this->body->AddControl(new TFooter(), true);
    }
}

?>
