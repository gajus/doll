# Doll

[![Build Status](https://travis-ci.org/gajus/doll.png?branch=master)](https://travis-ci.org/gajus/doll)
[![Coverage Status](https://coveralls.io/repos/gajus/doll/badge.png?branch=master)](https://coveralls.io/r/gajus/doll?branch=master)
[![Latest Stable Version](https://poser.pugx.org/gajus/doll/version.png)](https://packagist.org/packages/gajus/doll)
[![License](https://poser.pugx.org/gajus/doll/license.png)](https://packagist.org/packages/gajus/doll)

Extended PDO with deferred connection, logging of queries and prepared statements (including the statement execution parameters) and benchmarking. Doll's `\Gajus\Doll\PDO::execute()` method returns instance of `\Gajus\Doll\PDOStatement` instead of boolean response. There are no other *bells and whistles*.

## Deferred

When you iniate `\Gajus\Doll\PDO` instance:

```php
$db = new \Gajus\Doll\PDO('mysql');
```

Doll does not connect to the database. Instead, it will wait until you execute either of the following methods (i.e. run a query against the database):

* [PDO::prepare()](http://php.net/manual/en/pdo.prepare.php)
* [PDO::exec()](http://php.net/manual/en/pdo.exec.php)
* [PDO::query()](http://php.net/manual/en/pdo.query.php)
* [PDO::beginTransaction()](http://php.net/manual/en/pdo.begintransaction.php)
* [PDO::commit()](http://php.net/manual/en/pdo.commit.php)
* [PDO::rollBack()](http://php.net/manual/en/pdo.rollback.php)
* [PDOStatement::execute()](http://php.net/manual/en/pdostatement.execute.php)

## Documentation

Doll is a drop-in replecement for native PDO implementation, though vice-versa does not stand.

### Instantiating

[PDO::__construct](http://uk3.php.net/manual/en/pdo.construct.php) is using Data Source Name (DSN) to describe a connection to the data source. However, native PDO implementation separated out username, password and driver options into separate parameters. In practise, this makes sharing configuration cumbersome. As such, Doll opted to use a single array to describe connection:

```php
[
    'host' => '127.0.0.1',
    'driver' => 'mysql',
    'database' => null,
    'username' => null,
    'password' => null,
    'charset' => 'utf8',
    'collation' => 'utf8_general_ci',
    'driver_options' => []
]
```

#

### Chaining

Native [PDOStatement::execute()](http://www.php.net/manual/en/pdostatement.execute.php) returns a boolean value indicating state of the transaction. However, if you are using [PDO::ERRMODE_EXCEPTION](http://uk1.php.net/manual/en/pdo.error-handling.php) error handling strategy (Doll's default), the output is redundant. Doll returns instance of `\Gajus\Doll\PDOStatement` that allows chaining of calls, e.g.

```php
$input = $db
    ->prepare("SELECT ?")
    ->execute([1])
    ->fetch(PDO::FETCH_COLUMN);
```

In case you forgot, native PDO implementation requires you to store the PDOStatement object:

```php
$sth = $db->prepare("SELECT ?");
$sth->execute([1]);
$input = $sth->fetch(PDO::FETCH_COLUMN);
```

### Logging & Benchmarking

Doll supports query and statement execution logging. To enable logging, you need to set `\Gajus\Doll\PDO::ATTR_LOGGING` attribute to `true`.

```php
$db = new \Gajus\Doll\PDO('mysql');
$db->setAttribute(\Gajus\Doll\PDO::ATTR_LOGGING, true);

$db->prepare("SELECT :foo, SLEEP(.2)")->execute(['foo' => 'a']);

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

## Strict-type parameter binding

Previous Doll implementation (before it had a name), supported syntactical sugar allowing to define parameter type while defining a prepared statement, e.g.

```php
$db
    ->prepare("SELECT 1 FROM `foo` WHERE `bar_id` = i:bar_id AND `baz` = s:baz")
    ->execute(['bar_id' => 1, 'baz' => 'qux'])
    ->fetch(PDO::FETCH_ASSOC);
```

However, this raised issues with code portability across projects that don't support this syntax. MySQL itself is fairly good with [type converersion in expression evaluation](http://dev.mysql.com/doc/refman/5.5/en/type-conversion.html).

## Logging

Doll used to implement logging

## Watch out

* Doll defers PDO constructor until a query is executed against the database.
* Doll constructor will disable `PDO::ATTR_EMULATE_PREPARES`.
* Doll constructor will set `PDO::ATTR_ERRMODE` to `PDO::ERRMODE_EXCEPTION`. If you try to change it, Doll will throw an exception.
* Doll constructor will set `PDO::ATTR_STATEMENT_CLASS` to use Doll's PDOStatement extension.
* Doll's [execute](http://php.net/manual/en/pdostatement.execute.php) method will return instance of [PDOStatement](http://php.net/manual/en/class.pdostatement.php) instead of boolean value.
* Doll is tested only with MySQL.