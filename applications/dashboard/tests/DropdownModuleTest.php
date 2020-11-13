<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard;

use PHPUnit\Framework\TestCase;
use VanillaTests\BootstrapTrait;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;

/**
 * Tests for the dropdown module.
 */
class DropdownModuleTest extends TestCase {

    use HtmlNormalizeTrait;
    use BootstrapTrait;

    /**
     * Tests for adding items.
     */
    public function testAddItems() {
        $module = new \DropdownModule();
        $module->addLinkIf(false, 'not added', 'https://notadded.com');
        $module->addLinkIf(true, 'added', 'https://added.com');
        $module->addLinkIf(true, 'match', 'http://vanilla.test/dropdownmoduletest');
        $module->addLinkIf(true, 'match partial', '/');


        $expected = [
            'item1' => [
                'text' => 'added',
                'url' => 'https://added.com',
                'key' => [
                    0 => 'item1',
                ],
                'cssClass' => ' dropdown-menu-link-item1',
                'isActive' => false,
                'listItemCssClass' => '',
                'type' => 'link',
                '_sort' => 0,
            ],
            'item2' => [
                'text' => 'match',
                'url' => 'http://vanilla.test/dropdownmoduletest',
                'key' => [
                    0 => 'item2',
                ],
                'cssClass' => ' dropdown-menu-link-item2',
                'isActive' => true,
                'listItemCssClass' => 'active',
                'type' => 'link',
                '_sort' => 1,
            ],
            'item3' => [
                'text' => 'match partial',
                'url' => '/',
                'key' => [
                    0 => 'item3',
                ],
                'cssClass' => ' dropdown-menu-link-item3',
                'isActive' => true,
                'listItemCssClass' => 'active',
                'type' => 'link',
                '_sort' => 2,
            ],
        ];
        $this->assertSame($expected, $module->getItems());
    }
}
