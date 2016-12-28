<?php

namespace b166er;


abstract class AbstractCreator extends BaseClass
{

    protected $topic = '';
    protected $part;
    
    //php7: public function __construct(string $part)
    public function __construct($part)
    {
        $this->part = $part;

    }

    //php7: public function new()
    public function create()
    {
        $class_definition_exists = $this->definitionExists($this->part);

        if ($class_definition_exists) {
            $name = $this->part;
        } else {
            $name = 'Part';
        }
        $dao_class = '\\b166er\\' . $name . ucfirst($this->topic);
        return new $dao_class($this->part);
    }
    
    //php7: protected function definitionExists(string $nam) : bool
    protected function definitionExists($nam) 
    {
        global $_path;

        $tar = array(
            $_path . "/parts/" . $nam . "/" . $this->topic . ".php", // app_path/parts/user/class.php
            $_path . "/" . $nam . "/" . $this->topic . ".php",       // app_path/user/class.php
            __DIR__ . "/" . $nam . "-" . $this->topic . ".php",      // DIR/user-class.php
            __DIR__ . "/parts/" . $nam . "-" . $this->topic . ".php" // DIR/parts/user-class.php
        );

        foreach ($tar as $pth) {

            if (is_file($pth)) {
                require_once($pth);
                return true;
            }
        }
        return false;
    }


}

//abstract class Maker implements Iclass_definition_existance
//{
//    use class_definition_existance;
//
//}

trait class_definition_existance
{
    //php7: public function class_definition_exists(string $nam, string $res = 'class') : bool
    public function class_definition_exists($nam, $res = 'class') 
    {
        global $_path;

        $tar = array(
            $_path . "/parts/" . $nam . "/" . $res . ".php",
            $_path . "/" . $nam . "/" . $res . ".php",
            __DIR__ . "/" . $nam . "-" . $res . ".php",
            __DIR__ . "/parts/" . $nam . "-" . $res . ".php"
        );

        foreach ($tar as $pth) {
            $tmp = $pth . "/" . $res . ".php";
            if (is_file($tmp)) {
                require_once($tmp);
                return true;
            }
        }
        return false;
    }
}

abstract class DataSet extends Container implements IDataset
{
    protected $buffer_pdo;
    protected $buffer_DBMS;

    //php7: public function getPDO() : \PDO
    public function getPDO()
    {
        return $this->buffer_pdo;
    }

    public function setPDO(\PDO $pdo)
    {
        $this->buffer_pdo = $pdo;
    }

    //php7: public function getDBMS() : string
    public function getDBMS() 
    {
        try {

            if (empty($this->buffer_DBMS)) {

                $this->buffer_DBMS = $this->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME);

                if (is_null($this->buffer_DBMS)) {

                    throw new \PDOException('Cannot retrieve database management system');

                } else {

                    return $this->buffer_DBMS;

                }

            } else {

                return $this->buffer_DBMS;

            }

        } catch (\Exception $e) {

            exception_display($e);

            die();

        }
    }
}

trait ParentPDO
{
    //php7: public function getPDO() : \PDO
    public function getPDO() 
    {
        try {

            if ($this->parent() instanceof IDataset) {

                return $this->parent()->getPDO();

            } else {

                throw new \Exception('Cannot retrieve PDO object. Parent object not instance of IDataset');
            }

        } catch (\Exception $e) {

            exception_display($e);

            die();
        }

    }

    //php7: public function getDBMS() : string
    public function getDBMS() 
    {
        try {
            if ($this->parent() instanceof IDataset) {

                return $this->parent()->getDBMS();

            } else {

                throw new \Exception('Cannot retrieve DBMS. Parent object not instance of IDataset');
            }

        } catch (\Exception $e) {

            exception_display($e);

            die();
        }
    }
}

class PrototypeCollection extends Container
{
    protected $_key = 'prototype';
}

class DatabaseCollection extends Container
{
    protected $_key = 'database';

    //php7: public function newChild() : Container
    public function newChild() 
    {
        return new Database();
    }
}

class Database extends DataSet
{

    //php7: public function getPDO() : \PDO
    public function getPDO() 
    {
        try {

            if (is_null($this->buffer_pdo)) {

                $_dsn = empty($this->get('dsn')) ? $this->get('dsn/' . PHP_OS) : $this->get('dsn');

                if (!$_dsn) throw new \PDOException('invalid DSN');

                $this->buffer_pdo = new \PDO($_dsn, $this->get('user'), $this->get('password'));

            } else {

                if (!(get_class($this->buffer_pdo) == 'PDO')) {

                    throw new \PDOException('Not a PDO object');
                }

            }
        } catch (\Exception $e) {

            exception_display($e);

            die();

        }
        return $this->buffer_pdo;

    }
}


