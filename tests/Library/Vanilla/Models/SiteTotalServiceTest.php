<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Models;

use Vanilla\Models\SiteTotalService;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for SiteTotalService.
 */
class SiteTotalServiceTest extends SiteTestCase
{
    use SchedulerTestTrait;
    use CommunityApiTestTrait;

    /**
     * Test that items over a specific table size threshold have their calculation deferred.
     */
    public function testExpensiveThreshold()
    {
        $this->resetTable("Discussion");
        // 3 discussions is above the threshold.
        $this->createDiscussion();
        $this->createDiscussion();
        $this->createDiscussion();

        // Pause the scheduler so the counts don't happen immediately.
        $this->getScheduler()->pause();
        $this->runWithConfig(
            [
                SiteTotalService::CONF_EXPENSIVE_COUNT_THRESHOLD => 2,
            ],
            function () {
                $service = $this->getSiteTotalService();

                // Since we have 3 discussions it's calculations should be deferred until it finishes calculating.
                // Do it a few times just to make sure it keeps returning that default value.
                $service->getTotalForType("discussion");
                $total = $this->getSiteTotalService()->getTotalForType("discussion");
                $this->assertEquals(-1, $total);

                // Now unpause.
                $this->getScheduler()->resume();
                $total = $this->getSiteTotalService()->getTotalForType("discussion");
                $this->assertEquals(3, $total);
            }
        );
    }

    /**
     * Test our exception throwing.
     */
    public function testRecordTypeNotFound()
    {
        $this->expectExceptionMessage("RecordType not found");
        $this->getSiteTotalService()->getTotalForType("notreal");
    }

    /**
     * @return SiteTotalService
     */
    private function getSiteTotalService(): SiteTotalService
    {
        return self::container()->get(SiteTotalService::class);
    }
}
