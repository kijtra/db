<?php
namespace Kijtra\DB;

class Connection extends \PDO
{
    private $config = array(
        'dsn' => null,
        'scheme' => null,
        'host' => null,
        'user' => null,
        'pass' => null,
        'name' => null,
        'port' => null,
        'path' => null,
        'charset' => null
    );

    private $options = array(
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    );

    private $isConstruct = false;

    private $history;

    public function __construct($dsn, $user = null, $pass = null, $options = null, History $history = null)
    {
        $this->history = $history;
        $this->isConstruct = true;

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
