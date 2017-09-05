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

        /** @var \UsersApiController $usersAPIController */
        $usersAPIController = static::container()->get('UsersAPIController');

        $user = $usersAPIController->post([
            'name' => "MessagesUser$id",
            'email' => "MessagesUser$id@example.com",
            'password' => "$%#$&ADSFBNYI*&WBV$id",
        ]);
        self::$userID = $user['userID'];

        /** @var \ConversationsApiController $conversationsAPiController */
        $conversationsAPiController = static::container()->get('ConversationsAPiController');

        $conversation = $conversationsAPiController->post([
            'participantIDs' => [self::$userID]
        ]);
        self::$conversationID = $conversation['conversationID'];

        // Disable email sending.
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get('Config');
        $config->set('Garden.Email.Disabled', true, true, false);
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
     */
    public function testDelete() {
        $this->markTestSkipped('MessageAPIController does not support delete yet.');
    }

    /**
     * {@inheritdoc}
     */
    public function testGetEdit($record = null) {
        $this->markTestSkipped('MessageAPIController does not support patch yet.');
    }

    /**
     * {@inheritdoc}
     */
    public function testGetEditFields() {
        $this->markTestSkipped('MessageAPIController does not support patch yet.');
    }

    /**
     * {@inheritdoc}
     */
    public function testPatch() {
        $this->markTestSkipped('MessageAPIController does not support patch yet.');
    }

    /**
     * {@inheritdoc}
     */
    public function testPatchSparse($field = null) {
        $this->markTestSkipped('MessageAPIController does not support patch yet.');
    }

    /**
     * {@inheritdoc}
     */
    public function testPatchFull() {
        $this->markTestSkipped('MessageAPIController does not support patch yet.');
    }
}
