<?php
namespace Kijtra\DB;

// http://stackoverflow.com/a/7716896/3101326
class Statement extends \PDOStatement
{
    private $history;
    private $binds = array();

    protected function __construct(History $history = null)
    {
        $this->history = $history;
    }

    public function bindParam($param, &$value, $type = \PDO::PARAM_STR, $length = null, $options = null)
    {
        $this->binds[] = $value;
        parent::bindParam($param, $value, $type, $length, $options);
    }

    public function bindValue($param, $value, $type = \PDO::PARAM_STR)
    {
        $this->binds[] = $value;
        return parent::bindValue($param, $value, $type);
    }

    public function execute($values = array())
    {
        $binds = $this->binds;
        $this->binds = array();

        if (!empty($values)) {
            $this->history->set($this->queryString, $values);
            return parent::execute($values);
        } else {
            $this->history->set($this->queryString, $binds);
            return parent::execute();
        }
    }
}
