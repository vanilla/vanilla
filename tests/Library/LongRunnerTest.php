<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library;

use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerMultiAction;
use Vanilla\Web\SystemTokenUtils;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Fixtures\Scheduler\LongRunnerFixture;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Verify basic behavior of LongRunner.
 */
class LongRunnerTest extends SiteTestCase
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

    /**
     * Test pausing and resuming of a multi action.
     */
    public function testMultiAction()
    {
        $firstIterationResult = $this->getLongRunner()
            ->setMaxIterations(2)
            ->runApi(
                new LongRunnerMultiAction([
                    new LongRunnerAction(LongRunnerFixture::class, "yieldBack", [[1, 2, 3]]),
                    new LongRunnerAction(LongRunnerFixture::class, "yieldBack", [[4, 5, 6]]),
                    new LongRunnerAction(LongRunnerFixture::class, "yieldBack", [[7]]),
                ])
            );

        $this->assertEquals(7, $firstIterationResult->getSerializedData()["progress"]["countTotalIDs"]);
        $this->assertEquals([1, 2], $firstIterationResult->getSerializedData()["progress"]["successIDs"]);

        // We can resume the existing item.
        $this->getLongRunner()->setMaxIterations(1);
        $secondIterationResult = $this->resumeLongRunner($firstIterationResult);
        $this->assertEquals(7, $secondIterationResult->getBody()["progress"]["countTotalIDs"]);
        $this->assertEquals([3], $secondIterationResult->getBody()["progress"]["successIDs"]);

        // We can move onto the next item.
        $this->getLongRunner()->setMaxIterations(2);
        $thirdIterationResult = $this->resumeLongRunner($secondIterationResult);
        $this->assertEquals(7, $thirdIterationResult->getBody()["progress"]["countTotalIDs"]);
        $this->assertEquals([4, 5], $thirdIterationResult->getBody()["progress"]["successIDs"]);

        // We can finish.
        $this->getLongRunner()->setMaxIterations(null);
        $finalResult = $this->resumeLongRunner($thirdIterationResult);
        $this->assertEquals(7, $finalResult->getBody()["progress"]["countTotalIDs"]);
        $this->assertEquals([6, 7], $finalResult->getBody()["progress"]["successIDs"]);
    }
}
