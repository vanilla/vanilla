<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Vanilla\Addon;
use VanillaTests\Fixtures\MockAddon;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for the addon class.
 */
class AddonTest extends MinimalContainerTestCase {

    /**
     * Test that the name of an addon is correct.
     *
     * @param Addon $addon
     * @param string $expectedName
     *
     * @dataProvider provideAddonNames
     */
    public function testAddonName(Addon $addon, string $expectedName) {
        $this->assertEquals($expectedName, $addon->getName());
    }

    /**
     * @return array
     */
    public function provideAddonNames(): array {
        return [
            [
                new MockAddon('addon1', [ 'name' => 'Addon 1' ]),
                'Addon 1',
            ],
            [
                new MockAddon('addon2', [
                    'name' => 'Addon 2',
                    'displayName' => 'Addon 2 Display Name',
                ]),
                'Addon 2 Display Name',
            ],
            [
                new MockAddon('addon3'),
                'addon3',
            ]
        ];
    }
}
