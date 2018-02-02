<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.3
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

controls.php
HTML elements classes
Classes:
 - TBlockControl - parent class for most html elements
 - TObjectControl - represents HTML object element
 - TFlashControl - represents HTML object element with flash animation
 - TImgControl - represents HTML img element
 - TListControl -  represents HTML lists (ol/ul/dl); an successor of DB lists
*/

include_once("basecontrols.php");
include_once("tables.php");

class TBlockControl extends TOnwedControl
{
    public function __construct($parent, $tag = "div")
    {
        parent::__construct($parent, $tag);
    }

    public function AddHeading($level, $class = "", $id = "")
    {
        $level = intval($level);
        if ($level < 1) $level = 1;
        if ($level > 6) $level = 6;
        $tag = "h" . strval($level);
        return $this->AddBlock($tag, $class, $id);
    }

    public function AddBlock($tag = "div", $class = "", $id = "")
    {
        $ctrl = new TBlockControl($this, $tag);
        $ctrl->SetAttr('class', $class);
        $ctrl->SetAttr('id', $id);
        $this->AddControl($ctrl);
        return $ctrl;
    }

    public function AddPar($class = "", $id = "")
    {
        return $this->AddBlock("p", $class, $id);
    }

    public function AddLink($href, $hint = "", $hrefastext = false)
    {
        $ctrl = new TLinkControl($this, $href, $hint);
        if ($hrefastext) $ctrl->content = $href;
        if ((!$hrefastext) && ($hint != "")) $ctrl->content = $hint;
        $this->AddControl($ctrl);
        return $ctrl;
    }

    public function AddImage($src, $hint = "", $setsizes = false)
    {
        $ctrl = new TImgControl($this, $src, $hint, $setsizes);
        $this->AddControl($ctrl);
        return $ctrl;
    }

    public function AddSimple($tag = "br", $hasclose = false)
    {
        $ctrl = new TControl($this, $tag);
        $ctrl->hasclose = $hasclose;
        $this->AddControl($ctrl);
        return $ctrl;
    }

    public function AddFlash($src, $width = "", $height = "")
    {
        $ctrl = new TFlashControl($this, $src, $width, $height);
        $this->AddControl($ctrl);
        return $ctrl;
    }

    public function AddForm($name, $script = "", $type = 0)
    {
        $ctrl = new TForm($this, $name, $script, $type);
        $this->AddControl($ctrl);
        return $ctrl;
    }

    public function AddTable($cols, $rows, $autofill = true, $headrow = -1)
    {
        $ctrl = new TTableControl($this, $cols, $rows, $autofill, $headrow);
        $this->AddControl($ctrl);
        return $ctrl;
    }

    public function AddExTable($cols, $hashead = true, $hasfoot = false, $autofill = true)
    {
        $ctrl = new TExTableControl($this, $cols, $hashead, $hasfoot, $autofill);
        $this->AddControl($ctrl);
        return $ctrl;
    }

    public function AddList($type, $sep = "")
    {
        $ctrl = new TListControl($this, $type, $sep);
        $this->AddControl($ctrl);
        return $ctrl;
    }

    public function InsertJS($jscode)
    {
        $ctrl = $this->CreateChildControl('script');
        $ctrl->SetAttr('type', 'text/javascript');
        $ctrl->content = $jscode;
    }

    public function AddJS($file, $relative = true)
    {
        if ($relative) {
            $parent = $this->ParentDocument();
            if (isset($parent)) $relative = $parent->prefix;
            else $relative = "";
        } else {
            $relative = "";
        }
        $ctrl = $this->CreateChildControl('script');
        $ctrl->SetAttr('src', $relative . $file);
        $ctrl->SetAttr('type', 'text/javascript');
    }

    public function LoadFile($filename, $tocontrol = false)
    {
        $handle = fopen($filename, "r");
        $contents = fread($handle, filesize($filename));
        fclose($handle);
        if (!$tocontrol) $this->content = $contents;
        else $tocontrol->content = $contents;
    }

    public function LoadFileTpl($filename, $tocontrol = false)
    {
        $handle = fopen($filename, "r");
        $contents = fread($handle, filesize($filename));
        fclose($handle);
        if (!$tocontrol) $this->content = $this->ProcessTpl($contents);
        else $tocontrol->content = $this->ProcessTpl($contents);
    }

    /*
      abstract public function AddDBForm($name, $script="", $type=0);
      abstract public function AddDBList($type);
      abstract public function LoadDB($table, $tfield, $cfield, $cvalue, $db = null);
      abstract public function LoadDBTpl($table, $tfield, $cfield, $cvalue, $db = null);
    */
    public function ProcessTpl($tpl)
    {
        $parent = $this->ParentDocument();
        $patterns[0] = '/href=("|\')(?!((http)|(https)|(ftp)|(mailto)|(\/\/)))/';
        $patterns[1] = '/src=("|\')(?!((http)|(https)|(ftp)|(\/\/)))/';
        return preg_replace_callback($patterns, create_function('$matches', '
            $str=($matches[0]);
            $x=strlen($str);
            $str1=$str[$x];
            $str2=substr($str,0,$x);
            return $str2."' . $parent->prefix . '".$str1;'),
            $tpl);
    }
}

class TObjectControl extends TBlockControl
{
    protected $params;