abstract class DatabaseStructureCollection extends Container implements IDataset
{
    use ParentPDO;

    //php7: public function __construct(string $name = 'Structure collection') // string $name
    public function __construct($name = 'Structure collection') 
    {
        $this->_key = $name;
    }

    //php7: public function newChild() : Container
    public function newChild() 
    {
        return null;
    }
}

class TableDefCollection extends DatabaseStructureCollection
{
    use ParentPDO;

    //php7: public function __construct(string $name = 'TableDefs')
    public function __construct($name = 'TableDefs')
    {
        parent::__construct($name);
    }

    //php7: public function newChild() : Container
    public function newChild() 
    {
        return new TableDef();
    }
}

class ViewDefCollection extends DatabaseStructureCollection
{
    use ParentPDO;

    //php7: public function __construct(string $name = 'ViewDefs')
    public function __construct($name = 'ViewDefs')
    {
        parent::__construct($name);
    }

    //php7: public function newChild() : Container
    public function newChild() 
    {
        return new TableDef();
    }
}
//
//class TableCollection extends DatabaseStructureCollection
//{
//    use ParentPDO;
//
//    public function __construct(string $name = 'tables')
//    {
//        parent::__construct($name);
//    }
//
//    public function newChild() : Container
//    {
//        return new Table();
//    }
//}

abstract class DatabaseStructure extends Container implements IDataset
{
    use ParentPDO;

    //php7:  public function __construct(string $name = 'database structure')
    public function __construct($name = 'database structure')
    {
        $this->_key = $name;
    }

    abstract function connect();

}

/**
 * Class TableDef
 * @package b166er
 *
 */
class TableDef extends DatabaseStructure
{
    use ParentPDO;

    //php7: public function __construct(string $name = 'table definition')
    public function __construct($name = 'table definition')
    {
        parent::__construct($name);

    }

    function connect()
    {
        // TODO: Implement connect() method.
    }
}

class ViewDef extends DatabaseStructure
{
    use ParentPDO;

    //php7: public function __construct(string $name = 'view definition')
    public function __construct($name = 'view definition')
    {
        parent::__construct($name);

    }

    function connect()
    {
        // TODO: Implement connect() method.
    }
}
//
//class Table extends DatabaseStructure
//{
//    use ParentPDO;
//
//    public function __construct(string $name = 'table')
//    {
//        parent::__construct($name);
//
//    }
//
//    function connect()
//    {
//        // TODO: Implement connect() method.
//    }
//}

class Record extends DataSet
{

}

class Part extends Container
{
    public $error = 0;
    public $message = '';

    public function fill()
    {

    }

    public function createAttributesIterator($attribute)
    {
        return new AttributesIterator($this, $attribute);
    }
}

abstract class Field extends Container implements IValidation
{

    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'int';
    const TYPE_DATETIME = 'datetime';
    const TYPE_BOOLEAN = 'bool';
    const TYPE_DATE = 'date';

    const SQL_NO_ATTRIBUTE = 0b0;
    const SQL_PRIMARY_KEY = 0b1;
    const SQL_REQUIRED = 0b10;
    const SQL_UNIQUE = 0b100;
    const SQL_AUTO_INCREMENT = 0b1000;
    const SQL_INDEX = 0b10000;
    const SQL_FOREIGN_KEY = 0b100000;


    public function Validate()
    {
        if (is_null($this->get('')) || $this->get('') == '') {

        }
        return true;
    }

    protected function errorSet($err_code, $err_description = '')
    {
        $this->set('ERROR', $err_code);
        $this->set('ERROR/DESCRIPTION', $err_description);

        if (empty($this->parent()->error)) {
            $this->parent()->error = $err_code;
            $this->parent()->message = $this->key() . ': ' . $err_description;
        }
    }
}

class DataField extends Field
{
    public function __construct($name, $type, $attributes, $len = 0)
    {
        $this->setKey($name);
        $this->set('ATTRIBUTES', $attributes);
        $this->set('TYPE', $type);
        $this->set('LENGTH', $len);
    }

    //php7: public function is($attribute) : bool
    public function is($attribute) 
    {
        $_attr = $this->get('ATTRIBUTES');
        if (($_attr & $attribute) == $attribute) {
            return true;
        } else {
            return false;
        }
    }
}

