<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.3
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

forms.php                         
HTML form elements classes        

- TFormControl - basic class for form controls (based on TOnwedControl)  
 - TTextEdit - textarea element wrapper
 - TInputControl - input element wrapper
   - TUploadFile - create file upload control and *provides filesystem helper interface*
   - TFListControl - select element
     - TRadioGroup - set of radio buttons 
     - TFMLListControl - select with optgroup support
 - TCustomForm - basic form class
   - TFieldset - fieldset element wrapper 
   - TForm - form element wrapper
 - TFormData - class for automatic load get/post values into the form  
*/

include_once("controls.php");

class TFormControl extends TOnwedControl
{
    public $nameasid;   //if true, repeat name attribute value as id attr.
    public $breakln;    //if true adds <br /> tag; works only with TForm.Generate

    public function __construct($parent, $name = "")
    {
        parent::__construct($parent);
        $this->name = $name;
        $this->nameasid = true;
        $this->breakln = false;
        $this->SetDefAttrs();
    }

    protected function SetDefAttrs()
    {
        if ($this->nameasid) {
            $this->SetAttr("id", $this->name);
        }
    }

    public function GetTag($attrs = "")
    {
        if ($this->inneronly) return "";
        $this->SetDefAttrs();
        $attrs .= $this->attrs->GetAllAttrs();
        if ((!$this->hasclose) && (CConst::isxhtml())) $attrs .= " /";
        $nameattr = "";
        if ($this->tag != "div") {  //case for TRadioGroup, etc.
            $nameattr = " name=\"{$this->name}\"";
        }
        return "<$this->tag{$nameattr}{$attrs}>";
    }

    public function GetComplete($offset)
    {
        if ($this->breakln) {
            $offset = " <br />";
        }
        return parent::GetComplete($offset);
    }
}

class TInputControl extends TFormControl
{
    public $readonly;
    protected $size;     //e.g. 5 (for text only)
    protected $tagtype;  //e.g. text, submit, file

    public function __construct($parent, $name = "", $type = "")
    {
        parent::__construct($parent, $name);
        $this->tag = "input";
        $this->tagtype = $type;
        $this->size = 0;
        $this->hasclose = false;
        $this->readonly = false;
        $this->SetDefAttrs();
    }

    protected function SetDefAttrs()
    {
        parent::SetDefAttrs();
        $this->SetAttr("type", $this->tagtype);
        if ($this->size > 0) $this->SetAttr("size", $this->size);
        if ($this->readonly) $this->SetAttr("readonly", "readonly");
    }

    public function SetSize($len = 0)
    {
        $this->size = $len;
    }
}

class TFListControl extends TFormControl
{
    protected $itemtag;     //e.g. option
    protected $size;        //e.g. 4 (if > 0 then listbox, esle combobox)
    protected $defaultv;    //selected value
    protected $defaultvt;   //if true sets default by the value attribute value, not by text

    public function __construct($parent, $name = "")
    {
        parent::__construct($parent, $name);
        $this->tag = "select";
        $this->itemtag = "option";
        $this->size = 0;
        $this->defaultvt = false;
        $this->SetDefAttrs();
    }

    protected function SetDefAttrs()
    {
        parent::SetDefAttrs();
        if ($this->size > 0) $this->SetAttr("size", $this->size);
    }

    public function SetSize($len = 0)
    {
        $this->size = intval($len);
    }

    public function SetSel($value, $values = NULL)
    {
        if (isset($values) && is_null($value)) $value = $values->{$this->name};
        $this->defaultv = $value;
    }

    public function ChangeItemTag($val)
    {
        if ($val) $this->itemtag = $val; //use carefully!
    }

    public function AddItems($vals, $texts = array(), $selval = null)
    {
        $i = 0;
        foreach ($vals as $val) {
            if (isset($texts[$i])) $s = $texts[$i]; else $s = "";
            $this->AddItem($val, $s);
            $i++;
        }
    }

    public function AddItem($val, $txt = "", $issel = false)
    {
        $ctrl = $this->CreateChildControl($this->itemtag);
        $ctrl->attrs->SetAttr('value', $val);
        if ($txt == "") $txt = $val;
        $ctrl->content = $txt;
        return $ctrl;
    }

