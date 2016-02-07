<?php
/**
 *  Kijtra/DB
 *
 * @link      https://github.com/kijtra/db
 * @copyright Copyright (c) 22016 Kijtra
 * @license   MIT License (http://opensource.org/licenses/mit-license.php)
 */
namespace Kijtra;

/**
 * DB
 *
 * This is the primary class with which you instantiate,
 * configure, and run a Kijtra/DB application.
 *
 * Usage same of PDO constructor (http://php.net/manual/ja/pdo.construct.php)
 */
class DB
{
    /**
     * History object
     *
     * @access private
     * @var History
     */
    private $history;

    /**
     * Config object
     *
     * @access private
     * @var Config
     */
    private $config;

    /**
     * PDO connection object
     *
     * @access private
     * @var object
     */
    private $conn;

    /**
     * Loaded table objects
     *
     * @access private
     * @var array
     */
    private static $tables = array();

    /**
     * Singleton object optional use
     *
     * @access private
     * @var object
     */
    private static $singleton;

    /**
     * Create new object
     *
     * @param string|array  $dsn  Data Source Name or database config array
     * @param string  $username  Database Username
     * @param string  $password  Database Password
     * @param string  $options  PDO driver option
     * @throws PDOException when Database connect failed
     */
    public function __construct($dsn, $username = null, $password = null, $options = null)
    {
        $this->history = new \Kijtra\DB\History();
        $this->config = new \Kijtra\DB\Config($dsn, $username, $password);
        $this->conn = new \Kijtra\DB\Connection(
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

    /**
     * Config getter
     *
     * @param string  $key  Config array key
     * @return mixed  If pass $key return one, else all config data
     */
    public function getConfig($key = null)
    {
        if (isset($key)) {
            if ($this->config->offsetExists($key)) {
                return $this->config->offsetGet($key);
            }
        } else {
            return $this->config->all();
        }
    }

    /**
     * Get Query history
     *
     * @return array  SQL sentense and Binded data in array
     */
    public function getHistory()
    {
        return $this->history->get();
    }

    /**
     * Get Database Table info object
     *
     * @param string  $name  Table Name
     * @return object  Table data object
     */
    public function getTable($name)
    {
        $dbname = $this->config['name'];
        if (!empty(self::$tables[$name])) {
            return self::$tables[$name];
        } elseif (!empty(self::$tables[$dbname.$name])) {
            return self::$tables[$dbname.$name];
        }

        $table = new \Kijtra\DB\Table($name, $this->conn);
        $name = $table->name();
        return self::$tables[$dbname.$name] = $table;
    }

    /**
     * Get Table's Columns info object (shortcut)
     *
     * @param string  $name  Table Name
     * @return array  Table's Columns info array
     */
    public function getColumns($name)
    {
        return $this->table($name)->columns();
    }

    /**
     * DB/Flow runner
     *
     * Starting Flow object
     *
     * @param string  $sql  SQL sentence
     * @return object  DB/Flow object
     */
    public function sql($sql)
    {
        $flow = new \Kijtra\DB\Flow($this->conn);
        return $flow->sql($sql);
    }

    /**
     * Singleton method
     *
     * Use singleton object at self
     *
     * @see __construct
     */
    public static function singleton()
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


    /********************************************************************************
     * Aliases
     *******************************************************************************/

    /**
     * @see getConfig
     */
    public function config($key = null)
    {
        return $this->getConfig($key);
    }

    /**
     * @see getHistory
     */
    public function history()
    {
        return $this->getHistory();
    }

    /**
     * @see getTable
     */
    public function table($name)
    {
        return $this->getTable($name);
    }

    /**
     * @see getColumns
     */
    public function columns($name)
    {
        return $this->getColumns($name);
    }


    /********************************************************************************
     * Overloads
     *******************************************************************************/

    /**
     * Pass method to PDO object
     *
     * @param string  $method  PDO method name
     * @param array  $args  PDO method arguments
     * @return object  PDO method results
     */
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

    /**
     * var_dump checker for Developer
     *
     * @return object  PDO object
     */
    public function __debugInfo()
    {
        return $this->conn;
    }
}
