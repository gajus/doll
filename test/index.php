<?php
set_include_path( __DIR__ . '/../src/' );

spl_autoload_register();

$db = new \gajus\doll\LPDO('mysql:dbname=test');

$sth = $db->prepare("SELECT :foo, SLEEP(.2);");

if (!($sth instanceof \gajus\doll\PDOStatement)) {
	throw new \Exception('$sth is not instance of \gajus\doll\PDOStatement.');
}

$log = $db->getLog();

if (count($log) !== 0) {
	throw new ErrorException('Log is not empty.');
}

try {
	$sth->execute(['bar' => 'test']);
	
	throw new \Exception('Exception is not thrown.');
} catch (\PDOException $e) {}

if (count($db->getLog()) !== 0) {
	throw new \Exception('Log is not empty.');
}

$sth->execute(['foo' => 'a']); $a_statement_execution_line = __LINE__;

$log = $db->getLog();

if (count($log) !== 1) {
	throw new \Exception('Log does not have 1 entry.');
}

if (empty($log[0]['parameters'])) {
	throw new \Exception('Statement execution parameters are empty.');
} else if (!isset($log[0]['parameters']['foo'])) {
	throw new \Exception('Statement execution parameter "foo" is not found.');
} else if ($log[0]['parameters']['foo'] !== 'a') {
	throw new \Exception('Statement execution parameter "foo" is not eq. to "a".');
}

$sth->execute(['foo' => 'b']); $b_statement_execution_line = __LINE__;

$log = $db->getLog();

if (count($log) !== 2) {
	throw new \Exception('Log does not have 2 entries.');
}

if ($log[0]['backtrace']['line'] !== $a_statement_execution_line) {
	throw new \Exception('Backtrace is not referring to A statement execution.');
} else if ($log[1]['backtrace']['line'] !== $b_statement_execution_line) {
	throw new \Exception('Backtrace is not referring to B statement execution.');
}

#if ((int) ($query_log[0]['duration']/100000) !== 2) {
#	throw new \Exception('Query expected to run exactly two milliseconds.');
#}

#$result = $sth->fetchAll(PDO::FETCH_ASSOC);

#if ($result[0]['bar'] !== null) {
#	throw new \Execption('Bar parameter is expected to be null.');
#}

// Maximum MySQL profilig history size is 100, http://dev.mysql.com/doc/refman/5.6/en/show-profile.html.

for ($i = 0; $i < 100; $i++) {
	$db->query("/* {$i} */ SELECT 'a';");
}

$db->query("SELECT 'b'");

$log = $db->getLog();

$last_query = array_pop($log);

if ($last_query['statement'] !== $last_query['query']) {
	throw new \Exception('Log is misalignment.');
}

echo 'Ok' . PHP_EOL;