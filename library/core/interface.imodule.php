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
    public function AssetTarget();

    /**
     * Returns the xhtml for this module as a fully parsed and rendered string.
     */
    public function FetchView();

    /**
     * Returns the location of the view for this module in the filesystem.
     *
     * @param string $View
     * @param string $ApplicationFolder
     */
    public function FetchViewLocation($View = '', $ApplicationFolder = '');

    /**
     * Returns the name of the module.
     */
    public function Name();

    /**
     * Renders the module.
     */
    public function Render();
}
