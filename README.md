# Doll

Extended PDO with deferred connection, logging of queries and prepared statements (including the statement execution parameters) and benchmarking. Doll [execute](http://php.net/manual/en/pdostatement.execute.php) method returns instance of [PDOStatement](http://php.net/manual/en/class.pdostatement.php) instead of boolean response. There are no other *bells and whistles*.

## Deferred

When you iniate \gajus\doll\PDO instance:

```php
$db = new \gajus\doll\PDO('mysql');
```

Doll does not connect to the database. Instead, it will wait until you use either of the following methods:

* [PDO::prepare()](http://php.net/manual/en/pdo.prepare.php)
* [PDO::exec()](http://php.net/manual/en/pdo.exec.php)
* [PDO::query()](http://php.net/manual/en/pdo.query.php)
* [PDO::beginTransaction()](http://php.net/manual/en/pdo.begintransaction.php)
* [PDO::commit()](http://php.net/manual/en/pdo.commit.php)
* [PDO::rollBack()](http://php.net/manual/en/pdo.rollback.php)
* [PDOStatement::execute()](http://php.net/manual/en/pdostatement.execute.php)

## Chaining

[PDOStatement::execute()](http://www.php.net/manual/en/pdostatement.execute.php) returns a boolean value indicating success or failure of the transaction. However, if you are using [PDO::ERRMODE_EXCEPTION](http://uk1.php.net/manual/en/pdo.error-handling.php) error handling stratery (, which you should be using), the output is redundant. Doll returns instance of [PDOStatement](http://php.net/manual/en/class.pdostatement.php) that allows chaining of calls, e.g.

```php
$db
	->prepare("SELECT ?;")
	->execute([1])
	->fetch(PDO::FETCH_COLUMN);
```

In case you forgot, native PDO implementation requires you to store the PDOStatement object.

```php
$sth = $db->prepare("SELECT ?;");
$sth->execute([1]);
$sth->fetch(PDO::FETCH_COLUMN);
```

## Logging

TBD

## Debugging

TBD

## Strict-type parameter binding

Previous Doll implementation (before it had a name), had syntactical sugar allowing to define parameter type while defining a prepared statement, e.g.

```php
$db
    ->prepare("SELECT 1 FROM `foo` WHERE `bar_id` = i:bar_id AND `baz` = s:baz;")
    ->execute(['bar_id' => 1, 'baz' => 'qux'])
    ->fetch(PDO::FETCH_ASSOC);
```

However, this raised issues with code reusability across projects that don't support this syntax. Furtermore, MySQL itself is fairly good with [type converersion in expression evaluation](http://dev.mysql.com/doc/refman/5.5/en/type-conversion.html).

## gajus\pdo\debug\PDO

Utilises `gajus\pdo\log\PDO` extension to log the queries and MySQL profiling (http://dev.mysql.com/doc/refman/5.5/en/show-profile.html) to produce a breakdown of every query execution time.
