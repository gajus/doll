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
    public function testTypeHint ($parameter_marker_name, $value) {
        $sth = $this->db->prepare("SELECT {$parameter_marker_name}:foo");

        $reflection = new ReflectionProperty($sth, 'named_parameter_markers');
        $reflection->setAccessible(true);
        $named_parameter_markers = $reflection->getValue($sth);

        $this->assertSame($value, $named_parameter_markers[0]['type']);
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
    public function testInferredTypeHinting ($parameter_marker_name) {
        $sth = $this->db->prepare("SELECT :{$parameter_marker_name}");

        $reflection = new ReflectionProperty($sth, 'named_parameter_markers');
        $reflection->setAccessible(true);
        $named_parameter_markers = $reflection->getValue($sth);

        $this->assertSame($named_parameter_markers[0]['type'], PDO::PARAM_INT);
    }

    public function inferredTypeHintingProvider () {
        return [
            ['id'],
            ['foo_id']
        ];
    }
}