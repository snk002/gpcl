<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.3
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

basedoc.php
Document classes.
 - TDocument - basic class to build an HTML page
 - TDocumentPart - successor of major document parts (head/body)
 - TDocumentHead - represents a document head (HTML head element)
 - TDocumentBody - represents a document head (HTML body element)
*/

include_once("const.php");
include_once("procs.php");
include_once("controls.php");
include_once(CValues::$lang . ".inc");

class TDocument extends TOnwedControl
{
    /*
       var $parentcontrol; //parent object reference
       var $tag;           //e.g. input
       var $attrs;         //array
       var $hasclose;      //has close tag or not?
       var $content;       //text between open and close tags
       var $controls;   //array;
       var $endcontent; //text between last child close tag and this element close tags
    */
  protected $depth;     //nesting level relative to website root, integer  
  protected $server;    //server name (domain only)
  protected $serveruri; //server name with protocol name (http://)  
  protected $path;      //file path  
  protected $filename;  //file name
//  protected $uri;       //full www path
  protected $prefix;    //relative path to server root (based on depth)
  protected $doctype;   //!DOCTYPE tag (complete). This is not included into controls
  protected $charset;   //charset value
  protected $title;     //title tag value
  public $head;         //TDocHead object
  public $body;         //TDocBody object
    protected $xhtmlattr; //if true adds xhtml attrs to html tag

    public function __construct($title, $checksrv = false)
    {
        if (CValues::$autoconv) {
            if (isset($_GET)) $_GET = ccb($_GET);
            if (isset($_POST)) $_POST = ccb($_POST);
        }
        parent::__construct(NULL);
        $this->tag = "html";
        $this->parentcontrol = NULL;
        $this->title = $title;
        $this->xhtmlattr = CConst::isxhtml();
        $this->doctype = CConst::doctype();
        $this->charset = CValues::$charset;
        $this->server = $_SERVER['SERVER_NAME'];
        if ($checksrv) {
            if ($this->server != CValues::$siteuri) die(lc_adenied . $_SERVER['SERVER_ADDR']);
        }
        $this->path = $_SERVER['REQUEST_URI'];
        $this->filename = basename($this->path);
        $this->serveruri = "http://" . $this->server;
//    $this->uri = $this->serveruri.$this->path; 
        $this->depth = similar_text($this->path, '///////////') - CValues::$basedepth;
//        $dir = substr(strrchr($this->path, '/'), 1);
//    if (($dir != "") && ($this->depth == 0)) $this->depth=1;
        $this->titlelvl = $this->depth + CValues::$basedepth;
        $this->SetPrefix();
        $this->head = new TDocumentHead($this, $this->title);
        $this->AddControl($this->head);
        $this->body = new TDocumentBody($this);
        $this->AddControl($this->body);
    }

    public function SetPrefix($val = "")
    {
        if ($val == "") for ($i = 0; $i < $this->depth; $i++) $val .= "../";
        $this->prefix = $val;
    }

    public function uri()
    {
        return $this->serveruri . $this->path;
    }

    public function Redirect($uri, $delay = 0, $code = 0, $msg = "")
    {
        $delay = intval($delay);
        if ($delay == 0) {
            if ($code > 0) header("HTTP/1.1 $code $msg");
            header("Location: $uri");
        } else {
            header("Refresh: $delay; URL=$uri");
        }
    }

    public function AddKeywords($val)
    {
        $ctrls = $this->head->GetControls("meta", "name", "keywords");
        if (count($ctrls) > 0) $ctrls[0]->attrs->AddValue("content", " " . $val);
        else $this->SetKeywords($val);
    }

    public function SetKeywords($val)
    {
        if ($val != "") {
            $this->head->DelMeta("name", "keywords");
            $this->head->AddMNAME("keywords", $val);
        }
    }

    public function AddDescription($val)
    {
        $ctrls = $this->head->GetControls("meta", "name", "description");
        if (count($ctrls) > 0) $ctrls[0]->attrs->AddValue("content", " " . $val);
        else $this->SetDescription($val);
    }

    public function SetDescription($val)
    {
        if ($val != "") {
            $this->head->DelMeta("name", "description");
            $this->head->AddMNAME("description", $val);
        }
    }

