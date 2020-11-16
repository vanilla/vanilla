<?php
/**
 * @author Dani M <dani.m@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers;

use Garden\Events\ResourceEvent;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the `LogController` class.
 *
 */
class LogControllerTest extends SiteTestCase {
    use EventSpyTestTrait;

    /** @var \LogController */
    private $controller;

    /** @var \LogModel */
    private $logModel;

    /** @var \CommentModel */
    private $commentModel;

    /** @var \CommentModel */
    private $discussionModel;


    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();

        $this->controller = self::container()->get(\LogController::class);
        $this->controller->getImports();
        $this->logModel = self::container()->get(\LogModel::class);
        $this->commentModel = self::container()->get(\CommentModel::class);
        $this->discussionModel = self::container()->get(\DiscussionModel::class);
        $this->controller->Request = self::container()->get(\Gdn_Request::class);
        $this->controller->initialize();
    }

    /**
     * @inheritDoc
     */
    public static function setupBeforeClass(): void {
        parent::setUpBeforeClass();
        $session = self::container()->get('Session');
        $session->validateTransientKey(true);
    }

    /**
     * Test LogController::notSpam() not causing duplication on restore of a comment.
     */
    public function testNotSpamNoDuplicationCommentRestore(): void {
        $data = [
            'Body' => 'test comment',
            'CommentID' => 20,
            'DiscussionID' => 20,
            'InsertUserID' => 1,
            'Format' => 'Text',
        ];

        $logIDs = $this->logModel->insert('Spam', 'Comment', $data);
        $this->controller->Request->setMethod('Post');
        $this->controller->Request->setRequestArguments(
            \Gdn_Request::INPUT_POST,
            [
                'LogIDs' => $logIDs, 'UserID' => $data['InsertUserID']
            ]
        );
        $this->controller->addDefinition('Roles', 'Roles');
        $this->controller->deliveryType(DELIVERY_TYPE_NONE);
        $this->controller->notSpam();
        $countRestore = $this->commentModel->getCount(['CommentID' => $data['CommentID']]);
        $this->assertEquals(1, $countRestore);
    }


    /**
     * Test LogController::notSpam() not deleting the log + the discussion upon restore.
     */
    public function testSpamDiscussionRestore(): void {
        $userData = [
            "Name" =>  __FUNCTION__."testrestoreuser",
            "Email" => "testrestoreuser@example.com",
            "Password" => "vanilla"
        ];
        $this->userModel->save($userData);
        $logData = [
            "Name" => __FUNCTION__."test discusionSpamRestore",
            "Body" => "test discusionSpamRestore",
            "CategoryID" => 1,
            "InsertUserID" => 1,
            "Format" => "Text",
            "Email" => $userData["Email"],
            "DateInserted" => "2020-01-01 00:00:00"
        ];

        $logID = $this->logModel->insert('Spam', 'Discussion', $logData);
        $this->controller->Request->setMethod('Post');
        $this->controller->Request->setRequestArguments(
            \Gdn_Request::INPUT_POST,
            [
                'LogIDs' => $logID, 'UserID' => $logData['InsertUserID']
            ]
        );
        $this->controller->addDefinition('Roles', 'Roles');
        $this->controller->deliveryType(DELIVERY_TYPE_NONE);
        $this->controller->notSpam();
        $logCount = $this->logModel->getCountWhere(['LogID' => $logID]);
        $discussionCount = $this->discussionModel->getCount(['d.Name' => $logData["Name"]]);
        $this->assertEquals(1, $discussionCount);
        $this->assertEquals(0, $logCount);

        $discussion = $this->discussionModel->getWhere(['d.Name' => $logData["Name"]])->firstRow(DATASET_TYPE_ARRAY);
        $this->assertNotEmpty($discussion['DateLastComment']);

        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                'discussion',
                ResourceEvent::ACTION_INSERT,
                [
                    'name' => $logData['Name'],
                ]
            )
        );
    }

    /**
     * Test marking comments as not spam
     */
    public function testSpamCommentRestore(): void {
        $userData = [
            "Name" =>  __FUNCTION__."testrestoreuser",
            "Email" => "testrestoreuser@example.com",
            "Password" => "vanilla"
        ];
        $userID = $this->userModel->save($userData);
        $preCommentCount = $this->commentModel->getCount();
        $logData = [
            "Body" => __FUNCTION__,
            "DiscussionID" => 1,
            "InsertUserID" => $userID,
            "Format" => "Markdown",
            "DateInserted" => "2020-01-01 00:00:00",
            "InsertIPAddress" => "127.0.0.1",
            "Username" => $userData['Name'],
            "Email" => $userData['Email'],
            "IPAddress" => "127.0.0.1",
        ];

        $logID = $this->logModel->insert('Spam', 'Comment', $logData);
        $logCount = $this->logModel->getCountWhere(['LogID' => $logID]);

        // assert the log item was created
        $this->assertEquals(1, $logCount);

        $this->bessy()->post('/log/NotSpam', ['LogIDs' => $logID]);
        $postCommentCount = $this->commentModel->getCount();
        $logCount = $this->logModel->getCountWhere(['LogID' => $logID]);

        // assert the log item was deleted and a comment created
        $this->assertEquals(0, $logCount);
        $this->assertEquals($preCommentCount + 1, $postCommentCount);

        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                'comment',
                ResourceEvent::ACTION_INSERT,
                [
                    'body' => \Gdn::formatService()->renderHTML($logData['Body'], $logData['Format']),
                ]
            )
        );
    }

    /**
     * Test LogController::notSpam() not causing duplication on restore of a user.
     */
    public function testNotSpamNoDuplicationUserRestore(): void {
        $data = [
            "Name" =>  "testrestoreuser",
            "Email" => "testrestoreuser@example.com",
            "Password" => "vanilla"
        ];

        $this->userModel->save($data);
        $logID = $this->logModel->insert('Spam', 'Registration', $data);
        $this->controller->Request->setMethod('Post');
        $this->controller->Request->setRequestArguments(
            \Gdn_Request::INPUT_POST,
            [
                'LogIDs' => $logID
            ]
        );
        $this->controller->addDefinition('Roles', 'Roles');
        $this->controller->deliveryType(DELIVERY_TYPE_NONE);
        $this->controller->notSpam();
        $countRestore = $this->logModel->getCountWhere(['LogID' => $logID]);
        $this->assertEquals(0, $countRestore);
        $countUsers = $this->userModel->getCountWhere(['Email' => $data['Email']]);
        $this->assertEquals(1, $countUsers);
    }
}
