<?php
class DSNConstructorTest extends PHPUnit_Framework_TestCase {
    public function testDefaultDSN () {
        $dsn_constructor = new \Gajus\Doll\DSNConstructor();

        $dsn = $dsn_constructor->getDSN();

        $this->assertSame('mysql:charset=utf8', $dsn);
    }

    /**
     * @dataProvider explicitDSNProvider
     */
    public function testExplicitDSN ($input_data_source, $output_expected_dsn) {
        $dsn_constructor = new \Gajus\Doll\DSNConstructor($input_data_source);

        $dsn = $dsn_constructor->getDSN();

        $this->assertSame($output_expected_dsn, $dsn);
    }

    public function explicitDSNProvider () {
        return [
            [
                [
                    'host' => '127.0.0.1',
                    'port' => 3306,
                    'database_name' => 'foo',
                    'charset' => 'utf8'
                ],
                'mysql:host=127.0.0.1;port=3306;dbname=foo;charset=utf8'
            ],
            [
                [
                    'unix_socket' => 'var',
                    'database_name' => 'foo',
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
        $dsn_constructor = new \Gajus\Doll\DSNConstructor($input_data_source);

        $username = $dsn_constructor->getUsername();

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
        $dsn_constructor = new \Gajus\Doll\DSNConstructor($input_data_source);

        $password = $dsn_constructor->getPassword();

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
        $dsn_constructor = new \Gajus\Doll\DSNConstructor($input_data_source);

        $driver_options = $dsn_constructor->getDriverOptions();

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
        new \Gajus\Doll\DSNConstructor([
            'foo' => 'bar'
        ]);
    }

    /**
     * @expectedException Gajus\Doll\Exception\LogicException
     * @expectedExceptionMessage "host" and "unix_socket" database source parameters cannot be used together.
     */
    public function testHostUnixSocketCannotBeUserTogether () {
        new \Gajus\Doll\DSNConstructor([
            'host' => '127.0.0.1',
            'unix_socket' => '/var'
        ]);
    }

    /**
     * @expectedException Gajus\Doll\Exception\LogicException
     * @expectedExceptionMessage "port" database source parameter cannot be used without "host".
     */
    public function testPortCannotBeUsedWithoutHost () {
        new \Gajus\Doll\DSNConstructor([
            'port' => '3306'
        ]);
    }
}