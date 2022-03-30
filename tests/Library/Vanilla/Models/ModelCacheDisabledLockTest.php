<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Models;

use Vanilla\CurrentTimeStamp;
use Vanilla\FeatureFlagHelper;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Models\ModelCache;
use Vanilla\Models\PipelineModel;
use VanillaTests\Fixtures\TestCache;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the `ModelCache` class.
 */
class ModelCacheDisabledLockTest extends ModelCacheTest {

    /**
     * Disable the lock store.
     */
    public function setUp(): void {
        parent::setUp();
        \Gdn::config()->saveToConfig('Cache.LockStore', 'disabled');
    }
}
