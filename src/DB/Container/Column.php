<?php
namespace Kijtra\DB\Container;

/**
 * follow PSR-11 Container interface
 * https://github.com/container-interop/fig-standards/blob/master/proposed/container.md
 */

use \Kijtra\DB\Constant;
use \Kijtra\DB\Connection;
use \Kijtra\DB\Container\Table;

class Column implements Constant, \ArrayAccess, \IteratorAggregate
{
    private $table;

    private $raw = array();
    private $data = array();

    public function __construct($table, $raw)
    {
        $classTable = Constant::CLASS_TABLE;
        if(!($table instanceof $classTable)) {
            throw new \Exception('Table is not object.');
        }

        $this->table = $table;

        if (is_array($raw)) {
            $this->raw = $raw;
        }

        $this->format($raw);
    }

    public function table($key = null)
    {
        if (empty($key)) {
            return $this->table;
        } else {
            return $this->table->offsetGet($key);
        }
    }

    public function siblings($name = null)
    {
        return $this->table->columns($name);
    }

    private function format($raw)
    {
        $requires = $primaries = $indicies = array();

        $data = array(
            'name' => $raw['Field'],
            'comment' => (!empty($raw['Comment']) ? $raw['Comment'] : null),
            'type' => $this->formatType($raw['Type']),
            'length' => $this->formatLength($raw['Type']),
            'unsigned' => $this->formatUnsigned($raw['Type']),
            'default' => $this->formatDefault($raw['Default'], $raw['Extra']),
            'charset' => $this->formatCharset($raw['Collation']),
            'require' => $this->formatRequire($raw['Null']),
            'primary' => $this->formatPrimary($raw['Key']),
            'index' => $this->formatIndex($raw['Key']),
            'auto_increment' => $this->formatAutoIncrement($raw['Extra']),
            'value' => null,
            'raw' => $raw,
        );

        if (!empty($data['comment']) && !empty($data['charset'])) {
            $data['comment'] = mb_convert_encoding($data['comment'], $data['charset'], 'auto');
        }

        return $this->data = $data;
    }


    private function formatType($value)
    {
        $value = strtolower($value);

        if (
            (0 === strpos($value, 'tinyint') && false !== strpos($value, '(1)')) ||
            0 === strpos($value, 'bool') ||
            0 === strpos($value, 'bit')
        ) {
            return 'bool';
        } elseif (
            false !== strpos($value, 'int') ||
            0 === strpos($value, 'year')
        ) {
            return 'integer';
        } elseif (
            false !== strpos($value, 'float') ||
            false !== strpos($value, 'decimal') ||
            false !== strpos($value, 'double') ||
            0 === strpos($value, 'dec')
        ) {
            return 'float';
        } elseif (
            false !== strpos($value, 'char') ||
            false !== strpos($value, 'text')
        ) {
            return 'string';
        } elseif (
            0 === strpos($value, 'timestamp') ||
            0 === strpos($value, 'datetime')
        ) {
            return 'datetime';
        } elseif (0 === strpos($value, 'date')) {
            return 'date';
        } elseif (0 === strpos($value, 'time')) {
            return 'time';
        } elseif (false !== strpos($value, 'binary')) {
            return 'binary';
        } elseif (false !== strpos($value, 'blob')) {
            return 'blob';
        } elseif (
            false !== strpos($value, 'enum') ||
            0 === strpos($value, 'set')
        ) {
            return 'set';
        } elseif (
            false !== strpos($value, 'geometry') ||
            false !== strpos($value, 'point') ||
            false !== strpos($value, 'polygon') ||
            false !== strpos($value, 'linestring')
        ) {
            return 'geometry';
        } elseif (false !== strpos($value, 'json')) {
            return 'json';
        } else {
            return 'unknown';
        }
    }

    private function formatLength($value)
    {
        if (preg_match('/\((\d+)\)/', $value, $match)) {
            return (int)$match[1];
        }
    }

    private function formatUnsigned($value)
    {
        return (false !== strpos(strtolower($value), 'unsigned'));
    }

    private function formatRequire($value)
    {
        if (empty($value)) {
            return false;
        }

        return ('no' == strtolower($value));
    }

    private function formatDefault($value, $extra)
    {
        if (preg_match('/CURRENT_(TIME|DATE)/i', $value) || preg_match('/CURRENT_(TIME|DATE)/i', $extra)) {
            return 'current_timestamp';
        } else {
            return $value;
        }
    }

    private function formatCharset($value)
    {
        if (!empty($value)) {
            $value = strtolower($value);
            $charset = substr($value, 0, strpos($value, '_'));
            $charset = str_replace('mb4', '', $charset);
            return $charset;
        }
    }

    private function formatPrimary($value)
    {
        if (empty($value)) {
            return false;
        }

        return (0 === strpos(strtolower($value), 'pri'));
    }

    private function formatIndex($value)
    {
        if (empty($value)) {
            return false;
        }

        return (preg_match('/\A(pri|key|mul)/i', strtolower($value)) >= 0);
    }

    private function formatAutoIncrement($value)
    {
        if (empty($value)) {
            return false;
        }

        return (0 === strpos(strtolower($value), 'auto_incr'));
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
