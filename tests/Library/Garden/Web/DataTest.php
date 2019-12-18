<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Garden\Web;

use Garden\Web\Data;
use PHPUnit\Framework\TestCase;

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
                'items' => [
                    ['v' => 'a'],
                    ['v' => 'b'],
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
        $this->data->setHeader('HTTP_xxx', 'xxx');
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
     * Test for offsetUnset().
     */
    public function testOffsetUnset() {
        $this->assertArrayHasKey('userID', $this->data);
        $this->data->offsetUnset('userID');
        $this->assertArrayNotHasKey('userID', $this->data);
    }

    /**
     * Test for count().
     */
    public function testCount() {
        $this->assertCount(3, $this->data);
        $this->data->offsetUnset('userID');
        $this->assertCount(2, $this->data);
    }
}
