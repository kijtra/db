<?php
namespace Kijtra;

// http://stackoverflow.com/a/7716896/3101326
class Statement extends \PDOStatement
{
    public static $history_max = 10;
    private static $history = array();

    protected function __construct()
    { /* need this empty construct */ }

    public static function addHistory($sql, $binds = null)
    {
        self::$history[] = array(
            'sql' => $sql,
            'binds' => $binds
        );

        if (count(self::$history) > self::$history_max) {
            array_shift(self::$history);
        }
    }

    public static function getHistory()
    {
        return self::$history;
    }

    public function execute($values=array())
    {
        self::addHistory($this->queryString, $values);

        try {
            return parent::execute($values);
        } catch (\PDOException $e) {
            throw $e;
        }
    }
}
