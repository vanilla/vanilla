<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/conversations endpoints.
 */
class ConversationsAllowModerationTest extends ConversationsTest {

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass() {
        parent::setupBeforeClass();

        /** @var \Gdn_Configuration $config */
        $config = static::container()->get('Config');
        // Allow moderator/admins to moderate the conversations.
        $config->set('Conversations.Moderation.Allow', true, true, false);
    }

    /**
     * Test GET /conversations/<id>.
     *
     * @return array
     */
    public function testGet() {
        return parent::testGet();
    }

    /**
     * Test GET /conversations/<id>/participants.
     */
    public function testGetParticipants() {
        parent::testGetParticipants();
    }

    /**
     * Test GET /conversations.
     *
     * @return array Returns the fetched data.
     */
    public function testIndex() {
        parent::testIndex();
    }

    /**
     * Test POST /conversations/<id>/participants.
     *
     * @return array The conversation.
     */
    public function testPostParticipants() {
        return parent::testPostParticipants();
    }
}
