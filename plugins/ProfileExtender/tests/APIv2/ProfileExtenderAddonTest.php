<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ProfileExtender\Tests\APIv2;

use Exception;
use Gdn_Configuration;
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
            'Name' => 'DateOfBirth',
            'Label' => 'Birthday',
            'FormType' => 'DateOfBirth'
        ]);

        $this->bessy()->post('/settings/profile-field-add-edit', [
            'Name' => 'dropdown',
            'Label' => 'Dropdown',
            'FormType' => 'Dropdown',
            'Options' => "Option1\nOption2"
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

    /**
     * Test editing Profile Extender fields over the APIv2 users/{id}/extended endpoint.
     *
     * @param string $field
     * @param string|bool $value
     * @param string $fieldToCheck
     * @param string|bool $expectedValue
     * @dataProvider provideTestPatchUsersExtendedEndpointData
     */
    public function testPatchUsersExtendedEndpoint($field, $value, $fieldToCheck, $expectedValue) {
        $id = $this->memberID;
        $result = $this->api()->patch("/users/{$id}/extended", [$field => $value])->getBody();
        $this->assertSame($result[$fieldToCheck], $expectedValue);
    }

    /**
     * Provides an array of data for testPatchUsersExtendedEndpoint().
     *
     * @return array
     */
    public function provideTestPatchUsersExtendedEndpointData(): array {
        $data = [
            'testTextInput' => [
                'text',
                'sometext',
                'text',
                'sometext'
            ],
            'testCheckboxInput' => [
                'check',
                true,
                'check',
                true,
            ],
            'textDropdown' => [
                'dropdown',
                'Option2',
                'dropdown',
                'Option2',
            ]
        ];

        return $data;
    }

    /**
     * Test patching multiple fields from
     */
    public function testPatchingMultipleFieldsFromExtendedEndpoint(): void {
        $id = $this->memberID;
        $fieldsToPatch = [
            'text' => 'foo',
            'check' => false,
            'DateOfBirth' => '1980-06-17',
            'dropdown' => 'Option1'
        ];
        $result = $this->api()->patch("/users/{$id}/extended", $fieldsToPatch)->getBody();
        $formattedDate = new \DateTimeImmutable($result['DateOfBirth']);
        $result['DateOfBirth'] = $formattedDate->format('Y-m-d');
        $this->assertEquals($fieldsToPatch, $result);
    }

    /**
     * Test patching invalid data.
     */
    public function testPatchWithBadValues(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("DateOfBirth is not a valid datetime.");
        $this->expectExceptionMessage("dropdown must be one of: Option1, Option2.");
        $this->expectExceptionMessage("text is not a valid string.");
        $id = $this->memberID;
        $fieldsToPatch = [
            'text' => false,
            'DateOfBirth' => true,
            'dropdown' => 'Option3'
        ];
        $this->api()->patch("/users/{$id}/extended", $fieldsToPatch);
    }

    /**
     * Test schema.
     */
    public function testSchemaExists(): void {
        $openApi = $this->api()->get('/open-api/v3')->getBody();
        $schemaProperties = $openApi['components']['schemas']['ExtendedUserFields']['properties'];
        $this->assertSame($schemaProperties['text']['type'], 'string');
        $this->assertSame($schemaProperties['check']['type'], 'boolean');
        $this->assertSame($schemaProperties['DateOfBirth']['type'], 'string');
        $this->assertSame($schemaProperties['DateOfBirth']['format'], 'date-time');
        $this->assertSame($schemaProperties['dropdown']['type'], 'string');
        $this->assertSame(count($schemaProperties['dropdown']['enum']), 2);
    }

    /**
     * Test profile fields reordering.
     */
    public function testProfileFieldsReordering(): void {
        $id = $this->memberID;
        $fieldsToPatch = [
            'text' => 'foo',
            'check' => false,
            'DateOfBirth' => '1980-06-17',
            'dropdown' => 'Option1'
        ];

        $this->api()->patch("/users/{$id}/extended", $fieldsToPatch);
        $profileFields = $this->profileExtender->getUserFields($this->memberID);
        $config = self::container()->get(Gdn_Configuration::class);
        $fieldsInConfig = $config->get('ProfileExtender.Fields');
        $configFieldsNamesAsKey = array_flip(array_column($fieldsInConfig, "Name"));

        //order is not the same
        $this->assertNotEquals(array_key_first($profileFields), array_key_first($configFieldsNamesAsKey));
        $reorderedProfileFields = $this->profileExtender->reorderProfileFields($profileFields);
        $this->assertIsArray($reorderedProfileFields);
        $this->assertCount(count($profileFields), $reorderedProfileFields);
        //reorder array should be the same as the initial one, only order is changed
        $this->assertEquals($profileFields, $reorderedProfileFields);

        //and order should match with the one in config
        $this->assertEquals(array_key_first($reorderedProfileFields), array_key_first($configFieldsNamesAsKey));

        //scenario when user has more profile fields than in config
        $newFieldsInConfig = array_splice($fieldsInConfig, 0, 3);
        $config->set('ProfileExtender.Fields', $newFieldsInConfig);
        $newFieldsInConfig = $config->get('ProfileExtender.Fields');
        $newConfigFieldsNamesAsKey = array_flip(array_column($newFieldsInConfig, "Name"));
        $this->assertNotEquals(array_key_first($profileFields), array_key_first($newConfigFieldsNamesAsKey));
        $newReorderedProfileFields = $this->profileExtender->reorderProfileFields($profileFields);

        //should still be the same as the initial one, even if in the config now we have less fields
        $this->assertEquals($profileFields, $newReorderedProfileFields);
    }
}
