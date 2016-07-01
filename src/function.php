<?php
namespace Kijtra;

/**
 * Bonus: singleton function (PHP >= 5.6)
 * ex) use function \Kijtra\DB as db;
 */
function DB()
{
    static $instance = null;
    if (null === $instance) {
        $ref = new \ReflectionClass(__NAMESPACE__.'\\DB');
        $instance = $ref->newInstanceArgs(func_get_args());
    }
    return $instance;
}
