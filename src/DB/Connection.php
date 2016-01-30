<?php
namespace Kijtra\DB;

use \Kijtra\DB\History;

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
        $history = null
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

    public function exec($query, $noLog = false)
    {
        if (!$noLog && isset($this->history)) {
            $this->history->set($query);
        }

        return parent::exec($query);
    }

    public function query($query, $noLog = false)
    {
        if (!$noLog && isset($this->history)) {
            $this->history->set($query);
        }

        return parent::query($query);
    }
}