    public function GetComplete($offset='')
    {
        $this->SetSelected();
        return parent::GetComplete("");
    }

    protected function SetSelected($defaultv = "")
    {
        if ($defaultv != "") $this->defaultv = $defaultv;
        if ($this->defaultv === "") return;
        foreach ($this->controls as $ctrl) {
            if ($this->defaultvt) {
                if ($ctrl->attrs->GetAttrValue("value") == $this->defaultv) $ctrl->SetAttr('selected', 'selected');
            } else {
                if ($ctrl->content == $this->defaultv) $ctrl->SetAttr('selected', 'selected');
            }
        }
    }
}

class TRadioGroup extends TFListControl
{
    protected $styleclass;
    protected $itemtagtype;

    public function __construct($parent, $name = "", $styleclass = "")
    {
        parent::__construct($parent, $name);
        $this->tag = "div";
        $this->itemtag = "input";
        $this->itemtagtype = "radio";
        $this->styleclass = $styleclass;
        $this->hasclose = true;
    }

    public function AddItems($vals, $texts = array(), $selval = "")
    {
        $i = 0;
        foreach ($vals as $val) {
            if (isset($texts[$i])) $s = $texts[$i]; else $s = "";
            $this->AddItem($val, $s, ($val == $selval));
            $i++;
        }
        $this->size = $i;
    }

    public function AddItem($val, $txt = '', $issel = false)
    {
        $ctrl = new TInputControl($this, $this->name, $this->itemtagtype);
        $ctrl->attrs->SetAttr('value', $val);
        if ($issel) $ctrl->attrs->SetAttr('checked', 'checked');
        $ctrl->content = $txt . "<br />";
        $this->AddControl($ctrl);
        return $ctrl;
    }

    public function GetComplete($offset ='')
    {
        return parent::GetComplete();
    }

    protected function SetDefAttrs()
    {
        if ($this->styleclass != "") {
            $this->SetAttr("class", $this->styleclass);
        } else {
            $x = $this->size * 1.25;
            if ($x == 0) $x = 5;
            $this->SetAttr("style", "height: {$x}em; width: 15em; border: solid 1px #79A3B0;");
        }
    }
}

class TFMLListControl extends TFListControl
{
    protected $mlDelim;    //&nbsp; or other...
    protected $groupsTag;  //e.g. optgroup for ListBox/ComboBox
    protected $grouptype;  //0 = plain; 1 = using spaces; 2 = using OPTGROUP element

    public function __construct($parent, $name = "", $grouptype = 0)
    {
        parent::__construct($parent, $name);
        $this->mlDelim = "&nbsp;&nbsp;";
        $this->groupsTag = "optgroup";
        $this->grouptype = $grouptype;
        $this->defaultvt = true;
    }

    public function SetGroups($tag, $type)
    {
        $this->groupsTag = $tag;
        $this->grouptype = $type;
    }

    public function AddGroup($label)
    {
        $ctrl = new TFListControl($this);
        $ctrl->tag = $this->groupsTag;
        $ctrl->size = 0;
        $ctrl->defaultv = $this->defaultv;
        $ctrl->defaultvt = $this->defaultvt;
        $ctrl->attrs->SetAttr("label", $label);
        $this->AddControl($ctrl);
        return $ctrl;
    }

    protected function SetSelected($defaultv = "")
    {
        if ($defaultv != "") $this->defaultv = $defaultv;
        parent::SetSelected($this->defaultv);
        foreach ($this->controls as $ctrl) {
            if ($ctrl->tag == $this->groupsTag) {
                $ctrl->SetSelected($this->defaultv);
            }
        }
    }
}

class TTextEdit extends TFormControl
{
    protected $rows;
    protected $cols;

    public function __construct($parent, $name = "", $cols = 40, $rows = 4)
    {
        parent::__construct($parent, $name);
        $this->tag = 'textarea';
        $this->rows = $rows;
        $this->cols = $cols;
    }

    public function SetSize($cols, $rows)
    {
        $this->cols = $cols;
        $this->rows = $rows;
    }

    protected function SetDefAttrs()
    {
        parent::SetDefAttrs();
        $this->SetAttr("cols", $this->cols);
        $this->SetAttr("rows", $this->rows);
    }
}

