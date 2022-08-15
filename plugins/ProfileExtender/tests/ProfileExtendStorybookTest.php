<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Addons\ProfileExtender;

use VanillaTests\Storybook\StorybookGenerationTestCase;

/**
 * Class ProfileExtendStorybookTests
 *
 * @package VanillaTests\Addons\ProfileExtender
 */
class ProfileExtendStorybookTest extends StorybookGenerationTestCase
{
    use ProfileExtenderTestTrait;

    /**
     * {@inheritdoc}
     */
    public static function getAddons(): array
    {
        return ["vanilla", "profileextender"];
    }

    /**
     * Test field is on edit profile page.
     */
    public function testEditProfile()
    {
        $this->getProfileExtenderPlugin()->updateUserFields(\Gdn::session()->User->UserID, [
            "text" => "This is a text field",
            "dropdown" => "Option2",
            "check" => true,
        ]);

        $encodedName = rawurlencode(\Gdn::session()->User->Name);
        $this->generateStoryHtml("/profile/edit/$encodedName", "Edit Profile (Extended)");
    }
}
