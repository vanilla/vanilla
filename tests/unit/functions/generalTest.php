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

    public function isUrlProvider() {
        return array(
            array('nope', false),
            array('//codeception.local', true),
            array('https://codeception.local', true),
            array('http://codeception.local', true),
            array(0, false),
            array(false, false),
            array(null, false),
        );
    }
    /**
     * @dataProvider isUrlProvider
     * @param $string
     * @param $expected
     */
    public function testIsUrl($string, $expected) {
        $this->assertEquals(IsUrl($string), $expected);
    }

    public function testAttribute() {
        $attribute = Attribute('test', 'value');
        $expected = ' test="value"';
        $this->assertEquals($expected, $attribute);

        $attribute = Attribute(array('test1' => 'value1', 'test2' => 'value2'));
        $expected = ' test1="value1" test2="value2"';
        $this->assertEquals($expected, $attribute);

        $attribute = Attribute(array('test1' => 'value1', 'test2' => 'value2'), 'test2');
        $expected = ' test1="value1"';
        $this->assertEquals($expected, $attribute);


    }

    public function offsetLimitProvider() {
        return array(
            array('p1', 30, array(0, 30)),
            array('p2', 30, array(30, 30)),
            array(1, 30, array(1, 30)),
            array('0lim30', 10, array(0, 30)),
            array('0limz', 10, array(0, 10)),
            array('1-30', 10, array(0, 30)),
            array('0lin30', 10, array(0, 30)),
            array('0linz', 10, array(0, 10)),
            array('fail', 10, array(0, 10)),
            array('fail', -10, array(0, 50)),
            array(-10, -10, array(0, 50)),
        );
    }

    /**
     * @dataProvider offsetLimitProvider
     * @param $offsetOrPage
     * @param $limitOrPageSize
     * @param $expected
     */
    public function testOffsetLimit($offsetOrPage, $limitOrPageSize, $expected) {
        $this->assertEquals($expected, OffsetLimit($offsetOrPage, $limitOrPageSize));
    }

//    public function testAddActivity() {
//        AddActivity(1, 'WallPost', 'Test WallPost', '', '', false);
//    }


}