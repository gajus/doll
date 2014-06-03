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
     * @expectedException Gajus\Doll\Exception\InvalidArgumentException
     * @expectedExceptionMessage Prepared statement executed without values for all the placeholders.
     */
    public function testExecuteStatementWithoutAllTheValues () {
        $this->db
            ->prepare("SELECT :foo, :bar")
            ->execute(['foo' => 1]);
    }

    /**
     * @expectedException Gajus\Doll\Exception\InvalidArgumentException
     * @expectedExceptionMessage Prepared statement with named placeholders executed using list.
     */
    public function testExecuteStatementWithNamedPlaceholdersUsingList () {
        $this->db
            ->prepare("SELECT :foo, :bar")
            ->execute([1, 2]);
    }

    /**
     * @expectedException Gajus\Doll\Exception\InvalidArgumentException
     * @expectedExceptionMessage Prepared statement executed with undefined parameters.
     */
    public function testExecuteStatementWithUndefindedNamedParameters () {
        $this->db
            ->prepare("SELECT 1")
            ->execute(['bar' => 'test']);
    }
}