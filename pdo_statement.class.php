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
					if (array_key_exists($name, $parameters) && $parameters[$name] === null) {
						$this->bindValue($i + 1, null, PDO::PARAM_NULL);
					} else {
						throw new \PDOException("Missing parameter '{$name}'.");
					}
				} else {
					$this->bindValue($i + 1, $parameters[$name], $this->placeholder_param_types[$name]);
				}
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