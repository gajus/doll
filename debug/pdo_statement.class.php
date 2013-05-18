<?php
namespace ay\pdo\debug;

class PDOStatement extends \ay\pdo\PDOStatement {    
	public function execute($parameters = []) {
		$return = parent::execute($parameters);
		
		$this->dbh->registerQuery();
		
		return $return;
	}
}