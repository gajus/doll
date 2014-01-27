<?php
namespace gajus\doll;

/**
 * @link https://github.com/gajus/doll for the canonical source repository
 * @copyright Copyright (c) 2013-2014, Anuary (http://anuary.com/)
 * @license https://github.com/gajus/doll/blob/master/LICENSE BSD 3-Clause
 */
class PDOStatement extends \PDOStatement {
	private
		$dbh;
	
	final protected function __construct(PDO $dbh) {
		$this->dbh = $dbh;
	}
	
	public function nextRowset() {
 		if (!parent::nextRowset()) {
			throw new \RuntimeException('Rowset is not available.');
		}
		
		return $this;
	}

	/**
	 * @return PDOStatement
	 */
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