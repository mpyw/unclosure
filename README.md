# Unclosure [![Build Status](https://github.com/mpyw/unclosure/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/mpyw/unclosure/actions) [![Coverage Status](https://coveralls.io/repos/github/mpyw/unclosure/badge.svg?branch=master)](https://coveralls.io/github/mpyw/unclosure?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mpyw/unclosure/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mpyw/unclosure/?branch=master)

Closure unwrapper especially suited for Laravel PDO.

## Requirements

| Package | Version | Mandatory |
|:---|:---|:---:|
| PHP | <code>^7.4 &#124;&#124; ^8.0</code> | ✅ |
| PHPStan | <code>&gt;=1.1</code> | |

## Installing

```bash
composer require mpyw/unclosure
```

# Examples

## Call `PDO::setAttribute()` after database connection has been established

```php
<?php

use Mpyw\Unclosure\Value;
use PDO;

function withEmulation(PDO $pdo, bool $enabled): PDO
{
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $enabled);
    return $pdo;
}

$connector = function (string $dsn) {
    return new PDO($dsn);
};

// Eager Evaluation
$pdo = Value::withCallback($connector('sqlite::memory:'), 'withEmulation', true);

// Lazy Evaluation
$pdo = Value::withCallback($connector, 'withEmulation', true);
$pdo = $pdo('sqlite::memory:');
```

## Temporarily change `PDO` attributes

```php
<?php

use Mpyw\Unclosure\Value;
use PDO;

function switchingEmulationTo(bool $enabled, &$pdo, callable $callback, ...$args)
{
    return Value::withEffect(
        $pdo,
        function (PDO $pdo) use ($enabled) {
            $original = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $enabled);
            
            return function (PDO $pdo) use ($original) {
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $original);
            };
        },
        $callback,
        ...$args
    );
}

$connector = function (string $dsn) {
    return new PDO($dsn);
};

// Eager Evaluation
$pdo = $connector('sqlite::memory:');
switchingEmulationTo(true, $pdo, function () use ($pdo) {
    // Run queries that require prepared statement emulation
});

// Lazy Evaluation
$pdo = $connector;
switchingEmulationTo(true, $pdo, function () use (&$pdo) {
    $pdo = $pdo('sqlite::memory:');
    
    // Run queries that require prepared statement emulation
});
```

# API

## `Value::withCallback()`

```php
static mixed|Closure withCallback(mixed|Closure $value, callable $callback, mixed ...$args)
```

Call `$callback($value, ...$args)` or wrap its call into `Closure`.

- When you pass `$value` as `Closure`:
  - Return wrapped `Closure` which returns **`$callback($value(), ...$args)`**.
- When you pass `$value` as a raw value:
  - Return **`$callback($value, ...$args)`**.

### Arguments and Return Value

| Name | Type | Description |
|:---|:---|:---|
|`$value`|mixed<br>(A) `Closure`|**First** argument for `$callback` which is unwrappable|
|`$callback`|(B) callable|A callback which takes **unwrapped `$value`** as the first argument|
|`...$args`|mixed|**Second, third, ...** arguments for `$callback`|
|&lt;Return Value&gt; |mixed<br>(C) `Closure`|Decorated `Closure` or an unwrapped value|

#### (A) `$value`

```php
$value(mixed ...$initialArgs): mixed
```

| Name | Type | Description |
|:---|:---|:---|
|`...$initialArgs`|mixed|Arguments for unwrapping `Closure`|
| &lt;Return Value&gt; |mixed|An unwrapped value|

#### (B) `$callback`

```php
$callback(mixed $value, ...$args): mixed
```

| Name | Type | Description |
|:---|:---|:---|
|`$value`|mixed|An **unwrapped** value|
|`...$args`|mixed|Arguments from `Value::withCallback()`|
| &lt;Return Value&gt; |mixed|A decorated unwrapped value|

#### (C) Return Value

```php
*(mixed ...$initialArgs): mixed
```

| Name | Type | Description |
|:---|:---|:---|
|`...$initialArgs`|mixed|Arguments for unwrapping `$value`|
| &lt;Return Value&gt; |mixed|An unwrapped value which is propagated from `$value(...$initialArgs)`|

## `Value::withEffect()`<br>`Value::withEffectForEach()`

```php
public static withEffect(mixed|Closure &$value, callable $configurator, callable $callback, mixed ...$args): mixed
public static withEffectForEach((mixed|Closure)[] &$values, callable $configurator, callable $callback, mixed ...$args): mixed
```

Call `$callback(...$args)`, watching new assignment on `&$value`. **You can conditionally unwrap `$value` in your `$callback` by yourself.**

- When you pass `$value` as `Closure`:
  - If `$value` has been unwrapped, configurations via `$configurator` are applied.
  - **If `$value` still remains as `Closure`, all configurations are canceled.**
- When you pass `$value` as a raw value:
  - Configurations via `$conigurator` are immediately applied.

### Arguments and Return Value

| Name | Type | Description |
|:---|:---|:---|
|`&$value`|mixed<br>(A) `Closure`|An argument for `$configurator` which is unwrappable|
|`$configurator`|(B) callable|A configurator callback which takes **unwrapped `$value`** as the first argument|
|`$callback`|(D) callable|Any callback function|
|`...$args`|mixed|Arguments for `$callback`|
| &lt;Return Value&gt; |mixed|Return value from `$callback(...$args)`|

#### (A) `&$value`

```php
*(): mixed
```

| Name | Type | Description |
|:---|:---|:---|
| &lt;Return Value&gt; |mixed|An unwrapped value|

#### (B) `$configurator`

```php
$configurator(mixed $value): ?callable
```

| Name | Type | Description |
|:---|:---|:---|
| `mixed` |mixed|An **unwrapped** value|
| &lt;Return Value&gt; |null<br>(C) callable|An optional disposer function corresponding to the configurator|

#### (C) `$configurator` Return Value

```php
*(mixed $value): void
```

| Name | Type | Description |
|:---|:---|:---|
| `mixed` |mixed|An **unwrapped** value|

#### (D) `$callback`

```php
$callback(...$args): mixed
```

| Name | Type | Description |
|:---|:---|:---|
|`...$args`|mixed|Arguments from `Value::withEffect()`|
| &lt;Return Value&gt; |mixed||
