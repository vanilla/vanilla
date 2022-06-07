<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library;

use Garden\Web\Data;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Web\SystemTokenUtils;
use VanillaTests\BootstrapTestCase;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Fixtures\Scheduler\LongRunnerFixture;
use VanillaTests\SchedulerTestTrait;

/**
 * Verify basic behavior of LongRunner.
 */
class LongRunnerTest extends BootstrapTestCase
{
    use ExpectExceptionTrait;
    use SchedulerTestTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->container()
            ->rule(LongRunnerFixture::class)
            ->setShared(true);

        // A little dirty but allow us to validate sessions without requiring a full site setup.
        \Gdn::session()->UserID = 1;
    }

    /**
     * Test that we return a response to continue running when incomplete.
     */
    public function testIncomplete()
    {
        $this->getLongRunner()->setTimeout(2);
        $response = $this->getLongRunner()->runApi(
            new LongRunnerAction(LongRunnerFixture::class, "yieldIDs", [1, 1, 2, 2])
        );
        // There were errors with code 500
        $this->assertEquals(500, $response->getStatus());
        $data = $response->getSerializedData();
        $this->assertCount(1, $data["progress"]["successIDs"]);
        $this->assertCount(1, $data["progress"]["failedIDs"]);
        $this->assertNotNull(1, $data["callbackPayload"]);
        return $data["callbackPayload"];
    }

    /**
     * Test handling an incomplete callback payload and running it to completion.
     *
     * @param string $callbackPayload The callback payload from the previous test.
     *
     * @depends testIncomplete
     */
    public function testHandlesCallbackPayload(string $callbackPayload)
    {
        $response = $this->getLongRunner()->runApi(
            LongRunnerAction::fromCallbackPayload(
                $callbackPayload,
                self::container()->getArgs(SystemTokenUtils::class),
                \Gdn::request()
            )
        );
        // More errors occured with 500 even though we "finished".
        $this->assertEquals(500, $response->getStatus());
        $data = $response->getSerializedData();
        $this->assertCount(2, $data["progress"]["successIDs"]);
        $this->assertCount(2, $data["progress"]["failedIDs"]);
        $this->assertNull($data["callbackPayload"]);
    }

    /**
     * Test that we return an appropriate response when the job is run completely.
     */
    public function testComplete()
    {
        $result = $this->getLongRunner()->runImmediately(
            new LongRunnerAction(LongRunnerFixture::class, "yieldIDs", [3])
        );

        $this->assertEquals([1, 2, 3], $result->getSuccessIDs());
        $this->assertEquals([], $result->getFailedIDs());
    }

    /**
     * Test how we handle failed ids coming back.
     */
    public function testCompleteWithFailed()
    {
        $result = $this->getLongRunner()->runImmediately(
            new LongRunnerAction(LongRunnerFixture::class, "yieldIDs", [3, 2])
        );

        $this->assertEquals([1, 2, 3], $result->getSuccessIDs());
        $this->assertEquals([4, 5], $result->getFailedIDs());
    }

    /**
     * Test that we receive an error message if we try to run a long runner as guest.
     */
    public function testNotSignedIn()
    {
        $session = \Gdn::session();
        $session->UserID = \UserModel::GUEST_USER_ID;
        $this->runWithExpectedExceptionCode(403, function () {
            $this->getLongRunner()->runApi(new LongRunnerAction(LongRunnerFixture::class, "yieldIDs", []));
        });
        $session->UserID = 1;
    }

    /**
     * Test that we validate our long-running task is system callable.
     */
    public function testNotSystemCallable()
    {
        $this->runWithExpectedExceptionCode(500, function () {
            $this->getLongRunner()->runApi(new LongRunnerAction(LongRunnerFixture::class, "notSystemCallable", []));
        });

        $this->runWithExpectedExceptionCode(500, function () {
            $this->getLongRunner()->runApi(new LongRunnerAction(self::class, "testNotSystemCallable", []));
        });
    }

    /**
     * Test what happens if our long-running task doesn't return a generator.
     */
    public function testNotGenerator()
    {
        $this->runWithExpectedExceptionCode(500, function () {
            $this->getLongRunner()->runApi(new LongRunnerAction(LongRunnerFixture::class, "notGenerator", []));
        });
    }

    /**
     * Test what happens when we return a bad return or yield after a return.
     */
    public function testBadGeneratorReturn()
    {
        $this->getLongRunner()->setTimeout(0);
        $this->runWithExpectedExceptionCode(500, function () {
            $this->getLongRunner()->runApi(
                new LongRunnerAction(LongRunnerFixture::class, "catchAndReturn", [["not-args"]])
            );
        });

        $this->runWithExpectedExceptionCode(500, function () {
            $this->getLongRunner()->runApi(
                new LongRunnerAction(LongRunnerFixture::class, "catchAndReturn", [["yield"]])
            );
        });
    }

    /**
     * Test when we timed out, but the generator was finished anyways.
     */
    public function testFinishedGeneratorReturn()
    {
        $this->getLongRunner()->setTimeout(0);
        $data = $this->getLongRunner()->runApi(
            new LongRunnerAction(LongRunnerFixture::class, "catchAndReturn", [LongRunner::FINISHED])
        );
        $this->assertEquals(200, $data->getStatus());
    }

    /**
     * Test what happens if your long-running task finishes at the same time that we time-out.
     */
    public function testNoNextArgs()
    {
        // Run once with some leftovers.
        $data = $this->getLongRunner()
            ->setTimeout(2)
            ->runApi(new LongRunnerAction(LongRunnerFixture::class, "canRunWithSameArgs", [[1, 2, 3, 4]]))
            ->getSerializedData();
        // Run again with the same args, but it should finish.
        $data = $this->getLongRunner()
            ->runApi(
                LongRunnerAction::fromCallbackPayload(
                    $data["callbackPayload"],
                    self::container()->getArgs(SystemTokenUtils::class),
                    \Gdn::request()
                )
            )
            ->getSerializedData();
        $this->assertNull($data["callbackPayload"]);
    }
}
