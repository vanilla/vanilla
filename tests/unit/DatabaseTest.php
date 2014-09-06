<?php


class DatabaseTest extends \Codeception\TestCase\Test
{
   /**
    * @var \UnitTester
    */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testDatabase() {
        $this->tester->seeInDatabase('GDN_User', array('Name' => 'admin', 'Admin' => 1));
    }

}