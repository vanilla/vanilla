<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\VariableProviders;

/**
 * Interface to implement to provide custom default quick-links.
 */
interface QuickLinkProviderInterface {

    /**
     * Provide some quick links.
     *
     * @return QuickLink[]
     */
    public function provideQuickLinks(): array;
}
