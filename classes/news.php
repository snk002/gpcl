<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.3
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

news.php
 
Classes:
 - TNewsH - create news archive
 
Usage:
$news = new TNewsH($this->centcol, 10, 2, $this->db);
$news->SetDB("newstable", "newsdate", "newstitle", "newsid");
$news->baseurl = "news/";
$newsblock = $news->GetArchivesList(12,2010);
*/
include_once("img.php");

class TNewsH extends TDBBase
{
    public $parentdoc;
    public $newscat;
    public $dateformat;
    public $baseurl;      //e.g. "news.php?id=" or "news/"
    public $format;       //0 = UL, 1 = OL, 2 = DL
    protected $condition; //e.g. '(and newstype = 2)';
    protected $tablename;
    protected $datefield;
    protected $headfield;
    protected $idfield;   //used as a part of page url (e.g. "5" in "news.php?id=5" or "2010/12/12" in "news/2010/12/12")

    public function __construct($parent, $num = 0, $format = 0, $dbh = NULL)
    {
        parent::__construct($parent, $dbh);
        $this->format = $format;
        $this->forum_url = CValues::$siteuri . "/forums";
        $this->textlimit = 30;
        $this->limit = $num;
        $this->formatid = $format;
        $this->parentdoc = $parent->ParentDocument();
        $this->newscat = 0;
        $this->dateformat = "%d.%m.%Y";
        $this->baseurl = "";
    }

    public function SetDB($tablename, $datefield, $headfield, $idfield, $condition = "")
    {
        $this->tablename = $tablename;
        $this->datefield = $datefield;
        $this->headfield = $headfield;
        $this->idfield = $idfield;
        $this->condition = $condition;
    }

    public function GetArchivesList($mon, $year, $limit = 0)
    {
        if ($limit > 0) $llim = " limit 0, $limit "; else $llim = "";
        if ($mon > 0) $mlim = " and (DATE_FORMAT($this->datefield,\"%m\")=$mon) "; else $mlim = "";
        if ($this->condition != "") $cond = $this->condition . " and ";
        $sql = "SELECT $this->headfield, $this->idfield, DATE_FORMAT($this->datefield,'$this->dateformat') as date FROM $this->tablename WHERE $cond (DATE_FORMAT($this->datefield,\"%Y\")=$year) $mlim ORDER BY $this->datefield desc $llim ";
        if (!$this->db->Query($sql)) return false;
        $ret = $this->parentcontrol->AddList($this->format);
        while ($row = $this->db->FetchArray()) {
            $ret->AddItem($row[2] . " ")->AddLink($this->parentdoc->prefix . $this->baseurl . $row[1], $row[0]);
        }
        return $ret;
    }

    public function GetArchivesContents($aurl, $yparam, $mparam)
    {
        if ($this->condition != "") $cond = " WHERE " . $this->condition;
        $sql = "SELECT DATE_FORMAT($this->datefield,\"%Y\") as YY, DATE_FORMAT($this->datefield,\"%m\") as MM FROM $this->tablename $cond ORDER BY $this->datefield desc";
        if (!$this->db->Query($sql)) return false;
        $this->db->FetchObject();  //skip last
        $oyear = 0;
        $omonth = 0;
        $mcount = 0;
        $ret = $this->parentcontrol->AddTable(3, 4, false);
        while ($row = $this->db->FetchObject()) {
            $cyear = intval($row->YY);
            $cmonth = intval($row->MM);
            $trow = null;
            if ($oyear !== $cyear) {
                if (isset($trow) && ($trow->cellcount > 0))
                    while ($trow->cellcount < 3) $trow->CreateCell();
                $trow = $ret->CreateRow();
                $trow->CreateCell('th', 3)->content = $row->YY;
                $mcount = 0;
            }
            if ($omonth !== $cmonth) {
                if ($mcount == 0) $trow = $ret->CreateRow();
                $trow->CreateCell('td')->content = "<a href=\"" . $this->parentdoc->prefix . $aurl . $yparam . $cyear . $mparam . $cmonth . "\">" . MonAsStr($row->MM) . "</a>";
                $mcount++;
                if ($mcount > 2) $mcount = 0;
            }
            $omonth = intval($row->MM);
            $oyear = intval($row->YY);
        };
        if ($trow->cellcount > 0)
            while ($trow->cellcount < 3) $trow->CreateCell();
        $ret->SetAttr("class", "newsarc");
        return $ret;
    }
}

?>

