<?php
namespace Kijtra\DB;

abstract class Component
{
    private $table;
    private $formatter;
    private $validator;

    final public static function __callStatic($table, $args)
    {
        if (empty(self::$objects[$table])) {
            $object = new \stdClass();
            $object->table = $table;
            self::$objects[$table] = new \SplObjectStorage($object);
        }
    }

    final public function table()
    {
        return $this->table;
    }

    public static function formatter()
    {
        return $this->formatter;
    }

    final public function validator()
    {
        return $this->validator;
    }
}
