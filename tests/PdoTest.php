<?php
class PDOTest extends PHPUnit_Framework_TestCase {
    private
        $db;

    public function setUp () {
        $this->db = new \Gajus\Doll\PDO([
            'username' => 'travis',
            'database' => 'doll'
        ]);
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
            [\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION]
        ];
    }

    public function testPDOStatementIsDoll () {
        $sth = $this->db
            ->prepare("SELECT :foo");
        
        $this->assertInstanceOf('Gajus\Doll\PDOStatement', $sth);
    }

    public function testExecutedStatementReturnsStatement () {
        $sth = $this->db
            ->prepare("SELECT :foo")
            ->execute(['foo' => 1]);

        $this->assertInstanceOf('Gajus\Doll\PDOStatement', $sth);
    }

    /**
     * @expectedException PDOException
     * @expectedExceptionMessage SQLSTATE[HY093]: Invalid parameter number: parameter was not defined
     */
    public function testThrowPDOException () {
        $sth = $this->db
            ->prepare("SELECT :foo")
            ->execute(['bar' => 'test']);
    }

    /**
     * @expectedException Gajus\Doll\Exception\InvalidArgumentException
     * @expectedExceptionMessage You cannot bind multiple values to a single parameter. You cannot bind more values than specified.
     */
    public function testThrowDollException () {
        $sth = $this->db
            ->prepare("SELECT 1")
            ->execute(['bar' => 'test']);
    }
}