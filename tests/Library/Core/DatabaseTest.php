<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Core;

use VanillaTests\BootstrapTestCase;
use VanillaTests\Bootstrap;
use Gdn_Database;
use Garden\EventManager;
use Vanilla\Contracts\ConfigurationInterface;
use VanillaTests\ExpectExceptionTrait;

/**
 * Test the {@link Gdn_Database} class.
 */
class DatabaseTest extends BootstrapTestCase
{
    use ExpectExceptionTrait;

    /**
     * Tests that a basic SQL query works and returns the intended result
     */
    public function testBasicSqlQuery()
    {
        $db = new Gdn_Database(Bootstrap::testDbConfig());
        // $db->setDependencies is intentionally not called ot test operation without an EventManager

        $dataSet = $db->query("SELECT :inputValue AS `testValue`", ["inputValue" => "it works"]);
        $result = $dataSet->resultArray();

        $this->assertEquals([["testValue" => "it works"]], $result);
    }

    /**
     * Tests that a filter event is fired and transforms a query's result set
     */
    public function testQueryFilterEvent()
    {
        $db = static::container()->get(Gdn_Database::class);
        $querySql = "SELECT :inputValue AS `testValue`";
        $queryParams = ["inputValue" => 4];
        $queryOptions = ["queryOption" => "optionValue"];

        static::container()
            ->get(EventManager::class)
            ->bind("database_query_before", function (array $eventParams, string $eventSql, array $eventOptions) use (
                $queryOptions,
                $queryParams,
                $querySql
            ) {
                $this->assertEquals($queryParams, $eventParams);
                $this->assertEquals($querySql, $eventSql);
                $this->assertEquals($queryOptions, $eventOptions);

                foreach ($eventParams as &$value) {
                    $value = $value * 3;
                }

                return $eventParams;
            });

        $dataSet = $db->query($querySql, $queryParams, $queryOptions);
        $result = $dataSet->resultArray();

        $this->assertEquals([["testValue" => 12]], $result);
    }

    /**
     * Test that we can run a callback with certain SQL modes and they will be reset even if everything blows up.
     */
    public function testRunWithSqlMode()
    {
        static::container()
            ->get(EventManager::class)
            ->unbindAll();
        $db = static::container()->get(Gdn_Database::class);
        $originalModes = $db->getSqlModes();
        $modesInCallback = null;
        $this->runWithExpectedExceptionMessage("Boom", function () use ($db, &$modesInCallback) {
            $db->runWithSqlMode([Gdn_Database::SQL_MODE_NO_AUTO_VALUE_ZERO], function () use ($db, &$modesInCallback) {
                $modesInCallback = $db->getSqlModes();
                throw new \Exception("Boom");
            });
        });
        $modesAfterCallback = $db->getSqlModes();
        $this->assertTrue(in_array(Gdn_Database::SQL_MODE_NO_AUTO_VALUE_ZERO, $modesInCallback));
        $this->assertFalse(in_array(Gdn_Database::SQL_MODE_NO_AUTO_VALUE_ZERO, $modesAfterCallback));
        $this->assertSame($originalModes, $db->getSqlModes());
    }
}
