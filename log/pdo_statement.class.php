<?php
namespace ay\pdo\log;

class PDO_Statement extends \ay\pdo\PDO_Statement {	
	public function execute($parameters = []) {
		$response = parent::execute($parameters);
		
		$this->dbh->onQuery('execute', $this->queryString, $parameters);
		
		return $response;
	}
}