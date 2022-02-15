<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Modules;

use Vanilla\Community\UserSpotlightModule;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\Storybook\StorybookGenerationTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests covering the UserSpotlightModule and the UserSpotlightWidget.
 */
class UserSpotlightModuleTest extends StorybookGenerationTestCase {
    use LayoutTestTrait, UsersAndRolesApiTestTrait;

    public static $addons = ['vanilla'];

    /** @var string */
    private $descriptionStub = 'Mauris eu volutpat nibh. Nam non nulla vel massa congue rutrum. Nullam arcu mi, aliquet sed malesuada condimentum, bibendum at urna.';

    /** @var array */
    private $users = [];

    /**
     * Setup data for tests.
     */
    public function setUp(): void {
        parent::setUp();
        $this->users[] = $this->createUser();
    }

    /**
     * Test rendering of the UserSpotlightModule.
     */
    public function testRender() {
        $this->users[] = $this->createUser();
        $this->generateStoryHtml('/', 'User Spotlight Module');
    }

    /**
     * Event handler to mount Discussions module.
     *
     * @param \Gdn_Controller $sender
     */
    public function base_render_before(\Gdn_Controller $sender) {
        /** @var UserSpotlightModule $module */
        $module = self::container()->get(UserSpotlightModule::class);
        $module->setTitle('User Spotlight Module');
        $module->setDescription($this->descriptionStub);
        $module->setUserID($this->users[0]['userID']);
        $sender->addModule($module);
    }

    /**
     * Test hydrating the UserSpotlightWidget.
     */
    public function testHydrateUserSpotlightWidget() {
        $apiParams = [
            'userID' => $this->users[0]['userID']
        ];
        $containerOptions = [
            'borderType' => 'shadow'
        ];
        $spec = [
            '$hydrate' => 'react.userspotlight',
            'title' => 'Our top member of the month',
            'description' => $this->descriptionStub,
            'apiParams' => $apiParams,
            'containerOptions' => $containerOptions
        ];
        $expected = [
            '$reactComponent' => 'UserSpotlightWidget',
            '$reactProps' => [
                'title' => 'Our top member of the month',
                'description' => $this->descriptionStub,
                'apiParams' => $apiParams,
                'containerOptions' => $containerOptions,
                'userTextAlignment' => 'left',
                'userInfo' => [
                    'banned' => 0,
                    'name' => $this->users[0]['name'],
                    'photoUrl' => 'http://vanilla.test/applications/dashboard/design/images/defaulticon.png',
                    'url' => 'http://vanilla.test/profile/' . $this->users[0]['name'],
                    'userID' => $this->users[0]['userID'],
                    'private' => false,
                    'punished' => 0,
                ]
            ]
        ];

        $hydrator = $this->getLayoutService()->getHydrator(null);
        $actual = $hydrator->resolve($spec, []);
        // normalize dateLastActive data
        unset($actual['$reactProps']['userInfo']['dateLastActive']);

        $this->assertEquals($expected, $actual);
    }
}
