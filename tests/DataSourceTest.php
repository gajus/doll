<?php
class DataSourceTest extends PHPUnit_Framework_TestCase {
    public function testDefaultDSN () {
        $data_source = new \Gajus\Doll\DataSource();

        $dsn = $data_source->getDSN();

        $this->assertSame('mysql:charset=utf8', $dsn);
    }

    /**
     * @dataProvider explicitDSNProvider
     */
    public function testExplicitDSN ($input_data_source, $output_expected_dsn) {
        $data_source = new \Gajus\Doll\DataSource($input_data_source);

        $dsn = $data_source->getDSN();

        $this->assertSame($output_expected_dsn, $dsn);
    }

    public function explicitDSNProvider () {
        return [
            [
                [
                    'host' => '127.0.0.1',
                    'port' => 3306,
                    'database' => 'foo',
                    'charset' => 'utf8'
                ],
                'mysql:host=127.0.0.1;port=3306;dbname=foo;charset=utf8'
            ],
            [
                [
                    'unix_socket' => 'var',
                    'database' => 'foo',
                    'charset' => 'utf8'
                ],
                'mysql:dbname=foo;unix_socket=var;charset=utf8'
            ]
        ];
    }

    /**
     * @dataProvider getUsernameProvider
     */
    public function testGetUsername ($input_data_source, $output_username) {
        $data_source = new \Gajus\Doll\DataSource($input_data_source);

        $username = $data_source->getUsername();

        $this->assertSame($output_username, $username);
    }

    public function getUsernameProvider () {
        return [
            [
                [
                    'username' => 'foo'
                ],
                'foo'
            ],
            [
                [],
                null
            ]
        ];
    }

    /**
     * @dataProvider getPasswordProvider
     */
    public function testGetPassword ($input_data_source, $output_password) {
        $data_source = new \Gajus\Doll\DataSource($input_data_source);

        $password = $data_source->getPassword();

        $this->assertSame($output_password, $password);
    }

    public function getPasswordProvider () {
        return [
            [
                [
                    'password' => 'bar'
                ],
                'bar'
            ],
            [
                [],
                null
            ]
        ];
    }

    /**
     * @dataProvider getDriverOptionsProvider
     */
    public function testGetDriverOptions ($input_data_source, $output_data_source) {
        $data_source = new \Gajus\Doll\DataSource($input_data_source);

        $driver_options = $data_source->getDriverOptions();

        $this->assertSame($output_data_source, $driver_options);   
    }

    public function getDriverOptionsProvider () {
        return [
            [
                [
                    'driver_options' => [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]
                ],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]
            ],
            [
                [],
                []
            ]
        ];
    }

    /**
     * @expectedException Gajus\Doll\Exception\InvalidArgumentException
     * @expectedExceptionMessage Unrecognized database source parameter.
     */
    public function testInvalidParameter () {
        new \Gajus\Doll\DataSource([
            'foo' => 'bar'
        ]);
    }

    /**
     * @expectedException Gajus\Doll\Exception\LogicException
     * @expectedExceptionMessage "host" and "unix_socket" database source parameters cannot be used together.
     */
    public function testHostUnixSocketCannotBeUserTogether () {
        new \Gajus\Doll\DataSource([
            'host' => '127.0.0.1',
            'unix_socket' => '/var'
        ]);
    }

    /**
     * @expectedException Gajus\Doll\Exception\LogicException
     * @expectedExceptionMessage "port" database source parameter cannot be used without "host".
     */
    public function testPortCannotBeUsedWithoutHost () {
        new \Gajus\Doll\DataSource([
            'port' => '3306'
        ]);
    }

    public function testCreatePDO () {
        $dsn = $_ENV['dsn'];
        $dsn['driver_options'] = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ];

        $data_source = new \Gajus\Doll\DataSource($dsn);

        $dbh = $data_source->constructPDO('PDO');

        $this->assertInstanceOf('PDO', $dbh);

        $this->assertSame(\PDO::ERRMODE_EXCEPTION, $dbh->getAttribute(\PDO::ATTR_ERRMODE));
        $this->assertSame('doll', $dbh->query("SELECT DATABASE()")->fetch(\PDO::FETCH_COLUMN));
    }
}