class StringField extends DataField
{
    public function __construct($name, $attributes, $len = 0)
    {
        parent::__construct($name, Field::TYPE_STRING, $attributes, $len);
    }
}

class IntField extends DataField
{
    public function __construct($name, $attributes, $len)
    {
        parent::__construct($name, Field::TYPE_INTEGER, $attributes, $len);
    }

    public function Validate()
    {

        if (parent::Validate() == false) return false;

        //INTEGER LOGIC
        if (preg_match('/[^0-9]+$/', $this->_value)) {
            $this->errorSet(ERR::WRONG_DATA, 'not number');
            return false;
        }
        return true;
    }
}

class DateField extends DataField
{

    protected $date;

    public function __construct($name, $attributes, $len)
    {
        parent::__construct($name, Field::TYPE_DATE, $attributes, $len);
    }

    public function Validate()
    {

        if (parent::Validate() == false) return false;

        //DATE LOGIC
        $temp = date_create_from_format('Y-m-d H:i:s', $this->_value);
        $temp2 = date_create_from_format('Y-m-d', $this->_value);

        if (!$temp) $temp = $temp2;

        $this->date = $temp;

        if (!$this->date) {
            $this->errorSet(ERR::WRONG_DATA, 'not date');
            //$this->set('ERROR', 'NotDate');
            $this->_value = false;
            return false;
        } else {
            $this->_value = $this->date->format('Y-m-d');
            return true;
        }
    }
}

class DatetimeField extends DataField
{
    protected $datetime;

    public function __construct($name, $attributes, $len)
    {
        parent::__construct($name, Field::TYPE_DATETIME, $attributes, $len);
    }

    public function Validate()
    {
        if (parent::Validate() == false) return false;

        //DATETIME LOGIC
        $temp = date_create_from_format('Y-m-d H:i:s', $this->_value);
        $temp2 = date_create_from_format('Y-m-d', $this->_value);

        if (!$temp) $temp = $temp2;

        $this->datetime = $temp;

        if (!$this->datetime) {
            $this->errorSet(ERR::WRONG_DATA, 'not date');
            $this->_value = false;
            return false;
        } else {
            $this->_value = $this->datetime->format('Y-m-d H:i:s');
            return true;
        }
    }
}

class BoolField extends DataField
{
    public function __construct($name, $attributes, $len)
    {
        parent::__construct($name, Field::TYPE_BOOLEAN, $attributes, $len);
    }

    public function Validate()
    {

        if (parent::Validate() == false) return false;

        //BOOLEAN LOGIC

        if (!($this->_value == true || $this->_value == false)) {
            $this->errorSet(ERR::WRONG_DATA, 'not bool');
            return false;
        } else {
            return true;
        }
    }
}

class ClassCreator extends AbstractCreator
{
    protected $topic = 'class';

    //php7: public function new() //overrides the AbstractCreator->new()
    public function create() //overrides the AbstractCreator->new()

    {
        try {


            /*
             * checks whether there's an object with that name in __app
             */
            $_proto = __app()->contains('prototype/' . strtolower($this->part));
            /*
             * if so return a copy of it
             */
            if ($_proto) {
                return $_proto->copy();
            }

            /*
             * at this point there's no prototype definition
             * with that name in __app.
             *
             * $_class stores the name the class should have if
             * it existed
            */

            $_class = "\\b166er\\" . ucfirst($this->part);

            /*
             * importing the definition of the class provided the path
             * is a meaningful one.
             */

            if (!class_exists($_class)) {
                $definition_exists = $this->definitionExists($this->part);

            }

            /*
             * try again to check for class availability in the project scope
             */

            if (class_exists($_class)) {
                $_new = new $_class();

            } else {

                /*
                 * if not, create a simple Part object. No more, no less
                 */
                $_new = new Part();

            }

            $_new->setKey($this->part);

            /*
             *  so let's check for some database definition
             *  to store in $def
             */

            $def = object_defined_in_db_collection($this->part, __app()->contains('database'));

            /*
             * if so
             */
            if ($def) {

                $DBU = DBU::deploy($def->getDBMS());

                /*
                 * merge the data into the object
                 */
                $DBU::merge_schema_from_database($_new, $def);
            }

            /*
             * complete the definition of the object with additional
             * informations provided by the fill() function
             */
            $_new->fill();

            /*
             * add the freshly created object to the prototype collection
             */
            __app()->contains('prototype')->add($_new);

            /*
             * and return a copy of it
             */
            return $_new->copy();

        } catch (\Exception $e) {

            exception_display($e);
            die();
        }
    }

}
