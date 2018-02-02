<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.3
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

tables.php
HTML Table-related classes
Classes:
 - TTableRow - an HTML tables row (tr) 
 - TTableCell  - an HTML table cell (td/th)
 - TCustomTable - an successor of HTML tables and table parts
 - TTableControl - represents HTML table element 
 - TExTableControl - represents HTML table with thead/tbody/tfooter
*/

include_once("controls.php");

class TTableRow extends TOnwedControl
{
  public $isheading;
  protected $cellcount;

  public function __construct($parent, $tag = "tr")
  {
    parent::__construct($parent, $tag);
    $this->isheading = false;
  }

  public function CellCount()
  {
    return $this->vellcount;
  }

  public function AddCell($colspan = "", $rowspan = "")
  {
    if ($this->isheading) $tag = 'th'; else $tag = 'td';
    $ctrl = $this->CreateCell($tag, $colspan, $rowspan);
    return $ctrl;
  }

  public function CreateCell($tag = "", $colspan = "", $rowspan = "")
  {
    $ctrl = new TTableCell($this, $tag);
    $ctrl->SetAttr('colspan', $colspan);
    $ctrl->SetAttr('rowspan', $rowspan);
    $this->AddControl($ctrl);
    $this->cellcount++;
    return $ctrl;
  }

  public function DeleteCell($col)
  {
    unset($this->controls[$col]);
    $this->cellcount--;
  }

  public function Fill($values = array())
  {
    $cells = $this->GetCells();
    for ($i = 0; (($i < count($values)) && ($i < $this->cellcount)); $i++) {
      $cells[$i]->content = $values[$i];
    }
  }

  public function GetCells()
  {
    $ret = array();
    foreach ($this->controls as $ctrl) {
      if (($ctrl->tag == "td") || ($ctrl->tag == "th")) $ret[] = $ctrl;
    }
    return $ret;
  }

  public function Cell($col)
  {
    return $this->controls[$col];
  }
}

class TTableCol extends TControl
{
  public function __construct($parent, $span = 1)
  {
    parent::__construct($parent);
    $this->tag = "col";
    $this->hasclose = false;
    if ($span > 1) $this->SetAttr("span", $span);
  }
}

class TTableColgroup extends TControl
{
  public function __construct($parent, $span = 1)
  {
    parent::__construct($parent);
    $this->tag = "colgroup";
    if ($span > 1) $this->SetAttr("span", $span);
  }

  public function AddCol($span = 1)
  {
    $ctrl = new TTableCol($this, $span);
    $this->AddControl($ctrl);
    return $ctrl;
  }
}

class TCustomTable extends TOnwedControl
{
  protected $cols;
  protected $rows;
  protected $headrow;
  protected $isfilled;

  public function __construct($parent, $tag, $cols, $rows, $autofill, $headrow)
  {
    parent::__construct($parent, $tag);
    $this->headrow = $headrow;
    $this->cols = $cols;
    $this->rows = $rows;
    if ($autofill) $this->FillTable();
  }

  protected function FillTable()
  {
    for ($i = 0; $i < $this->rows; $i++) {
      $h = ($this->headrow == $i);
      $row = $this->CreateRow($h);
      for ($j = 0; $j < $this->cols; $j++) {
        $row->CreateCell();
      }
    }
    $this->isfilled = true;
  }

  protected function CreateRow($ishead = false)
  {
    $ctrl = new TTableRow($this);
    $ctrl->isheading = $ishead;
    $this->AddControl($ctrl);
    return $ctrl;
  }

  public function FillRow($rowid, $values = array())
  {
    $row = $this->Row($rowid);
    $row->Fill($values);
  }

  public function Row($rowid)
  {
    if (!$this->isfilled) return false;
    $rows = $this->GetControls('tr');
    return $rows[$rowid];
  }

  public function AddRow($values = array(), $isheading = false)
  {
    $row = $this->CreateRow($isheading);
    $this->rows++;
    for ($i = 0; $i < $this->cols; $i++) {
      $row->CreateCell();
    }
    $row->Fill($values);
    return $row;
  }

  public function Cell($col, $row)
  {
    if (!$this->isfilled) return false;
    $rows = $this->GetControls('tr');
    $cells = $rows[$row]->GetCells();
    $cell = $cells[$col];
    return $cell;
  }
}

class TTableControl extends TCustomTable
{
  public function __construct($parent, $cols = 2, $rows = 2, $autofill = true, $headrow = -1)
  {
    parent::__construct($parent, "table", $cols, $rows, $autofill, $headrow);
  }

  public function AddCol($span = 1)
  {
    $ctrl = new TTableCol($this, $span);
    $this->AddControl($ctrl);
    return $ctrl;
  }

  public function AddColGroup($span = 1)
  {
    $ctrl = new TTableColgroup($this, $span);
    $this->AddControl($ctrl);
    return $ctrl;
  }
}

class TExTableControl extends TOnwedControl
{
  public $thead;
  public $tbody;
  public $tfooter;

  public function __construct($parent, $cols = 2, $hashead = true, $hasfoot = false, $autofill = true)
  {
    parent::__construct($parent, "table");
    if ($hashead) {
      $this->thead = new TCustomTable($this, "thead", $cols, 1, $autofill, 0);
      $this->AddControl($this->thead);
    }
    $this->tbody = new TCustomTable($this, "tbody", $cols, 1, $autofill, -1);
    $this->AddControl($this->tbody);
    if ($hasfoot) {
      $this->tfooter = new TCustomTable($this, "tfooter", $cols, 1, $autofill, -1);
      $this->AddControl($this->tfooter);
    }
  }

  public function AddCol($span = 1)
  {
    $ctrl = new TTableCol($this, $span);
    $this->AddControl($ctrl);
    return $ctrl;
  }

  public function AddColGroup($span = 1)
  {
    $ctrl = new TTableColgroup($this, $span);
    $this->AddControl($ctrl);
    return $ctrl;
  }
}

?>
