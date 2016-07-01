<?php
namespace Kijtra\DB;

class History implements \IteratorAggregate, \Serializable
{
    /**
     * Container object
     *
     * @access private
     * @var Container
     */
    private $container;

    /**
     * History datas
     * @var array
     */
    private $data = array();

    /**
     * Create new History object
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
     * Add history data
     * @param string $sql   SQL string
     * @param array  $binds binded data
     */
    public function add($sql, $binds = array())
    {
        $max = $this->container->config['history_max'];

        if (!empty($max)) {
            array_unshift($this->data, array(
                'sql' => $sql,
                'bind' => $binds,
            ));

            if (count($this->data) > $max) {
                array_pop($this->data);
            }
        }
    }

    /**
     * Get all history data
     * @return array history datas
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
     * Data serialize
     * @return string serialized data
     */
    public function serialize()
    {
        return serialize($this->data);
    }

    /**
     * Data unserialize (not support)
     * @param array $data
     */
    public function unserialize($data)
    {
    }

    /**
     * For var_dump
     * @return array History datas
     */
    public function __debugInfo()
    {
        return $this->data;
    }
}
