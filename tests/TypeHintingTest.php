<?php
class TypeHintingTest extends PHPUnit_Framework_TestCase {
    private
        $db;

    public function setUp () {
        $this->db = new \Gajus\Doll\PDO(new \Gajus\Doll\DataSource($_ENV['dsn']));
    }

    /**
     * @dataProvider typeHintProvider
     */
    public function testTypeHint ($name, $value) {
        $sth = $this->db->prepare("SELECT {$name}:foo");

        $reflection = new ReflectionProperty($sth, 'placeholders');
        $reflection->setAccessible(true);
        $placeholders = $reflection->getValue($sth);

        $this->assertSame($value, $placeholders[0]['type']);
    }

    public function typeHintProvider () {
        return [
            ['', PDO::PARAM_STR],
            ['b', PDO::PARAM_BOOL],
            ['n', PDO::PARAM_NULL],
            ['i', PDO::PARAM_INT],
            ['s', PDO::PARAM_STR],
            ['l', PDO::PARAM_LOB]
        ];
    }

    /**
     * @dataProvider inferredTypeHintingProvider
     */
    public function testInferredTypeHinting ($parameter_name) {
        $sth = $this->db->prepare("SELECT :{$parameter_name}");

        $reflection = new ReflectionProperty($sth, 'placeholders');
        $reflection->setAccessible(true);
        $placeholders = $reflection->getValue($sth);

        $this->assertSame($placeholders[0]['type'], PDO::PARAM_INT);
    }

    public function inferredTypeHintingProvider () {
        return [
            ['id'],
            ['foo_id']
        ];
    }
}