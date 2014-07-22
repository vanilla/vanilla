<?php
namespace functions;


class generalTest extends \Codeception\TestCase\Test {
   /**
    * @var \UnitTester
    */
   protected $tester;

   protected function _before() {
   }

   protected function _after() {
   }

   // tests
   public function testGetValue() {
      $test = array();
      $this->assertTrue(GetValue('test', $test, true));
      $this->assertFalse(GetValue('test', $test, false));
   }

}