<?php
/**
 * Module interface
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * An interface for in-page modules.
 */
interface Gdn_IModule {

    /**
     * Returns the name of the asset where this component should be rendered.
     */
    public function assetTarget();

    /**
     * Returns the xhtml for this module as a fully parsed and rendered string.
     */
    public function fetchView();

    /**
     * Returns the location of the view for this module in the filesystem.
     *
     * @param string $View The name of the view to lookup.
     * @param string $ApplicationFolder The name of the application folder that contains the view.
     */
    public function fetchViewLocation($View = '', $ApplicationFolder = '');

    /**
     * Returns the name of the module.
     */
    public function name();

    /**
     * Renders the module.
     */
    public function render();
}
