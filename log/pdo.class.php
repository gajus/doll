<?php
namespace ay\pdo\log;

class PDO extends \ay\pdo\PDO {
	protected
		$query_log = [];
	
	public function __construct($dsn, $username = null, $password = null, array $driver_options = []) {
		parent::__construct($dsn, $username, $password, $driver_options);
		
	    $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['ay\pdo\log\PDO_Statement', [$this]]);
    }
	
	public function getQueryLog () {
		return $this->query_log;
	}
	
	public function exec ($statement) {
		$result = parent::exec($statement);
		
		$this->registerQuery($statement);
		
		return $result;
	}
	
	/**
	 * Method [ <internal:PDO> public method query ] {}
	 */
	public function query ($statement) {
		$args = func_get_args();
		$num = func_num_args();
		
		if ($num === 1) {
			$result = parent::query($args[0]);
		} else if ($num === 2) {
			$result = parent::query($args[0], $args[1]);
		} else if ($num === 3) {
			$result = parent::query($args[0], $args[1], $args[2]);
		}
		
		$this->registerQuery($statement);
		
		return $result;
	}
	
	public function beginTransaction () {
		$result = parent::beginTransaction();
		
		$this->registerQuery('START TRANSACTION');
		
		return $result;
	}
	
	public function commit () {
		$result = parent::commit();
		
		$this->registerQuery('COMMIT');
		
		return $result;
	}
	
	public function rollBack () {
		$result = parent::rollBack();
		
		$this->registerQuery('ROLLBACK');
		
		return $result;
	}
	
	protected function registerQuery ($statement, array $arguments = []) {
		$statement = trim(preg_replace('/\s+/', ' ', str_replace("\n", ' ', $statement)));
		$backtrace = debug_backtrace()[1];
		
		$this->query_log[] = ['query' => $statement, 'arguments' => $arguments, 'backtrace' => debug_backtrace()];
	}
}