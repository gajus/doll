<?php
namespace Gajus\Doll;

/**
 * @link https://github.com/gajus/doll for the canonical source repository
 * @license https://github.com/gajus/doll/blob/master/LICENSE BSD 3-Clause
 */
class DataSource {
    static private
        /**
         * @var array
         */
        $default_data_source = [
            'host' => null,
            'port' => null,
            'unix_socket' => null,
            'driver' => 'mysql',
            'database' => null,
            'user' => null,
            'password' => null,
            'charset' => 'utf8',
            'driver_options' => []
        ];

    private
        /**
         * Definition of the data source.
         *
         * @var array
         */
        $data_source = [];

    /**
     * @param array $data_source
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\LogicException
     */
    public function __construct (array $data_source = []) {
        if (array_diff_key($data_source, static::$default_data_source)) {
            throw new Exception\InvalidArgumentException('Unrecognized database source parameter.');
        }

        $this->data_source = $data_source + array_filter(static::$default_data_source);

        if (isset($this->data_source['host'], $this->data_source['unix_socket'])) {
            throw new Exception\LogicException('"host" and "unix_socket" database source parameters cannot be used together.');
        }

        if (!isset($this->data_source['host']) && isset($this->data_source['port'])) {
            throw new Exception\LogicException('"port" database source parameter cannot be used without "host".');
        }
    }

    /**
     * Generate the connection string.
     *
     * @return string
     */
    public function getDSN () {
        $dsn = [];

        // Pick $data_source parameters that make up the DSN string.
        $map = [
            'host' => 'host',
            'port' => 'port',
            'dbname' => 'database',
            'unix_socket' => 'unix_socket',
            'charset' => 'charset'
        ];

        foreach ($map as $dsn_name => $data_source_parameter_name) {
            if (isset($this->data_source[$data_source_parameter_name])) {
                $dsn[] = $dsn_name . '=' . $this->data_source[$data_source_parameter_name];
            }
        }

        if ($dsn) {
            $dsn = $this->data_source['driver'] . ':' . implode(';', $dsn);
        } else {
            $dsn = $this->data_source['driver'];
        }

        return $dsn;
    }

    /**
     * @return null|string
     */
    public function getUser () {
        return isset($this->data_source['user']) ? $this->data_source['user'] : null;
    }

    /**
     * @array null|string
     */
    public function getPassword () {
        return isset($this->data_source['password']) ? $this->data_source['password'] : null;
    }

    /**
     * @return array
     */
    public function getDriverOptions () {
        return isset($this->data_source['driver_options']) ? $this->data_source['driver_options'] : [];
    }
}
