<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ProfileExtender\Tests\APIv2;

use Vanilla\Attributes;

/**
 * Tests for the profile extender addon.
 */
class ProfileExtenderAddonTest extends \VanillaTests\SiteTestCase {
    /**
     * @var \ProfileExtenderPlugin
     */
    protected $profileExtender;

    /**
     * {@inheritdoc}
     */
    public static function getAddons(): array {
        return ['vanilla', 'profileextender'];
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
        parent::setUp();

        $this->container()->call(function (\ProfileExtenderPlugin $profileExtender) {
            $this->profileExtender = $profileExtender;
        });

        $this->bessy()->post('/settings/profile-field-add-edit', [
            'Name' => 'text',
            'Label' => 'Text',
            'FormType' => 'TextBox'
        ]);

        $this->bessy()->post('/settings/profile-field-add-edit', [
            'Name' => 'check',
            'Label' => 'Check',
            'FormType' => 'CheckBox'
        ]);

        $this->bessy()->post('/settings/profile-field-add-edit', [
            'Name' => 'birthday',
            'Label' => 'Birthday',
            'FormType' => 'DateOfBirth'
        ]);

        $this->createUserFixtures();
    }

    /**
     * Test the basic profile extender get/set flow.
     */
    public function testUpdateUserField(): void {
        $this->profileExtender->updateUserFields($this->memberID, ['text' => __FUNCTION__]);
        $values = $this->profileExtender->getUserFields($this->memberID);
        $this->assertSame(__FUNCTION__, $values['text']);
    }

    /**
     * Test basic profile field expansion.
     */
    public function testBasicExpansion(): void {
        $fields = ['text' => __FUNCTION__, 'check' => true];

        $this->profileExtender->updateUserFields($this->memberID, $fields);
        $data = $this->api()->get("/users/{$this->memberID}", ['expand' => \ProfileExtenderPlugin::FIELD_EXTENDED])->getBody();
        $this->assertArraySubsetRecursive($fields, $data['extended']);
    }

    /**
     * Verify our expander still creates an empty Attributes object for users with no extended profile fields.
     */
    public function testEmptyExpansion(): void {
        $result = $this->profileExtender->getUserProfileValuesChecked([$this->memberID]);
        $this->assertInstanceOf(Attributes::class, $result[$this->memberID]);
        $this->assertSame(0, $result[$this->memberID]->count());
    }

    /**
     * The /users/me endpoint should expand all fields.
     */
    public function testMeDefaultExpansion(): void {
        $fields = ['text' => __FUNCTION__, 'check' => true];

        $this->profileExtender->updateUserFields($this->api()->getUserID(), $fields);
        $data = $this->api()->get("/users/me")->getBody();
        $this->assertArraySubsetRecursive($fields, $data['extended']);
    }

    /**
     * Verify Profile Extender values appear when editing user profiles, complete with values.
     */
    public function testFieldsOnEditProfile(): void {
        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $this->profileExtender->updateUserFields($session->UserID, ['text' => __FUNCTION__]);

        $result = $this->bessy()->getHtml("profile/edit");
        $result->assertFormInput("text", __FUNCTION__);
    }
}
