<?php
namespace ay\pdo;

class PDO extends \PDO {
	const FETCH_KEY_ASSOC = 'ay0';

	public function __construct($dsn, $username = null, $password = null, array $driver_options = []) {	
		parent::__construct($dsn, $username, $password, $driver_options);
		
		$this->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		$this->setAttribute(\PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		$this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['ay\pdo\Pdo_Statement', [$this]]);
	}
	
	public function prepare ($statement, $driver_options = []) {
		try {	
			return parent::prepare($statement, $driver_options);
		} catch (\PDOException $e) {
			ay( $e );
		}
	}
}