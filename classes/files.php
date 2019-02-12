<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.4 beta
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

files.php
File system classes and routenes, includes upload  
Classes:
- TUploadH - file upload helper
*/

include_once("cconst.php");
include_once("procs.php");

class TUploadH extends TControl
{
    public $uploaddir;  //uploads dir on server (path)
    public $filename;   //file name only (excl. path)
    public $checkimage; //if true checks file for image (gif, jpeg, or png)
    public $imagetype;  //if checkimage is true, this contain type of image, e.g. image/gif
    public $silent;     //if true no error messages displayed
    public $blacklist;  //array of forbidden extentions
    public $multi;      //multiple files support

    public function __construct($name = "", $uploaddir = "", $multi = false)
    {
        parent::__construct(NULL);
        $this->name = $name;
        $this->multi = $multi;
        $this->silent = true;
        $this->blacklist = array(".php", ".phtml", ".php3", ".php4", ".htm", ".html", ".xhtml", ".asp", ".jsp", ".aspx", ".exe", ".com", ".sh");
        if ($uploaddir == "") $this->uploaddir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/";
        else $this->uploaddir = $uploaddir;
    }

    public function GetFileExt($filename = "")
    {
        if ($filename == "") $filename = $_FILES[$this->name]['name'];
        return substr(strrchr($filename, '.'), 1);
    }

    public function CheckForImage($deep = false, $i = -1) {
        if ($i>=0) {
            $ftype = $_FILES[$this->name]['type'][$i];
            $fname = $_FILES[$this->name]['name'][$i];
            $tname = $_FILES[$this->name]['tmp_name'][$i];
        } else {
            $ftype = $_FILES[$this->name]['type'];
            $fname = $_FILES[$this->name]['name'];
            $tname = $_FILES[$this->name]['tmp_name'];
        }
        if (($ftype != "image/gif") && ($ftype != "image/jpeg") && ($ftype != "image/png")) {
            if (!$this->silent) echo $ftype . ": CHECK MIMETYPE FAILS<br />";
            return false;
        }
        if ($deep) {
            $imageinfo = getimagesize($tname);
            if ($imageinfo['mime'] != 'image/gif' && $imageinfo['mime'] != 'image/jpeg' && $imageinfo['mime'] != 'image/png') {
                if (!$this->silent) echo $fname . ": CHECK IMAGESIZE FAILS<br />";
                return false;
            }
            $this->imagetype = $imageinfo['mime'];
        }
        return true;
    }

    public function Upload($filename = "", $i=-1)
    {
        if (($this->multi) && ($i>=0)) {
            $fname = $_FILES[$this->name]['name'][$i];
            $tname = $_FILES[$this->name]['tmp_name'][$i];
        } else {
            $fname = $_FILES[$this->name]['name'][0];
            $tname = $_FILES[$this->name]['tmp_name'][0];
        }
        foreach ($this->blacklist as $item) {
            if (preg_match("/$item\$/i", $fname)) {
                if (!$this->silent) echo $fname . ": CHECK EXTENTION FAILS<br />";
                return false;
            }
        }
        if ($this->checkimage) {
            if (!$this->CheckForImage(true,$i)) return false;
        }
        if ($filename == "") $this->filename = basename($fname);
        else $this->filename = $filename;
        $uploadfile = $this->uploaddir . $this->filename;
        @mkdir($this->uploaddir, 0777, true);
        return (move_uploaded_file($tname, $uploadfile));
    }

    public function CheckOn()
    {
        return (basename($_FILES[$this->name]['name']) != "");
    }

    public function fullpath()
    {
        return $this->uploaddir . $this->filename;
    }

    public function GetInput($name = "", $attrs = "")
    {
        if ($name != "") $this->name = $name;
        return "<input name=\"$this->name\" $attrs />";
    }

    public function GetFormOpen($formname, $script = "")
    {
        if ($script == "") $script = $_SERVER['PHP_SELF'];
        return "<form name=\"$formname\" id=\"$formname\" enctype=\"multipart/form-data\" action=\"$script\" method=\"POST\">";
    }
}

?>
