<?php
class PDOTest extends PHPUnit_Framework_TestCase {
    private
        $db;

    public function setUp () {
        $this->db = new \Gajus\Doll\PDO(new \Gajus\Doll\DataSource($_ENV['dsn']));
    }

    /**
     * @see https://github.com/gajus/doll/issues/15
     * @expectedException Gajus\Doll\Exception\BadMethodCallException
     * @expectedExceptionMessage Method does not expect the additional parameters.
     */
    public function testQueryWithMoreThanOneParameter () {
        $this->db->query("SELECT 1", null);
    }
}