abstract class TCustomForm extends TBlockControl
{
    public $nameasid;   //if true, repeat name attribute value as id attr.
    public $breakln;
    public $values;

    public function __construct($parent)
    {
        parent::__construct($parent);
    }

    public function AddPassword($name, $size = 0, $value = "")
    {
        $ctrl = $this->AddCustomInput($name, 'password');
        $ctrl->SetSize($size);
        //if ($value!="") $ctrl->SetAttr("value",$value);
        $this->SetDefVal($ctrl, $name, $value);
        return $ctrl;
    }

    public function AddCustomInput($name, $type = 'text')
    {
        $ctrl = new TInputControl($this, $name, $type);
        $ctrl->nameasid = $this->nameasid;
        $ctrl->breakln = $this->breakln;
        $this->AddControl($ctrl);
        return $ctrl;
    }

    protected function SetDefVal($ctrl, $name, $value)
    {
        if (!is_null($value)) $ctrl->SetAttr("value", $value);
        else if (isset($this->values)) $ctrl->SetAttr("value", $this->values->$name);
    }

    public function AddHidden($name, $value)
    {
        $ctrl = $this->AddCustomInput($name, 'hidden');
        //$ctrl->SetAttr("value",$value);
        $this->SetDefVal($ctrl, $name, $value);
        return $ctrl;
    }

    public function AddButton($name, $caption)
    {
        $ctrl = $this->AddCustomInput($name, 'button');
        $ctrl->SetAttr("value", $caption);
        return $ctrl;
    }

    public function AddSubmit($name, $caption = "")
    {
        $ctrl = $this->AddCustomInput($name, 'submit');
        $ctrl->SetAttr("value", $caption);
        return $ctrl;
    }

    public function AddReset($name, $caption = "")
    {
        $ctrl = $this->AddCustomInput($name, 'reset');
        $ctrl->SetAttr("value", $caption);
        return $ctrl;
    }

    public function AddFileSet($name, $text)
    {
        $this->AddLabeledEdit($name, $text)->readonly = true;
        $ctrl = $this->AddFile($name);
        return $ctrl;
    }

    public function AddLabeledEdit($name, $text, $size = 0, $value = "")
    {
        $this->AddLabel($name, $text);
        return $this->AddEdit($name, $size, $value);
    }

    public function AddLabel($for, $text)
    {
        $ctrl = $this->CreateChildControl('label');
        $ctrl->SetAttr('for', $for);
        $ctrl->content = $text;
        return $ctrl;
    }

    public function AddEdit($name, $size = 0, $value = "")
    {
        $ctrl = $this->AddCustomInput($name, 'text');
        $ctrl->SetSize($size);
        $this->SetDefVal($ctrl, $name, $value);
        return $ctrl;
    }

    public function AddFile($name)
    {
        $ctrl = new TUploadFile($this, $name);
        $ctrl->nameasid = $this->nameasid;
        $ctrl->breakln = $this->breakln;
        $this->AddControl($ctrl);
        return $ctrl;
    }

    public function AddCheckBox($name, $checked = false, $text = "")
    {
        $ctrl = $this->AddCustomInput($name, 'checkbox');
        if (!is_null($checked)) {
            if ($checked) $ctrl->SetAttr("checked", "checked");
        } else {
            if ((isset($this->values)) && (($this->values->$name == "on") || ($this->values->$name == true))) $ctrl->SetAttr("checked", "checked");
        }
        if ($text != "") $ctrl->endcontent = $text;
        return $ctrl;
    }

    public function AddRadioButton($name, $value = "", $checked = false)
    {
        $ctrl = $this->AddCustomInput($name, 'radio');
        if ($value != "") $ctrl->SetAttr("value", $value);
        if ($checked) $ctrl->SetAttr("checked", "checked");
        return $ctrl;
    }

    public function AddRadioGroup($name, $values = array(), $texts = array(), $selval = "")
    {
        $ctrl = new TRadioGroup($this, $name);
        $ctrl->nameasid = $this->nameasid;
        $ctrl->breakln = $this->breakln;
        if ($values) {
            if ((isset($this->values)) && is_null($selval)) $selval = $this->values->$name;
            $ctrl->AddItems($values, $texts, $selval);
        }
        $this->AddControl($ctrl);
        return $ctrl;
    }

