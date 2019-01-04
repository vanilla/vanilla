<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Core;

use VanillaTests\SharedBootstrapTestCase;
use Gdn_DataSet;

/**
 * Test the {@link Gdn_DataSet} class.
 */
class DataSetTest extends SharedBootstrapTestCase {
    /**
     * A basic test of newing up a dataset.
     */
    public function testNewingUp() {
        $ds = new Gdn_DataSet([['foo' => 123, 'bar' => 'baz'], ['foo' => 345, 'bar' => 'sme']]);

        $this->assertSame(2, $ds->numRows());
    }

    /**
     * Test json serialization.
     */
    public function testJsonSerialize() {
        $dt = new \DateTimeImmutable('2000-01-01');
        $ds = new Gdn_DataSet([['dt' => $dt, 'IPAddress' => ipEncode('127.0.0.1')]]);

        $expected = json_encode([['dt' => $dt->format(\DateTime::RFC3339), 'IPAddress' => '127.0.0.1']]);
        $json = json_encode($ds);
        $this->assertEquals($expected, $json);
    }

    /**
     * Test json serialization does not affect original data.
     */
    public function testJsonSerializeOriginal() {
        $dt = new \DateTimeImmutable('2000-01-01');
        $data = [['dt' => $dt, 'IPAddress' => ipEncode('127.0.0.1')]];
        $ds = new Gdn_DataSet($data);

        json_encode($ds); // The result isn't used, but make sure Gdn_DataSet::jsonSerialize is executed.
        $this->assertEquals($data, $ds->result());
    }
}
