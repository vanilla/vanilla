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
class MessagesTest extends AbstractResourceTest
{
    protected static $userID;

    protected static $conversationID;

    /**
     * @var bool
     */
    protected $moderationAllowed = false;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = "")
    {
        $this->baseUrl = "/messages";

        parent::__construct($name, $data, $dataName);
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setupBeforeClass();

        /**
         * @var \Gdn_Session $session
         */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo["adminUserID"], false, false);

        // Disable flood control checks on the models and make sure that those specific instances are injected into the controllers.
        $conversationModel = self::container()
            ->get(\ConversationModel::class)
            ->setFloodControlEnabled(false);
        self::container()->setInstance(\ConversationModel::class, $conversationModel);
        $conversationMessageModel = self::container()
            ->get(\ConversationMessageModel::class)
            ->setFloodControlEnabled(false);
        self::container()->setInstance(\ConversationMessageModel::class, $conversationMessageModel);

        /** @var \UsersApiController $usersAPIController */
        $usersAPIController = static::container()->get("UsersAPIController");

        $user = $usersAPIController->post([
            "name" => "MessagesUser1",
            "email" => "MessagesUser1@example.com",
            "password" => "$%#$&ADSFBNYI*&WBV1",
        ]);
        self::$userID = $user["userID"];

        /** @var \ConversationsApiController $conversationsApiController */
        $conversationsApiController = static::container()->get("ConversationsApiController");

        // Create the conversation as the newly created user.
        $session->start(self::$userID, false, false);

        $conversation = $conversationsApiController->post([
            "participantUserIDs" => [self::$userID],
        ]);
        self::$conversationID = $conversation["conversationID"];

        // Disable email sending.
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get("Config");
        $config->set("Garden.Email.Disabled", true, true, false);

        $session->end();
    }

    /**
     * We don't care about main images for this endpoint.
     */
    public function testMainImageField()
    {
        $this->markTestSkipped();
    }

    /**
     * {@inheritdoc}
     */
    public function record()
    {
        return array_merge(parent::record(), ["conversationID" => self::$conversationID]);
    }

    /**
     * {@inheritdoc}
     */
    public function indexUrl()
    {
        $indexUrl = $this->baseUrl;
        $indexUrl .= "?" . http_build_query(["conversationID" => self::$conversationID]);
        return $indexUrl;
    }

    /**
     * {@inheritdoc}
     * @requires function MessagesApiController::delete
     */
    public function testDelete()
    {
        $this->fail(__METHOD__ . " needs to be implemented");
    }

    /**
     * Test GET /resource/<id>.
     */
    public function testGet()
    {
        $this->expectModerationException();

        parent::testGet();
    }

    /**
     * {@inheritdoc}
     * @requires function MessagesApiController::patch
     */
    public function testGetEdit($record = null)
    {
        $this->fail(__METHOD__ . " needs to be implemented");
    }

    /**
     * {@inheritdoc}
     * @requires function MessagesApiController::patch
     */
    public function testGetEditFields()
    {
        $this->fail(__METHOD__ . " needs to be implemented");
    }

    /**
     * {@inheritdoc}
     * @requires function MessagesApiController::patch
     */
    public function testEditFormatCompat(string $editSuffix = "/edit")
    {
        $this->fail(__METHOD__ . " needs to be implemented");
    }

    /**
     * Test GET /messages.
     */
    public function testIndex()
    {
        $this->expectModerationException();

        parent::testIndex();
    }

    /**
     * Test POST /resource.
     *
     * @param array|null $record Fields for a new record.
     * @param array $extra Additional fields to send along with the POST request.
     * @return array Returns the new record.
     */
    public function testPost($record = null, array $extra = [])
    {
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
    public function testPatch()
    {
        $this->fail(__METHOD__ . " needs to be implemented");
    }

    /**
     * {@inheritdoc}
     * @requires function MessagesApiController::patch
     */
    public function testPatchSparse($field)
    {
        $this->fail(__METHOD__ . " needs to be implemented");
    }

    /**
     * {@inheritdoc}
     * @requires function MessagesApiController::patch
     */
    public function testPatchFull()
    {
        $this->fail(__METHOD__ . " needs to be implemented");
    }

    /**
     * Test that output is sanitized when user posts a XSS vector.
     *
     * @param array $xssVectorFormat
     * @param string $xssVector
     * @param string $sanitizedOutput
     * @return void
     * @dataProvider provideXssVectorSanitized
     */
    public function testPostXssVectorOutputSanitized(
        array $xssVectorFormat,
        string $xssVector,
        string $sanitizedOutput
    ): void {
        $postBody = $this->testPost(array_merge($this->record(), $xssVectorFormat));
        $this->assertStringNotContainsString($xssVector, $postBody["body"]);
        $this->assertStringContainsString($sanitizedOutput, $postBody["body"]);
        $messageID = $postBody["messageID"];

        // Need to switch back to user posting the message when getting the message
        $currentUserID = $this->api()->getUserID();
        $this->api()->setUserID(self::$userID);
        $response = $this->api()->get("/messages/{$messageID}");
        $this->api()->setUserID($currentUserID);

        $this->assertTrue($response->isSuccessful());
        $getBody = $response->getBody();
        $this->assertStringContainsString($sanitizedOutput, $getBody["body"]);
    }

    /**
     * XSS vector / sanitized output data provider.
     *
     * @return iterable
     */
    public function provideXssVectorSanitized(): iterable
    {
        $xssVector = '"><<iframe/><iframe src=javascript:alert(document.domain)></iframe>';
        yield "iframe with src javascript, markdown format" => [
            "xssVectorFormat" => [
                "body" => $xssVector,
                "format" => "markdown",
            ],
            "xssVector" => $xssVector,
            "sanitizedOutput" => "\"&gt;&lt;", //stripped tags
        ];
        yield "iframe with src javascript, rich format" => [
            "xssVectorFormat" => [
                "body" => json_encode([["insert" => "{$xssVector}"]]),
                "format" => "rich",
            ],
            "xssVector" => $xssVector,
            "sanitizedOutput" => htmlspecialchars($xssVector, ENT_NOQUOTES),
        ];
        yield "iframe with src javascript in code block, rich format" => [
            "xssVectorFormat" => [
                "body" => json_encode([
                    [
                        "attributes" => ["code" => true],
                        "insert" => "{$xssVector}",
                    ],
                    ["insert" => '\n'],
                ]),
                "format" => "rich",
            ],
            "xssVector" => $xssVector,
            "sanitizedOutput" => htmlspecialchars($xssVector, ENT_NOQUOTES),
        ];
    }

    /**
     * Expect exceptions if conversation moderation isn't allowed.
     */
    private function expectModerationException(): void
    {
        if (!$this->moderationAllowed) {
            $this->expectException(\Exception::class);
            $this->expectExceptionCode(403);
            $this->expectExceptionMessage("The site is not configured for moderating conversations.");
        }
    }
}
