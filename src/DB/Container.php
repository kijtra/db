<?php
namespace Kijtra\DB;

use Kijtra\DB;
use Kijtra\DB\Config;
use Kijtra\DB\Error;
use Kijtra\DB\History;

class Container
{
    public $db;
    public $config;
    public $history;
    public $error;

    public function __construct(DB $db, Config $config)
    {
        $this->db = $db;
        $this->config = $config;
        $this->history = new History($this);
        $this->error = new Error($this);
    }
}
