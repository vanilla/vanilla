<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/conversations endpoints.
 */
class ConversationsTest extends AbstractAPIv2Test {

    private $baseUrl = '/conversations';

    private static $userIDs = [];

    private $originalConfigEmailValue;

    private $pk = 'conversationID';

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass() {
        parent::setupBeforeClass();

        /**
         * @var \Gdn_Session $session
         */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);

        /** @var \UsersApiController $usersAPIController */
        $usersAPIController = static::container()->get('UsersAPIController');

        foreach ([1, 2, 3, 4] as $id) {
            $user = $usersAPIController->post([
                'name' => "ConversationsUser$id",
                'email' => "ConversationsUser$id@example.com",
                'password' => "$%#$&ADSFBNYI*&WBV$id",
            ]);
            self::$userIDs[] = $user['userID'];
        }

        // Disable email sending.
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get('Config');
        $config->set('Garden.Email.Disabled', true, true, false);

        $session->end();
    }

    /**
     * Test GET /conversations/<id>.
     */
    public function testGet() {
        $conversation = $this->testPost();

        $result = $this->api()->get(
            "{$this->baseUrl}/{$conversation[$this->pk]}"
        );

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertRowsEqual($conversation, $result->getBody());
        $this->assertCamelCase($result->getBody());

        return $result->getBody();
    }


    /**
     * Test GET /conversations/<id>/participants.
     */
    public function testGetParticipants() {
        $conversation = $this->testPostParticipants();

        $result = $this->api()->get(
            "{$this->baseUrl}/{$conversation[$this->pk]}/participants"
        );

        $expectedCountParticipant = count(self::$userIDs) + 1;
        $expectedFirstParticipant = [
            'userID' => $this->api()->getUserID(),
            'deleted' => false,
        ];

        $this->assertEquals(200, $result->getStatusCode());

        $participants = $result->getBody();

        $this->assertTrue(is_array($participants));
        $this->assertEquals($expectedCountParticipant, count($participants));
        $this->assertRowsEqual($expectedFirstParticipant, $participants[0]);
    }

    /**
     * Test GET /conversations.
     *
     * @return array Returns the fetched data.
     */
    public function testIndex() {
        $nbsInsert = 3;

        // Insert a few rows.
        $rows = [];
        for ($i = 0; $i < $nbsInsert; $i++) {
            $rows[] = $this->testPost();
        }

        $result = $this->api()->get($this->baseUrl, ['insertUserID' => $this->api()->getUserID()]);
        $this->assertEquals(200, $result->getStatusCode());

        $rows = $result->getBody();
        $this->assertGreaterThan($nbsInsert, count($rows));
        // The index should be a proper indexed array.
        for ($i = 0; $i < count($rows); $i++) {
            $this->assertArrayHasKey($i, $rows);
        }
    }

    /**
     * @requires function ConversationAPIController::delete
     */
    public function testDelete() {
        $this->fail(__METHOD__.' needs to be implemented');
    }

    /**
     * Test DELETE /conversations/<id>/leave.
     *
     * @return array Returns the fetched data.
     */
    public function testDeleteLeave() {
        $conversation = $this->testPost();

        $result = $this->api()->delete(
            "{$this->baseUrl}/{$conversation[$this->pk]}/leave"
        );

        $this->assertEquals(204, $result->getStatusCode());

        $participantsResult = $this->api()->get(
            "{$this->baseUrl}/{$conversation[$this->pk]}/participants"
        );

        $this->assertEquals(200, $participantsResult->getStatusCode());

        $expectedFirstParticipant = [
            'userID' => $this->api()->getUserID(),
            'deleted' => true,
        ];
        $participants = $participantsResult->getBody();

        $this->assertTrue(is_array($participants));
        $this->assertRowsEqual($expectedFirstParticipant, $participants[0]);
    }

    /**
     * Test POST /conversations.
     *
     * @return array The conversation.
     */
    public function testPost() {
        $postData = [
            'participantUserIDs' => array_slice(self::$userIDs, 0, 2)
        ];
        $expectedResult = [
            'insertUserID' => $this->api()->getUserID(),
            'countParticipants' => 3,
            'countMessages' => 0,
            'countReadMessages' => 0,
            'dateLastViewed' => null,
        ];

        $result = $this->api()->post(
            $this->baseUrl,
            $postData
        );

        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();
        $this->assertTrue(is_int($body[$this->pk]));
        $this->assertTrue($body[$this->pk] > 0);

        $this->assertRowsEqual($expectedResult, $body, true);

        return $body;
    }

    /**
     * Test POST /conversations/<id>/participants.
     */
    public function testPostParticipants() {
        $conversation = $this->testPost();

        $postData = [
            'participantUserIDs' => array_slice(self::$userIDs, 2)
        ];
        $result = $this->api()->post(
            "{$this->baseUrl}/{$conversation[$this->pk]}/participants",
            $postData
        );

        $this->assertEquals(201, $result->getStatusCode());

        $updatedConversation = $result->getBody();

        $this->assertEquals($conversation['countParticipants'] + 2, $updatedConversation['countParticipants']);

        return $conversation;
    }
}
