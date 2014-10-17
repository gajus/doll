<?php
class PDOTest extends PHPUnit_Framework_TestCase {
    private
        $db;

    public function setUp () {
        $this->db = new \Gajus\Doll\PDO(new \Gajus\Doll\DataSource($_ENV['dsn']));
    }

    /**
     * @dataProvider defaultAttributeProvider
     */
    public function testDefaultAttribute ($attribute, $value) {
        $this->assertSame($value, $this->db->getAttribute($attribute));
    }

    public function defaultAttributeProvider () {
        return [
            #[\PDO::ATTR_STRINGIFY_FETCHES, false],
            #[\PDO::ATTR_EMULATE_PREPARES, false],
            [\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION],
            [\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC]
        ];
    }
}