    public function __construct($parent, $url = "", $type = "")
    {
        parent::__construct($parent, "object");
        $this->SetAttr("data", $url);
        $this->SetAttr("type", $type);
    }

    public function SetParam($name, $value)
    {
        if (isset($this->params[$name])) {
            $param = $this->params[$name];
            $param->SetAttr("value", $value);
        } else {
            $this->AddParam($name, $value);
        }
    }

    public function AddParam($name, $value)
    {
        $param = $this->AddSimple("param");
        $param->SetAttr("name", $name);
        $param->SetAttr("value", $value);
        $param->hasclose = false;
        $this->params[$name] = $param;
        return $param;
    }
}

class TFlashControl extends TObjectControl
{
    public function __construct($parent, $url = "", $width = "", $height = "")
    {
        parent::__construct($parent, $url, "application/x-shockwave-flash");
        $this->SetAttr("height", $height);
        $this->SetAttr("width", $width);
        $this->AddParam("movie", $url);
    }

    public function SetFile($url)
    {
        $this->SetAttr("data", $url);
        $this->SetParam("movie", $url);
    }
}

class TLinkControl extends TBlockControl
{
    public function __construct($parent, $href = "", $hint = "")
    {
        parent::__construct($parent, "a");
        $this->SetAttr("href", $href, true);
        $this->SetAttr("title", $hint);
    }
}

class TImgControl extends TControl
{
    protected $width;
    protected $height;

    public function __construct($parent, $src, $alt = "", $setsizes = false)
    {
        parent::__construct($parent, "img");
        $this->hasclose = false;
        $this->SetAttr("src", $src);
        $this->SetAttr("alt", $alt);
        if ($setsizes) {
            $imageinfo = getimagesize($src);
            $this->width = $imageinfo[0];
            $this->height = $imageinfo[1];
            if ($this->width > 0) $this->SetAttr("width", $this->width);
            if ($this->height > 0) $this->SetAttr("height", $this->height);
        }
    }

    public function SetManualSizes($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }
}

class TTableCell extends TBlockControl
{
    public function __construct($parent, $tag = "")
    {
        if ($tag == "") {
            if ($parent->isheading) $tag = "th"; else $tag = "td";
        }
        parent::__construct($parent, $tag);
    }
}

class TListControl extends TOnwedControl
{
    public $defaultvt;   //LI/DT
    public $basehref;  //for DL only, DD
    protected $itemtag;      //0 = UL, 1 = OL , 2 = DL, 3 = not a HTML list, text separator is used
    protected $itemtag2; //for 3 ((pseudo-list) type
    protected $type;    //if true sets default by the value attribute value, not by text
    protected $separator;     //if links used, this added as prefix (typically it should like document->prefix)

    public function __construct($parent, $type = 0, $sep = "")
    {
        parent::__construct($parent);
        $this->SetType($type, $sep);
        $this->defaultvt = false;
    }

    public function SetType($type, $sep = "")
    {
        if ($type > -1) $this->type = $type;
        $this->separator = $sep;
        switch ($this->type) {
            case 0:
                $this->tag = "ul";
                $this->itemtag = "li";
                break;
            case 1:
                $this->tag = "ol";
                $this->itemtag = "li";
                break;
            case 2:
                $this->tag = "dl";
                $this->itemtag = "dt";
                $this->itemtag2 = "dd";
                break;
            case 3:
                $this->tag = "div";
                $this->itemtag = "";
                $this->itemtag2 = "";
        }
    }

    public function AddItems($texts = array(), $texts2 = array(), $urls = array())
    {
        $i = 0;
        foreach ($texts as $txt) {
            if (isset($texts[$i])) $s = $texts[$i]; else $s = "";
            if (isset($texts2[$i])) $s2 = $texts2[$i]; else $s2 = "";
            if (isset($urls[$i])) $u = $urls[$i]; else $u = "";
            $this->AddItem($s, $s2, $u);
            $i++;
        }
    }

    public function AddItem($txt = "", $txt2 = "", $url = "")
    {
        if ($this->type != 3) $ctrl = new TBlockControl($this, $this->itemtag);
        else {
            $ctrl = new TNoControlEx($this);
            $ctrl->AddNone()->content = $this->separator;
        }
        $this->AddControl($ctrl);
        if ($this->type != 2) $url = $txt2;
        if ($txt != "")
            if ($url == "") $ctrl->content = $txt;
            else $ctrl->AddLink($this->basehref . $url, $txt);
        if ($this->type == 2) {
            $ctrl = new TBlockControl($this, $this->itemtag2);
            $this->AddControl($ctrl);
            if ($txt2 != "") $ctrl->content = $txt2;
        }
        return $ctrl; //returns DD control for DL list
    }

    public function AddList($type)
    {
        $ctrl = new TListControl($this, $type);
        $this->AddControl($ctrl);
        return $ctrl;
    }
}

?>
