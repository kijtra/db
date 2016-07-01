<?php
/**
 *  Kijtra/DB
 *
 * @link      https://github.com/kijtra/db
 * @copyright Copyright (c) 22016 Kijtra
 * @license   MIT License (http://opensource.org/licenses/mit-license.php)
 */
namespace Kijtra;

use Kijtra\DB\Config;
use Kijtra\DB\Container;

/**
 * DB
 *
 * This is the primary class with which you instantiate,
 * configure, and run a Kijtra/DB application.
 */
class DB
{
    private static $storage;
    private $pdo;

    /**
     * Create DB instance
     * @param mixed Config params
     */
    public function __construct()
    {
        if (null === self::$storage) {
            self::$storage = new \SplObjectStorage();
        }

        $args = func_get_args();
        $len = count($args);

        if (1 === $len && $args[0] instanceof \PDO) {
            $this->pdo = $args[0];
            $database = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
            $process = $this->pdo->query('SHOW PROCESSLIST;')->fetch(\PDO::FETCH_ASSOC);
            $config = new Config(array(
                'host' => substr($process['Host'], 0, strpos($process['Host'], ':')),
                'database' => $database,
                'username' => $process['User'],
            ));
            $container = new Container($this, $config);
            self::$storage[$this->pdo] = $container;
            $this->pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array(
                __NAMESPACE__.'\\DB\\Statement',
                array($container),
            ));
        } else {
            $config = new Config();
            if (1 === $len) {
                $config->set($args[0]);
            } elseif (2 === $len) {
                $config->set($args[0], $args[1]);
            } elseif (3 === $len) {
                $config->set($args[0], $args[1], $args[3]);
            } else {
                call_user_func_array(array($config, 'set'), $args);
            }

            if ($pdo = $this->db($config->dsn, $config->username, $config->password, $config->options)) {
                $container = new Container($this, $config);
                self::$storage[$pdo] = $container;
                $pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array(
                    __NAMESPACE__.'\\DB\\Statement',
                    array($container),
                ));
            };
        }
    }

    /**
     * Get PDO instance
     * @param  string $dsn     Data Source Name
     * @param  string $user    Username
     * @param  string $pass    Password
     * @param  array  $options PDO Options
     * @return object PDO instance
     */
    public function db($dsn = null, $user = null, $pass = null, $options = array())
    {
        if (null === $this->pdo) {
            try {
                $this->pdo = new \PDO($dsn, $user, $pass, $options);
            } catch (\PDOException $e) {
                throw $e;
            }
        }

        return $this->pdo;
    }

    /**
     * Get Query Histories
     * @return array History array
     */
    public function history()
    {
        if (!empty($this->pdo)) {
            return self::$storage[$this->pdo]->history->get();
        }
    }

    /**
     * Get Errors
     * @return array Error array
     */
    public function error()
    {
        if (!empty($this->pdo)) {
            return self::$storage[$this->pdo]->error->get();
        }
    }

    /**
     * Call PDO methods
     * @param  string $method Method Name
     * @param  array  $args   Method arguments
     * @return mixed  PDO result
     */
    public function __call($method, $args)
    {
        if (!$pdo = $this->db()) {
            throw new \Exception('PDO Not initialized.');
        }

        $container = self::$storage[$pdo];

        if (!method_exists($pdo, $method)) {
            $exception = new \Exception(sprintf('Method "%s" not defined at PDO.', $method));
            $container->error->add($exception);
            if (!$container->config['silent']) {
                throw $exception;
            } else {
                return $this;
            }
        }

        try {
            if (empty($args)) {
                return $pdo->$method();
            } else {
                $len = count($args);

                if ('exec' === $method || 'query' === $method) {
                    call_user_func_array(array($container->history, 'add'), $args);
                }

                if (1 === $len) {
                    return $pdo->$method($args[0]);
                } elseif (2 === $len) {
                    return $pdo->$method($args[0], $args[1]);
                } elseif (3 === $len) {
                    return $pdo->$method($args[0], $args[1], $args[3]);
                } else {
                    return call_user_func_array(array($pdo, $method), $args);
                }
            }
        } catch (\PDOException $e) {
            $container->error->add($e);
            if (!$container->config['silent']) {
                throw $e;
            } else {
                return $this;
            }
        }
    }
}
