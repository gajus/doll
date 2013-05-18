<?php
namespace ay\pdo\debug;

class PDOStatement extends \ay\pdo\PDOStatement {    
	public function execute($parameters = []) {
		$this->dbh->registerQuery();
	
		return parent::execute($parameters);
	}
}