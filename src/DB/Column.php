<?php
namespace Kijtra\DB;

class Column implements \ArrayAccess
{
    private $db;
    private $tableName;
    private $raws;
    private $table;
    private $columns = array();
    private $requires = array();
    private $primaries = array();
    private $indicies = array();

    public function __construct($tableName, Connection $db = null)
    {
        $this->db = $db;
        $this->setInfo($tableName);
    }

    private function setInfo($tableName)
    {
        if (!empty($this->raws)) {
            return;
        } elseif(!is_object($this->db)) {
            throw new \Exception('No database connection.');
        }

        try {
            $sql = "SHOW TABLE STATUS LIKE ?;";
            $stmt = $this->db->prepare($sql);
            if ($stmt->execute(array($tableName), true)) {
                $table = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (empty($table)) {
                    throw new \Exception(sprintf('Table "%s" not found.', $tableName));
                }

                $sql = "SHOW FULL COLUMNS FROM `{$table['Name']}`;";
                if ($query = $this->db->query($sql, true)) {
                    $columns = array();
                    while($val = $query->fetch(\PDO::FETCH_ASSOC)) {
                        $columns[] = $val;
                    }

                    if (empty($columns)) {
                        throw new \Exception(sprintf('Table "%s" has no columns.', $tableName));
                    }

                    if (
                        !empty($table['Comment']) &&
                        !empty($table['Collation']) &&
                        ($charset = $this->formatCharset($table['Collation']))
                    ) {
                        $table['Comment'] = mb_convert_encoding($table['Comment'], $charset, 'auto');
                    }

                    $this->table = $table;
                    $this->raws = $columns;
                    $this->columns = $this->getColumn();
                }
            }
        } catch(\PDOException $e) {
            throw $e;
        }
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getRequire()
    {
        return $this->requires;
    }

    public function getPrimary()
    {
        return $this->primaries;
    }

    public function getIndex()
    {
        return $this->indicies;
    }

    public function getRaw()
    {
        return $this->raws;
    }

    public function getColumn()
    {
        if (!empty($this->columns)) {
            return $this->columns;
        }

        $datas = array();
        foreach($this->raws as $column) {
            $data = array(
                'name' => $column['Field'],
                'comment' => (!empty($column['Comment']) ? $column['Comment'] : null),
                'type' => $this->formatType($column['Type']),
                'length' => $this->formatLength($column['Type']),
                'unsigned' => $this->formatUnsigned($column['Type']),
                'default' => $this->formatDefault($column['Default'], $column['Extra']),
                'charset' => $this->formatCharset($column['Collation']),
                'require' => $this->formatRequire($column['Null']),
                'primary' => $this->formatPrimary($column['Key']),
                'index' => $this->formatIndex($column['Key']),
                'auto_increment' => $this->formatAutoIncrement($column['Extra']),
            );

            if (!empty($data['comment']) && !empty($data['charset'])) {
                $data['comment'] = mb_convert_encoding($data['comment'], $data['charset'], 'auto');
            }

            if ($data['require']) {
                $this->requires[] = $column['Field'];
            }

            if ($data['primary']) {
                $this->primaries[] = $column['Field'];
            }

            if ($data['index']) {
                $this->indicies[] = $column['Field'];
            }

            $datas[$column['Field']] = $data;
        }

        return $datas;
    }


    private function formatType($value)
    {
        $value = strtolower($value);

        if (
            (0 === strpos($value, 'tinyint') && false !== strpos($value, '(1)')) ||
            0 === strpos($value, 'bool')
        ) {
            return 'bool';
        } elseif (
            false !== strpos($value, 'int') ||
            false !== strpos($value, 'bit') ||
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

    private function formatCharset($value)
    {
        if (!empty($value)) {
            $value = strtolower($value);
            return substr($value, 0, strpos($value, '_'));
        } elseif (!empty($this->table['Collation'])) {
            $value = strtolower($this->table['Collation']);
            return substr($value, 0, strpos($value, '_'));
        }
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


    public function offsetSet($offset, $value)
    {
        if (array_key_exists($offset, $this->table)) {
            $this->columns[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->columns[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->columns[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->columns[$offset]) ? $this->columns[$offset] : null;
    }

    public function __debugInfo()
    {
        return $this->getColumn();
    }
}
