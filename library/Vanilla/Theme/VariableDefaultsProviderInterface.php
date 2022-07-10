<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

/**
 * Interface for providing variables on a theme.
 */
interface VariableDefaultsProviderInterface {

    /**
     * Get variables to include with a theme.
     *
     * @return array
     */
    public function getVariableDefaults(): array;
}
