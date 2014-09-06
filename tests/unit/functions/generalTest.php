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
        $test['test'] = 'test';
        $this->assertEquals('test', GetValue('test', $test));
        $this->assertEquals('test', GetValue('test', $test, null, true));
        $this->assertFalse(GetValue('test', $test));

        $test = new \stdClass();;
        $this->assertTrue(GetValue('test', $test, true));
        $this->assertFalse(GetValue('test', $test, false));
        $test->test = 'test';
        $this->assertEquals('test', GetValue('test', $test));
        $this->assertEquals('test', GetValue('test', $test, null, true));
        $this->assertFalse(GetValue('test', $test));

    }

    public function absoluteSourceProvider() {
        return array(
            array('/img/testing/test.jpg', 'http://codeception.local/', 'http://codeception.local/img/testing/test.jpg'),
            array('http://codeception.local/img/testing/test.jpg', 'http://codeception.local/', 'http://codeception.local/img/testing/test.jpg'),
            array('img/testing/test.jpg', 'http://codeception.local/testpage', 'http://codeception.local/testpage/img/testing/test.jpg'),
        );
    }

    /**
     * @dataProvider absoluteSourceProvider
     * @param $srcPath
     * @param $url
     * @param $expected
     */
    public function testAbsoluteSource($srcPath, $url, $expected) {

        $absoluteSource = AbsoluteSource($srcPath, $url);
        $this->assertEquals($expected, $absoluteSource);

    }


}