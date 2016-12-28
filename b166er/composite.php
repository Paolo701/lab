<?php

namespace b166er;
/**
 * Class Component
 * @package b166er
 */
abstract class BaseClass{}

class Component extends BaseClass
{

    protected $_parent;
    protected $_key;
    protected $_value;

    /**
     * @param Component $com
     * @return bool
     */
    public function add(Component $com)
    {
        return false;
    }

    // public function copy() : Component
    public function copy()
    {
        return clone $this;
    }
    /**
     * @param string $key
     * @param string $separator
     * @return null
     */
    public function get($key = '', $separator = "/")
    {
        if ($key == "") {
            return $this->_value;
        } else {
            return null;
        }
    }

    public function equals(Component $com)
    {
        if (($this->key() != $com->key()) ||
            ($this->get("") != $com->get("")) ||
            ($this->size() != $com->size())
        ) {
            return false;
        }
        $arr = $com->toArray();
        foreach ($arr as $r) {
            $a = $this->get($r->key());
            $b = $com->get($r->key());
            if ($a != $b) {
                return false;
            }
        }
        return true;
    }

    public function key()
    {
        return $this->_key;
    }

    public function size()
    {
        return 0;
    }

    public function toArray()
    {
        return array();
    }

    public function exists($key, $separator = "/")
    {
        return $this->contains($key, $separator = "/");

    }

    /**
     * @param $key
     * @param string $separator
     * @return $this|bool
     */
    public function contains($key, $separator = "/") 
    {
        /*
         * retrieves itself if asked for default
         * key. False in any other case
         */
        if (($key === "") || ($key == $this->_key)) {
            return $this;
        } else {
            return null;
        }
    }

    public function getComposite()
    {
        return null;
    }

    public function import(array $arr)
    {
        return false;
    }

    public function parent()
    {
        return $this->_parent;
    }

    public function remove($key, $separator = "/")
    {
        return false;
    }

    public function set($key, $val, $separator = "/")
    {
        $this->_value = $val;
    }

    protected function newChild()
    {
        return null;
    }

    public function setKey($key)
    {
        $this->_key = $key;
    }

    public function setParent(Component $com)
    {
        $this->_parent = $com;
    }

    public function createIterator()
    {
        return new NullComponentIterator($this);
    }

    public function createTreeIterator()
    {
        return new NullComponentIterator($this);
    }

    public function __toString()
    {
        return (string)$this->_key . "=>" . trim($this->_value);
    }

}

/**
 * Class Container
 * @package b166er
 */
class Container extends Component
{
    protected $_child = array();

    public function equals(Component $com)
    {
        if (parent::equals($com) == false) {
            return false;
        }
        $arr = $com->toArray();
        foreach ($arr as $r) {
            if (!($r->equals($this->_child[$r->key()]))) {
                return false;
            }
        }
        return true;
    }

    public function get($key = '', $separator = '/')
    {
        if ($key == '') {
            return $this->_value;
        } else {
            $temp = $this->contains($key, $separator);
            return $temp ? $temp->get() : null;
        }
    }

    public function getComposite()
    {
        return $this;
    }

