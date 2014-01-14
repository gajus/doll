# Doll

Extended PDO with deferred connection, logging of queries and prepared statements (including the statement execution parameters) and benchmarking. Doll's `\gajus\doll\PDO::execute()` method returns instance of `\gajus\doll\PDOStatement` instead of boolean response. There are no other *bells and whistles*.

## Deferred

When you iniate `\gajus\doll\PDO` instance:

```php
$db = new \gajus\doll\PDO('mysql');
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

Doll is a drop-in replecement for native PDO implementation, though vice-versa does not apply.

### Chaining

Native [PDOStatement::execute()](http://www.php.net/manual/en/pdostatement.execute.php) returns a boolean value indicating state of the transaction. However, if you are using [PDO::ERRMODE_EXCEPTION](http://uk1.php.net/manual/en/pdo.error-handling.php) error handling stratery (Doll's default), the output is redundant. Doll returns instance of `\gajus\doll\PDOStatement` that allows chaining of calls, e.g.

```php
$db
	->prepare("SELECT ?;")
	->execute([1])
	->fetch(PDO::FETCH_COLUMN);
```

In case you forgot, native PDO implementation requires you to store the PDOStatement object:

```php
$sth = $db->prepare("SELECT ?;");
$sth->execute([1]);
$sth->fetch(PDO::FETCH_COLUMN);
```

### Logging & Benchmarking

Doll supports query and statement execution logging. To enable logging, you need to set `\gajus\doll\PDO::ATTR_LOGGING` attribute to `true`.

```php
$db = new \gajus\doll\PDO('mysql');
$db->setAttribute(\gajus\doll\PDO::ATTR_LOGGING, true);

$db->prepare("SELECT :foo, SLEEP(.2);")->execute(['foo' => 'a']);

$log = $db->getLog();

var_dump($log);
```

The log output contains the following information about each query:

```
array(1) {
  [0]=>
  array(5) {
    ["statement"]=>
    string(23) "SELECT :foo, SLEEP(.2);"
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
      string(23) "gajus\doll\PDOStatement"
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

Previous Doll implementation (before it had a name), had syntactical sugar allowing to define parameter type while defining a prepared statement, e.g.

```php
$db
    ->prepare("SELECT 1 FROM `foo` WHERE `bar_id` = i:bar_id AND `baz` = s:baz;")
    ->execute(['bar_id' => 1, 'baz' => 'qux'])
    ->fetch(PDO::FETCH_ASSOC);
```

However, this raised issues with code reusability across projects that don't support this syntax. Furtermore, MySQL itself is fairly good with [type converersion in expression evaluation](http://dev.mysql.com/doc/refman/5.5/en/type-conversion.html).

## Watch out

* Doll defers PDO constructor until a query is executed against the database.
* Doll constructor will disabled `PDO::ATTR_EMULATE_PREPARES`.
* Doll constructor will automatically set `PDO::ATTR_ERRMODE` to `PDO::ERRMODE_EXCEPTION`.
* Doll constructor will automatically set `PDO::ATTR_STATEMENT_CLASS` to use Doll's PDOStatement extension.
* Doll's [execute](http://php.net/manual/en/pdostatement.execute.php) method will return instance of [PDOStatement](http://php.net/manual/en/class.pdostatement.php) instead of boolean value.
* Doll is tested only with MySQL.