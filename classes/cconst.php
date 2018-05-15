<?php
/*
GPCL for PHP (General Purpose Class Library) version 2.4 beta
(c) 2008-2018 Sergei Korzhinskii - www.snkey.net
All rights reserved.

cconst.php
Classes:
 - TObject - Basic abstract class for all others in GPCL
 - CConst - Set of internal definitions.  

*/

abstract class TObject
{
    protected $parentcontrol; //parent object reference

    public function __construct($parent = NULL)
    {
        $this->parentcontrol = $parent;
    }

    public function __get($pname)
    {
        if (property_exists($this, $pname)) return $this->$pname;
        else return null;
    }

    public function __isset($pname)
    {
        return isset($this->$pname);
    }

    public function GetParentControl()
    {
        if ($this->HasParent()) return $this->parentcontrol;
        else return NULL;
    }

    public function HasParent()
    {
        return isset($this->parentcontrol);
    }

    public function GetParentProperty($name)
    {
        if (!$this->HasParent()) return NULL;
        /** @$this->parentcontrol TObject */
        $parent = $this->parentcontrol;
        while ((!property_exists($parent, $name)) || (!isset($parent->$name))) {
            $parent = $parent->parentcontrol;
        }
        if (property_exists($parent, $name)) return $parent->$name;
        else return NULL;
    }
}

abstract class TComponent extends TObject
{
    protected $name; //control internal name (in TFormControl and it's descendants this = name attribute)

    public function __construct($parent)
    {
        parent::__construct($parent);
    }

    public function SetName($newname)
    {
        if ($newname != "") $this->name = $newname;
    }

    public function ParentDocument()
    {
        $parent = $this->parentcontrol;
        if (!isset($this->parentcontrol)) return NULL;
        while (!property_exists($parent, 'prefix')) {
            $parent = $parent->parentcontrol;
        }
        if (property_exists($parent, 'prefix')) return $parent;
        else return NULL;
    }
}

class CConst
{
    public static $vmajor = 2;
    public static $vminor = 4;
    public static $vsub = " beta";
    public static $xmlns = "https://www.w3.org/1999/xhtml";

    public static function engine()
    {
        if (CValues::$lang == "ru") {
            return "Сделано пр помощи <a href=\"http://www.snkey.net\">GPCL для PHP</a> версии " . self::version(true);
        }
        if (CValues::$lang == "de") {
            return "Powered by <a href=\"http://www.snkey.net\">GPCL fur PHP</a> version " . self::version(true);
        }
        return "Powered by <a href=\"http://www.snkey.net\">GPCL for PHP</a> version " . self::version(true);
    }

    public static function version($full = true)
    {
        $ret = self::$vmajor . '.' . self::$vminor;
        if ($full) $ret .= self::$vsub;
        return $ret;
    }

    public static function isxhtml()
    {
        return !((CValues::$doctype == "HTML4") || (CValues::$doctype == "HTML4S") || (CValues::$doctype == "HTML5"));
    }

    public static function doctype()
    {
        if (CValues::$doctype == "XHTML11") {
            return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
        }
        if (CValues::$doctype == "XHTML1") {
            return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        }
        if (CValues::$doctype == "XHTML1S") {
            return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
        }
        if (CValues::$doctype == "HTML4") {
            return '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
        }
        if (CValues::$doctype == "HTML4S") {
            return '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
        }
        return '<!DOCTYPE html>';
    }
}

?>
