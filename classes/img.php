<?php

/*
GPCL for PHP (General Purpose Class Library) version 2.4 beta
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

img.php
Picture processing classes and functions 
Classes:
 - TIMGH - image resize, concat (merge) pictures, add text
*/

class TIMGH
{
    public $filename;    //source image (or top layer)
    public $newname;     //destination image; NULL means RAW output
    // * Resize *
    public $newsizex;    //resize -x
    public $newsizey;    //resize -y
    public $truecolor;   //JPEG only
    public $noupscale;   //if true then file not changed if original image is smaller
    public $stretch;     //if true then proportions can changed
    public $quality;     //JPEG or PNG quality
    // * Merge and text *
    public $bgfilename;  //filename for bottom layer (for megre)
    public $blposx;      //left of image/text on bg layer, -1 means center
    public $blposy;      //top of image/text on bg layer, -1 means center
    public $angle;       //angle. For non-ttf fonts only 0 or 90 deg. supported
    public $fontsize;    //font size, relative (1-5) if system font iss used
    public $fontname;    //ttf file pathname
    public $fontcolor;   //text color, array(R,G,B[,A])
    protected $sizex; //read-only access provided trougth the __get function
    protected $sizey;
    protected $imagetype;
    protected $typeid;
    protected $img;
    protected $bgimg;
    protected $inited;
    public static $GIF = 1;
    public static $JPG = 2;
    public static $PNG = 3;

    public function __construct($fn = '')
    {
        if ($fn != '') $this->filename = $fn;
        $this->quality = 50;
        $this->noupscale = true;
        $this->stretch = false;
        $this->fontsize = 2;
        $this->fontcolor = array(0, 0, 0); //black
    }

    public function __get($varName)
    {
        return $this->$varName;
    }

    public function puttext($text, $newfn = NULL, $dst_x = -1, $dst_y = -1)
    {
        if ($newfn <> '') $this->newname = $newfn;
        if (!$this->init($this->img, $this->filename)) {
            echo 'Error on init image file';
            return false;
        }
        if ($dst_x == -1) {
            $dst_x = intval(($this->sizex) / 2);
        }
        if ($dst_y == -1) {
            $dst_y = intval(($this->sizey) / 2);
        }
        if (count($this->fontcolor) > 3) $color = imagecolorallocatealpha($this->img, $this->fontcolor[0], $this->fontcolor[1], $this->fontcolor[2], $this->fontcolor[3]);
        else $color = imagecolorallocate($this->img, $this->fontcolor[0], $this->fontcolor[1], $this->fontcolor[2]);
        if ($this->fontname == "") {
            if ($this->angle > 45) imagestringup($this->img, $this->fontsize, $dst_x, $dst_y, $text, $color);
            else imagestring($this->img, $this->fontsize, $dst_x, $dst_y, $text, $color);
        } else {
            imagettftext($this->img, $this->fontsize, $this->angle, $dst_x, $dst_y, $color, $this->fontname, $text);
        }
        if ($this->newname == NULL) Header($this->GetHTTPHead());
        switch ($this->typeid) {
            case 1:
                if ($this->newname == NULL) imagegif($this->img); else imagegif($this->img, $this->newname);
                break;
            case 2:
                imagejpeg($this->img, $this->newname, $this->quality);
                break;
            case 3:
                $qty = 1 + round(($this->quality - 1) / 10);
                imagepng($this->img, $this->newname, $qty);
                break;
        }
        // imagedestroy($this->img);
        return true;
    }

    protected function init(&$obj, $fn = '')
    {
        if ($fn == '') $fn = $this->filename;
        $imageinfo = getimagesize($fn);
        $this->sizex = $imageinfo[0];
        $this->sizey = $imageinfo[1];
        $this->typeid = $imageinfo[2];
        switch ($this->typeid) {
            case TIMGH::$GIF:
                $obj = imagecreatefromgif($fn);
                $this->truecolor = false;
                $this->imagetype = 'image/gif';
                break;
            case TIMGH::$JPG:
                $obj = imagecreatefromjpeg($fn);
                $this->truecolor = true;
                $this->imagetype = 'image/jpeg';
                break;
            case TIMGH::$PNG:
                $obj = imagecreatefrompng($fn);
                $this->truecolor = false;
                $this->imagetype = 'image/png';
                break;
            default:
                return false;
        }
        $this->inited = (($this->sizex > 0) && ($this->sizey > 0));
        return $this->inited;
    }

