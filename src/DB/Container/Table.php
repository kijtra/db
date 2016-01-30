<?php
namespace Kijtra\DB\Container;

/**
 * follow PSR-11 Container interface
 * https://github.com/container-interop/fig-standards/blob/master/proposed/container.md
 */

use \Kijtra\DB\Constant;
use \Kijtra\DB\Connection;
use \Kijtra\DB\Container\Column;

class Table implements Constant, \ArrayAccess, \IteratorAggregate
{
    private $conn;
    private $raw = array();
    private $data = array();

    private $name;
    private $queryName;
    private $columns = array();

    private $values = array();
    private $formatter = array();
    private $validator = array();

    public function __construct($name, $conn)
    {
        $classConnection = Constant::CLASS_CONNECTION;
        if (!($conn instanceof $classConnection)) {
            throw new \Exception('Database not connected.');
        } elseif(!is_string($name)) {
            throw new \Exception('Table name is not string.');
        }

        $config = $conn->config;

        $dbName = $config['name'];
        $tableName = str_replace(array("'", "`"), '', $name);
        $tableName = explode('.', $tableName);
        if (!empty($tableName[1])) {
            $dbName = $tableName[0];
            $tableName = $tableName[1];
        } else {
            $tableName = $tableName[0];
        }

        $this->name = $tableName;
        $this->queryName  = "`".trim($conn->quote($dbName), "'")."`.";
        $this->queryName .= "`".trim($conn->quote($tableName), "'")."`";

        try {
            $sql  = "SHOW TABLE STATUS FROM ";
            $sql .= "`".trim($conn->quote($dbName), "'")."` LIKE ?;";
            $stmt = $conn->prepare($sql);
            if ($stmt->execute(array($this->name), true)) {
                $raw = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (empty($raw)) {
                    throw new \Exception(sprintf('Table "%s" not found.', $this->name));
                }

                $this->raw = $raw;

                $data = array();
                foreach($raw as $key => $val) {
                    $lower = strtolower($key);
                    $data[$lower] = $val;

                    if ('collation' == $lower) {
                        $data['charset'] = $this->formatCharset($val);
                    }
                }

                foreach(array(
                    'rows',
                    'avg_row_length',
                    'data_length',
                    'max_data_length',
                    'index_length',
                    'data_free',
                    'auto_increment'
                ) as $val) {
                    if (array_key_exists($val, $data)) {
                        $data[$val] = (int)$data[$val];
                    }
                }

                if (!empty($data['comment']) && !empty($data['charset'])) {
                    $data['comment'] = mb_convert_encoding(
                        $data['comment'],
                        $data['charset'],
                        'auto'
                    );
                }

                $data['index'] = $data['primary'] = $data['require'] = array();
                $data['raw'] = $raw;

                $this->data = $data;

                $sql  = "SHOW FULL COLUMNS FROM ".$this->queryName.";";
                if ($query = $conn->query($sql, true)) {
                    if (!$query->rowCount()) {
                        throw new \Exception(sprintf('Table "%s" has no columns.', $tableName));
                    }

                    $columnClass = Constant::CLASS_COLUMN;
                    $requires = $primaries = $indicies = array();
                    while($val = $query->fetch(\PDO::FETCH_ASSOC)) {
                        $column = new $columnClass($this, $val);
                        $this->columns[$column['name']] = $column;

                        if ($column['require']) {
                            $requires[] = $column['name'];
                        }

                        if ($column['primary']) {
                            $primaries[] = $column['name'];
                        }

                        if ($column['index']) {
                            $indicies[] = $column['name'];
                        }
                    }

                    $this->offsetSet('require', $requires);
                    $this->offsetSet('primary', $primaries);
                    $this->offsetSet('index', $indicies);
                }
            }
        } catch(\PDOException $e) {
            throw $e;
        }
    }

    public function name()
    {
        return $this->name;
    }

    public function qname()
    {
        return $this->queryName;
    }

    public function columns($key = null)
    {
        if (empty($key)) {
            return $this->columns;
        } elseif (!empty($this->columns[$key])) {
            return $this->columns[$key];
        }
    }

    public function values($values)
    {
        if (is_array($values)) {
            $columns = $this->columns();
            $filtered = array();
            foreach($values as $column => $val) {
                if (!empty($this->columns[$column])) {
                    $this->columns[$column]->offsetSet('value', $val);
                }
            }
        }

        return $this;
    }


    public function formatter($arg1 = null, $arg2 = null)
    {
        $callbacks = null;
        if (is_array($arg1)) {
            $callbacks = $arg1;
        } elseif(is_string($arg1) && !empty($arg2)) {
            $callbacks = array($arg1 => $arg2);
        }

        if (!empty($callbacks)) {
            foreach($callbacks as $column => $callback) {
                if (!empty($this->columns[$column]) && is_callable($callback)) {
                    $callback = $callback->bindTo($this->columns[$column]);
                    $this->formatter[$column] = $callback;
                }
            }
        }

        return $this;
    }

    public function validator($arg1 = null, $arg2 = null)
    {
        $callbacks = null;
        if (is_array($arg1)) {
            $callbacks = $arg1;
        } elseif(is_string($arg1) && !empty($arg2)) {
            $callbacks = array($arg1 => $arg2);
        }

        if (!empty($callbacks)) {
            foreach($callbacks as $column => $callback) {
                if (!empty($this->columns[$column]) && is_callable($callback)) {
                    $callback = $callback->bindTo($this->columns[$column]);
                    $this->validator[$column] = $callback;
                }
            }
        }

        return $this;
    }

    public function format()
    {
        if (empty($this->formatter)) {
            return;
        }

        foreach($this->formatter as $column => $callback) {
            $result = $callback($this->columns[$column]->offsetGet('value'));
            $this->columns[$column]->offsetSet('value', $result);
        }

        return $this;
    }

    public function validate()
    {
        if (empty($this->validator)) {
            return;
        }

        $results = array();
        foreach($this->validator as $column => $callback) {
            $results[] = $callback($this->columns[$column]->offsetGet('value'));
        }

        return $results;
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

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function __debugInfo()
    {
        return $this->data;
    }
}
