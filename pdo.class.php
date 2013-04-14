<?php
namespace ay\pdo;

class PDO extends \PDO {
	const FETCH_KEY_ASSOC = 'ay0';

    public function __construct($dsn, $username = null, $password = null, array $driver_options = []) {
		parent::__construct($dsn, $username, $password, $driver_options);
		
	    $this->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		$this->setAttribute(\PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['ay\pdo\PDOStatement', [$this]]);
    }
    
    public function prepare($statement, $driver_options = []) {
    	$param_types = [
	        'b'	=> PDO::PARAM_BOOL,
	        'n' => PDO::PARAM_NULL,
	        'i' => PDO::PARAM_INT,
	        's' => PDO::PARAM_STR,
	        'l'	=> PDO::PARAM_LOB
	    ];
    
 		$placeholder_param_types = [];
 		$placeholders = [];
 		
		$query = preg_replace_callback('/([bnisl])\:(\w+)/', function ($b) use ($param_types, &$placeholder_param_types, &$placeholders) {
			$placeholder_param_types[$b[2]] = $param_types[$b[1]];
		    
		    $placeholders[] = $b[2];
		    
		    return '?';
		}, $statement);
		
		$statement = parent::prepare($query, $driver_options);
		$statement->placeholders = $placeholders;
		$statement->placeholder_param_types = $placeholder_param_types;
		
		return $statement;
	}
	
	public function exec($query) {
		$response = parent::exec($query);
	
		if (!$response) {
			return FALSE;
		}
	
		if (strpos(trim($query), 'INSERT') === 0) {
			return $this->lastInsertId();
		}
		
		return $response;
	}

    private function bind($b) {
		$this->binds[$b[2]]	= $this->data_types[$b[1]];

        return ':' . $b[2];
	}
}

class PDOStatement extends \PDOStatement {
	public $dbh,
		   $placeholder_param_types,
		   $placeholders;
	
  	protected function __construct(PDO $dbh) {
  		$this->dbh = $dbh;
    }
    
    public function nextRowset() {
 		if (!parent::nextRowset()) {
		    throw new PDOException('Rowset is not available.');
	    }
	    
	    return $this;
    }
    
	public function fetchAll ($how = null, $class_name = null, $ctor_args = null) {
		if ($how === PDO::FETCH_KEY_ASSOC) {
	    	$result = parent::fetchAll(PDO::FETCH_ASSOC);
	    	return array_combine(array_map('array_shift', $result), $result);
		} else {
			return call_user_func_array(['parent', 'fetchAll'], func_get_args());
		}
	}
    
	public function execute($parameters = []) {
		// it might be that the query is using question-mark binding,
		// in which case the input paramters will have numeric keys
		if (key($parameters) !== 0) {
			$parameters	= array_intersect_key($parameters, $this->placeholder_param_types);
			
			foreach ($this->placeholders as $i => $name) {
				if (!isset($parameters[$name])) {
					throw new \PDOException("Missing parameter '{$name}'.");
				}
			
				$this->bindValue($i + 1, $parameters[$name], $this->placeholder_param_types[$name]);
			}
			
			foreach ($parameters as $name => $value) {
				$this->bindValue($name, $value, $this->placeholder_param_types[$name]);
			}
			
			parent::execute();
		} else {
			if (!empty($this->placeholder_param_types)) {
				throw new \PDOException('Executing a prepared statement containing named placeholders using unnamed parameters.');
			}
		
			parent::execute($parameters);
		}
		
		if (strpos(trim($this->queryString), 'INSERT') === 0) {		
			return $this->dbh->lastInsertId();
		}

		return $this;
	}
	
	public function bindValue ($parameter, $value, $data_type = \PDO::PARAM_INT) {
		parent::bindValue($parameter, $value, $data_type);
		
		return $this;
	}
}

class PDOException extends \Exception {}