<?php
class PdoTest extends PHPUnit_Framework_TestCase {
    public function testDefaultErrorHandlingIsException () {
        $db = new \Gajus\Doll\PDO('mysql:dbname=test');
        $this->assertSame(\PDO::ERRMODE_EXCEPTION, $db->getAttribute(\PDO::ATTR_ERRMODE));
    }

    public function testExecutedStatementReturnsStatement () {
        $db = new \Gajus\Doll\PDO('mysql:dbname=test');
        $sth = $db->prepare("SELECT :foo")->execute(['foo' => 1]);
        $this->assertInstanceOf('Gajus\Doll\PDOStatement', $sth);
    }

    public function testPdoStatementInheritance () {
        $db = new \Gajus\Doll\PDO('mysql:dbname=test');
        $sth = $db->prepare("SELECT :foo");
        $this->assertInstanceOf('Gajus\Doll\PDOStatement', $sth);
    }

    /**
     * @expectedException PDOException
     * @expectedExceptionCode HY093
     */
    public function testThrowPdoException () {
        $db = new \Gajus\Doll\PDO('mysql:dbname=test');
        $sth = $db->prepare("SELECT :foo");
        $sth->execute(['bar' => 'test']);
    }
}