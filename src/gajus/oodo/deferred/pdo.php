<?php
namespace ay\pdo\deferred;

class PDO extends \ay\pdo\PDO {
	private
		$constructor_parameters;
	
	public function __construct($dsn, $username = null, $password = null, array $driver_options = []) {
		$this->constructor_parameters = [$dsn, $username, $password, $driver_options];
	}
	
	public function isInitialised () {
		return !isset($this->constructor_parameters);
	}
	
	protected function onInitialisation () {}
	
	protected function onQuery ($type, $statement, array $parameters = []) {
		if (!$this->isInitialised()) {
			parent::__construct($this->constructor_parameters[0], $this->constructor_parameters[1], $this->constructor_parameters[2], $this->constructor_parameters[3]);
			
			unset($this->constructor_parameters);
			
			$this->onInitialisation();
		}
	}
	
	public function prepare ($statement, $driver_options = []) {
		$this->onQuery('prepare', $statement);
		
		return parent::prepare($statement, $driver_options);
	}
	
	public function exec ($statement) {
		$this->onQuery('exec', $statement);
	
		return parent::exec($statement);
	}
	
	/**
	 * Method [ <internal:PDO> public method query ] {}
	 */
	public function query ($statement) {
		$this->onQuery('query', $statement);
	
		$args = func_get_args();
		$num = func_num_args();
		
		if ($num === 1) {
			return parent::query($statement);
		} else if ($num === 2) {
			return parent::query($statement, $args[1]);
		} else if ($num === 3) {
			return parent::query($statement, $args[1], $args[2]);
		}
	}
	
	public function beginTransaction () {
		$this->onQuery('beginTransaction', 'START TRANSACTION');
	
		return parent::beginTransaction();
	}
	
	public function commit () {
		$this->onQuery('commit', 'COMMIT');
		
		return parent::commit();
	}
	
	public function rollBack () {
		$this->onQuery('rollBack', 'ROLLBACK');
	
		return parent::rollBack();
	}
}