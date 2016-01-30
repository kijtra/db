<?php
namespace Kijtra;

use \Kijtra\DB\History;
use \Kijtra\DB\Config;
use \Kijtra\DB\Connection;
use \Kijtra\DB\Table;

class DB
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
        $this->conn = new Connection(
            $this->config['dsn'],
            $this->config['user'],
            $this->config['pass'],
            $options,
            $this->config,
            $this->history
        );

        $this->conn->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array(
            __NAMESPACE__.'\\DB\\Statement',
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
        $dbname = $this->config['name'];
        if (!empty($this->tables[$name])) {
            return $this->tables[$name];
        } elseif (!empty($this->tables[$dbname.$name])) {
            return $this->tables[$dbname.$name];
        }

        $table = new Table($name, $this->conn);
        $name = $table->name();
        return $this->tables[$dbname.$name] = $table;
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

    public function __debugInfo()
    {
        return $this->conn;
    }
}
