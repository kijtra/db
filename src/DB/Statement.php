<?php
/**
 *  Kijtra/DB
 *
 * @link      https://github.com/kijtra/db
 * @copyright Copyright (c) 22016 Kijtra
 * @license   MIT License (http://opensource.org/licenses/mit-license.php)
 */
namespace Kijtra\DB;

use Kijtra\DB\Container;

/**
 * DB/Statement
 *
 * PDO Statement wrapper. (extended PDOStatement class)
 */
class Statement extends \PDOStatement
{
    /**
     * Container object
     *
     * @access private
     * @var Container
     */
    private $container;

    /**
     * Binded data buffer
     *
     * @access private
     * @var array
     */
    private $binds = array();

    /**
     * Create new PDOStatement object
     *
     * @param @param object $container Container object
     */
    protected function __construct($container = null)
    {
        if (!empty($container) && $container instanceof Container) {
            $this->container = & $container;
        }
    }

    /**
     * PDOStatement bindParam() wrapper
     *
     * Buffering bind datas
     *
     * @param string $param   Parameter identifier
     * @param string $value   Variable to bind to the SQL statement parameter
     * @param string $type    Explicit data type for the parameter using the PDO::PARAM_* constants
     * @param string $length  Length of the data type
     * @param string $options Driver options
     */
    public function bindParam($param, &$value, $type = \PDO::PARAM_STR, $length = null, $options = null)
    {
        $this->binds[] = $value;
        return parent::bindParam($param, $value, $type, $length, $options);
    }

    /**
     * PDOStatement bindValue() wrapper
     *
     * Buffering bind datas
     *
     * @param string $param Parameter identifier
     * @param string $value Variable to bind to the SQL statement parameter
     * @param string $type  Explicit data type for the parameter using the PDO::PARAM_* constants
     */
    public function bindValue($param, $value, $type = \PDO::PARAM_STR)
    {
        $this->binds[] = $value;
        return parent::bindValue($param, $value, $type);
    }

    /**
     * PDOStatement execute() extend history and callback
     *
     * @param  array    $values   Variable to bind to the SQL statement parameter
     * @param  callable $callback callback function binded PDO object
     * @param  bool     $noLog    If true, History not add
     * @return object   PDOStatement object
     */
    public function execute($values = array())
    {
        if (empty($values)) {
            $values = $this->binds;
            $this->binds = array();
        }

        $this->container->history->add($this->queryString, $values);

        try {
            if (!empty($values)) {
                return parent::execute($values);
            } else {
                return parent::execute();
            }
        } catch (\PDOException $e) {
            $this->container->error->add($e);
            if (!$this->container->config['silent']) {
                throw $e;
            } else {
                return $this;
            }
        }
    }
}
