<?php
namespace Kijtra\DB\Container;

/**
 * follow PSR-11 Container interface
 * https://github.com/container-interop/fig-standards/blob/master/proposed/container.md
 */

use \Kijtra\DB\Constant;

class Base implements Constant, \ArrayAccess, \IteratorAggregate
{
    public $conn;
    protected $raw = array();
    protected $data = array();

    public function data()
    {
        return $this->data;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        return $this->offsetGet($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundException`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($id)
    {
        return $this->offsetExists($id);
    }

    protected function formatCharset($value)
    {
        if (!empty($value)) {
            $value = strtolower($value);
            $charset = substr($value, 0, strpos($value, '_'));
            $charset = str_replace('mb4', '', $charset);
            return $charset;
        }
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function offsetExists($offset)
    {
        if (!is_string($offset)) {
            throw new \TypeError('Argument must be of the type string, '.gettype($offset).' given, called');
        }

        $offset = strtolower($offset);
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        if (!is_string($offset)) {
            throw new \TypeError('Argument must be of the type string, '.gettype($offset).' given, called');
        }

        $offset = strtolower($offset);
        if (array_key_exists($offset, $this->data)) {
            return $this->data[$offset];
        }
    }

    public function offsetSet($offset, $value) {
        if (!is_string($offset)) {
            throw new \TypeError('Argument must be of the type string, '.gettype($offset).' given, called');
        }

        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset) {
        if (!is_string($offset)) {
            throw new \TypeError('Argument must be of the type string, '.gettype($offset).' given, called');
        }

        unset($this->data[$offset]);
    }

    function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function __debugInfo()
    {
        return $this->data;
    }
}
