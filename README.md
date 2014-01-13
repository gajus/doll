## gajus\PDO

Extended PDO with strict-type parameter binding, deffered database connection, query and prepared statement logging (including the parameters used to execute the statement), and debugging features.

### Prepared statement chaining and inline strict-type parameter binding

#### The native PDO implementation

```PHP
$sth = $db
	->prepare("SELECT 1 FROM `foo` WHERE `bar_id` = :bar_id AND `baz` = :baz;");

$sth->bindValue('bar_id', 1, PDO::PARAM_INT);
$sth->bindValue('baz', 'qux');

$sth->execute();

$sth->fetch(PDO::FETCH_ASSOC);
```

#### The gajus\PDO implementation

```PHP
$db
	->prepare("SELECT 1 FROM `foo` WHERE `bar_id` = i:bar_id AND `baz` = s:baz;")
	->execute(['bar_id' => 1, 'baz' => 'qux'])
	->fetch(PDO::FETCH_ASSOC);
```

Native PDO implementation returns boolean value indicating either success or failure. `gajus\PDO` will return `false` in case of a failure and instance of the `PDOStatement` otherwise.

Instead of using `bindValue` to define the parameter type, you can prefix the placeholder with either parameter type single-character reference, e.g. `i` for integer, `s` for string, etc.

## gajus\pdo\log\PDO

Enables logging of all the queries, including prepared statement and the respective parameters. Queries can be retrieved as an array using gajus\PDO `getQueryLog` method.

## gajus\pdo\debug\PDO

Utilises `gajus\pdo\log\PDO` extension to log the queries and MySQL profiling (http://dev.mysql.com/doc/refman/5.5/en/show-profile.html) to produce a breakdown of every query execution time.

## gajus\pdo\deferred\PDO

Used when majority of the requests are handled from the cache-database. `gajus\pdo\deferred\PDO` does not establish connection to the database until at least one query is executed.