<?php
namespace Kijtra\DB;

use \Kijtra\DB\Constant;

// http://stackoverflow.com/a/7716896/3101326
class Statement extends \PDOStatement implements Constant
{
    private $history;
    private $binds = array();

    protected function __construct($history = null)
    {
        $classHistory = self::CLASS_HISTORY;
        if ($history instanceof $classHistory) {
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

    public function execute($values = array(), $noLog = false)
    {
        $binds = $this->binds;
        $this->binds = array();

        if (!empty($values)) {
            if (!$noLog) {
                $this->history->set($this->queryString, $values);
            }

            return parent::execute($values);
        } else {
            if (!$noLog) {
                $this->history->set($this->queryString, $binds);
            }

            return parent::execute();
        }
    }
}
