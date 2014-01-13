<?php
namespace gajus\oodo\log;

class PDO extends \gajus\oodo\deferred\PDO {
	protected
		$query_log = [];

	public function onInitialisation () {
		$this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['gajus\oodo\log\PDO_Statement', [$this]]);
		
		parent::onInitialisation();
	}
	
	/**
	 * Has to be public because of the \ay\pdo\log\PDO_Statement.
	 */
	public function onQuery ($type, $statement, array $parameters = []) {
		parent::onQuery($type, $statement, $parameters);
		
		if ($type !== 'prepare') {
			$statement = trim(preg_replace('/\s+/', ' ', str_replace("\n", ' ', $statement)));
			$backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 5);
			$backtrace = array_pop($backtrace);
			
			$this->query_log[] = ['statement' => $statement, 'parameters' => $parameters, 'backtrace' => $backtrace];
		}
	}
	
	public function getQueryLog () {
		return $this->query_log;
	}
}