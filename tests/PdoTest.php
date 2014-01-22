<?php
class PdoTest extends PHPUnit_Framework_TestCase {
    public function testDefaultErrorHandlingIsException () {
        $db = new \gajus\doll\PDO('mysql:dbname=test');
        $this->assertSame(PDO::ERRMODE_EXCEPTION, $db->getAttribute(PDO::ATTR_ERRMODE));
    }

    #public function testDefaultEmulatePrepares () {
    #    $db = new \gajus\doll\PDO('mysql:dbname=test');
    #    $this->assertSame(false, $db->getAttribute(\PDO::ATTR_EMULATE_PREPARES));
    #}

    public function testExecutedStatementReturnsStatement () {
        $db = new \gajus\doll\PDO('mysql:dbname=test');
        $sth = $db->prepare("SELECT :foo")->execute(['foo' => 1]);
        $this->assertInstanceOf('gajus\doll\PDOStatement', $sth);
    }

    public function testPdoStatementInheritance () {
        $db = new \gajus\doll\PDO('mysql:dbname=test');
        $sth = $db->prepare("SELECT :foo");
        $this->assertInstanceOf('gajus\doll\PDOStatement', $sth);
    }

    /**
     * @expectedException PDOException
     * @expectedExceptionCode HY093
     */
    public function testThrowPdoException () {
        $db = new \gajus\doll\PDO('mysql:dbname=test');
        $sth = $db->prepare("SELECT :foo");
        $sth->execute(['bar' => 'test']);
    }
}