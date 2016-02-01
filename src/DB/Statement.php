<?php
namespace Kijtra\DB;

use \Kijtra\DB\History;

// http://stackoverflow.com/a/7716896/3101326
class Statement extends \PDOStatement
{
    private $history;
    private $binds = array();

    protected function __construct($history = null)
    {
        if ($history instanceof History) {
            $this->history = $history;
        }
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

    public function execute($values = array(), $callback = null, $noLog = false)
    {
        $binds = $this->binds;
        $this->binds = array();

        if (!empty($values)) {
            if (!$noLog) {
                $this->history->set($this->queryString, $values);
            }

            $result =  parent::execute($values);

            if ($callback instanceof \Closure) {
                $callback = $callback->bindTo($result);
                $callback($result);
            }

            return $result;
        } else {
            if (!$noLog) {
                $this->history->set($this->queryString, $binds);
            }

            $result =  parent::execute();

            if ($callback instanceof \Closure) {
                $callback = $callback->bindTo($result);
                $callback($result);
            }

            return $result;
        }
    }
}
