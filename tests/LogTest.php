<?php
class LogTest extends PHPUnit_Framework_TestCase {
    private
        $db;

    public function setUp () {
        $this->db = new \Gajus\Doll\PDO(new \Gajus\Doll\DataSource([
            'username' => 'travis',
            'database' => 'doll'
        ]));
    }

    public function testDefaultToNoLogging () {
        $this->assertFalse($this->db->getAttribute(\Gajus\Doll\PDO::ATTR_LOGGING));
    }

    public function testEnableLogging () {
        $this->db->setAttribute(\Gajus\Doll\PDO::ATTR_LOGGING, true);

        $this->assertTrue($this->db->getAttribute(\Gajus\Doll\PDO::ATTR_LOGGING));
    }

    public function testLogEachStatementExecution () {
        $this->db->setAttribute(\Gajus\Doll\PDO::ATTR_LOGGING, true);

        $sth = $this->db->prepare("SELECT 1");

        $sth->execute();
        $sth->execute();

        $log = $this->db->getLog();

        $this->assertCount(2, $log);
    }

    public function testDoNotLogStatementExecutionWhenLoggingIsNotEnabled () {
        $sth = $this->db->prepare("SELECT 1");

        $sth->execute();
        $sth->execute();

        $log = $this->db->getLog();

        $this->assertCount(0, $log);
    }

    public function testDoNotLogNotExecutedStatement () {
        $sth = $this->db->prepare("SELECT 1");

        $this->assertCount(0, $this->db->getLog());
    }

    public function testExecutedStatementBacktraceAlignment () {
        $this->db->setAttribute(\Gajus\Doll\PDO::ATTR_LOGGING, true);

        $sth = $this->db->prepare("SELECT 1");

        $sth->execute(); $statement_execution_line = __LINE__;

        $log = $this->db->getLog();

        $this->assertCount(1, $log);
        $this->assertSame($statement_execution_line, $log[0]['backtrace']['line']);
    }

    public function testExecutedStatementParameterLogging () {
        $this->db->setAttribute(\Gajus\Doll\PDO::ATTR_LOGGING, true);

        $sth = $this->db->prepare("SELECT :foo");
        $sth->execute(['foo' => 1]);

        $log = $this->db->getLog();

        $this->assertCount(1, $log);
        $this->assertSame(['foo' => 1], $log[0]['parameters']);
    }

    public function testExecutedStatementLogAlignmentWithProfiles () {
        $this->db->setAttribute(\Gajus\Doll\PDO::ATTR_LOGGING, true);

        for ($i = 0; $i < 200; $i++) {
            $this->db->query("/* {$i} */ SELECT 'a'");
        }

        $log = $this->db->getLog();

        $this->assertCount(200, $log);
        $this->assertSame($log[199]['statement'], $log[199]['query']);
        $this->assertSame("/* 199 */ SELECT 'a'", $log[199]['statement']);
    }

    public function testStatementExecutionTimeTracking () {
        $this->db->setAttribute(\Gajus\Doll\PDO::ATTR_LOGGING, true);

        $this->db->query("SELECT SLEEP(.2)");

        $log = $this->db->getLog();

        $this->assertSame(2, (int) round($log[0]['duration']/100000));
    }
}