    public function GetHTTPHead()
    {
        switch ($this->typeid) {
            case TIMGH::$GIF:
                return "Content-type: image/gif";
                break;
            case TIMGH::$JPG:
                return "Content-type: image/jpeg";
                break;
            case TIMGH::$PNG:
                return "Content-type: image/png";
                break;
        }
        return "";
    }

    public function merge($bgfn = "", $newfn = NULL, $dst_x = -1, $dst_y = -1)
    {
        if ($bgfn <> '') $this->bgfilename = $bgfn;
        if ($newfn <> '') $this->newname = $newfn;
        if (($this->newname == '') && ($newfn != NULL)) $this->newname = $this->filename;
        if (!$this->init($this->bgimg, $this->bgfilename)) {
            echo 'Error on init bg image file';
            return false;
        }
        $bgwidth = $this->sizex;
        $bgheight = $this->sizey;
        if (!$this->init($this->img, $this->filename)) {
            echo 'Error on init image file';
            return false;
        }
        if ($dst_x == -1) {
            $dst_x = intval(($bgwidth - $this->sizex) / 2);
        }
        if ($dst_y == -1) {
            $dst_y = intval(($bgheight - $this->sizey) / 2);
        }
        if ($this->angle != 0) $this->img = imagerotate($this->img, $this->angle, 0);
        //echo $bgwidth ." ". $bgheight ." ". $this->sizex ." ". $this->sizey ." ". $dst_x ." ".$dst_y;
        //resource $dst_im, resource $src_im, int $dst_x, int $dst_y, int $src_x, int $src_y, int $src_w, int $src_h
        if (imagecopy($this->bgimg, $this->img, $dst_x, $dst_y, 0, 0, $this->sizex, $this->sizey)) {
            if ($this->newname == NULL) Header($this->GetHTTPHead()); //else echo $this->newname;
            switch ($this->typeid) {
                case TIMGH::$GIF:
                    if ($this->newname == NULL) imagegif($this->bgimg); else imagegif($this->bgimg, $this->newname);
                    break;
                case TIMGH::$JPG:
                    imagejpeg($this->bgimg, $this->newname, $this->quality);
                    break;
                case TIMGH::$PNG:
                    $qty = 1 + round(($this->quality - 1) / 10);
                    imagepng($this->bgimg, $this->newname, $qty);
                    break;
            }
            //  imagedestroy($this->img);
            imagedestroy($this->bgimg);
        }
        return true;
    }

    public function resize($newx = 0, $newy = 0, $newfn = '')
    {
        if (!$this->inited) {
            if (!$this->init($this->img, $this->filename)) {
                echo 'Error on init image file';
                return false;
            }
        }
        if ($newx > 0) $this->newsizex = $newx;
        if ($newy > 0) $this->newsizey = $newy;
        if ($newfn <> '') $this->newname = $newfn;
        if ($this->newname == '') $this->newname = $this->filename;
        if ((!$this->noupscale) && ($this->sizex <= $this->newsizex) &&
            ($this->sizey <= $this->newsizey) && ($this->newname == $this->filename)
        ) {
            imagedestroy($this->img);
            return false;
        }
        if ($this->noupscale) {
            if ($this->newsizex > $this->sizex) $this->newsizex = $this->sizex;
            if ($this->newsizey > $this->sizey) $this->newsizey = $this->sizey;
        }
        if ($this->stretch) {
            $nw = $this->newsizex;
            $nh = $this->newsizey;
        } else {
            $kx = $this->newsizex / $this->sizex;
            $ky = $this->newsizey / $this->sizey;
            $k = $kx > $ky ? $ky : $kx;
            $nw = intval(round($this->sizex * $k));
            $nh = intval(round($this->sizey * $k));
        }
        if ($this->truecolor) $new = imagecreatetruecolor($nw, $nh); else $new = imagecreate($nw, $nh);
        imagecopyresampled($new, $this->img, 0, 0, 0, 0, $nw, $nh, $this->sizex, $this->sizey);
        if ($this->newname == NULL) Header($this->GetHTTPHead());
        switch ($this->typeid) {
            case TIMGH::$GIF:
                if ($this->newname == NULL) imagegif($new); else imagegif($new, $this->newname);
                break;
            case TIMGH::$JPG:
                imagejpeg($new, $this->newname, $this->quality);
                echo "$this->newname<br />";
                break;
            case TIMGH::$PNG:
                $qty = 1 + round(($this->quality - 1) / 10);
                imagepng($new, $this->newname, $qty);
                break;
        }
        // imagedestroy($this->img);
        imagedestroy($new);
        return true;
    }
}

?>
