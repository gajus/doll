# ay\PDO

This PDO extensions provides the benefit of allowing explicit inline parameter type binding as well chaining of method calls.

## Retrieving data from a prepared statement

### The native PDO implementation

```
$sth = $db
	->prepare("SELECT 1 FROM `foo` WHERE `bar_id` = :bar_id AND `baz` = :baz;");

$sth->bindValue('bar_id', 1, PDO::PARAM_INT);
$sth->bindValue('baz', 'qux');

$sth->execute();

$sth->fetch(PDO::FETCH_ASSOC);
```

### The ay\PDO implementation

```
$db
	->prepare("SELECT 1 FROM `foo` WHERE `bar_id` = i:bar_id AND `baz` = s:baz;")
	->execute(['bar_id' => 1, 'baz' => 'qux'])
	->fetch(PDO::FETCH_ASSOC);
```

## Inserting data to a database

### The native PDO implementation

```
$db
	->prepare("INSERT INTO `foo` SET `bar` = s:bar;")
	->execute(['bar' => 'qux']);

$foo_id = $db->lastInsertId();
```

### The ay\PDO implementation

```
$foo_id = $db
	->prepare("INSERT INTO `foo` SET `bar` = s:bar;")
	->execute(['bar' => 'qux']);
```