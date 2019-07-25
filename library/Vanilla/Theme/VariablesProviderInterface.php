<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

 namespace Vanilla\Theme;

 /**
  * Interface for providing variables on a theme.
  */
interface VariablesProviderInterface {

    /**
     * Get variables to include with a theme.
     *
     * @return array
     */
    public function getVariables(): array;
}
