<?php
namespace ay\pdo\log;

class PDO_Statement extends \ay\pdo\PDO_Statement {	
	public function execute($parameters = []) {
		$return = parent::execute($parameters);
		
		$this->dbh->registerQuery($this->queryString, $parameters);
		
		return $return;
	}
}