    public function AddComboBox($name, $values, $selval = '')
    {
        return $this->AddComboBoxEx($name, $values, array(), $selval);
    }

    public function AddComboBoxEx($name, $values, $texts, $selval = '')
    {
        $ctrl = new TFMLListControl($this, $name);
        $ctrl->nameasid = $this->nameasid;
        $ctrl->breakln = $this->breakln;
        $ctrl->SetSel($selval, $this->values);
        $ctrl->AddItems($values, $texts);
        $this->AddControl($ctrl);
        return $ctrl;
    }

    public function AddListBox($name, $values, $size = 4)
    {
        return $this->AddListBoxEx($name, $values, array(), $size);
    }

    public function AddListBoxEx($name, $values, $texts, $size)
    {
        $ctrl = $this->AddComboBoxEx($name, $values, $texts);
        $ctrl->SetSize($size);
        return $ctrl;
    }

    public function AddFieldset($legend)
    {
        $ctrl = new TFieldset($this, $legend);
        $this->AddControl($ctrl);
        $ctrl->nameasid = $this->nameasid;
        $ctrl->breakln = $this->breakln;
        if (isset($this->values)) $ctrl->values = $this->values;
        return $ctrl;
    }

    public function AddTextedit($name, $cols, $rows, $text = "")
    {
        $ctrl = new TTextedit($this, $name, $cols, $rows);
        if ((isset($this->values)) && is_null($text)) $text = $this->values->$name;
        $ctrl->Content($text);
        $this->AddControl($ctrl);
        return $ctrl;
    }

    public function GetSubmitted($ctrlname)
    {
//echo "this->method = $this->method";
        if ($this->method == "get") {
            if (isset($_GET[$ctrlname])) return $_GET[$ctrlname];
            else return NULL;
        }
        if ($this->method == "post") {
            if (isset($_POST[$ctrlname])) return $_POST[$ctrlname];
            else return NULL;
        }
        return NULL;
    }

    protected function CheckForFile()
    {
        foreach ($this->controls as $ctrl) {
            if (property_exists($ctrl, 'tagtype'))
                if ($ctrl->tagtype == "file") return true;
            if (method_exists($ctrl, 'CheckForFile'))
                if ($ctrl->CheckForFile()) return true;
        }
        return false;
    }
}

class TFieldset extends TCustomForm
{
    protected $legend;   //text string only

    function __construct($parent, $legend)
    {
        parent::__construct($parent);
        $this->tag = 'fieldset';
        $this->nameasid = false;
        $this->legend = $legend;
        if ($this->legend != "") {
            $ctrl = new TControl($this, 'legend');
            $ctrl->content = $legend;
            $this->AddControl($ctrl);
        }
    }
}

class TFormData extends TObject
{
    protected $adata;
    protected $types;
    protected $method;
    protected $softtypes = true; //true=only listed in types checked, false=not-listed skipped (def. for DB)

    public function __construct($parent, $method = "get", $types = NULL)
    {
        parent::__construct($parent);
        $this->adata = array();
        $this->method = $method;
        if (isset($parent)) $this->LinkToForm($parent);
        if (isset($types)) $this->SetDataTypes($types);
        $this->LoadData();
    }

    public function LinkToForm($parent)
    {
        $this->parentcontrol = $parent;
        if (isset($parent->method)) $this->method = $parent->method;
    }

    public function SetDataTypes($types, $soft = true)
    {
        $this->types = $types;
        $this->softtypes = $soft;
    }

    public function LoadData()
    {
        if ($this->method == "get") $this->LoadGet();
        else if ($this->method == "post") $this->LoadPost();
        if (isset($this->types)) $this->CheckDataTypes();
    }

    protected function LoadGet()
    {
        foreach ($_GET as $key => $val) {
            $this->adata[$key] = $val;
        }
    }

    protected function LoadPost()
    {
        foreach ($_POST as $key => $val) {
            $this->adata[$key] = $val;
        }
    }

    protected function CheckDataTypes()
    {
        if ($this->softtypes) {
            $this->CheckDataTypesSoft();
            return;
        }
        foreach ($this->types as $key => $value) {
            if (isset($this->adata[$key])) {
                $this->adata[$key] = $this->CheckDataType($this->types[$key], $this->adata[$key]);
            } else {
                if ($value == "bcb") $this->adata[$key] = false;
            }
        }
    }

