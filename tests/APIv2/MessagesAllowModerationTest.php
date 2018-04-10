<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/messages endpoints.
 */
class MessagesAllowModerationTest extends MessagesTest {

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass() {
        parent::setupBeforeClass();

        // Allow moderator/admins to moderate the conversations.
        $config = static::container()->get('Config');
        $config->set('Conversations.Moderation.Allow', true, true, false);
    }

    /**
     * Test GET /resource/<id>.
     */
    public function testGet() {
        parent::testGet();
    }

    /**
     * Test GET /messages.
     */
    public function testIndex() {
        parent::testIndex();
    }
}
