<?php
namespace ay\pdo\debug;

class PDO extends \ay\pdo\log\PDO {	
	public function onInitialisation () {
		parent::onInitialisation();
	
		\PDO::exec("SET `profiling` = 1;");
		\PDO::exec("SET `profiling_history_size` = 100;");
	}
	
	public function onQuery ($type, $statement, array $parameters = []) {
		parent::onQuery($type, $statement, $parameters);
		
		if ($this->query_log && count($this->query_log) % 100 === 0) {
			$this->addProfileData();
		}
	}
	
	private function addProfileData () {
		$queries = \PDO::query("SHOW PROFILES;")
			->fetchAll(PDO::FETCH_ASSOC);
		
		foreach ($queries as $q) {
			if ($q['Query'] === 'SET `profiling_history_size` = 100') {
				continue;
			}
			
			$this->query_log[$q['Query_ID'] - 2]['duration'] = 1000000 * $q['Duration'];
			$this->query_log[$q['Query_ID'] - 2]['profile_query'] = $q['Query'];
		}
	}
	
	public function getQueryLog () {
		$this->addProfileData();
		
		return $this->query_log;
	}
}