<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Search;

use Vanilla\Search\MysqlSearchDriver;
use Vanilla\Search\SearchOptions;
use Vanilla\Search\SearchService;
use VanillaTests\BootstrapTestCase;

/**
 * Tests for the search service.
 */
class SearchServiceTest extends BootstrapTestCase
{
    /**
     * Test that the service has driver fallback if it can't be located except on searches.
     */
    public function testDriverNotFound()
    {
        $this->runWithConfig(
            [
                "Vanilla.Search.Driver" => "nonexistent",
            ],
            function () {
                $service = self::container()->get(SearchService::class);
                // Always enabled in prod.
                $service->registerActiveDriver(self::container()->get(MysqlSearchDriver::class));

                // Fallback to mysql for other checks on the driver.
                $driver = $service->getActiveDriver();
                $this->assertInstanceOf(MysqlSearchDriver::class, $driver);

                // But searching specifically will throw.
                $this->expectExceptionMessage("Could not find");
                $service->search([], new SearchOptions());
            }
        );
    }
}
