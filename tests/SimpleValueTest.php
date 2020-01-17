<?php

namespace Mpyw\Unclosure\Tests;

use Closure;
use Mpyw\Unclosure\Value;
use PHPUnit\Framework\TestCase;
use stdClass;

class SimpleValueTest extends TestCase
{
    public function testEagerCallback(): void
    {
        $value = 1;

        $value = Value::withCallback($value, function ($value, $plus) {
            return $value + $plus;
        }, 10);

        $value = Value::withCallback($value, function ($value, $mul) {
            return $value * $mul;
        }, 5);

        $this->assertIsInt($value);
        $this->assertSame(55, $value);
    }

    public function testLazyCallback(): void
    {
        $value = function (int $initialValue) {
            return $initialValue;
        };

        $value = Value::withCallback($value, function ($value, $plus) {
            return $value + $plus;
        }, 10);

        $value = Value::withCallback($value, function ($value, $mul) {
            return $value * $mul;
        }, 5);

        $this->assertInstanceOf(Closure::class, $value);
        $this->assertSame(55, $value(1));
    }

    public function testEagerEffect(): void
    {
        $value = (object)['value' => 0];

        $result = Value::withEffect(
            $value,
            function ($value) {
                ++$value->value;
            },
            function ($arg) {
                return $arg;
            },
            123
        );

        $this->assertSame(123, $result);
        $this->assertSame(1, $value->value);
    }

    public function testEagerEffectWithDisposer(): void
    {
        $value = (object)['value' => 0];

        $result = Value::withEffect(
            $value,
            function ($value) {
                ++$value->value;

                return function ($value) {
                    ++$value->value;
                };
            },
            function ($arg) {
                return $arg;
            },
            123
        );

        $this->assertSame(123, $result);
        $this->assertInstanceOf(stdClass::class, $value);
        $this->assertSame(2, $value->value);
    }

    public function testLazyEffect(): void
    {
        /* @var \Closure|\stdClass $value */
        $value = function (int $initialValue) {
            return (object)['value' => $initialValue];
        };

        $result = Value::withEffect(
            $value,
            function ($value) {
                ++$value->value;
            },
            function ($arg) use (&$value) {
                $value = $value(0);

                return $arg;
            },
            123
        );

        $this->assertSame(123, $result);
        $this->assertInstanceOf(stdClass::class, $value);
        $this->assertSame(1, $value->value);
    }

    public function testLazyEffectWithDisposer(): void
    {
        /* @var \Closure|\stdClass $value */
        $value = function (int $initialValue) {
            return (object)['value' => $initialValue];
        };

        $result = Value::withEffect(
            $value,
            function ($value) {
                ++$value->value;

                return function ($value) {
                    ++$value->value;
                };
            },
            function ($arg) use (&$value) {
                $value = $value(0);

                return $arg;
            },
            123
        );

        $this->assertSame(123, $result);
        $this->assertInstanceOf(stdClass::class, $value);
        $this->assertSame(2, $value->value);
    }

    public function testCanceledEffect(): void
    {
        /* @var \Closure|\stdClass $value */
        $value = function ($initialValue) {
            return (object)['value' => $initialValue];
        };

        $result = Value::withEffect(
            $value,
            function ($value) {
                ++$value->value;
            },
            function ($arg) {
                return $arg;
            },
            123
        );

        $this->assertSame(123, $result);
        $this->assertInstanceOf(Closure::class, $value);
        $this->assertSame(0, $value(0)->value);
    }

    public function testMultiEagerEffects(): void
    {
        $values = [(object)['value' => 0], (object)['value' => 1000]];

        $result = Value::withEffectForEach(
            $values,
            function ($value) {
                ++$value->value;
            },
            function ($arg) {
                return $arg;
            },
            123
        );

        $this->assertSame(123, $result);
        $this->assertSame(1, $values[0]->value);
        $this->assertSame(1001, $values[1]->value);
    }

    public function testMultiEagerEffectsWithDisposers(): void
    {
        $values = [(object)['value' => 0], (object)['value' => 1000]];

        $result = Value::withEffectForEach(
            $values,
            function ($value) {
                ++$value->value;

                return function ($value) {
                    ++$value->value;
                };
            },
            function ($arg) {
                return $arg;
            },
            123
        );

        $this->assertSame(123, $result);
        $this->assertSame(2, $values[0]->value);
        $this->assertSame(1002, $values[1]->value);
    }

    public function testMultiLazyEffects(): void
    {
        /* @var \Closure[]|\stdClass[] $values */
        $values[] = function () {
            return (object)['value' => 0];
        };
        $values[] = function () {
            return (object)['value' => 1000];
        };

        $result = Value::withEffectForEach(
            $values,
            function ($value) {
                ++$value->value;
            },
            function ($arg) use (&$values) {
                $values[0] = $values[0]();
                $values[1] = $values[1]();

                return $arg;
            },
            123
        );

        $this->assertSame(123, $result);
        $this->assertIsArray($values);
        $this->assertInstanceOf(stdClass::class, $values[0]);
        $this->assertInstanceOf(stdClass::class, $values[1]);
        $this->assertSame(1, $values[0]->value);
        $this->assertSame(1001, $values[1]->value);
    }

    public function testMultiLazyEffectsWithDisposers(): void
    {
        /* @var \Closure[]|\stdClass[] $values */
        $values[] = function () {
            return (object)['value' => 0];
        };
        $values[] = function () {
            return (object)['value' => 1000];
        };

        $result = Value::withEffectForEach(
            $values,
            function ($value) {
                ++$value->value;

                return function ($value) {
                    ++$value->value;
                };
            },
            function ($arg) use (&$values) {
                $values[0] = $values[0]();
                $values[1] = $values[1]();

                return $arg;
            },
            123
        );

        $this->assertSame(123, $result);
        $this->assertIsArray($values);
        $this->assertInstanceOf(stdClass::class, $values[0]);
        $this->assertInstanceOf(stdClass::class, $values[1]);
        $this->assertSame(2, $values[0]->value);
        $this->assertSame(1002, $values[1]->value);
    }

    public function testMultiCanceledEffects(): void
    {
        /* @var \Closure[]|\stdClass[] $values */
        $values[] = function () {
            return (object)['value' => 0];
        };
        $values[] = function () {
            return (object)['value' => 1000];
        };

        $result = Value::withEffectForEach(
            $values,
            function ($value) {
                ++$value->value;
            },
            function ($arg) use (&$values) {
                return $arg;
            },
            123
        );

        $this->assertSame(123, $result);
        $this->assertIsArray($values);
        $this->assertInstanceOf(Closure::class, $values[0]);
        $this->assertInstanceOf(Closure::class, $values[1]);
        $this->assertSame(0, $values[0]()->value);
        $this->assertSame(1000, $values[1]()->value);
    }
}
