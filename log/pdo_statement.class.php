<?php
namespace ay\pdo\log;

class PDO_Statement extends \ay\pdo\PDO_Statement {	
	public function execute ($parameters = []) {
		#try {
			if ($parameters) {
				#if ($this->queryString === 'INSERT INTO `permission_group_permission` SET `permission_group_id` = ?, `permission_id` = ?;') {
				#	ay( parent::execute($parameters) );
				#}
				
				parent::execute($parameters);
			} else {
				parent::execute();
			}
		#} catch (\PDOException $e) {
		#	ay( $e );
		#}
		
		$this->dbh->onQuery('execute', $this->queryString, $parameters);
		
		return $this;
	}
}