    protected function CheckDataTypesSoft()
    {
        foreach ($this->adata as $key => $value) {
            if (isset($this->types[$key])) {
                $this->adata[$key] = $this->CheckDataType($this->types[$key], $value);
            }
        }
    }

    protected function CheckDataType($dt, $value)
    {
        if ($dt == "int") {
            return intval($value);
        }
        if ($dt == "dbl") {
            return floatval($value);
        }
        if ($dt == "bcb") {
            return ($value == "on");
        }
        if ($dt == "url") {
            return filter_var($value, FILTER_SANITIZE_URL);
        }
        if ($dt == "eml") {
            return filter_var($value, FILTER_SANITIZE_EMAIL);
        }
        if (substr($dt, 0, 3) == "str") {
            $len = intval(substr($dt, 3, 10));
            if ($len > 0) return substr(trim($value), 0, $len);
            else return trim($value);
        }
        return NULL;
    }

    public function ResetData()
    {
//echo "Reset data<br />";
        foreach ($this->adata as $k => $v) $this->adata[$k] = NULL; //unset($this->adata[$k]);
        $this->adata = array();
    }

    public function AssignFrom($obj)
    {
        foreach ($obj as $key => $value) {
            $this->adata[$key] = $obj->$key;
        }
    }

    public function AssignTo($obj)
    {
        foreach ($this->adata as $key => $value) {
            if (property_exists($obj, $key)) {
                $obj->$key = $value;
            }
        }
    }

    public function __get($name)
    {
        if (isset($this->adata) && array_key_exists($name, $this->adata)) return $this->adata[$name];
        else return false;
    }

    public function __set($name, $val)
    {
        $this->adata[$name] = $val;
    }

    public function __isset($name)
    {
        return isset($this->adata[$name]);
    }

    public function prn()
    {
        echo "<pre>";
        print_r($this->adata);
        echo "</pre>";
    }
}

class TForm extends TCustomForm
{
    public $values;     //optional TFormData object
    protected $script;  //e.g. 'check.php'
    protected $enctype; //e.g. "multipart/form-data"
    protected $method;  //get/post/post-data
    public static $FORM_GET = 0;
    public static $FORM_POST = 1;
    public static $FORM_DATA = 2;

    /* use type = 2 to support file uploads */

    public function __construct($parent, $name = "", $script = "", $type = 0)
    {
        parent::__construct($parent);
        $this->name = $name;
        $this->script = $script;
        $this->values = NULL;
        $this->tag = 'form';
        $this->SetType($type);
    }

    public function SetType($type)
    {
        switch ($type) {
            case TForm::$FORM_GET:
                $this->enctype = "application/x-www-form-urlencoded";
                $this->method = "get";
                break;
            case TForm::$FORM_POST:
                $this->enctype = "application/x-www-form-urlencoded";
                $this->method = "post";
                break;
            case TForm::$FORM_DATA:
                $this->enctype = "multipart/form-data";
                $this->method = "post";
                break;
        };
    }

    public function MakeAutoData($types = NULL)
    {
        $this->values = new TFormData($this, NULL, $types);
    }

    public function LinkAutoData($formdata)
    {
        $this->values = $formdata;
        $this->values->LinkToForm($this);
    }

    public function GetTag()
    {
        if ($this->inneronly) return "";
        if ($this->script == "") $script = $_SERVER['PHP_SELF']; else $script = $this->script;
        $name = "";
        if ($this->name != "") {
            $name = " name=\"$this->name\"";
            if ($this->nameasid) $name .= " id=\"$this->name\"";
        }
        $attrs = $this->attrs->GetAllAttrs();
        if ($this->CheckForFile()) {
            $this->enctype = "multipart/form-data";
            $this->method = "post";
        }
        return "<$this->tag$name enctype=\"$this->enctype\" action=\"$script\" method=\"$this->method\"{$attrs}>";
    }
}

class TUploadFile extends TInputControl
{
    function __construct($parent, $name = "")
    {
        parent::__construct($parent, $name, "file");
    }
}

?>
