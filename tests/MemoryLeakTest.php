<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\CallbackJob;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

class MemoryLeakTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->createCategory();
        $this->createDiscussion();
    }

    /**
     * @return void
     * @dataProvider provideThings
     */
    public function testOne()
    {
        $this->assertTrue(true);
    }

    /**
     * @return void
     * @dataProvider provideThings
     */
    public function testTwo()
    {
        $this->assertTrue(true);
    }

    public function provideThings()
    {
        return [
            [1],
            [2],
            [3],
            [4],
            [5],
            [6],
            [7],
            [8],
            [9],
            [10],
            [1],
            [2],
            [3],
            [4],
            [5],
            [6],
            [7],
            [8],
            [9],
            [10],
            [1],
            [2],
            [3],
            [4],
            [5],
            [6],
            [7],
            [8],
            [9],
            [10],
            [1],
            [2],
            [3],
            [4],
            [5],
            [6],
            [7],
            [8],
            [9],
            [10],
        ];
    }
}
