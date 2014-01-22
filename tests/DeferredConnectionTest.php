<?php
class DeferredConnectionTest extends PHPUnit_Framework_TestCase {
    public function testDeferredConnectionNotConnectedUponConstruction () {
        $db = new \gajus\doll\PDO('mysql:dbname=test');

        $this->assertFalse($db->isInitialized());
    }

    /*public function testDeferredConnectionNotConnectedAfterStatement () {
        $db = new \gajus\doll\PDO('mysql:dbname=test');

        $db->prepare("SELECT 1;");

        $this->assertFalse($db->isInitialized());
    }*/

    public function testConnectedAfterStatementExecution () {
        $db = new \gajus\doll\PDO('mysql:dbname=test');

        $sth = $db->prepare("SELECT 1;");
        $sth->execute();

        $this->assertTrue($db->isInitialized());
    }

    public function testConnectedAfterQuery () {
        $db = new \gajus\doll\PDO('mysql:dbname=test');

        $db->query("SELECT 1;");

        $this->assertTrue($db->isInitialized());
    }

    public function testConnectedAfterExec () {
        $db = new \gajus\doll\PDO('mysql:dbname=test');

        $db->exec("SELECT 1;");

        $this->assertTrue($db->isInitialized());
    }

    public function testConnectedAfterBeginTransaction () {
        $db = new \gajus\doll\PDO('mysql:dbname=test');

        $db->beginTransaction();

        $this->assertTrue($db->isInitialized());
    }
}