    public function import(array $arr)
    {
        if (empty($arr)) {
            return false;
        }
        try {
            foreach ($arr as $key => $value) {
                if (is_array($value)) {
                    //$t=reset($value);
                    foreach ($value as $k => $v) {
                        $this->set($key . '/' . $k, $v);
                    }
                } else {
                    $this->set($key, $value);
                }
            }
            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $key
     * @param $val
     * @param string $separator
     */
    public function set($key, $val, $separator = "/")
    {
        if ($key === "") {
            $this->_value = $val;
            return;
        }
        $parts = explode($separator, $key);
        $cursor = $this;
        //
        foreach ($parts as $part) {
            if (!$cursor->contains($part)) {
                $new = $cursor->newChild();
                $new->setKey($part);
                $cursor->add($new);
            }
            $cursor = $cursor->contains($part);
        }
        $cursor->set('', $val);
    }

//    public function set0($key, $val, $separator = "/")
//    {
//        if ($key === "") {
//            $this->_value = $val;
//            return;
//        }
//
//        $split = strpos($key, $separator);
//
//        if ($split) {
//            $parts = explode($separator, $key);
//            $t = $this;
//            //
//            for ($i = 0; $i <= count($parts) - 1; $i++) {
//                //
//                if (!$t->contains($parts[$i])) {
//                    //
//                    $temp = new Container();
//                    $temp->setKey($parts[$i]);
//                    $t->add($temp);
//                }
//                //
//                if (!is_null($parts[$i + 1])) {
//                    //
//                    $t = $t->contains($parts[$i]);
//                    //
//                } else {
//                    //
//                    $t->set($parts[$i], $val);
//                }
//            }
//            //
//        } else {
//            //
//            if ($this->contains($key)) {
//                //
//                $this->_child[$key]->set("", $val);
//                //
//            } else {
//                /*
//                policy of container creates new key if not
//                already existing.
//                 */
//                $temp = new Container();
//                $temp->setKey($key);
//                $temp->set("", $val);
//                $this->add($temp);
//            }
//        }
//    }
    // protected function newChild() : Container
    protected function newChild()
    {
        return new Container();
    }

    public function add(Component $com)
    {
        $temp_con = $this->contains($com->key());
        // if it exists
        if ($temp_con) {
            $com->setParent($this);
            //all the magic is here
            //function defined in global.php
            array_splice_assoc($this->_child, $com->key(), 1, array($com->key() => $com));
        } else {
            $com->setParent($this);
            $this->_child[$com->key()] = $com;
        }
        return $this->_child[$com->key()];
    }

    public function contains($key, $separator = "/")
    {
        if (is_null($key) || ($key === "")) {
            return $this;
        }
        $parts = explode($separator, $key);
        $cursor = $this;
        //
        foreach ($parts as $part) {
            if (!$cursor->_child[$part]) {
                return null;
            }
            $cursor = $cursor->_child[$part];
        }
        return $cursor;
    }

//    public function contains0($key, $separator = "/")
//    {
//        /*
//         * recursive function that accepts a path
//         * and returns the component or false if not existing
//         */
//        if (is_null($key)) {
//            return $this;
//        }
//        if ($key === "") {
//            return $this;
//        }
//        $split = strpos($key, $separator);
//
//        if ($split) {
//            //
//            $keys[0] = substr($key, 0, $split);
//            //
//            $keys[1] = substr($key, $split + 1);
//
//            //
//        } else {
//            //
//            $keys[0] = $key;
//
//            //
//            if (array_key_exists($keys[0], $this->_child)) {
//                //
//                return $this->_child[$keys[0]];
//
//            } else {
//                //
//                return false;
//            }
//        }
//        //
//        if (array_key_exists($keys[0], $this->_child)) {
//            //recursive
//            return $this->_child[$keys[0]]->contains($keys[1], $separator);
//
//        } else {
//            return false;
//        }
//    }

    public function remove($key, $separator = "/")
    {
        $ret = true;
        if (strpos($key, $separator)) {
            $con = $this->contains($key, $separator = "/");
            if ($con) {
                $arr_key = explode($separator, $this->key());
                $last = $arr_key[sizeof($arr_key) - 1];
                $p_con = $con->parent();
                $ret = $p_con->remove($last);
            }else{
                $ret = false;
            }
        } else {
            $this->_child[$key]->_parent = null;
            unset($this->_child[$key]);
            $ret = true;
        }
        return $ret;
    }

    public function createIterator()
    {
        return new ComponentIterator($this);
    }

    public function createTreeIterator()
    {
        return new ComponentTreeIterator($this);
    }

    public function size()
    {
        return count($this->_child);
    }

    public function toArray()
    {
        return $this->_child;
    }

    // public function copy() : Component
    public function copy()
    {
        $clone = clone $this;

        $it = $this->createTreeIterator();
        while ($it->valid()) {
            $temp = clone $it->current();
            $clone->contains($it->path())->add($temp);

            $it->next();
        }
        $clone->_parent = null;
        return $clone;
    }

    public function __toString()
    {
        $temp = parent::__toString();
        $it = $this->createIterator();
        while ($it->valid()) {
            //
            $temp .= "   " . $it->current() . PHP_EOL;
            //
            $it->next();
        } //
        $it = null;
        return $temp;
    }

}

?>
