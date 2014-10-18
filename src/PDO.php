<?php
namespace Gajus\Doll;

/**
 * @link https://github.com/gajus/doll for the canonical source repository
 * @license https://github.com/gajus/doll/blob/master/LICENSE BSD 3-Clause
 */
class PDO extends \PDO {
    const ATTR_LOGGING = 'Gajus\Doll\1';
    const ATTR_INFERRED_TYPE_HINTING = 'Gajus\Doll\2';

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
         * Values for attributes defined in the Gajus\Doll\PDO class.
         * 
         * @var array
         */
        $custom_attributes = [],
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

        $this->setAttribute(\Gajus\Doll\PDO::ATTR_INFERRED_TYPE_HINTING, true);
        $this->setAttribute(\Gajus\Doll\PDO::ATTR_LOGGING, false);
    }

    /**
     * @return boolean Indicates whether PDO has connected to the database.
     */
    public function isConnected () {
        return $this->is_connected;
    }

    /**
     * When setAttribute is used prior to connecting to the database, the attribute is logged.
     * When setAttribute is used after connecting to the database, the attribute is applied.
     * The logged attributes are automatically applied after connecting to the database.
     * 
     * @param string $attribute
     * @param mixed $value
     */
    public function setAttribute ($attribute, $value) {
        // @see https://github.com/gajus/doll/issues/16
        if ($attribute === \PDO::ATTR_ERRMODE) {
            throw new Exception\InvalidArgumentException('Doll does not allow to change PDO::ATTR_ERRMODE.');
        }

        // @see
        if ($attribute === \PDO::ATTR_STATEMENT_CLASS) {
            throw new Exception\InvalidArgumentException('Doll does not allow to change PDO::ATTR_STATEMENT_CLASS.');
        }

        if ($attribute === \Gajus\Doll\PDO::ATTR_INFERRED_TYPE_HINTING) {
            $this->custom_attributes[$attribute] = !!$value;

            return;
        }
        
        if ($attribute === \Gajus\Doll\PDO::ATTR_LOGGING) {
            if ($this->isConnected()) {
                throw new Exception\RuntimeException('Cannot change Gajus\Doll\PDO::ATTR_LOGGING value after connection is established.');
            }

            $this->custom_attributes[$attribute] = !!$value;

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
        if ($attribute === \Gajus\Doll\PDO::ATTR_LOGGING || $attribute === \Gajus\Doll\PDO::ATTR_INFERRED_TYPE_HINTING) {
            return $this->custom_attributes[$attribute];
        }

        $this->connect();

        return parent::getAttribute($attribute);
    }

    /**
     * @param string $statement
     * @param array $driver_options
     * @return Gajus\Doll\PDOStatement
     */
    public function prepare ($query_string, $driver_options = []) {
        $this->connect();

        $this->on('prepare', $query_string);

        $param_types = [
            'b' => \PDO::PARAM_BOOL,
            'n' => \PDO::PARAM_NULL,
            'i' => \PDO::PARAM_INT,
            's' => \PDO::PARAM_STR,
            'l' => \PDO::PARAM_LOB
        ];

        $named_parameter_markers = [];
        
        $query_string_with_question_mark_parameter_markers = preg_replace_callback('/([bnisl]?)\:(\w+)/', function ($b) use ($param_types, &$named_parameter_markers) {
            $parameter_marker = [
                'name' => $b[2]
            ];

            if (strtolower($parameter_marker['name']) !== $parameter_marker['name']) {
                throw new Exception\InvalidArgumentException('Parameter marker names must be lowercase.');
            }

            if ($b[1]) {
                $parameter_marker['type'] = $param_types[$b[1]];
            } else if ($this->getAttribute(\Gajus\Doll\PDO::ATTR_INFERRED_TYPE_HINTING)) {
                if ($parameter_marker['name'] === 'id' || mb_substr($parameter_marker['name'], -3) === '_id') {
                    $parameter_marker['type'] = \PDO::PARAM_INT;
                }
            }

            if (!isset($parameter_marker['type'])) {
                $parameter_marker['type'] = \PDO::PARAM_STR;
            }

            $named_parameter_markers[] = $parameter_marker;

            return '?';
        }, $query_string);

        $statement = parent::prepare($query_string_with_question_mark_parameter_markers, $driver_options);
        $statement->setParameterMarkerNames($query_string, $named_parameter_markers);

        return $statement;
    }

    public function exec ($statement) {
        $this->connect();

        $execution_wall_time = -microtime(true);
    
        $response = parent::exec($statement);

        $this->on('exec', $statement, $execution_wall_time + microtime(true));

        return $response;
    }

    /**
     * @see https://github.com/gajus/doll/issues/15
     * @see https://github.com/gajus/doll/issues/14
     * @return PDOStatement
     */
    public function query ($statement) {
        $this->connect();

        $execution_wall_time = -microtime(true);

        if (func_num_args() > 1) {
            throw new Exception\BadMethodCallException('Method does not expect the additional parameters.');
        }

        $response = parent::query($statement);

        $this->on('query', $statement, $execution_wall_time + microtime(true));

        return $response;
    }

    public function beginTransaction () {
        $this->connect();

        $execution_wall_time = -microtime(true);
    
        $response = parent::beginTransaction();

        $this->on('beginTransaction', 'START TRANSACTION', $execution_wall_time + microtime(true));

        return $response;
    }
    
    public function commit () {
        $this->connect();

        $execution_wall_time = -microtime(true);

        $response = parent::commit();

        $this->on('commit', 'COMMIT', $execution_wall_time + microtime(true));
        
        return $response;
    }
        
    /**
     * @return bool
     */
    public function rollBack () {
        $this->connect();

        $execution_wall_time = -microtime(true);

        $response = parent::rollBack();

        $this->on('rollBack', 'ROLLBACK', $execution_wall_time + microtime(true));

        $this->register($event);

        return $response;
    }

    /**
     * This has to be public since it is accessed by the instance of \Gajus\Doll\PDOStatement.
     *
     * @param string $method Method used to execute the query: exec, prepare/execute, query, including beginTransaction, commit and rollBack.
     * @param string $statement The query or prepared statement.
     * @param float $execution_wall_time Statement execution time calculated using microtime.
     * @param array $parameters The parameters used to execute a prepared statement.
     * @return null
     */
    public function on ($method, $statement, $execution_wall_time = null, array $parameters = []) {
        if ($method === 'prepare' || !$this->getAttribute(\Gajus\Doll\PDO::ATTR_LOGGING)) {
            return;
        }

        $statement = trim(preg_replace('/\s+/', ' ', str_replace("\n", ' ', $statement)));
        $backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

        $this->log[] = [
            'statement' => $statement,
            'parameters' => $parameters,
            'execution_wall_time' => $execution_wall_time,
            'backtrace' => $backtrace
        ];
    
        if (count($this->log) % 100 === 0) {
            $this->applyProfileData();
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

        parent::setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        parent::setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        parent::setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['Gajus\Doll\PDOStatement', [$this]]);
        parent::setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        $this->is_connected = true;

        foreach ($this->attributes as $attribute => $value) {
            $this->setAttribute($attribute, $value);
        }

        if ($this->getAttribute(\Gajus\Doll\PDO::ATTR_LOGGING)) {
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
        
        // http://dev.mysql.com/doc/refman/5.7/en/show-profiles.html
        // "These statements are deprecated and will be removed in a future MySQL release. Use the Performance Schema instead; see Chapter 21, MySQL Performance Schema."
        // However, information_schema does not give access to the original query via the query_id.
        
        #$queries = \PDO::query("SELECT * FROM `information_schema`.`profiling` ORDER BY `query_id`, `seq`")
        #    ->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($queries as $q) {
            // The original query is executed using parent:: method (not in the log).
            if ($q['Query'] === 'SET `profiling_history_size` = 100') {
                continue;
            }

            $this->log[$q['Query_ID'] - 2]['execution_duration'] = $q['Duration'];
            $this->log[$q['Query_ID'] - 2]['execution_overhead'] = $this->log[$q['Query_ID'] - 2]['execution_wall_time'] - $q['Duration'];
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