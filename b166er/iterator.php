<?php
/*
 * Date: 24/06/14
 * Time: 22.32
 */
//****************************************************
//****************************************************
//*******************   PATTERN   ********************
//****************************************************
//******************** ITERATOR  *********************
//****************************************************
//****************************************************
namespace b166er;

class ComponentIterator implements \Iterator
{
    protected $_child = array();
    protected $_count;

    public function __construct(Component $root)
    {
        $this->_child = $root->toArray();
        $this->rewind();
    }

    public function current()
    {
        return array_values($this->_child)[$this->_count];
    }

    public function key()
    {
        return array_keys($this->_child)[$this->_count];
    }

    public function next()
    {
        ++$this->_count;
        return $this->current();
    }

    public function rewind()
    {
        $this->_count = 0;
        return $this->current();
    }
    
    //php7: public function valid() : bool
    public function valid()
    {
        return is_object($this->current());
    }

//    public function hasNext() : bool
//    {
//        $temp_count = $this->_count + 1;
//        $temp_child = array_values($this->_child)[$temp_count];
//        return is_object($temp_child);
//    }
}

class NullComponentIterator extends ComponentIterator
{
    public function __construct(Component $com)
    {
        $this->_child = $com;
    }

    public function current()
    {
        return null;
    }

    public function next()
    {
        return false;
    }

    public function rewind()
    {
        return false;
    }

    public function remove()
    {
    }

    public function valid()
    {
        return false;
    }

//    public function hasNext() : bool
//    {
//        return false;
//    }
}

class AttributesIterator extends ComponentIterator
{
    public function __construct(Component $root, $attribute)
    {
        $lis = $root->toArray();
        $temp = array();
        foreach ($lis as $elem) {
            $element_attributes = $elem->get('ATTRIBUTES');
            if (($element_attributes & $attribute) == $attribute) {
                $temp[] = $elem;
            }
        }
        $new_com = new Container();
        $new_com->import($temp);
        parent::__construct($new_com);
    }
}

class ComponentTreeIterator extends ComponentIterator
{
    protected $innerIterator = null;
    protected $outerIterator;

    protected $_current;
    protected $_key;
    protected $_path = '';
    protected $_depth = 0;

    public function __construct(Container $root, ComponentTreeIterator $outerIterator = null)
    {
        $this->outerIterator = $outerIterator;
        parent::__construct($root);

        $this->_current = $this->parent_current();
//        $this->_key = $this->parent_key();
        $this->_key = '';
    }

    public function getDepth()
    {
        return $this->_depth;
    }

    //php7: private function isNode() : bool
    private function isNode()
    {
        return ($this->parent_current()->size() > 0) ? true : false;
    }

    protected function parent_current()
    {
        return parent::current();
    }

    public function current()
    {
        return $this->_current;
    }

    public function key()
    {
        return $this->_key;
    }

    public function path()
    {
        return $this->_path;
    }

    protected function parent_key()
    {
        return parent::key();
    }

    public function next()
    {
        //contains the cursor being analyzed
        $cursor_iterator = $this;
        $next_element = null;

        //drills down the tree structure
        while ($cursor_iterator->innerIterator) {
            $cursor_iterator = $cursor_iterator->innerIterator;
        }

        //two cases: it's an internal node (node)
        //            or an external one   (leaf)
        if ($cursor_iterator->isNode()) {
            $cursor_iterator->innerIterator = new ComponentTreeIterator($cursor_iterator->parent_current(), $cursor_iterator);
            $next_element = $cursor_iterator->innerIterator->_current;
        } else {
            $next_element = $cursor_iterator->parent_next();

        }

        //the cursor goes back to 'surface'
        //adjusting the next element if
        //the current branch has already been visited
        while ($cursor_iterator->outerIterator) {
            if (!$cursor_iterator->valid()) {
                $next_element = $cursor_iterator->outerIterator->parent_next();
                $cursor_iterator = $cursor_iterator->outerIterator;
                $cursor_iterator->innerIterator = null;
            } else {
                break;
            }
        }

        //retrieves the path to the current key
        //and the depth of the key
        $path = '';
        $depth = 0;

        if ($this->parent_current()) {
            $temp = $this;
            do {
                if (!is_null($temp->innerIterator)) {
                    $path .= '/' . $temp->parent_current()->key();
                    $depth++;
                }
                $temp = $temp->innerIterator;


            } while ($temp->innerIterator);
        }

        $this->_current = $next_element;

        $this->_key = is_null($this->parent_current()) ? '' : $this->parent_current()->key();
        $this->_path = trim($path, '/');
        $this->_depth = $depth;

        return $this->_current;
    }

    protected function parent_next()
    {
        ++$this->_count;
        $this->_current = array_values($this->_child)[$this->_count];
        $this->_key = array_keys($this->_child)[$this->_count];
        return $this->_current;
    }

    public function rewind()
    {
        $this->_count = 0;
        $this->_current = $this->parent_current();
        return $this->current();
    }

    //php7: public function valid() : bool
    public function valid()
    {
        return is_object($this->parent_current());
    }
}

?>