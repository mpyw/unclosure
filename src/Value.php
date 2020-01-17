<?php

namespace Mpyw\Unclosure;

use Closure;

class Value
{
    /**
     * Handle effect with callback.
     *
     * @param  \Closure|mixed &$value
     * @param  callable       $configurator
     * @param  callable       $callback
     * @param  mixed          ...$args
     * @return mixed
     */
    public static function withEffect(&$value, callable $configurator, callable $callback, ...$args)
    {
        $original = $value;
        $disposer = null;

        try {
            $value = static::withCallback($value, function ($value) use ($configurator, &$disposer) {
                $disposer = $configurator($value);
                return $value;
            });

            return $callback(...$args);
        } finally {
            $effected = $value;

            if ($effected instanceof Closure) {
                $value = $original;
            } elseif (is_callable($disposer)) {
                $disposer($effected);
            }
        }
    }

    /**
     * Handle effect with callback for each value.
     *
     * @param  \Closure[]|mixed[] &$values
     * @param  callable           $configurator
     * @param  callable           $callback
     * @param  mixed              ...$args
     * @return mixed
     */
    public static function withEffectForEach(array &$values, callable $configurator, callable $callback, ...$args)
    {
        $current = function () use ($callback, $args) {
            return $callback(...$args);
        };

        foreach ($values as &$value) {
            $current = function () use (&$value, $current, $configurator) {
                return static::withEffect($value, $configurator, $current);
            };
        }
        unset($value);

        return $current();
    }

    /**
     * Decorate value with callback.
     *
     * @param  \Closure|mixed $value
     * @param  callable       $callback
     * @param  mixed          ...$args
     * @return \Closure|mixed
     */
    public static function withCallback($value, callable $callback, ...$args)
    {
        return $value instanceof Closure
            ? function (...$initialArgs) use ($value, $callback, $args) {
                return $callback($value(...$initialArgs), ...$args);
            }
        : $callback($value, ...$args);
    }
}
