# Doll

[![Build Status](https://travis-ci.org/gajus/doll.png?branch=master)](https://travis-ci.org/gajus/doll)
[![Coverage Status](https://coveralls.io/repos/gajus/doll/badge.png?branch=master)](https://coveralls.io/r/gajus/doll?branch=master)
[![Latest Stable Version](https://poser.pugx.org/gajus/doll/version.png)](https://packagist.org/packages/gajus/doll)
[![License](https://poser.pugx.org/gajus/doll/license.png)](https://packagist.org/packages/gajus/doll)

Extended PDO with inline type hinting, deferred connection support, logging and benchmarking.

## Single Parameter Constructor

[PDO::__construct](http://uk3.php.net/manual/en/pdo.construct.php) is using Data Source Name (DSN) string to describe the connection. PDO DSN implementation does not include username, password and driver options.

Doll instance is described using `DataSource` object:

```php
$data_source = new \Gajus\Doll\DataSource([
    'host' => '127.0.0.1',
    'driver' => 'mysql',
    'database' => null,
    'username' => null,
    'password' => null,
    'charset' => 'utf8',
    'collation' => 'utf8_general_ci',
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

## Inline Type Hinting

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

### Placeholder Reuse

PDO implementation does not allow reuse of the placeholders, e.g.

```php
$db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
$db->prepare("SELECT :foo, :foo");
```

The above would cause the following error:

> PDOException: SQLSTATE[HY093]: Invalid parameter number

Doll allows reuse of placeholders.

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

```
array(1) {
  [0]=>
  array(5) {
    ["statement"]=>
    string(23) "SELECT :foo, SLEEP(.2)"
    ["parameters"]=>
    array(1) {
      ["foo"]=>
      string(1) "a"
    }
    ["backtrace"]=>
    array(5) {
      ["file"]=>
      string(58) "/var/www/dev/gajus kuizinas/2014 01 13 doll/test/index.php"
      ["line"]=>
      int(28)
      ["function"]=>
      string(7) "execute"
      ["class"]=>
      string(23) "Gajus\Doll\PDOStatement"
      ["type"]=>
      string(2) "->"
    }
    ["duration"]=>
    float(200157.75)
    ["query"]=>
    string(19) "SELECT ?, SLEEP(.2)"
  }
}
```

Query execution "duration" and "query" parameters are retrieved using MySQL [SHOW PROFILES](http://dev.mysql.com/doc/refman/5.0/en/show-profiles.html). Doll will automatically run diagnostics every 100 executions to overcome [100 queries limit](http://dev.mysql.com/doc/refman/5.6/en/show-profile.html).

## Watch out

* Doll defers PDO constructor until a query is executed against the database.
* Doll constructor will disable `PDO::ATTR_EMULATE_PREPARES`.
* Doll constructor will set `PDO::ATTR_ERRMODE` to `PDO::ERRMODE_EXCEPTION`. If you try to change it, Doll will throw an exception.
* Doll constructor will set `PDO::ATTR_STATEMENT_CLASS` to use Doll's PDOStatement extension.
* Doll's [execute](http://php.net/manual/en/pdostatement.execute.php) method will return instance of [PDOStatement](http://php.net/manual/en/class.pdostatement.php) instead of boolean value.
* Doll is tested only with MySQL.