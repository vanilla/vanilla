<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Feature;

use Vanilla\Dashboard\Models\ProductMessageModel;

/**
 * Class representing a set of functionality in the product and if it has been enabled/used yet.
 *
 * Used in conjunction with {@link ProductMessageModel} to determine what message site admins should receive.
 */
abstract class Feature
{
    public function __construct(public string $featureID)
    {
    }

    /**
     * Check if the feature is enabled.
     *
     * @return bool
     */
    abstract public function isEnabled(): bool;
}
