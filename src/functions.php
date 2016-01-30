<?php
namespace Kijtra;

if (!function_exists('\\Kijtra\\db')) {
    function db() {
        static $instance = null;

        if (null === $instance) {
            $ref = new \ReflectionClass(__NAMESPACE__.'\\DB');
            $instance = $ref->newInstanceArgs(func_get_args());
        } elseif(1 === func_num_args()) {
            $args = func_get_args();
            if (is_string($args[0])) {
                $instance->exec("USE `{$args[0]}`;");
            }
        }

        return $instance;
    }
}
