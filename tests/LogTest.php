<?php
class LogTest extends PHPUnit_Framework_TestCase {
    public function testDefaultToNoLogging () {
        $db = new \Gajus\Doll\PDO('mysql:dbname=test');
        $this->assertFalse($db->getAttribute(\Gajus\Doll\PDO::ATTR_LOGGING));
    }

    public function testEnableLogging () {
        $db = new \Gajus\Doll\PDO('mysql:dbname=test');
        $db->setAttribute(\Gajus\Doll\PDO::ATTR_LOGGING, true);
        $this->assertTrue($db->getAttribute(\Gajus\Doll\PDO::ATTR_LOGGING));
    }

    public function testLogEachStatementExecution () {
        $db = new \Gajus\Doll\PDO('mysql:dbname=test');
        $db->setAttribute(\Gajus\Doll\PDO::ATTR_LOGGING, true);

        $sth = $db->prepare("SELECT 1;");
        $sth->execute();
        $sth->execute();

        $log = $db->getLog();

        $this->assertCount(2, $log);
    }

    public function testDoNotLogStatementExecutionWhenLoggingIsNotEnabled () {
        $db = new \Gajus\Doll\PDO('mysql:dbname=test');
        $sth = $db->prepare("SELECT 1;");
        $sth->execute();
        $sth->execute();

        $log = $db->getLog();

        $this->assertCount(0, $log);
    }

    public function testDoNotLogNotExecutedStatement () {
        $db = new \Gajus\Doll\PDO('mysql:dbname=test');
        $sth = $db->prepare("SELECT 1;");
        $this->assertCount(0, $db->getLog());
    }

    public function testExecutedStatementBacktraceAlignment () {
        $db = new \Gajus\Doll\PDO('mysql:dbname=test');
        $db->setAttribute(\Gajus\Doll\PDO::ATTR_LOGGING, true);

        $sth = $db->prepare("SELECT 1;");

        $sth->execute(); $statement_execution_line = __LINE__;

        $log = $db->getLog();

        $this->assertCount(1, $log);
        $this->assertSame($statement_execution_line, $log[0]['backtrace']['line']);
    }

    public function testExecutedStatementParameterLogging () {
        $db = new \Gajus\Doll\PDO('mysql:dbname=test');
        $db->setAttribute(\Gajus\Doll\PDO::ATTR_LOGGING, true);

        $sth = $db->prepare("SELECT :foo;");
        $sth->execute(['foo' => 1]);

        $log = $db->getLog();

        $this->assertCount(1, $log);
        $this->assertSame(['foo' => 1], $log[0]['parameters']);
    }

    public function testExecutedStatementLogAlignmentWithProfiles () {
        $db = new \Gajus\Doll\PDO('mysql:dbname=test');
        $db->setAttribute(\Gajus\Doll\PDO::ATTR_LOGGING, true);

        for ($i = 0; $i < 200; $i++) {
            $db->query("/* {$i} */ SELECT 'a'");
        }

        $log = $db->getLog();

        $this->assertCount(200, $log);
        $this->assertSame($log[199]['statement'], $log[199]['query']);
        $this->assertSame("/* 199 */ SELECT 'a'", $log[199]['statement']);
    }

    public function testStatementExecutionTimeTracking () {
        $db = new \Gajus\Doll\PDO('mysql:dbname=test');
        $db->setAttribute(\Gajus\Doll\PDO::ATTR_LOGGING, true);

        $db->query("SELECT SLEEP(.2)");

        $log = $db->getLog();

        $this->assertSame(2, (int) round($log[0]['duration']/100000));
    }
}