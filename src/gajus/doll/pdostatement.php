<?php
namespace gajus\doll;

class PDOStatement extends \PDOStatement {
	private
		$dbh;
	
	final protected function __construct(PDO $dbh) {
		$this->dbh = $dbh;
	}
	
	public function nextRowset() {
 		if (!parent::nextRowset()) {
			throw new \Exception('Rowset is not available.');
		}
		
		return $this;
	}

	public function execute ($parameters = []) {
		if ($parameters) {
			parent::execute($parameters);
		} else {
			parent::execute();
		}

		$this->dbh->on('execute', $this->queryString, $parameters);

		return $this;
	}
}