<?php
namespace gajus\doll;

class PDO extends \PDO {

	const FETCH_KEY_ASSOC = 'gajus\doll\0';
	const ATTR_LOGGING = 'gajus\doll\1';

	private
		/**
		 * Initial constructor parameters used to instantiate \PDO upon the first query.
		 *
		 * @param array
		 */
		$constructor = [],
		/**
		 * Database handle attributes that were set using setAttribute before
		 * PDO is constructed. 
		 * 
		 * @param array
		 */
		$attributes = [],
		/**
		 * Queries executed using exec, prepare/execute, query, including beginTransaction,
		 * commit and rollBack.
		 * 
		 * @param array
		 */
		$log = [],
		/**
		 * @param boolean
		 */
		$logging = false;

	/**
	 * Constructur will change the error handling scenario to PDO::ERRMODE_EXCEPTION,
	 * disable emulated queries and set PDO::ATTR_STATEMENT_CLASS to \gajus\doll\PDOStatement.
	 */
	public function __construct ($dsn, $username = null, $password = null, array $driver_options = []) {	
		$this->constructor = [$dsn, $username, $password, $driver_options];

		$this->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		$this->setAttribute(\PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		$this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['gajus\doll\PDOStatement', [$this]]);
	}

	private function isInitialized () {
		return !$this->constructor;
	}

	/**
	 * Logs database handle attributes that are set before PDO is constructed.
	 * 
	 * @param string $attribute
	 * @param mixed $value
	 */
	public function setAttribute ($attribute, $value) {
		if ($attribute === \gajus\doll\PDO::ATTR_LOGGING) {
			if ($this->isInitialized()) {
				throw new \RuntimeException('Cannot change logging value after initialization.');
			}

			if (!is_bool($value)) {
				throw new \InvalidArgumentException('Parameter value is not boolean.');
			}

			$this->logging = $value;

			return;
		}

		if (!$this->isInitialized()) {
			$this->attributes[$attribute] = $value;
		} else {
			parent::setAttribute($attribute, $value);
		}
	}

	public function getAttribute ($attribute) {
		if ($attribute === \gajus\doll\PDO::ATTR_LOGGING) {
			return $this->logging;
		}

		$this->connect();

		return parent::getAttribute($attribute);
	}

	public function prepare ($statement, $driver_options = []) {
		$this->on('prepare', $statement);
		
		return parent::prepare($statement, $driver_options);
	}

	public function exec ($statement) {
		$this->on('exec', $statement);
	
		return parent::exec($statement);
	}

	/**
	 * The implementation might seem odd, though the benchmark (PHP 5.4) shows
	 * that such implementation is noticeably faster than using call_user_func_array.
	 *
	 * Method [ <internal:PDO> public method query ] {}
	 */
	public function query ($statement) {
		$this->on('query', $statement);
	
		$args = func_get_args();
		$num = func_num_args();
		
		if ($num === 1) {
			return parent::query($statement);
		} else if ($num === 2) {
			return parent::query($statement, $args[1]);
		} else if ($num === 3) {
			return parent::query($statement, $args[1], $args[2]);
		}
	}

	public function beginTransaction () {
		$this->on('beginTransaction', 'START TRANSACTION');
	
		return parent::beginTransaction();
	}
	
	public function commit () {
		$this->on('commit', 'COMMIT');
		
		return parent::commit();
	}
	
	public function rollBack () {
		$this->on('rollBack', 'ROLLBACK');
	
		return parent::rollBack();
	}

	/**
	 * This has to be public since it is accessed by the instance of \gajus\doll\PDOStatement.
	 *
	 * @param string $method Method used to execute the query: exec, prepare/execute, query, including beginTransaction, commit and rollBack.
	 * @param string $statement The query or prepared statement.
	 * @param array $parameters The parameters used to execute a prepared statement.
	 */
	public function on ($method, $statement, array $parameters = []) {
		$this->connect();

		if ($this->logging && $method !== 'prepare') {
			$statement = trim(preg_replace('/\s+/', ' ', str_replace("\n", ' ', $statement)));
			$backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

			$this->log[] = ['statement' => $statement, 'parameters' => $parameters, 'backtrace' => $backtrace];
		
			if (count($this->log) % 100 === 0) {
				$this->applyProfileData();
			}
		}
	}

	final private function connect () {
		if ($this->isInitialized()) {
			return;
		}

		parent::__construct($this->constructor[0], $this->constructor[1], $this->constructor[2], $this->constructor[3]);

		$this->constructor = null;

		foreach ($this->attributes as $attribute => $value) {
			$this->setAttribute($attribute, $value);
		}
		
		$this->attributes = null;

		parent::exec("SET `profiling` = 1;");
		parent::exec("SET `profiling_history_size` = 100;");
	}

	/**
	 * Apply data from "SHOW PROFILES;" to the respective queries in the $log.
	 *
	 * @return void
	 */
	final private function applyProfileData () {
		if (!$this->isInitialized()) {
			return;
		}
		
		$queries = \PDO::query("SHOW PROFILES;")
			->fetchAll(PDO::FETCH_ASSOC);

		foreach ($queries as $q) {
			// The original query is executed using parent:: method (not in the log).
			if ($q['Query'] === 'SET `profiling_history_size` = 100') {
				continue;
			}
			
			$this->log[$q['Query_ID'] - 2]['duration'] = 1000000 * $q['Duration'];
			$this->log[$q['Query_ID'] - 2]['query'] = $q['Query'];
		}
	}

	public function getLog () {
		$this->applyProfileData();

		return $this->log;
	}
}