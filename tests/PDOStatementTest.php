<?php
class PDOStatementTest extends PHPUnit_Framework_TestCase {
    private
        $db;

    public function setUp () {
        $this->db = new \Gajus\Doll\PDO(new \Gajus\Doll\DataSource($_ENV['dsn']));
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
     * @expectedExceptionMessage Prepared statement executed without values for all the parameter markers.
     */
    public function testExecuteStatementWithoutAllTheValues () {
        $this->db
            ->prepare("SELECT :foo, :bar")
            ->execute(['foo' => 1]);
    }

    /**
     * @expectedException Gajus\Doll\Exception\InvalidArgumentException
     * @expectedExceptionMessage Prepared statement with named parameter markers executed using list.
     */
    public function testExecuteStatementWithNamedParameterMarkersUsingList () {
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