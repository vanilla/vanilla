<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Core;

use VanillaTests\BootstrapTestCase;
use Gdn_Database;
use Gdn_DataSet;
use Garden\EventManager;

/**
 * Test the {@link Gdn_DataSet} class.
 */
class DataSetTest extends BootstrapTestCase
{
    /**
     * A basic test of newing up a dataset.
     */
    public function testNewingUp()
    {
        $ds = new Gdn_DataSet([["foo" => 123, "bar" => "baz"], ["foo" => 345, "bar" => "sme"]]);

        $this->assertSame(2, $ds->numRows());
    }

    /**
     * Test json serialization.
     */
    public function testJsonSerialize()
    {
        $dt = new \DateTimeImmutable("2000-01-01");
        $ds = new Gdn_DataSet([["dt" => $dt, "IPAddress" => ipEncode("127.0.0.1")]]);

        $expected = json_encode([["dt" => $dt->format(\DateTime::RFC3339), "IPAddress" => "127.0.0.1"]]);
        $json = json_encode($ds);
        $this->assertEquals($expected, $json);
    }

    /**
     * Test json serialization does not affect original data.
     */
    public function testJsonSerializeOriginal()
    {
        $dt = new \DateTimeImmutable("2000-01-01");
        $data = [["dt" => $dt, "IPAddress" => ipEncode("127.0.0.1")]];
        $ds = new Gdn_DataSet($data);

        json_encode($ds); // The result isn't used, but make sure Gdn_DataSet::jsonSerialize is executed.
        $this->assertEquals($data, $ds->result());
    }

    /**
     * Test that fetching a simple result works
     */
    public function testFetchSimpleResult()
    {
        $pdo = static::container()
            ->get("Gdn_Database")
            ->connection();
        $dataSet = new Gdn_DataSet(null, DATASET_TYPE_ARRAY);
        // $dataSet->setDependencies is intentionally not called to verify that the absence of an EventManager works

        $pdoStatement = $pdo->query("SELECT 42 AS `testValue`");
        $dataSet->pdoStatement($pdoStatement);
        $result = $dataSet->result();

        $this->assertEquals([["testValue" => 42]], $result);
    }

    /**
     * Test that filter event is fired and used when available
     */
    public function testResultFilterEvent()
    {
        $pdo = static::container()
            ->get(Gdn_Database::class)
            ->connection();
        $dataSet = static::container()->get(Gdn_DataSet::class);
        $querySql = "SELECT 42 AS `testValue1`, 13 as `testValue2`";
        $queryOptions = ["queryOption1" => "optionValue1"];

        static::container()
            ->get(EventManager::class)
            ->bind("database_query_result_after", function (array $results, string $sql, array $eventOptions) use (
                $querySql,
                $queryOptions
            ) {
                foreach ($results as &$result) {
                    foreach ($result as &$value) {
                        $value = $value * 2;
                    }
                }

                $this->assertEquals($querySql, $sql);
                $this->assertEquals($queryOptions, $eventOptions);

                return $results;
            });

        $dataSet->dataSetType(DATASET_TYPE_ARRAY);
        $dataSet->setQueryOptions($queryOptions);
        $pdoStatement = $pdo->query($querySql);
        $dataSet->pdoStatement($pdoStatement);
        $result = $dataSet->result();

        $this->assertEquals([["testValue1" => 84, "testValue2" => 26]], $result);
    }

    /**
     * Test fetching a column from a dataset.
     */
    public function testColumn()
    {
        $ds = new Gdn_DataSet([["foo" => 123, "bar" => "baz"], ["foo" => 345, "bar" => "sme"]]);
        $this->assertEquals([123, 345], $ds->column("foo"));
        $this->assertEquals(["baz", "sme"], $ds->column("bar"));
    }
}
