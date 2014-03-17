<?php
class PdoStatementTest extends PHPUnit_Framework_TestCase {
    /**
     * @expectedException Gajus\Doll\Exception\InvalidArgumentException
     * @expectedExceptionMessage You cannot bind multiple values to a single parameter. You cannot bind more values than specified.
     */
    public function testDefaultErrorHandlingIsException () {
        $db = new \Gajus\Doll\PDO('mysql:dbname=test');
        $db
            ->prepare("SELECT 1")
            ->execute(['foo' => 'bar']);
    }
}