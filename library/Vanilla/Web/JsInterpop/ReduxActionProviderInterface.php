<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\JsInterpop;

/**
 * Interface for lazily generating redux actions.
 *
 * This is useful if you want to lazily create actions for a controller through some container configuration.
 */
interface ReduxActionProviderInterface {
    /**
     * @return ReduxAction[]
     */
    public function createActions(): array;
}
