<?php
namespace ay\pdo\debug;

class PDO_Statement extends \ay\pdo\PDO_Statement {    
	public function execute($arguments = []) {
		$return = parent::execute($arguments);
		
		$this->dbh->registerQuery($this->queryString, $arguments);
		
		return $return;
	}
}