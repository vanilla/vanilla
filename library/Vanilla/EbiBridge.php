<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

use Ebi\Ebi;
use Garden\EventManager;

/**
 * Enables Ebi templates to be used as views.
 */
class EbiBridge {
    /**
     * @var Ebi
     */
    private $ebi;

    public function __construct(Ebi $ebi) {
        // Add custom components.
        $ebi->defineComponent('asset', function ($props) use ($ebi) {
            if ($controller = $ebi->getMeta('.controller')) {
                /* @var \Gdn_Controller $controller */
                $controller->renderAsset($props['name']);
            }
        });

        // Add custom functions.
        $ebi->defineFunction('t');

        $this->ebi = $ebi;
    }

    /**
     * Render the given view.
     *
     * @param string $path The path to the view's file.
     * @param \Gdn_Controller $controller The controller that is rendering the view.
     * @param Addon $addon The addon that owns the view.
     */
    public function render($path, $controller, Addon $addon = null) {
        /* @var \Vanilla\EbiTemplateLoader $loader */
        $loader = $this->ebi->getTemplateLoader();
        $loader->setCurrentAddon($addon);

        // Figure out the component name.
        $component = str_replace('/', '.', substr($path, strrpos($path, '/views/') + 7, -5));

        // Load helpers.
        $this->loadHelpers($component, $addon);

        // Set up the initial data.
        $data = $controller ? $controller->Data : [];

        $this->ebi->setMeta('.controller', $controller); // hidden from template, must use special components.

        // Write the component.
        $this->ebi->write($component, $data);
    }

    /**
     * Load the helpers for a component.
     *
     * Helpers are files that contain several components are are always loaded when any view is rendered. Whenever a
     * view is rendered the following helpers are included.
     *
     * - All the helpers.html files at the root of the theme and addon that owns the component.
     * - All of the helpers in the same path as the component for the addon and the current themes.
     *
     * Helpers are loaded in reverse order so if a theme overrides a component it will take precedence.
     *
     * @param string $component The name of the component to load the helpers for.
     * @param Addon $baseAddon The base addon of the component.
     */
    private function loadHelpers($component, Addon $baseAddon) {
        // Grab the names of helper components we need.
        $helpers = ['helpers'];
        if ($current = strrchr($component, '.')) {
            $helpers[] = $current.'helpers';
        }

        // Grab the current addons.
        $loader = $this->getEbi()->getTemplateLoader();
        if ($loader instanceof EbiTemplateLoader) {
            /* @var EbiTemplateLoader $loader */
            $addons = array_reverse($loader->searchAddons($baseAddon));

            foreach ($addons as $addon) {
                /* @var Addon $addon */
                foreach ($helpers as $helper) {
                    $this->getEbi()->lookup($addon->getKey().'-'.$addon->getType().':'.$helper);
                }
            }
        }
    }

    /**
     * Get the ebi.
     *
     * @return Ebi Returns the ebi.
     */
    public function getEbi() {
        return $this->ebi;
    }
}
