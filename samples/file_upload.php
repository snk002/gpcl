<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.3
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

file_upload.php
File uploads example
*/

set_include_path("../classes");
require_once("controls.php");
require_once("documents.php");
require_once("forms.php");
require_once("files.php");

class TSampleUploadForm extends TForm {
    protected $path;  //path to uploads directory on server
    public $filename;
    public function __construct($parent, $name = "uploadform", $script = "", $path = "") {
        parent::__construct($parent, $name, $script, 2);
        $this->SetPath($path);
        $this->MakeForm();
    }
    public function SetPath($path) {
        if ($path!="") $this->path = $path;
        else $this->path = $_SERVER['DOCUMENT_ROOT']."/".CValues::$uploadbase;
        $this->path = AddSlash($this->path);
    }
    /* Override MakeForm function to add more controls */
    public function MakeForm() {
        $this->AddLabel("uploadfile","Select file to upload:");
        $this->AddFile("uploadfile");
        $this->AddBR();
        $this->AddHR();
        $this->AddSubmit("upload","Begin upload");
    }
    /* Override this to support upload multiple files (e.g. with foreach loop) */
    public function ProcessPostFiles($name = "uploadfile") {
        if ( ($_FILES[$name]['type'] != "image/gif") && ($_FILES[$name]['type'] != "image/jpeg") && ($_FILES[$name]['type'] != "image/png") ) {
            $this->filename = $this->UploadFile($name);
        } else {
            $this->filename = $this->UploadImage($name);
        }
    }
    public function UploadFile($file) {
        if ($_FILES[$file]["name"]!="") {
            $up = new TUploadH($file,$this->path);
            $up->checkimage = false;
            if (!$up->Upload()) {
                return "";
            }
            return $file["name"];
        }
        return "";
    }
    public function UploadImage($file, $tumbx = 0, $tumby = 0) {
        $maketumb = (($tumbx > 0) && ($tumby > 0));
        if ($_FILES[$file]["name"]!="") {
            $up = new TUploadH($file,$this->path);
            $up->checkimage = true;
            if (!$up->Upload()) {
                return "";
            } else {
                if ($maketumb) {
                    $pic = new TIMGH($up->fullpath());
                    $pic->resize($tumbx,$tumby,$this->path."tumb-".$up->filename);
                }
                return $_FILES[$file]["name"];
            }
        }
        return "";
    }
}

class UploadDocument extends TDocument {
    private $uplouadf;
    function __construct($title) {
        parent::__construct($title);
        $this->body->AddBlock("h3")->content = "Upload form example";
        $this->uplouadf = $this->body->AddControl(new TSampleUploadForm($this), true);
        if (isset($_POST["upload"])) {
            $this->uplouadf->ProcessPostFiles();
            if ($this->uplouadf->filename != "") $this->body->AddBlock("p")->content = "Upload {$this->uplouadf->filename} ok :)";
            else $this->body->AddBlock("p")->content = "Upload failed :(";
        }
    }
}

$doc = new UploadDocument("Upload test");
$doc->PrintAll(true);
?>
