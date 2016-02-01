<?php
namespace Kijtra\DB;

use \Kijtra\DB\Connection;
use \Kijtra\DB\Column;

class Table implements \ArrayAccess, \IteratorAggregate
{
    private $name;
    private $fullName;
    private $queryName;

    private $raw = array();
    private $data = array();

    private static $columns = array();

    public function __construct($name, $conn)
    {
        if (!($conn instanceof Connection)) {
            throw new \Exception('Database not connected.');
        } elseif(!is_string($name)) {
            throw new \TypeError('Argument must be of the type string, '.gettype($name).' given, called');
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
        $this->fullName  = $dbName.$tableName;
        $this->queryName  = "`".trim($conn->quote($dbName), "'")."`.";
        $this->queryName .= "`".trim($conn->quote($tableName), "'")."`";

        try {
            $sql  = "SHOW TABLE STATUS FROM ";
            $sql .= "`".trim($conn->quote($dbName), "'")."` LIKE ?;";
            $stmt = $conn->prepare($sql);
            if ($stmt->execute(array($this->name), null, true)) {
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
                        $data['charset'] = $this->correctCharset($val);
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
                    $charset = str_replace('mb4', '', $data['charset']);
                    $data['comment'] = mb_convert_encoding(
                        $data['comment'],
                        mb_internal_encoding(),
                        $charset
                    );
                }

                $data['index'] = $data['primary'] = $data['require'] = array();

                $this->data = $data;


                $sql  = "SHOW FULL COLUMNS FROM ".$this->queryName.";";
                if ($query = $conn->query($sql, null, true)) {
                    if (!$query->rowCount()) {
                        throw new \Exception(sprintf('Table "%s" has no columns.', $tableName));
                    }

                    $requires = $primaries = $indicies = array();
                    while($val = $query->fetch(\PDO::FETCH_ASSOC)) {
                        $column = new Column($this, $val);
                        self::$columns[$column['name']] = $column;

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

    public function get($key = null)
    {
        if (empty($key)) {
            return $this->data;
        } else {
            return $this->offsetGet($key);
        }
    }

    public function getRaw()
    {
        return $this->raw;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFullName()
    {
        return $this->fullName;
    }

    public function getQueryName()
    {
        return $this->queryName;
    }

    public function getColumns($key = null)
    {
        if (empty($key)) {
            return self::$columns;
        } elseif (!empty(self::$columns[$key])) {
            return self::$columns[$key];
        }
    }

    public function setValues($values)
    {
        $args = func_get_args();
        $num = func_num_args();
        if (2 == $num && is_string($args[0]) && !empty(self::$columns[$args[0]])) {
            self::$columns[$args[0]]->setValue($args[1]);
        } elseif(is_array($values)) {
            foreach($values as $column => $val) {
                if (!empty(self::$columns[$column])) {
                    self::$columns[$column]->setValue($val);
                }
            }
        }

        return $this;
    }

    public function getValues($key = null)
    {
        if (is_string($key)) {
            if (!empty(self::$columns[$key])) {
                return self::$columns[$key]->getValue();
            }
        } else {
            $values = array();
            foreach(self::$columns as $column) {
                $values[$column->getName()] = $column->getValue();
            }
            return $values;
        }
    }

    public function clearValues()
    {
        foreach(self::$columns as $key => $column) {
            self::$columns[$key]->removeValue();
        }

        return $this;
    }

    public function clearAll()
    {
        foreach(self::$columns as $key => $column) {
            self::$columns[$key]->removeValue()->removeFormatter()->removeValidator();
        }

        return $this;
    }

    public function setFormatters($values)
    {
        $args = func_get_args();
        $num = func_num_args();
        if (2 == $num && is_string($args[0]) && !empty(self::$columns[$args[0]])) {
            self::$columns[$args[0]]->setFormatter($args[1]);
        } elseif(is_array($values)) {
            foreach($values as $column => $val) {
                if (!empty(self::$columns[$column])) {
                    self::$columns[$column]->setFormatter($val);
                }
            }
        }

        return $this;
    }

    public function setValidators($values)
    {
        $args = func_get_args();
        $num = func_num_args();
        if (2 == $num && is_string($args[0]) && !empty(self::$columns[$args[0]])) {
            self::$columns[$args[0]]->setFormatter($args[1]);
        } elseif(is_array($values)) {
            foreach($values as $column => $val) {
                if (!empty(self::$columns[$column])) {
                    self::$columns[$column]->setFormatter($val);
                }
            }
        }

        return $this;
    }

    public function upsert($values = null, $callback = null)
    {
        if (!empty($values)) {
            if ($values instanceof \Closure) {
                $callback = $values;
            } else {
                $this->values($values);
            }
        }

        $values = $this->values;

        $primary = null;
        $primaries = $this->offsetGet('primary');
        if (1 == count($primaries)) {
            $primary = current($primaries);
            if (empty($values[$primary])) {
                $primary = null;
            }
        }
    }


    // Aliases

    public function raw()
    {
        return $this->getRaw();
    }

    public function name()
    {
        return $this->getName();
    }

    public function fullname()
    {
        return $this->getFullName();
    }

    public function fname()
    {
        return $this->getFullName();
    }

    public function qname()
    {
        return $this->getQueryName();
    }

    public function columns($key = null)
    {
        return $this->getColumns($key);
    }

    public function column($key)
    {
        return $this->getColumns($key);
    }

    public function clear()
    {
        return $this->clearValues();
    }

    public function setValue($key, $value)
    {
        return $this->setValues($key, $value);
    }

    public function getValue($key)
    {
        return $this->getValues($key);
    }

    public function formatters($args)
    {
        return $this->setFormatters($args);
    }

    public function validators($args)
    {
        return $this->setValidators($args);
    }

    public function formatter($key, $value)
    {
        return $this->setFormatters($key, $value);
    }

    public function validator($key, $value)
    {
        return $this->setValidators($key, $value);
    }


    // Helpers

    private function correctCharset($value)
    {
        if (!empty($value)) {
            $value = strtolower($value);
            $charset = substr($value, 0, strpos($value, '_'));
            return $charset;
        }
    }


    // Implements

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

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function __debugInfo()
    {
        return $this->data;
    }
}
