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
			parent::__construct($this->constructor[0], $this->constructor[1], $this->constructor[2], $this->constructor[3]);
		}
    }
	
	public function exec ($statement) {
		$this->defferedConstruct();
		
		return parent::exec($statement);
	}
	
	public function query ($statement) {
		$this->defferedConstruct();
	
		return call_user_func_array(['parent', 'query'], func_get_args());
	}
	
	public function beginTransaction () {
		$this->defferedConstruct();
		
		$this->registerQuery('START TRANSACTION');
	}
}