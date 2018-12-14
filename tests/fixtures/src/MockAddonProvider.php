<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Contracts;

class MockAddonProvider implements Contracts\AddonProviderInterface {

    /** @var array MockAddon[] */
    private $addons = [];

    /**
     * MockAddonProvider Constructor.
     *
     * @param array $addons Addons to initialize with.
     */
    public function __construct(array $addons) {
        $this->addons = $addons;
    }

    public function pushAddon(MockAddon $addon) {
        $this->addons[] = $addon;
    }

    public function getEnabled(): array {
        return $this->addons;
    }
}
