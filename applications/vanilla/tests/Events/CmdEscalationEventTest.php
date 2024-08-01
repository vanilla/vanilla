<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Events;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Events\ResourceEvent;
use Garden\Schema\ValidationException;
use Vanilla\Community\Events\cmdEscalationEvent;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\VanillaTestCase;

/**
 * Test for the cdmEscalation resource event.
 */
class CmdEscalationEventTest extends SiteTestCase
{
    use CommunityApiTestTrait, EventSpyTestTrait;

    /**
     * Test the cmdEscalation resource events on a discussion.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testNewEscalationOnDiscussion(): void
    {
        // Create a new escalation.
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $escalation = $this->createEscalation($discussion);
        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                "cmdEscalation",
                ResourceEvent::ACTION_INSERT,
                self::getEscalationResourceEventData($escalation)
            )
        );

        // Update the escalation and assign a user to it.
        $userID = $this->createUserFixture(VanillaTestCase::ROLE_ADMIN);
        $escalation = $this->api()
            ->patch("escalations/{$escalation["escalationID"]}", ["assignedUserID" => $userID])
            ->getBody();
        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                "cmdEscalation",
                ResourceEvent::ACTION_UPDATE,
                self::getEscalationResourceEventData($escalation)
            )
        );

        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                "cmdEscalation",
                cmdEscalationEvent::ACTION_ASSIGNED,
                self::getEscalationResourceEventData($escalation)
            )
        );
    }

    /**
     * Test the cmdEscalation resource events on a comment.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testNewEscalationOnComment(): void
    {
        // Create a new escalation with an assignee.
        $this->createCategory();
        $this->createDiscussion();
        $comment = $this->createComment();
        $userID = $this->createUserFixture(VanillaTestCase::ROLE_ADMIN);
        $escalation = $this->createEscalation($comment, ["assignedUserID" => $userID]);
        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                "cmdEscalation",
                ResourceEvent::ACTION_INSERT,
                self::getEscalationResourceEventData($escalation)
            )
        );

        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                "cmdEscalation",
                cmdEscalationEvent::ACTION_ASSIGNED,
                self::getEscalationResourceEventData($escalation)
            )
        );

        // Update the escalation.
        $userID = $this->createUserFixture(VanillaTestCase::ROLE_ADMIN);
        $escalation = $this->api()
            ->patch("escalations/{$escalation["escalationID"]}")
            ->getBody();
        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                "cmdEscalation",
                ResourceEvent::ACTION_UPDATE,
                self::getEscalationResourceEventData($escalation)
            )
        );
    }

    /**
     * Mock the escalation data.
     *
     * @param array $escalation
     * @return array
     */
    private static function getEscalationResourceEventData(array $escalation): array
    {
        return [
            "escalationID" => $escalation["escalationID"],
            "name" => $escalation["name"],
            "status" => $escalation["status"],
            "assignedUserID" => $escalation["assignedUserID"],
            "countComments" => $escalation["countComments"],
            "recordType" => $escalation["recordType"],
            "recordID" => $escalation["recordID"],
            "updateUserID" => $escalation["updateUserID"],
            "insertUserID" => $escalation["insertUserID"],
            "placeRecordType" => $escalation["placeRecordType"],
            "placeRecordID" => $escalation["placeRecordID"],
        ];
    }
}
