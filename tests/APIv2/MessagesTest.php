<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/messages endpoints.
 */
class MessagesTest extends AbstractResourceTest {

    protected static $userID;

    protected static $conversationID;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/messages';

        parent::__construct($name, $data, $dataName);
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void {
        parent::setupBeforeClass();

        /**
         * @var \Gdn_Session $session
         */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);

        // Disable flood control checks on the models and make sure that those specific instances are injected into the controllers.
        $conversationModel = self::container()->get(\ConversationModel::class)->setFloodControlEnabled(false);
        self::container()->setInstance(\ConversationModel::class, $conversationModel);
        $conversationMessageModel = self::container()->get(\ConversationMessageModel::class)->setFloodControlEnabled(false);
        self::container()->setInstance(\ConversationMessageModel::class, $conversationMessageModel);

        /** @var \UsersApiController $usersAPIController */
        $usersAPIController = static::container()->get('UsersAPIController');

        $user = $usersAPIController->post([
            'name' => "MessagesUser1",
            'email' => "MessagesUser1@example.com",
            'password' => "$%#$&ADSFBNYI*&WBV1",
        ]);
        self::$userID = $user['userID'];

        /** @var \ConversationsApiController $conversationsAPiController */
        $conversationsAPiController = static::container()->get('ConversationsAPiController');

        // Create the conversation as the newly created user.
        $session->start(self::$userID, false, false);

        $conversation = $conversationsAPiController->post([
            'participantUserIDs' => [self::$userID]
        ]);
        self::$conversationID = $conversation['conversationID'];

        // Disable email sending.
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get('Config');
        $config->set('Garden.Email.Disabled', true, true, false);

        $session->end();
    }

    /**
     * {@inheritdoc}
     */
    public function record() {
        return array_merge(parent::record(), ['conversationID' => self::$conversationID]);
    }

    /**
     * {@inheritdoc}
     */
    public function indexUrl() {
        $indexUrl = $this->baseUrl;
        $indexUrl .= '?'.http_build_query(['conversationID' => self::$conversationID]);
        return $indexUrl;
    }

    /**
     * {@inheritdoc}
     * @requires function MessagesApiController::delete
     */
    public function testDelete() {
        $this->fail(__METHOD__.' needs to be implemented');
    }

    /**
     * Test GET /resource/<id>.
     *
     * @expectedException \Exception
     * @expectedExceptionCode 403
     * @expectedExceptionMessage The site is not configured for moderating conversations.
     */
    public function testGet() {
        parent::testGet();
    }

    /**
     * {@inheritdoc}
     * @requires function MessagesApiController::patch
     */
    public function testGetEdit($record = null) {
        $this->fail(__METHOD__.' needs to be implemented');
    }

    /**
     * {@inheritdoc}
     * @requires function MessagesApiController::patch
     */
    public function testGetEditFields() {
        $this->fail(__METHOD__.' needs to be implemented');
    }

    /**
     * {@inheritdoc}
     * @requires function MessagesApiController::patch
     */
    public function testEditFormatCompat(string $editSuffix = "/edit") {
        $this->fail(__METHOD__.' needs to be implemented');
    }

    /**
     * Test GET /messages.
     *
     * @expectedException \Exception
     * @expectedExceptionCode 403
     * @expectedExceptionMessage The site is not configured for moderating conversations.
     */
    public function testIndex() {
        parent::testIndex();
    }

    /**
     * Test POST /resource.
     *
     * @param array|null $record Fields for a new record.
     * @param array $extra Additional fields to send along with the POST request.
     * @return array Returns the new record.
     */
    public function testPost($record = null, array $extra = []) {
        $currentUserID = $this->api()->getUserID();
        $this->api()->setUserID(self::$userID);

        $result = parent::testPost($record, $extra);

        $this->api()->setUserID($currentUserID);

        return $result;
    }

    /**
     * {@inheritdoc}
     * @requires function MessagesApiController::patch
     */
    public function testPatch() {
        $this->fail(__METHOD__.' needs to be implemented');
    }

    /**
     * {@inheritdoc}
     * @requires function MessagesApiController::patch
     */
    public function testPatchSparse($field = null) {
        $this->fail(__METHOD__.' needs to be implemented');
    }

    /**
     * {@inheritdoc}
     * @requires function MessagesApiController::patch
     */
    public function testPatchFull() {
        $this->fail(__METHOD__.' needs to be implemented');
    }
}
