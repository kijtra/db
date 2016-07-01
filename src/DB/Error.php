<?php
namespace Kijtra\DB;

class Error implements \IteratorAggregate
{
    /**
     * Container object
     *
     * @access private
     * @var Container
     */
    private $container;

    /**
     * Error datas (PDOException objects)
     * @var array
     */
    private $data = array();

    /**
     * Create new Error object
     *
     * @param object $container Container object
     */
    public function __construct($container = null)
    {
        if (!empty($container) && $container instanceof Container) {
            $this->container = & $container;
        }
    }

    /**
     * Add Error data
     * @param object $e Exception object
     */
    public function add($e)
    {
        if (!$e instanceof \PDOException || !$e instanceof \Exception) {
            return;
        }

        $max = $this->container->config['error_max'];

        if (!empty($max)) {
            array_unshift($this->data, $e);

            if (count($this->data) > $max) {
                array_pop($this->data);
            }
        }
    }

    /**
     * Get all error data
     * @return array Error datas (PDOException objects)
     */
    public function get()
    {
        return $this->data;
    }

    /**
     * IteratorAggregate::getIterator
     * @return object ArrayIterator object
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * For var_dump
     * @return array Error datas
     */
    public function __debugInfo()
    {
        return $this->data;
    }
}
