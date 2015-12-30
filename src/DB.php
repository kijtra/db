<?php
namespace Kijtra;

use Kijtra\Statement;

class DB
{
    private static $config = array(
        'dsn' => null,
        'scheme' => null,
        'host' => null,
        'user' => null,
        'pass' => null,
        'name' => null,
        'port' => null,
        'path' => null,
        'char' => null
    );

    private static $options = array(
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    );

    private static $instance = null;
    private $statement_class = array(__NAMESPACE__.'\\Statement', array());

    public function __construct($dsn = null, $user = null, $pass = null, $options = null)
    {
        if (!empty($dsn)) {
            self::$instance = null;

            if (is_string($dsn)) {
                $this->parseDSN($dsn);
            } elseif(is_array($dsn)) {
                $this->parseConfig($dsn);
            }

            if (empty(self::$config['dsn'])) {
                $dsn = self::$config['scheme'].':';
                $dsn .= 'host='.self::$config['host'].';';
                $dsn .= 'dbname='.self::$config['name'].';';
                if (!empty(self::$config['port'])) {
                    $dsn .= 'port='.self::$config['port'].';';
                }
                if (!empty(self::$config['char'])) {
                    $dsn .= 'charset='.self::$config['char'].';';
                }

                self::$config['dsn'] = $dsn;
            }

            if (is_string($user)) {
                self::$config['user'] = $user;
            }

            if (is_string($pass)) {
                self::$config['pass'] = $pass;
            }

            if (is_array($options)) {
                self::$options = $options;
            }

            if (empty(self::$options[\PDO::ATTR_STATEMENT_CLASS])) {
                self::$options[\PDO::ATTR_STATEMENT_CLASS] = $this->statement_class;
            }
        }

        self::getInstance();
    }

    private function parseDSN($dsn)
    {
        // URI style
        if (strpos($dsn, '://')) {
            $uri = parse_url($dsn);
            if (!empty($uri['scheme'])) {
                self::$config['scheme'] = $uri['scheme'];
            }
            if (!empty($uri['host'])) {
                self::$config['host'] = $uri['host'];
            }
            if (!empty($uri['port'])) {
                self::$config['port'] = $uri['port'];
            }
            if (!empty($uri['user'])) {
                self::$config['user'] = $uri['user'];
            }
            if (!empty($uri['pass'])) {
                self::$config['pass'] = $uri['pass'];
            }
            if (!empty($uri['query'])) {
                parse_str($uri['query'], $query);
                if (!empty($query['charset'])) {
                    self::$config['char'] = $query['charset'];
                }
            }
        } else {
            self::$config['dsn'] = $dsn;
            $scheme = strtolower(substr($dsn, 0, strpos($dsn, ':')));
            self::$config['scheme'] = $scheme;

            if (preg_match_all('/(\w+)=(\w+)/', $dsn, $m)) {
                foreach($m[1] as $key => $name) {
                    $value = $m[2][$key];
                    if ('host' == $name) {
                        self::$config['host'] = $value;
                    } elseif ('dbname' == $name) {
                        self::$config['name'] = $value;
                    } elseif ('charset' == $name) {
                        self::$config['char'] = $value;
                    } elseif ('port' == $name) {
                        self::$config['port'] = (int)$value;
                    }
                }
            }
        }

        if ('mysql' == self::$config['scheme'] || 'pgsql' == self::$config['scheme']) {
            if (empty(self::$config['host'])) {
                self::$config['host'] = 'localhost';
            }

            if (empty(self::$config['char'])) {
                self::$config['char'] = 'utf8';
            }

            if (empty(self::$config['port'])) {
                self::$config['port'] = ('mysql' == self::$config['scheme'] ? 3306 : 5432);
            }
        }
    }

    private function parseConfig($config)
    {
        foreach($config as $key => $val) {
            if (!is_string($val) && !is_int($val)) {
                continue;
            }

            if (array_key_exists($key, self::$config)) {
                self::$config[$key] = $val;
            } elseif('hostname' == $key || 'host_name' == $key) {
                self::$config['host'] = $config[$key];
            } elseif('database' == $key || 'dbname' == $key || 'db_name' == $key) {
                self::$config['name'] = $config[$key];
            } elseif('username' == $key || 'user_name' == $key) {
                self::$config['user'] = $config[$key];
            } elseif('password' == $key) {
                self::$config['pass'] = $config[$key];
            }
        }
    }

    public function getConfig()
    {
        return self::$config;
    }

    public function getOptions()
    {
        return self::$options;
    }

    public function setOptions($options)
    {
        self::$options = $options;
        if (null !== self::$instance) {
            if (empty(self::$options[\PDO::ATTR_STATEMENT_CLASS])) {
                self::$options[\PDO::ATTR_STATEMENT_CLASS] = $this->statement_class;
            }

            foreach(self::$options as $key => $val) {
                self::$instance->setAttribute($key, $val);
            }
        }
        return $this;
    }

    public function setHistoryMax($int)
    {
        if (ctype_digit(strval($int))) {
            Statement::$history_max = min((int)$int, 100);
        }
    }

    public static function getInstance()
    {
        if (null === self::$instance) {
            if (empty(self::$options[\PDO::ATTR_STATEMENT_CLASS])) {
                self::$options[\PDO::ATTR_STATEMENT_CLASS] = $this->statement_class;
            }

            try {
                $conn = new \PDO(
                    self::$config['dsn'],
                    self::$config['user'],
                    self::$config['pass'],
                    self::$options
                );
                if (is_object($conn)) {
                    self::$instance = $conn;
                }
            } catch(\PDOException $e) {
                throw new \PDOException($e);
            }
        }

        return self::$instance;
    }

    public function getHistory()
    {
        return Statement::getHistory();
    }

    public function __call($method, $args = array())
    {
        if (!self::$instance) {
            return false;
        }

        if (('query' === $method || 'exec' === $method) && !empty($args[0])) {
            Statement::addHistory($args[0]);
        }

        $len = count($args);
        if (0 === $len) {
            return self::$instance->$method();
        } elseif(1 === $len) {
            return self::$instance->$method($args[0]);
        } elseif(2 === $len) {
            return self::$instance->$method($args[0], $args[1]);
        } elseif(3 === $len) {
            return self::$instance->$method($args[0], $args[1], $args[2]);
        } elseif(4 === $len) {
            return self::$instance->$method($args[0], $args[1], $args[2], $args[3]);
        } else {
            return call_user_func_array(array(self::$instance, $method), $args);
        }
    }
}
