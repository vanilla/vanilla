<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Garden\Web;

use Garden\Web\Data;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Data class's methods.
 */

class DataTest extends TestCase {

    private $data;

    /**
     * Construct a Data object for testing.
     */
    public function setUp(): void {
        parent::setUp();
        $this->data = new Data(
            [
                'userID' => 123,
                'email' => 'rich@example.com',
                'time' => date_create_immutable('1980-06-17 20:00', new \DateTimeZone('UTC')),
                'IPAddress' => ipEncode('127.0.0.1'),
                'items' => [
                    ['v' => 'a'],
                    ['v' => 'b'],
                    ['v' => ['c' => 'd']],
                ]
            ],
            [

            ]
        );
    }

    /**
     * Test to make sure the Data object can be accessed like an array.
     */
    public function testArrayAccess() {
        $this->data['userID'] = 345;
        $this->assertSame(345, $this->data['userID']);


        json_encode($this->data);
    }

    /**
     * Tests for getting and setting Data items.
     */

    /**
     * Test {@link getDataItem()}.
     */
    public function testBasicGetDataItem() {
        $actual = $this->data->getDataItem('userID');
        $expected = 123;
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests for {@link setDataItem()}.
     */
    public function testBasicSetDataItem() {
        $alteredData = $this->data->setDataItem('userID', 345);
        $actual = $alteredData['userID'];
        $expected = 345;
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests for getting and setting entire Data object.
     */
    public function testBasicSetAndGetData() {
        $this->data->setData(['userID' => 345, 'email' => 'dick@example.com']);
        $expected = ['userID' => 345, 'email' => 'dick@example.com'];
        $actual = $this->data->getData();
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests for {@link addData()}
     */

    /**
     * Test with an already existing key.
     */
    public function testAddDataKeyAlreadyExists() {
        $this->data->addData(['c' => 'd'], 'items');
        $actual = $this->data['items'];
        $expected = ['c' => 'd'];
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with a new key.
     */
    public function testAddDataNewKey() {
        $this->data->addData(['dick@example.com'], 'secondary email');
        $actual = $this->data['secondary email'];
        $expected = ['dick@example.com'];
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with adding a new Data object.
     */
    public function testAddDataWithDataObject() {
        $this->data->addData(new Data(['newData' => 'new']), 'newData');
        $actual = $this->data['newData'];
        $expected = ['newData' => 'new'];
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with mergeMeta set to true.
     */
    public function testAddDataWithMergeMetaTrue() {
        $this->data->addData(new Data(['newData' => 'new']), 'newData', true);
        $actual = $this->data['newData'];
        $expected = ['newData' => 'new'];
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests for getStatus() and setStatus.
     */

    /**
     * Basic test for {@link setStatus()} and {@link getStatus()}
     */
    public function testSetAndGetStatus() {
        $this->data->setStatus(499);
        $actual = $this->data->getStatus();
        $expected = 499;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test {@link getStatus()} for null status.
     */
    public function testGetStatusNull() {
        $actual = $this->data->getStatus();
        $expected = 200;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test status > 527.
     */
    public function testGetStatusGreaterThan527() {
        $this->data->setStatus(550);
        $actual = $this->data->getStatus();
        $expected = 500;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test status < 100.
     */
    public function testGetStatusLessThan100() {
        $this->data->setStatus(80);
        $actual = $this->data->getStatus();
        $expected = 500;
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests for {@link getHeader()} and {@link setHeader()}.
     */

    /**
     * Test {@link getHeader()} with nonexistent header.
     */
    public function testGetHeaderNonexistent() {
        $actual = $this->data->getHeader('Host');
        $expected = null;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test set and get.
     */
    public function testSetAndGetHeader() {
        $this->data->setHeader('Host', 'example.com');
        $actual = $this->data->getHeader('Host');
        $expected = 'example.com';
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests for {@link hasHeader()}.
     */

    /**
     * Test where return is false.
     */
    public function testHasHeaderFalse() {
        $actual = $this->data->hasHeader('Host');
        $expected = false;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test where return is true.
     */
    public function testHasHeaderTrue() {
        $this->data->setHeader('Host', 'example.com');
        $actual = $this->data->hasHeader('Host');
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests for {@link getHeaders()}.
     */

    /**
     * Basic test.
     */
    public function testGetHeadersBasic() {
        $this->data->setHeader('Location', 'http://example.com/example');
        $this->data->setHeader('Warning', '199 Miscellaneous warning');
        $actual = $this->data->getHeaders();
        $expected = ['Location' => 'http://example.com/example', 'Warning' => '199 Miscellaneous warning'];
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with no headers.
     */
    public function testGetHeadersNone() {
        $actual = $this->data->getHeaders();
        $expected = [];
        $this->assertSame($expected, $actual);
    }

    /**
     * Test for altering CONTENT_TYPE and HTTP strings.
     */
    public function testGetHeadersAlteredStrings() {
        $this->data->setHeader('CONTENT_TYPE', 'text/html; charset=utf-8');
        $this->data->setHeader('HTTP_xxx', 'xxx');
        $actual = $this->data->getHeaders();
        $expected = ['Content-Type' => 'text/html; charset=utf-8', 'Http-Xxx' => 'xxx'];
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests for (@link render()}
     */

    /**
     * Test with no headers sent.
     */
    public function testRenderNoHeaders() {
        $expected = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        $actual = $this->getActualOutput($this->data->render());
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with one header sent.
     */
    public function testRenderOneHeader() {
        $this->data->setHeader('xxx', 'xxx');
        $expected = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        $actual = $this->getActualOutput($this->data->render());
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests for {@link offsetExists()}
     */

    /**
     * Test offsetExists() returns true.
     */
    public function testOffsetExistsTrue() {
        $actual = $this->data->offsetExists('userID');
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test offsetExists() returns false.
     */
    public function testOffsetExistsTrueInt() {
        $actual = $this->data->offsetExists('foo');
        $expected = false;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test for {@link offsetUnset()}.
     */
    public function testOffsetUnset() {
        $this->assertArrayHasKey('userID', $this->data);
        $this->data->offsetUnset('userID');
        $this->assertArrayNotHasKey('userID', $this->data);
    }

    /**
     * Test for {@link count()}.
     */
    public function testCount() {
        $this->assertCount(5, $this->data);
        $this->data->offsetUnset('userID');
        $this->assertCount(4, $this->data);
    }

    /**
     * Test for {@link getIterator()}
     */
    public function testGetIterator() {
        $iteratorObject = $this->data->getIterator();
        $actual = $iteratorObject['userID'];
        $expected = $this->data['userID'];
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test for {@link box()}.
     */

    /**
     * Test with data object.
     */
    public function testBoxWithDataObject() {
        $actual = $this->data->box($this->data);
        $expected = $this->data;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with array.
     */
    public function testBoxWithArray() {
        $actual = $this->data->box(['foo' => 'bar']);
        $expected = new Data(['foo' => 'bar']);
        $this->assertEquals($actual, $expected);
    }

    /**
     * Test with string.
     */
    public function testBoxWithString() {
        $this->expectExceptionMessage("Data:box() expects an instance of Data or an array.");
        $this->data->box('This should throw an exception');
    }

    /**
     * Tests for MetaTrait
     */

    /**
     * Tests for {@link addMeta()}.
     */

    /**
     * Test with two arguments (name and value).
     */
    public function testAddMetaNameValue() {
        $this->data->addMeta('Location', 'www.example.com/example');
        $expected = ['Location' => ['www.example.com/example']];
        $actual = $this->data->getMetaArray();
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with three arguments (name, key, and value).
     */
    public function testAddMetaNameValueAndKey() {
        $this->data->addMeta('foo', 'bar', 'baz');
        $expected = ['foo' => ['bar' => 'baz']];
        $actual = $this->data->getMetaArray();
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with already existing key.
     */
    public function testAddMetaWithAlreadyExistingKey() {
        $this->data->setMeta('foo', 'bar');
        $this->assertSame($this->data->getMetaArray(), ['foo' => 'bar']);
        $this->data->addMeta('foo', 'baz');
        $expected = ['foo' => ['bar', 'baz']];
        $actual = $this->data->getMetaArray();
        $this->assertSame($expected, $actual);
    }

    /**
     * Test {@link setMetaArray()}.
     */
    public function testSetMetaArray() {
        $this->data->setMetaArray(['foo' => 'bar']);
        $actual = $this->data->getMetaArray();
        $expected = ['foo' => 'bar'];
        $this->assertSame($expected, $actual);
    }

    /**
     * Test {@link mergeMetaArray()}
     */
    public function testMergeMetaArray() {
        $this->data->setMetaArray(['foo' => 'bar']);
        $this->data->mergeMetaArray(['bar' => 'baz']);
        $expected = ['foo' => 'bar', 'bar' => 'baz'];
        $actual = $this->data->getMetaArray();
        $this->assertSame($expected, $actual);
    }

    /**
     * Trying to get a data item from a non-array data is an exception.
     */
    public function testGetDataItemException() {
        $data = new Data('foo');
        $this->expectException(\Exception::class);
        $data->getDataItem('0');
    }

    /**
     * Trying to set a data item on a non-array data is an exception.
     */
    public function testSetDataItemException() {
        $data = new Data('foo');
        $this->expectException(\Exception::class);
        $data->setDataItem('0', 'b');
    }

    /**
     * Test the data constructor with an integer status.
     */
    public function testIntStatusConstructor() {
        $data = new Data([], 200);
        $this->assertSame(200, $data->getStatus());
    }
}
