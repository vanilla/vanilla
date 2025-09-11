<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers;

use RoleModel;
use Vanilla\Dashboard\Models\AiConversationModel;
use Vanilla\Models\ContentDraftModel;
use VanillaTests\SiteTestCase;

/**
 * Tests for the the role settings page /role
 */
class RoleControllerTest extends SiteTestCase
{
    /**
     * Test listing of roles.
     *
     * @return void
     */
    public function testListRoles()
    {
        $res = $this->bessy()->getHtml("/role");

        $allRoles = RoleModel::roles();
        foreach ($allRoles as $role) {
            $res->assertCssSelectorText("td", $role["Name"]);
        }
    }

    /**
     * Test the add role page.
     */
    public function testAddRole(): void
    {
        $res = $this->bessy()->getHtml("/role/add");
        $res->assertCssSelectorText("h1", "Add Role");
        $res->assertCssSelectorText(".RolePermissions th", "Settings");

        // Check that we have some basic permissions
        $res->assertCssSelectorExists("input[type='checkbox'][value='Garden.Settings.Manage']");
    }

    /**
     * Test feature flag filtering on the add/edit role page.
     *
     * @return void
     */
    public function testFilterFeatureFlags(): void
    {
        self::disableFeature(AiConversationModel::AI_CONVERSATION_FEATURE_FLAG);
        self::disableFeature(ContentDraftModel::FEATURE_SCHEDULE);
        $res = $this->bessy()->getHtml("/role/add");

        // These feature flagged permissions should be hidden
        $res->assertCssSelectorNotExists("input[type='checkbox'][value='Garden.Schedule.Allow']");
        $res->assertCssSelectorNotExists("input[type='checkbox'][value='Garden.aiAssistedSearch.View']");

        // Enable the feature flags and they should appear.
        self::enableFeature(AiConversationModel::AI_CONVERSATION_FEATURE_FLAG);
        self::enableFeature(ContentDraftModel::FEATURE_SCHEDULE);
        $res = $this->bessy()->getHtml("/role/edit/" . RoleModel::ADMIN_ID);
        $res->assertCssSelectorExists("input[type='checkbox'][value='Garden.Schedule.Allow']");
        $res->assertCssSelectorExists("input[type='checkbox'][value='Garden.aiAssistedSearch.View']");
    }
}
