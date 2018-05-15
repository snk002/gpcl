<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.4 beta
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

captcha.php
Captcha generator 
Classes:
 - TGaptcha - create captcha
 
Usage:
$c = new TCaptcha();
$c->fontname = "./arialbd.ttf";
$c->fontsize = 18;
$c->fontcolor = 0xa0040f;
$c->bgcolor = 0xfeecca;
$c->bgfilename = "background.jpg";
$c->angle = -2;
$c->waves = 3;
$c->lines = 1;
$c->GenerateText();
$c->Make("captcha.jpg");
*/
include_once("img.php");

class TCaptcha extends TIMGH
{
    public $ctext;        //captcha text
    public $symbols;      //array with symbols to generate captcha text
    public $minlength;    //minimal captcha text length
    public $maxlength;    //maximal captcha text length
    public $bgcolor;      //background color
    public $fontcolor;    //foreground color
    public $waves;        //0 = no, 1 = H, 2 = V, 3 = H+V
    public $lines;        //0 = no, 1 = bg, 2 = fg, 3 = bg+fg
    public $angle;        //use +/- 2-3 deg. max. typical
    public static $LINES_NO = 0; //no grid
    public static $LINES_BG = 1; //grid by background color
    public static $LINES_FG = 2; //grid by foreground color
    public static $LINES_FB = 3; //two grids, background & foreground
    public static $WAVES_NO = 0; //fit text by straight line
    public static $WAVES_HO = 1; //fit text by horizontal wave path
    public static $WAVES_VO = 2; //fit text by vertical wave path
    public static $WAVES_HV = 3; //fit text by horizontal & vertical wave paths

    public function __construct($fn = '')
    {
        parent::__construct($fn);
        $this->width = 130;
        $this->height = 50;
        $this->symbols = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "A", "B", "C", "D", "E", "F", "G", "Q", "H", "J", "K", "L", "M", "N", "V", "P", "R", "S", "T", "U", "W", "X", "Y", "Z");
        $this->minlength = 5;
        $this->maxlength = 7;
        $this->bgcolor = 0xffffff;
        $this->fontcolor = 0x000000;
        $this->waves = TCaptcha::$WAVES_HO;
        $this->lines = TCaptcha::$LINES_NO;
        $this->angle = 0;
        $this->typeid = TIMGH::$PNG; //PNG by default
    }

    public function Make($fn = NULL)
    {
        if ($fn <> '') $this->newname = $fn;
        if ($this->bgfilename != "") {
            if (!$this->init($this->bgimg, $this->bgfilename)) {
                echo 'Error on init bg image file';
                return false;
            }
        } else {
            $this->bgimg = imagecreatetruecolor($this->width, $this->height);
            imagefill($this->bgimg, 0, 0, $this->bgcolor);
        }
        if ($this->fontname == "") {
            imagestring($this->bgimg, $this->fontsize, 10, 15, $this->ctext, $this->fontcolor);
        } else {
            imagettftext($this->bgimg, $this->fontsize, $this->angle, rand(1, 5), rand($this->fontsize * 2, $this->fontsize * 2.5), $this->fontcolor, $this->fontname, $this->ctext);
        }
        $this->img = $this->bgimg;
        if (($this->waves > 0) || ($this->lines > 0)) {
            $this->img = imagecreatetruecolor($this->width, $this->height);
            imagefill($this->img, 0, 0, $this->bgcolor);
        }
        if (($this->waves == 1) || ($this->waves == 3)) {
            $this->Wave();
        }
        if (($this->lines == 1) || ($this->lines == 3)) {
            $this->LinesH($this->bgcolor, 12, 8, $this->bgimg);
            $this->LinesV($this->bgcolor, 20, 4, $this->bgimg);
            $this->img = $this->bgimg;
        }
        if (($this->waves > 1) || ($this->lines > 1)) {
            $this->img = imagecreatetruecolor($this->width, $this->height);
            imagefill($this->img, 0, 0, $this->bgcolor);
        }
        if (($this->lines == 2) || ($this->lines == 3)) {
            $this->LinesH($this->fontcolor, 20, 10, $this->bgimg);
            $this->LinesV($this->fontcolor, 30, 6, $this->bgimg);
            if ($this->waves <= 1) $this->img = $this->bgimg;
        }
        if (($this->waves == 2) || ($this->waves == 3)) {
            $this->Wave2();
        }
        if ($this->newname == NULL) {
            Header("Pragma: no-cache");
            Header($this->GetHTTPHead());
        }// else echo $this->newname;
        switch ($this->typeid) {
            case TIMGH::$GIF:
                if ($this->newname == NULL) imagegif($this->img); else imagegif($this->img, $this->newname);
                break;
            case TIMGH::$JPG:
                imagejpeg($this->img, $this->newname, $this->quality);
                break;
            case TIMGH::$PNG:
                $qty = 1 + round(($this->quality - 1) / 10);
                imagepng($this->img, $this->newname, $qty);
                break;
        }
        return true;
    }

    protected function Wave()
    {
        for ($x = 0; $x < $this->width; $x++) {
            if ($this->fontname != "") $k = 4 + $this->fontsize / 2; else $k = 10;
            $new_y = round(COS($x / $k + 12) * 10);
            for ($y = 0; $y < $this->height; $y++) {
                if (($y + $new_y <= 0) || ($y + $new_y >= $this->height)) $rgb = imagecolorat($this->bgimg, $x, 1);
                else $rgb = imagecolorat($this->bgimg, $x, $y + $new_y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $color = imagecolorallocate($this->img, $r, $g, $b);
                imagesetpixel($this->img, $x, $y, $color);
            }
        }
        $this->bgimg = $this->img;
    }

    protected function LinesH($color, $dest, $rand = 0, $img = NULL)
    {
        if (!$img) $img = $this->img;
        if ($rand == 0) {
            $rand = $dest;
            $o = 0;
        } else $o = round($rand / 2);
        for ($y = 0; $y <= $this->height; $y = $y + rand($dest, $rand)) {
            imageline($img, 0, $y + rand(-$o, $o), $this->width, $y + rand(-$o, $o), $color);
        }
    }

    protected function LinesV($color, $dest, $rand = 0, $img = NULL)
    {
        if (!$img) $img = $this->img;
        if ($rand == 0) {
            $rand = $dest;
            $o = 0;
        } else $o = round($rand / 2);
        for ($x = 0; $x <= $this->width; $x = $x + rand($dest, $rand)) {
            imageline($img, $x + rand(-$o, $o), 0, $x + rand(-$o, $o), $this->height, $color);
        }
    }

    protected function Wave2()
    {
        for ($y = 0; $y < $this->height; $y++) {
            if ($this->fontname != "") $k = 6 + $this->fontsize / 2; else $k = 14;
            $new_x = round(COS($y / $k + 15) * 10);
            for ($x = 0; $x < $this->width; $x++) {
                if (($x + $new_x <= 0) || ($x + $new_x >= $this->width)) $rgb = imagecolorat($this->bgimg, 1, $y);
                else $rgb = imagecolorat($this->bgimg, $x + $new_x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $color = imagecolorallocate($this->img, $r, $g, $b);
                imagesetpixel($this->img, $x, $y, $color);
            }
        }
        $this->bgimg = $this->img;
    }

    public function GenerateText()
    {
        $len = rand($this->minlength, $this->maxlength);
        $s = "";
        for ($i = 0; $i < $len; $i++) {
            $s .= $this->symbols[rand(0, count($this->symbols) - 1)];
        }
        $this->ctext = $s;
    }
}

?>
