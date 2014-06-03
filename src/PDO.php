<?php
namespace Gajus\Doll;

/**
 * @link https://github.com/gajus/doll for the canonical source repository
 * @license https://github.com/gajus/doll/blob/master/LICENSE BSD 3-Clause
 */
class PDO extends \PDO {
    const ATTR_LOGGING = 'Gajus\Doll\1';

    private
        /**
         * @var boolean
         */
        $is_connected = false,
        /**
         * @var DataSource
         */
        $data_source,
        /**
         * Database attributes that were set before database connection is established.
         * 
         * @var array
         */
        $attributes = [],
        /**
         * @var boolean
         */
        $logging = false,
        /**
         * @var array
         */
        $log = [];

    /**
     * The constructor does not 
     * 
     * @param array $constructor
     */
    public function __construct (\Gajus\Doll\DataSource $data_source) {
        $this->data_source = $data_source;
    }

    /**
     * @return boolean Indicates whether PDO has connected to the database.
     */
    public function isConnected () {
        return $this->is_connected;
    }

    /**
     * Logs database handle attributes that are set before PDO is constructed.
     * 
     * @param string $attribute
     * @param mixed $value
     */
    public function setAttribute ($attribute, $value) {
        if ($attribute === \PDO::ATTR_ERRMODE) {
            throw new Exception\InvalidArgumentException('Doll does not allow to change PDO::ATTR_ERRMODE.');
        }

        if ($attribute === \Gajus\Doll\PDO::ATTR_LOGGING) {
            if ($this->isConnected()) {
                throw new Exception\RuntimeException('Cannot change Gajus\Doll\PDO::ATTR_LOGGING value after connection is established.');
            }

            if (!is_bool($value)) {
                throw new Exception\InvalidArgumentException('Parameter value is not boolean.');
            }

            $this->logging = $value;

            return;
        }

        if (!$this->isConnected()) {
            $this->attributes[$attribute] = $value;
        } else {
            parent::setAttribute($attribute, $value);
        }
    }

    /**
     * @param int $attribute
     * @return mixed
     */
    public function getAttribute ($attribute) {
        if ($attribute === \gajus\doll\PDO::ATTR_LOGGING) {
            return $this->logging;
        }

        $this->connect();

        return parent::getAttribute($attribute);
    }

    /**
     * @param string $statement
     * @param array $driver_options
     * @return Gajus\Doll\PDOStatement
     */
    public function prepare ($statement, $driver_options = []) {
        $this->on('prepare', $statement);

        $param_types = [
            'b' => \PDO::PARAM_BOOL,
            'n' => \PDO::PARAM_NULL,
            'i' => \PDO::PARAM_INT,
            's' => \PDO::PARAM_STR,
            'l' => \PDO::PARAM_LOB
        ];

        $placeholders = [];
        
        $query = preg_replace_callback('/([bnisl]?)\:(\w+)/', function ($b) use ($param_types, &$placeholders) {
            
            $placeholders[] = [
                'name' => $b[2],
                'type' => $b[1] ? $param_types[$b[1]] : \PDO::PARAM_STR
            ];

            return '?';
        }, $statement);

        $statement = parent::prepare($query, $driver_options);
        $statement->setPlaceholders($placeholders);

        return $statement;
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
        
        return call_user_func_array(['parent', 'query'], $args);
    }

    public function beginTransaction () {
        $this->on('beginTransaction', 'START TRANSACTION');
    
        return parent::beginTransaction();
    }
    
    public function commit () {
        $this->on('commit', 'COMMIT');
        
        return parent::commit();
    }
        
    /**
     * @return bool
     */
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
     * @return null
     */
    public function on ($method, $statement, array $parameters = []) {
        $this->connect();

        if ($method !== 'prepare' && $this->logging) {
            $statement = trim(preg_replace('/\s+/', ' ', str_replace("\n", ' ', $statement)));
            $backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

            $this->log[] = [
                'statement' => $statement,
                'parameters' => $parameters,
                'backtrace' => $backtrace
            ];
        
            if (count($this->log) % 100 === 0) {
                $this->applyProfileData();
            }
        }
    }

    /**
     * Connect to the database using the constructor parameter and attributes
     * that were collected prior to triggering connection to the database.
     * 
     * @return null
     */
    private function connect () {
        if ($this->isConnected()) {
            return;
        }

        parent::__construct(
            $this->data_source->getDSN(),
            $this->data_source->getUsername(),
            $this->data_source->getPassword(),
            $this->data_source->getDriverOptions()
        );

        parent::setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        parent::setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        parent::setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        parent::setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['Gajus\Doll\PDOStatement', [$this]]);

        $this->is_connected = true;

        foreach ($this->attributes as $attribute => $value) {
            $this->setAttribute($attribute, $value);
        }

        if ($this->logging) {
            parent::exec('SET `profiling` = 1');
            parent::exec('SET `profiling_history_size` = 100');
        }
    }

    /**
     * Apply data from "SHOW PROFILES;" to the respective queries in the $log.
     *
     * @return void
     */
    private function applyProfileData () {
        if (!$this->isConnected() || !$this->log) {
            return;
        }

        $queries = \PDO::query("SHOW PROFILES")
            ->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($queries as $q) {
            // The original query is executed using parent:: method (not in the log).
            if ($q['Query'] === 'SET `profiling_history_size` = 100') {
                continue;
            }

            $this->log[$q['Query_ID'] - 2]['duration'] = 1000000 * $q['Duration'];
            $this->log[$q['Query_ID'] - 2]['query'] = $q['Query'];
        }
    }

    /**
     * @return array
     */
    public function getLog () {
        $this->applyProfileData();

        return $this->log;
    }
}