<?php
namespace gajus\oodo;

class PDO_Statement extends \PDOStatement {
	public
		$dbh,
		$placeholder_param_types,
		$placeholders;
	
	final protected function __construct(PDO $dbh) {
		$this->dbh = $dbh;
	}
	
	public function nextRowset() {
 		if (!parent::nextRowset()) {
			throw new \PDOException('Rowset is not available.');
		}
		
		return $this;
	}
		
	/*public function execute ($parameters = []) {
		#try {
			if ($parameters) {
				#if ($this->queryString === 'INSERT INTO `permission_group_permission` SET `permission_group_id` = ?, `permission_id` = ?;') {
				#	ay( parent::execute($parameters), $this->errorInfo() );
				#}
			
				parent::execute($parameters);
			} else {
				parent::execute();
			}
		#} catch (\PDOException $e) {
		#	ay( $e );
		#}

		return $this;
	}*/
	
	public function bindValue ($parameter, $value, $data_type = \PDO::PARAM_INT) {
		parent::bindValue($parameter, $value, $data_type);
		
		return $this;
	}
}