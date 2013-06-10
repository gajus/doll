<?php
namespace ay\pdo;

class PDO extends \PDO {
	const FETCH_KEY_ASSOC = 'ay0';

    public function __construct($dsn, $username = null, $password = null, array $driver_options = []) {    
		parent::__construct($dsn, $username, $password, $driver_options);
		
	    $this->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		$this->setAttribute(\PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		$this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['ay\pdo\Pdo_Statement', [$this]]);
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