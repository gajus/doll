<?php
namespace ay\pdo\deferred;

class PDO extends \ay\pdo\PDO {
	private
		$initialised = false,
		$constructor;
	
	public function __construct($dsn, $username = null, $password = null, array $driver_options = []) {
		$this->constructor = [$dsn, $username, $password, $driver_options];
	}
	
	private function defferedConstruct () {
		if ($this->initialised === false) {
			$this->initialised = true;
		
			parent::__construct($this->constructor[0], $this->constructor[1], $this->constructor[2], $this->constructor[3]);
		}
	}
	
	public function exec ($statement) {
		$this->defferedConstruct();
		
		return parent::exec($statement);
	}
	
	/**
	 * Method [ <internal:PDO> public method query ] {}
	 */
	public function query ($statement) {
		$this->defferedConstruct();
		
		$args = func_get_args();
		$num = func_num_args();
		
		if ($num === 1) {
			return parent::query($args[0]);
		} else if ($num === 2) {
			return parent::query($args[0], $args[1]);
		} else if ($num === 3) {
			return parent::query($args[0], $args[1], $args[2]);
		}
	}
	
	public function prepare ($statement, $driver_options = []) {
		$this->defferedConstruct();
		
		return parent::prepare($statement, $driver_options);
	}
	
	public function beginTransaction () {
		$this->defferedConstruct();
		
		parent::beginTransaction();
	}
}