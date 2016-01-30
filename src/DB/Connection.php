<?php
namespace Kijtra\DB;

use \Kijtra\DB\Constant;

class Connection extends \PDO implements Constant
{
    private $options = array(
        \PDO::ATTR_TIMEOUT => 10,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    );

    private $history;

    public function __construct($dsn, $user = null, $pass = null, $options = null, $history = null)
    {
        if (empty($options) || !is_array($options)) {
            $options = $this->options;
        }

        $classHistory = self::CLASS_HISTORY;
        if ($history instanceof $classHistory) {
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
