<?php
namespace Gajus\Doll;

/**
 * @link https://github.com/gajus/doll for the canonical source repository
 * @license https://github.com/gajus/doll/blob/master/LICENSE BSD 3-Clause
 */
class DSNConstructor {
    static private
        /**
         * @var array
         */
        $default_database_source = [
            'host' => null,
            'port' => null,
            'unix_socket' => null,
            'driver' => 'mysql',
            'database_name' => null,
            'username' => null,
            'password' => null,
            'charset' => 'utf8',
            #'collation' => 'utf8_general_ci',
            'driver_options' => []
        ];

    private
        /**
         * @var array
         */
        $database_source = [];

    /** 
     * @param array $database_source
     */
    public function __construct (array $database_source = []) {
        if (array_diff_key($database_source, static::$default_database_source)) {
            throw new Exception\InvalidArgumentException('Unrecognized database source parameter.');
        }

        $this->database_source = $database_source + array_filter(static::$default_database_source);

        if (isset($this->database_source['host'], $this->database_source['unix_socket'])) {
            throw new Exception\LogicException('"host" and "unix_socket" database source parameters cannot be used together.');
        }

        if (!isset($this->database_source['host']) && isset($this->database_source['port'])) {
            throw new Exception\LogicException('"port" database source parameter cannot be used without "host".');
        }
    }

    /**
     * Constructor parameters and generate the connection string.
     * 
     * @return string
     */
    public function getDSN () {
        $dsn = [];

        // Pick $data_source parameters that make up the DSN string.
        $map = [
            'host' => 'host',
            'port' => 'port',
            'dbname' => 'database_name',
            'unix_socket' => 'unix_socket',
            'charset' => 'charset'
        ];

        foreach ($map as $dsn_name => $data_source_parameter_name) {
            if (isset($this->database_source[$data_source_parameter_name])) {
                $dsn[] = $dsn_name . '=' . $this->database_source[$data_source_parameter_name];
            }
        }

        if ($dsn) {
            $dsn = $this->database_source['driver'] . ':' . implode(';', $dsn);
        } else {
            $dsn = $this->database_source['driver'];
        }

        return $dsn;
    }

    public function getUsername () {
        return isset($this->database_source['username']) ? $this->database_source['username'] : null;
    }

    public function getPassword () {
        return isset($this->database_source['password']) ? $this->database_source['password'] : null;
    }

    public function getDriverOptions () {
        return isset($this->database_source['driver_options']) ? $this->database_source['driver_options'] : [];
    }

    #public function createConnection () {
    #}
}