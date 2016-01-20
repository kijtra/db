<?php
namespace Kijtra;

class DB
{
    private $config;
    private $tables = array();

    public function __construct($dsn, $username = null, $password = null, $options = null)
    {
        $this->history = new DB\History();
        $this->config = new DB\Config($dsn, $username, $password);

        $this->db = new DB\Connection(
            $this->config['dsn'],
            $this->config['user'],
            $this->config['pass'],
            $options,
            $this->history
        );

        $this->db->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array(
            __NAMESPACE__.'\\DB\\Statement',
            array($this->history)
        ));
    }

    public function config($key = null)
    {
        if (isset($key)) {
            if (isset($this->config[$key])) {
                return $this->config[$key];
            }
        } else {
            return $this->config->all();
        }
    }

    public function history()
    {
        return $this->history->get();
    }

    public function column($tableName)
    {
        if (!empty($this->tables[$tableName])) {
            return $this->tables[$tableName];
        }

        try {
            return $this->tables[$tableName] = new DB\Column($tableName, $this->db);
        } catch(\PDOException $e) {
            throw $e;
        } catch(\Exception $e) {
            throw $e;
        }
    }

    public function columnInfo($tableName)
    {
        $column = $this->column($tableName);
        return $column->getRaw();
    }

    public function table($tableName)
    {
        $column = $this->column($tableName);
        return $column->getTable();
    }

    public function __call($method, $args)
    {
        $len = count($args);
        if (0 === $len) {
            return $this->db->$method();
        } elseif(1 === $len) {
            return $this->db->$method($args[0]);
        } elseif(2 === $len) {
            return $this->db->$method($args[0], $args[1]);
        } elseif(3 === $len) {
            return $this->db->$method($args[0], $args[1], $args[2]);
        } elseif(4 === $len) {
            return $this->db->$method($args[0], $args[1], $args[2], $args[3]);
        } elseif(5 === $len) {
            return $this->db->$method($args[0], $args[1], $args[2], $args[3], $args[4]);
        } else {
            return call_user_func_array(array($this->db, $method), $args);
        }
    }

    public function __debugInfo()
    {
        return $this->db;
    }
}
