<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ProfileExtender\Tests\APIv2;

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

        $r = $this->bessy()->post('/settings/profile-field-add-edit', [
            'Name' => 'text',
            'Label' => 'Text',
            'FormType' => 'TextBox'
        ]);

        $r = $this->bessy()->post('/settings/profile-field-add-edit', [
            'Name' => 'check',
            'Label' => 'Check',
            'FormType' => 'CheckBox'
        ]);

        $r = $this->bessy()->post('/settings/profile-field-add-edit', [
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
     * The /users/me endpoint should expand all fields.
     */
    public function testMeDefaultExpansion(): void {
        $fields = ['text' => __FUNCTION__, 'check' => true];

        $this->profileExtender->updateUserFields($this->api()->getUserID(), $fields);
        $data = $this->api()->get("/users/me")->getBody();
        $this->assertArraySubsetRecursive($fields, $data['extended']);
    }
}
