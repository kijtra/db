<?php
namespace Kijtra\DB;

use \Kijtra\DB\History;
use \Kijtra\DB\Event;

class Connection extends \PDO
{
    private $options = array(
        \PDO::ATTR_TIMEOUT => 10,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    );

    public $config;
    private $history;

    public function __construct(
        $dsn,
        $user = null,
        $pass = null,
        $options = null,
        $config = null,
        $history = null,
        $event = null
    )
    {
        if (empty($options) || !is_array($options)) {
            $options = $this->options;
        }

        $this->config = $config;

        if ($history instanceof History) {
            $this->history = $history;
        }

        parent::__construct($dsn, $user, $pass, $options);
    }

    public function exec($query, $callback = null, $noLog = false)
    {
        if (!$noLog && isset($this->history)) {
            $this->history->set($query);
        }

        $result =  parent::exec($query);

        if ($callback instanceof \Closure) {
            $callback = $callback->bindTo($result);
            $callback($result);
        }

        return $result;
    }

    public function query($query, $callback = null, $noLog = false)
    {
        if (!$noLog && isset($this->history)) {
            $this->history->set($query);
        }

        $result =  parent::query($query);

        if ($callback instanceof \Closure) {
            $callback = $callback->bindTo($result);
            $callback($result);
        }

        return $result;
    }
}
