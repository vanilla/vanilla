<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/conversations endpoints.
 */
class ConversationsAllowModerationTest extends ConversationsTest {

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();

        /** @var \Gdn_Configuration $config */
        $config = static::container()->get('Config');
        // Allow moderator/admins to moderate the conversations.
        $config->set('Conversations.Moderation.Allow', true, true, false);
    }

    /**
     * Test setup.
     */
    public function setUp(): void {
        parent::setUp();
        $this->moderationAllowed = true;
    }
}