    public function SetTitle($val)
    {
        $this->head->SetTitle($val);
    }

    public function PrintAll($stdfooter = false)
    {
        print($this->GetComplete($stdfooter));
    }

    public function GetComplete($stdfooter = false)
    {
        if ($this->xhtmlattr) {
            $this->SetAttr("xmlns", CConst::$xmlns);
            $this->SetAttr("xml:lang", CValues::$lang);
            $this->SetAttr("lang", CValues::$lang);
        }
        if ($stdfooter) $this->body->AddControl($this->body->MakeStdFooter());
        return $this->doctype . "\n" . parent::GetComplete(" ");
    }
}

class TDocumentPart extends TBlockControl
{
    public function __construct($parent)
    {
        parent::__construct($parent);
    }
}

class TDocumentHead extends TDocumentPart
{
    protected $title; //typically inherited from document

    public function __construct($parent, $title)
    {
        parent::__construct($parent);
        $this->tag = "head";
        $this->SetTitle($title);
        if ($this->parentcontrol->charset != "") $s = "; Charset={$this->parentcontrol->charset}";
        else $s="";
        $this->AddMHTTP("Content-Type", "text/html$s");
        if (CValues::$defcss != "") $this->AddCSS(CValues::$defcss, true);
        if (CValues::$defjs != "") $this->AddJS(CValues::$defjs, true);
    }

    public function SetTitle($title)
    {
        if ($title != "") $this->title = $title;
        $ctrl = $this->GetFirstControl('title');
        if (!$ctrl) $ctrl = $this->CreateChildControl('title');
        $ctrl->content = $title;
    }

    public function AddMHTTP($type, $content)
    {
        $this->AddMeta("http-equiv", $type, $content);
    }

    public function AddMeta($attr, $attrval, $content)
    {
        $meta = $this->CreateChildControl('meta');
        $meta->SetAttr($attr, $attrval);
        $meta->SetAttr('content', $content);
        $meta->hasclose = false;
    }

    /* Adds tags like:
     <meta http-equiv="Content-Type" content="text/html; Charset=Windows-1251" />
     <meta http-equiv="Expires" content="Mon, 01 Jan 1990 00:00:01 GMT" />
    */

    public function AddCSS($file, $relative = true)
    {
        if ($relative) $relative = $this->parentcontrol->prefix;
        else $relative = "";
        $ctrl = $this->CreateChildControl('link');
        $ctrl->SetAttr('rel', 'stylesheet');
        $ctrl->SetAttr('href', $relative . $file);
        $ctrl->SetAttr('type', 'text/css');
        $ctrl->hasclose = false;
    }

    public function DelMeta($attr, $attrval)
    {
        $a = $this->GetControls("meta", $attr, $attrval);
        foreach ($a as $ctrl) {
            $this->FreeControl($ctrl);
        }
    }

    public function AddMNAME($type, $content)
    {
        $this->AddMeta("name", $type, $content);
    }

    public function InsertCSS($csscode)
    {
        $ctrl = $this->CreateChildControl('style');
        $ctrl->SetAttr('type', 'text/css');
        $ctrl->content = $csscode;
    }

    public function AddFavIcon($file, $relative = true)
    {
        if ($relative) $relative = $this->parentcontrol->prefix;
        else $relative = "";
        $ctrl = $this->CreateChildControl('link');
        $ctrl->SetAttr('rel', 'icon');
        $ctrl->SetAttr('href', $relative . $file);
        $ctrl->SetAttr('type', 'image/x-icon');
        $ctrl->hasclose = false;
    }
}

class TDocumentBody extends TDocumentPart
{
    public function __construct($parent)
    {
        parent::__construct($parent);
        $this->tag = "body";
    }

    public function GetStdFooter()
    {
        return $this->MakeStdFooter()->GetComplete("");
    }

    public function MakeStdFooter()
    {
        $obj = new TNoControlEx($this);
        $obj->AddBR();
        $obj->AddHR();
        $obj->AddNone()->content = CValues::$copyright;
        $obj->AddBR();
        $obj->CreateControl("small")->CreateChildControl("i")->content = CConst::engine();
        return $obj;
    }
}

?>
