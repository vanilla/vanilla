<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Models;

use Garden\EventManager;
use VanillaTests\SiteTestCase;

/**
 * Tests for the `SpamModel` class.
 */
class SpamModelTest extends SiteTestCase
{
    const EVENT_NAME = "base_checkSpam";
    /**
     * @var \LogModel
     */
    private $logModel;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container()->call(function (\LogModel $logModel, EventManager $eventManager) {
            $this->logModel = $logModel;
            $this->eventManager = $eventManager;
        });
        $this->createUserFixtures();
        $this->getSession()->start($this->memberID);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $handlers = $this->eventManager->getHandlers(self::EVENT_NAME);
        foreach ($handlers as $handler) {
            $this->eventManager->unbind(self::EVENT_NAME, $handler);
        }
    }

    /**
     * By default, a spam record should go to the spam queue.
     */
    public function testDefaultSpam(): void
    {
        $fn = function (\SpamModel $sender, $args) {
            $sender->EventArguments["IsSpam"] = true;
        };

        $this->eventManager->bind(self::EVENT_NAME, $fn);

        $id = self::id();
        $isSpam = \SpamModel::isSpam("Discussion", ["Name" => __FUNCTION__, "Body" => __FUNCTION__, "RecordID" => $id]);
        $this->assertTrue($isSpam);

        $r = $this->logModel->getWhere(["RecordType" => "Discussion", "RecordID" => $id]);
        $this->assertCount(1, $r);

        $row = $r[0];
        $this->assertSame(\LogModel::TYPE_SPAM, $row["Operation"]);
    }

    /**
     * Addons should be able to change the type of log to moderation.
     */
    public function testChangeLogOperation(): void
    {
        $fn = function (\SpamModel $sender, $args) {
            $sender->EventArguments["IsSpam"] = true;
            $sender->EventArguments["Options"]["Operation"] = \LogModel::TYPE_MODERATE;
        };

        $this->eventManager->bind(self::EVENT_NAME, $fn);

        $id = self::id();
        $isSpam = \SpamModel::isSpam("Discussion", ["Name" => __FUNCTION__, "Body" => __FUNCTION__, "RecordID" => $id]);
        $this->assertTrue($isSpam);

        $r = $this->logModel->getWhere(["RecordType" => "Discussion", "RecordID" => $id]);
        $this->assertCount(1, $r);

        $row = $r[0];
        $this->assertSame(\LogModel::TYPE_MODERATE, $row["Operation"]);
    }
}
