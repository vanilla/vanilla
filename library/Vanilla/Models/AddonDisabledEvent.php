<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\Addon;

/**
 * PSR event that is dispatched when an addon is disabled.
 */
class AddonDisabledEvent
{
    /** @var Addon */
    private $addon;

    /**
     * Constructor.
     *
     * @param Addon $addon
     */
    public function __construct(Addon $addon)
    {
        $this->addon = $addon;
    }

    /**
     * @return Addon
     */
    public function getAddon(): Addon
    {
        return $this->addon;
    }
}
