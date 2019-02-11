# Doll

[![Build Status](https://travis-ci.org/gajus/doll.png?branch=master)](https://travis-ci.org/gajus/doll)
[![Coverage Status](https://coveralls.io/repos/gajus/doll/badge.png?branch=master)](https://coveralls.io/r/gajus/doll?branch=master)
[![Latest Stable Version](https://poser.pugx.org/gajus/doll/version.png)](https://packagist.org/packages/gajus/doll)
[![License](https://poser.pugx.org/gajus/doll/license.png)](https://packagist.org/packages/gajus/doll)

Extended PDO with inline type hinting, deferred connection support, logging and benchmarking.

## Single Parameter Constructor

[PDO::__construct](http://uk3.php.net/manual/en/pdo.construct.php) is using Data Source Name (DSN) string to describe the connection. PDO DSN implementation does not include user, password and driver options.

Doll instance is described using `DataSource` object:

```php
$data_source = new \Gajus\Doll\DataSource([
    'host' => '127.0.0.1',
    'driver' => 'mysql',
    'database' => null,
    'user' => null,
    'password' => null,
    'charset' => 'utf8',
    'driver_options' => []
]);
```

## Deferred Connection

PDO will establish a connection to the database upon initialization. If application initializes PDO during the bootstrap, but does not execute queries (e.g. request that is served from cache), the connection is unnecessary.

Doll will not connect to the database upon initialization:

```php
$db = new \Gajus\Doll\PDO($data_source);
```

The connection is deferred until either of the following methods are invoked:

* [PDO::prepare()](http://php.net/manual/en/pdo.prepare.php)
* [PDO::exec()](http://php.net/manual/en/pdo.exec.php)
* [PDO::query()](http://php.net/manual/en/pdo.query.php)
* [PDO::beginTransaction()](http://php.net/manual/en/pdo.begintransaction.php)
* [PDO::commit()](http://php.net/manual/en/pdo.commit.php)
* [PDO::rollBack()](http://php.net/manual/en/pdo.rollback.php)
* [PDOStatement::execute()](http://php.net/manual/en/pdostatement.execute.php)

## Default Attributes

| Attribute | PDO | Doll |
|---|---|---|
| `PDO::ATTR_ERRMODE` | `PDO::ERRMODE_SILENT` | `PDO::ERRMODE_EXCEPTION` |
| `PDO::ATTR_EMULATE_PREPARES` | `false` | `true` |
| `PDO::ATTR_DEFAULT_FETCH_MODE` | `PDO::FETCH_BOTH` | `PDO::FETCH_ASSOC` |
| `PDO::ATTR_STATEMENT_CLASS` | `PDOStatement` | `Gajus\Doll\PDOStatement` |

| Attribute | Reason |
|---|---|
| `PDO::ATTR_ERRMODE` | Enables [method chaining](#method-chaining). |
| `PDO::ATTR_EMULATE_PREPARES` | [`PDO_MYSQL`](http://php.net/manual/en/ref.pdo-mysql.php) will take advantage of native prepared statement support present in MySQL 4.1 and higher. It will always [fall back](http://lt1.php.net/manual/en/pdo.setattribute.php) to emulating the prepared statement if the driver cannot successfully prepare the current query. |
| `PDO::ATTR_DEFAULT_FETCH_MODE` | More convenient. |
| `PDO::ATTR_STATEMENT_CLASS` | Required for the [extended type hinting](#extended-type-hinting) implementation. |

Values for attributes not in the table do not differ.

## Method Chaining

[PDOStatement::execute()](http://www.php.net/manual/en/pdostatement.execute.php) returns a boolean value indicating the state of the transaction, e.g.

```php
$sth = $db->prepare("SELECT ?"); // PDOStatement
$sth->execute([1]); // boolean
$input = $sth->fetch(PDO::FETCH_COLUMN);
```

However, if you are using [PDO::ERRMODE_EXCEPTION](http://uk1.php.net/manual/en/pdo.error-handling.php) error handling strategy, the output of `execute` is redundant.

Doll forces `PDO::ERRMODE_EXCEPTION` error handling strategy, while `execute` method returns an instance of `\Gajus\Doll\PDOStatement`. This allows further method chaining, e.g.

```php
$input = $db
    ->prepare("SELECT ?") // Gajus\Doll\PDOStatement
    ->execute([1]) // Gajus\Doll\PDOStatement
    ->fetch(PDO::FETCH_COLUMN);
```

## Extended Type Hinting

### Inline Type Hinting

[PDOStatement::bindValue()](http://php.net/manual/en/pdostatement.bindvalue.php) method allows to set the parameter type. However, the syntax is verbose:

```php
$sth = $db->prepare("SELECT :foo, :bar, :baz");
$sth->bindValue('foo', 'foo', PDO::PARAM_STR);
$sth->bindValue('bar', 1, PDO::PARAM_INT);
$sth->bindValue('baz', $fp, PDO::PARAM_LOB);
$sth->execute();
```

Doll allows inline type hinting:

```php
$sth = $db->prepare("SELECT s:foo, i:bar, l:baz");
$sth->execute(['foo' => 'foo', 'bar' => 1, 'baz' => $fp]);
```

Doll implementation supports all of the parameter types:

|Name|Parameter Type|
|---|---|
|`b`|`PDO::PARAM_BOOL`|
|`n`|`PDO::PARAM_NULL`|
|`i`|`PDO::PARAM_INT`|
|`s`|`PDO::PARAM_STR`|
|`l`|`PDO::PARAM_LOB`|

### Inferred Type Hinting

When parameter name is "id" or ends with "_id", unless an explicit parameter type is set, Doll will use `PDO::PARAM_INT`, e.g.

```php
$db->prepare("SELECT :id, :foo_id");
```

Is equivalent to:

```php
$db->prepare("SELECT i:id, i:foo_id");
```

You can explicitly set the parameter type:

```php
$db->prepare("SELECT s:id, s:foo_id");
```

You can disable the inferred type hinting:

```php
$db->setAttribute(\Gajus\Doll\PDO::ATTR_INFERRED_TYPE_HINTING, false);
```

## Parameter Marker Reuse

Using Doll, you can reuse the named parameter markers in your prepared statements, e.g.

```php
$db->prepare("SELECT :foo, :foo");
```

The native PDO implementation does not support it. It will raise the following error:

> PDOException: SQLSTATE[HY093]: Invalid parameter number

## Logging and Benchmarking

Doll supports query and statement execution logging. To enable logging, you need to set `\Gajus\Doll\PDO::ATTR_LOGGING` attribute to `true`.

```php
$db->setAttribute(\Gajus\Doll\PDO::ATTR_LOGGING, true);

$db
    ->prepare("SELECT :foo, SLEEP(.2)")
    ->execute(['foo' => 'a']);

$log = $db->getLog();

var_dump($log);
```

The log output contains the following information about each query:

```php
array(1) {
    [0]=>
        array(7) {
            ["statement"]=>
                string(22) "SELECT :foo, SLEEP(.2)"
            ["parameters"]=>
                array(1) {
                    ["foo"]=>
                        string(1) "a"
                }
            ["execution_wall_time"]=>
                float(0.20117211341858)
            ["backtrace"]=>
                array(5) {
                    ["file"]=>
                        string(85) "/../doll/tests/LogTest.php"
                    ["line"]=>
                        int(28)
                    ["function"]=>
                        string(7) "execute"
                    ["class"]=>
                        string(23) "Gajus\Doll\PDOStatement"
                    ["type"]=>
                        string(2) "->"
                }
            ["execution_duration"]=>
                float(0.200723)
            ["execution_overhead"]=>
                float(0.00044911341857909)
            ["query"]=>
                string(19) "SELECT ?, SLEEP(.2)"
    }
}
```

"execution_duration" and "query" are retrieved from [SHOW PROFILES](http://dev.mysql.com/doc/refman/5.0/en/show-profiles.html). Doll will automatically run diagnostics every 100 executions to overcome the [limit of 100 queries](http://dev.mysql.com/doc/refman/5.6/en/show-profile.html).
