<?php
namespace ay\pdo;

class PDO_Statement extends \PDOStatement {
	public $dbh,
		   $placeholder_param_types,
		   $placeholders;
	
  	protected function __construct(PDO $dbh) {
  		$this->dbh = $dbh;
	}
	
	public function nextRowset() {
 		if (!parent::nextRowset()) {
			throw new \PDOException('Rowset is not available.');
		}
		
		return $this;
	}
		
	public function execute ($parameters = []) {
		parent::execute($parameters);

		return $this;
	}
	
	public function bindValue ($parameter, $value, $data_type = \PDO::PARAM_INT) {
		parent::bindValue($parameter, $value, $data_type);
		
		return $this;
	}
}