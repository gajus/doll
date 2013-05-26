<?php
namespace ay\pdo\debug;

class PDOStatement extends \ay\pdo\PDOStatement {    
	public function execute($arguments = []) {
		$return = parent::execute($arguments);
		
		$this->dbh->registerQuery($this->queryString, $arguments);
		
		return $return;
	}
}