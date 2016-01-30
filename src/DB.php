<?php
namespace Kijtra;

use \Kijtra\DB\Constant;
use \Kijtra\DB\History;
use \Kijtra\DB\Config;
use \Kijtra\DB\Connection;
use \Kijtra\DB\Container\Table;

class DB implements Constant
{
    private $history;
    private $config;
    private $conn;
    private $tables = array();

    private static $singleton;

    public function __construct($dsn, $username = null, $password = null, $options = null)
    {
        $this->history = new History();
        $this->config = new Config($dsn, $username, $password);
        $this->{self::PROP_CONN} = new Connection(
            $this->config['dsn'],
            $this->config['user'],
            $this->config['pass'],
            $options,
            $this->history
        );

        $this->{self::PROP_CONN}->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array(
            self::CLASS_STATEMENT,
            array($this->history)
        ));
    }

    public function config($key = null)
    {
        if (isset($key)) {
            if ($this->config->offsetExists($key)) {
                return $this->config->offsetGet($key);
            }
        } else {
            return $this->config->all();
        }
    }

    public function history()
    {
        return $this->history->get();
    }

    public function table($name)
    {
        if (!empty($this->tables[$name])) {
            return $this->tables[$name];
        }

        $reflection = new \ReflectionClass(self::CLASS_TABLE);
        $instance = $reflection->newInstanceWithoutConstructor();
        $instance->{self::PROP_CONN} = $this->{self::PROP_CONN};
        $instance->__construct($name);
        return $this->tables[$name] = $instance;
    }

    public function columns($name)
    {
        return $this->table($name)->columns();
    }

    public static function single()
    {
        if (null === self::$singleton) {
            $ref = new \ReflectionClass(__CLASS__);
            self::$singleton = $ref->newInstanceArgs(func_get_args());
        } elseif(1 === func_num_args()) {
            $args = func_get_args();
            if (is_string($args[0])) {
                self::$singleton->exec("USE `{$args[0]}`;");
            }
        }

        return self::$singleton;
    }


    // public function __get($tableName)
    // {
    //     return $this->table($tableName);
    // }

    public function __call($method, $args)
    {
        $len = count($args);
        if (0 === $len) {
            return $this->conn->$method();
        } elseif(1 === $len) {
            return $this->conn->$method($args[0]);
        } elseif(2 === $len) {
            return $this->conn->$method($args[0], $args[1]);
        } elseif(3 === $len) {
            return $this->conn->$method($args[0], $args[1], $args[2]);
        } elseif(4 === $len) {
            return $this->conn->$method($args[0], $args[1], $args[2], $args[3]);
        } elseif(5 === $len) {
            return $this->conn->$method($args[0], $args[1], $args[2], $args[3], $args[4]);
        } else {
            return call_user_func_array(array($this->conn, $method), $args);
        }
    }

    // public function __debugInfo()
    // {
    //     return $this->conn;
    // }
}
