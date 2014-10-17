<?php
class DeferredConnectionTest extends PHPUnit_Framework_TestCase {
    private
        $db;

    public function setUp () {
        $this->db = new \Gajus\Doll\PDO(new \Gajus\Doll\DataSource($_ENV['dsn']));
    }

    public function testDeferredConnectionNotConnectedUponConstruction () {
        $this->assertFalse($this->db->isConnected());
    }

    public function testConnectedAfterStatementExecution () {
        $sth = $this->db->prepare("SELECT 1;");
        $sth->execute();

        $this->assertTrue($this->db->isConnected());
    }

    public function testConnectedAfterQuery () {
        $this->db->query("SELECT 1;");

        $this->assertTrue($this->db->isConnected());
    }

    public function testConnectedAfterExec () {
        $this->db->exec("SELECT 1;");

        $this->assertTrue($this->db->isConnected());
    }

    public function testConnectedAfterBeginTransaction () {
        $this->db->beginTransaction();

        $this->assertTrue($this->db->isConnected());
    }
}