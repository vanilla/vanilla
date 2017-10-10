<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/messages endpoints.
 */
class MessagesTest extends AbstractResourceTest {

    private static $userID;

    private static $conversationID;

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
    public static function setUpBeforeClass() {
        parent::setupBeforeClass();

        /**
         * @var \Gdn_Session $session
         */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);

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
