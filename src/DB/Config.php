<?php
/**
 *  Kijtra/DB
 *
 * @link      https://github.com/kijtra/db
 * @copyright Copyright (c) 22016 Kijtra
 * @license   MIT License (http://opensource.org/licenses/mit-license.php)
 */
namespace Kijtra\DB;

/**
 * Config
 *
 * Database config object
 */
class Config implements \IteratorAggregate, \ArrayAccess, \Serializable
{
    /**
     * Config datas
     * @var array
     */
    private $data = array(
        'host' => 'localhost',
        'username' => null,
        'password' => null,
        'database' => null,
        'dsn' => null,
        'driver'    => 'mysql',
        'port'    => '3306',
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'options' => array(
            \PDO::ATTR_TIMEOUT => 10,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ),
        'history_max' => 100,
        'error_max' => 100,
        'silent' => false,
    );

    /**
     * Create Config instance
     * @param mixed Config params
     */
    public function __construct()
    {
        if (func_num_args()) {
            call_user_func_array(array($this, 'set'), func_get_args());
        }
    }

    /**
     * Fix and Set Config data
     * @param mixed any params
     */
    public function set()
    {
        $args = func_get_args();
        $num = func_num_args();

        // Find options
        if ($num > 1) {
            foreach ($args as $key => $val) {
                if (is_array($val)) {
                    $this->offsetSet('options', $val);
                    unset($args[$key]);
                    $args = array_values($args);
                    $num -= 1;
                    break;
                }
            }
        }

        if (1 === $num) {
            // Single array
            if (is_array($args[0])) {
                foreach ($args[0] as $key => $val) {
                    $this->offsetSet($key, $val);
                }
            }

            // Doctrine like URL
            elseif (is_string($args[0])) {
                $uri = parse_url($args[0]);

                if (!empty($uri['scheme'])) {
                    $this->offsetSet('driver', $uri['scheme']);
                }

                if (!empty($uri['host'])) {
                    $this->offsetSet('driver', $uri['host']);
                }

                if (!empty($uri['port'])) {
                    $this->offsetSet('port', $uri['port']);
                }

                if (!empty($uri['user'])) {
                    $this->offsetSet('username', $uri['user']);
                }

                if (!empty($uri['pass'])) {
                    $this->offsetSet('password', $uri['pass']);
                }

                if (!empty($uri['path'])) {
                    $this->offsetSet('database', dirname(trim($uri['path'], '/')));
                }

                if (!empty($uri['query'])) {
                    parse_str($uri['query'], $query);
                    foreach ($query as $key => $val) {
                        $this->offsetSet($key, $val);
                    }
                }
            }
        }

        // dsn, username, password
        elseif (3 === $num) {
            if (!preg_match('/^\w{2,}:.*/i', $args[0])) {
                throw new \InvalidArgumentException('Invalid DSN argument');
            }

            $this->offsetSet('dsn', $args[0]);
            $this->offsetSet('username', $args[1]);
            $this->offsetSet('password', $args[2]);
        }

        // host, database, username, password
        elseif (4 === $num) {
            $this->offsetSet('host', $args[0]);
            $this->offsetSet('database', $args[1]);
            $this->offsetSet('username', $args[2]);
            $this->offsetSet('password', $args[3]);
        }

        if (!$this->offsetGet('dsn')) {
            $dsn = null;
            if ($val = $this->offsetGet('driver')) {
                $dsn .= $val.':';

                if ($val = $this->offsetGet('host')) {
                    $dsn .= 'host='.$val;

                    if ($val = $this->offsetGet('port')) {
                        $dsn .= ';port='.$val;
                    }

                    if ($val = $this->offsetGet('database')) {
                        $dsn .= ';dbname='.$val;
                    }

                    if ($val = $this->offsetGet('charset')) {
                        $dsn .= ';charset='.strtoupper($val);
                    }

                    if ($val = $this->offsetGet('charset')) {
                        $dsn .= ';charset='.strtoupper($val);
                    }

                    $this->offsetSet('dsn', $dsn);
                }
            }
        }
    }

    /**
     * Fix Config key name
     * @param  string $name Key name
     * @return string Fixed Key name
     */
    private function fixName($name)
    {
        $name = strtolower($name);

        if ('hostname' === $name || 'h' === $name) {
            $name = 'host';
        } elseif ('user' === $name || 'usr' === $name || 'u' === $name) {
            $name = 'username';
        } elseif ('pass' === $name || 'pw' === $name || 'p' === $name) {
            $name = 'password';
        } elseif ('db' === $name || 'name' === $name || 'dbname' === $name || 'db_name' === $name || 'd' === $name) {
            $name = 'database';
        } elseif ('datasource' === $name || 'datasrc' === $name || 'ds' === $name) {
            $name = 'dsn';
        } elseif ('char' === $name || 'character' === $name || 'characterset' === $name) {
            $name = 'charset';
        } elseif ('collate' === $name) {
            $name = 'collation';
        } elseif ('opt' === $name || 'opts' === $name || 'option' === $name) {
            $name = 'options';
        } elseif ('history' === $name || 'histories' === $name || 'historymax' === $name) {
            $name = 'history_max';
        } elseif ('error' === $name || 'errors' === $name || 'errormax' === $name) {
            $name = 'error_max';
        } elseif ('is_silent' === $name || 'error_throw' === $name) {
            $name = 'silent';
        }

        return $name;
    }


    /**
     * overrides
     */

    public function __get($offset)
    {
        return $this->offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        $offset = $this->fixName($offset);
        if (array_key_exists($offset, $this->data)) {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        $offset = $this->fixName($offset);
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset)
    {
        $offset = $this->fixName($offset);
        unset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        $offset = $this->fixName($offset);
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function serialize()
    {
        return serialize($this->data);
    }

    public function unserialize($data)
    {
        $this->set(unserialize($data));
    }

    public function __debugInfo()
    {
        return $this->data;
    }
}
