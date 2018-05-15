<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.4 beta
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

forum_int.php
Forums integration. Last posts listing now supported. Compatible with vBulletin (VB) and PunBB.  
Classes:
 - TForumsH - grab latest topics from forum
Note: 
You should set db property by using connect() method.
Since version 1.6 you can set db property by using db parameter.
Since version 2.0 you can (and most) set forum engine.
*/

include_once("const.php");
include_once("db.php");

class TForumsH extends TDBBase
{
    public $oddclass;     //number of links
    public $evenclass;  //forum root url
    protected $limit;  //showed or skipped subforums (depending selmode), e.g "21,40";
    protected $forum_url;   //0 - forum_id ignored (inlude all), 1 - inlude only forum_id, 2 - inlude all but forum_id
    protected $forum_id;  //0 = UL list, 1 = OL list, 2 = DL list, 3 = span/br in div, 4 = p in div, 5 = table
    protected $selmode; //maximum length of display string
    protected $formatid;    //forum engine. One of: VB, PunBB, ...
    protected $textlimit;     //if you wish to highlight odd/even lines by CSS1 way, set these classes
    protected $engine;

    public function __construct($parent, $url = "", $db = NULL, $engine = "VB")
    {
        parent::__construct($parent, $db);
        $this->forum_url = $url;
        if ($this->forum_url == "") $this->forum_url = CValues::$siteuri . "/forums";
        $this->forum_id = "";
        $this->selmode = 0;
        $this->textlimit = 30;
        $this->formatid = 0;
    }

    public function SetForums($ids, $includeit = true)
    {
        $this->forum_id = $ids;
        if ($ids != "") {
            if ($includeit) $this->selmode = 1; else $this->selmode = 2;
        } else {
            $this->selmode = 0;
        }
    }

    public function GetTopics($num = 5, $format = 0, $maxlen = 0)
    {
        if ($num > 0) $this->limit = $num;
        if ($format > 0) $this->formatid = $format;
        if ($maxlen > 0) $this->textlimit = $maxlen;
        switch ($this->formatid) {
            case 0:
                $ret = new TListControl($this->parentcontrol, 0);
                break;
            case 1:
                $ret = new TListControl($this->parentcontrol, 1);
                break;
            case 2:
                $ret = new TListControl($this->parentcontrol, 2);
                break;
            case 3:
                $ret = new TBlockControl($this->parentcontrol, "div");
                break;
            case 4:
                $ret = new TBlockControl($this->parentcontrol, "div");
                break;
            case 5:
                $ret = new TTableControl($this->parentcontrol, 2, $this->limit, false);
                break;
        }
        $this->parentcontrol->AddControl($ret);
        $sql = $this->MakeSQL();
        if (!$this->db->Query($sql)) echo $this->db->GetError();
        $isodd = true;
        while ($thread = $this->db->FetchArray()) {
            $lastpost = $thread['lastpost'];
            $poster = $thread['lastposter'];
            $tid = $thread['threadid'];
            $pid = $thread['lastpostid'];
            $date2 = date("d.m.Y H:i", $lastpost);
            $title = $thread['title'];
            if (strlen($title) > $this->textlimit) {
                $title = substr($title, 0, $this->textlimit - 1);
                while ($title[strlen($title) - 1] == ".") $title = substr($title, 0, strlen($title) - 1);
                $title = $title . "&hellip;";
            };
            $s1 = "";
            if ($this->engine == "VB") $s1 = "<a href=\"{$this->forum_url}/showthread.php?p=$pid#post$pid\">$title</a>";
            if ($this->engine == "PunBB") $s1 = "<a href=\"{$this->forum_url}/viewtopic.php?pid=$pid#p$pid\">$title</a>";
            $s2 = substr($poster, 0, 18) . " <i>$date2</i>";
            $isodd = !$isodd;
            $item = null;
            if ($this->formatid < 3) {
                if ($this->formatid < 2) {
                    $s1 .= " &ndash; " . $s2;
                    $s2 = "";
                }
                $item = $ret->AddItem($s1, $s2);
            }
            if (($this->formatid == 3) or ($this->formatid == 4)) {
                if ($this->formatid == 3) $s = "span"; else $s = "p";
                $item = $ret->CreateControl($s);
                $item->content = $s1 .= " &ndash; " . $s2;
                if ($this->formatid == 3) $ret->AddBR();
            }
            if ($this->formatid == 5) {
                $item = $ret->AddRow(array($s1,$s2));
            }
            if ($item!==null) {
                if ($isodd) $item->SetAttr("class", $this->oddclass); else $item->SetAttr("class", $this->evenclass);
            }
        }
        return $ret;
    }

    private function MakeSQL()
    {
        if (($this->forum_id) and ($this->selmode > 0)) {
            if ($this->selmode == 2) $yn = "not";
            if ($this->engine == "VB") $forumid = "AND forumid $yn in ({$this->forum_id})";
            if ($this->engine == "PunBB") $forumid = "AND forum_id $yn in ({$this->forum_id})";
        }
        if ($this->limit > 0) {
            $limited = "LIMIT 0, $this->limit";
        }
        if ($this->engine == "VB") return "SELECT threadid, title, lastpost, lastposter, lastpostid FROM thread WHERE visible=1 AND open=1 $forumid ORDER BY lastpost DESC $limited";
        if ($this->engine == "PunBB") return "SELECT id as threadid, subject as title, last_post as lastpost, last_poster as lastposter, last_post_id as lastpostid FROM topics WHERE closed=0 $forumid ORDER BY last_post DESC $limited";
    }
}

?>
