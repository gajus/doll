<?php
require __DIR__ . '/../pdo.class.php';
require __DIR__ . '/../pdo_statement.class.php';
require __DIR__ . '/../deferred/pdo.class.php';
require __DIR__ . '/../log/pdo.class.php';
require __DIR__ . '/../log/pdo_statement.class.php';
require __DIR__ . '/../debug/pdo.class.php';

$db = new \ay\pdo\debug\PDO('mysql:dbname=test');

if (!($db instanceof \ay\pdo\PDO)) {
	throw new ErrorException('Expecting $db to be instance of \ay\pdo\PDO.');
} else if (!($db instanceof \ay\pdo\deferred\PDO)) {
	throw new ErrorException('Expecting $db to be instance of \ay\pdo\deferred\PDO.');
} else if (!($db instanceof \ay\pdo\log\PDO)) {
	throw new ErrorException('Expecting $db to be instance of \ay\pdo\log\PDO.');
} else if (!($db instanceof \ay\pdo\debug\PDO)) {
	throw new ErrorException('Expecting $db to be instance of \ay\pdo\debug\PDO.');
}

if ($db->isInitialised() !== false) {
	throw new ErrorException('Should not be initialised.');
}

$sth = $db
	->prepare("SELECT SLEEP(.2), 1, b:foo `foo`, n:bar `bar`, i:baz `baz`, s:qux `qux`, l:quux `quux`;");
	
if ($db->isInitialised() !== true) {
	throw new ErrorException('Should be initialised.');
}

if (!($sth instanceof \ay\pdo\PDO_Statement)) {
	throw new ErrorException('\ay\pdo\deferred\PDO statement expected to be an instance of \ay\pdo\PDO_Statement.');
}

if (!($sth instanceof \ay\pdo\log\PDO_Statement)) {
	throw new ErrorException('\ay\pdo\log\PDO statement expected to be an instance of \ay\pdo\log\PDO_Statement.');
}

if (count($db->getQueryLog()) !== 0) {
	throw new ErrorException('Query Log is expected to be empty.');
}

try {
	$sth->execute(['foo' => 'test']);
	
	throw new ErrorException('Expecting PDOException.');
} catch (\PDOException $e) {}

if (count($db->getQueryLog()) !== 0) {
	throw new ErrorException('Query Log is expected to be empty.');
}

$sth->execute(['foo' => 'a', 'bar' => 'test', 'baz' => 'test', 'qux' => 'test', 'quux' => 'test']); $a_statement_execution_line = __LINE__;

if (count($db->getQueryLog()) !== 1) {
	throw new ErrorException('Query Log is expected to have 1 entry.');
}

$sth->execute(['foo' => 'b', 'bar' => 'test', 'baz' => 'test', 'qux' => 'test', 'quux' => 'test']); $b_statement_execution_line = __LINE__;

$query_log = $db->getQueryLog();

if (count($query_log) !== 2) {
	throw new ErrorException('Query Log is expected to have 2 entries.');
}

if ($query_log[0]['backtrace']['line'] !== $a_statement_execution_line) {
	throw new ErrorException('Backtrace is supposed to refer the statement A execution.');
} else if ($query_log[1]['backtrace']['line'] !== $b_statement_execution_line) {
	throw new ErrorException('Backtrace is supposed to refer the statement B execution.');
}

if ((int) ($query_log[0]['duration']/100000) !== 2) {
	throw new ErrorException('Query expected to run exactly two milliseconds.');
}

$result = $sth->fetchAll(PDO::FETCH_ASSOC);

if ($result[0]['bar'] !== null) {
	throw new ErrorExecption('Bar parameter is expected to be null.');
}

for ($i = 0; $i < 100; $i++) {
	$db->query("SELECT 'a';");
}

$db->query("SELECT 'b'");

$summary = $db->getQueryLog();

$last_query = array_pop($summary);

if ($last_query['raw_query'] !== $last_query['profile_query']) {
	throw new \ErrorException('Query misalignment.');
}

echo 'Ok' . PHP_EOL;