<?php if (!defined('APPLICATION')) exit();

/**
 * Abstract module that all components can stem from.
 * Provides helper functions for rendering frequently-used HTML elements.
 * Renders php or smarty views and mustache templates.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @since 2.3
 */
abstract class ComponentModule extends Gdn_Module {

    /**
     * @var string The filename of view to render, excluding the extension.
     */
    private $view;

    /**
     * @var string How to render the view.
     */
    private $renderAs = '';

    /**
     * The constructor method should be called by all extending classes' constructors.
     * The view should be the default php view for any extending class.
     *
     * @param string $view The filename of the view to render, excluding the extension.
     */
    function __construct($view) {
        $this->view = $view;
    }

    /**
     * Outputs standardized HTML for a badge.
     * A badge generally designates a count, and displays with a contrasting background.
     *
     * @param string|int $badge Info to put into a badge, usually a number.
     * @return string Badge HTML string.
     */
    public function badge($badge) {
        return '<span class="badge">'.$badge.'</span>';
    }

    /**
     * Outputs standardized HTML for a popin badge.
     * A popin contains data that is injected after the page loads.
     * A badge generally designates a count, and displays with a contrasting background.
     *
     * @param string $rel Callback endpoint for a popin.
     * @return string Popin HTML string.
     */
    public function popin($rel) {
        return '<span class="badge Popin js-popin" rel="'.$rel.'"></span>';
    }

    /**
     * Outputs standardized HTML for an icon.
     * Uses the same css class naming conventions as font-vanillicon.
     *
     * @param string $icon Name of the icon you want to use, excluding the 'icon-' prefix.
     * @return string Icon HTML string.
     */
    public function icon($icon) {
        $icon = strtolower($icon);
        return '<span class="icon icon-'.$icon.'"></span>';
    }

    /**
     * Sets the view. Useful for overriding the default view.
     *
     * @param string $view The filename of view to render, excluding the extension.
     */
    public function setView($view) {
        $this->view = $view;
    }

    /**
     * Each component finalizes its data for output in this method. (i.e., sorts data
     * It must return a boolean value.
     *
     * @return boolean Whether to render the component.
     */
    abstract function prepare();

    /**
     * Prepares the components for output and renders the view.
     */
    public function toString() {
        if ($this->prepare()) {
            switch ($this->renderAs) {
                case 'mustache':
                    echo $this->renderMustache();
                    break;
                default:
                    echo $this->renderPhp();
                    break;
            }
        }
    }

    /**
     * Renders php views or smarty templates.
     *
     * @return string HTML content of the view.
     */
    private function renderPhp() {
        $controller = new Gdn_Controller();
        $controller->setData('sender', $this);
        return $controller->fetchView($this->view, 'modules', 'dashboard');
    }

    /**
     * Ensures mustache is properly configured and sets Mustache as the rendering engine.
     * Must be invoked to use a mustache view.
     */
    public function useMustache() {
        if (!$this->hasMustacheConfigured()) {
            // TODO: figure out how to set an error message here.
            // ErrorMessage('Mustache Engine is not configured.', , 'toString');
            return;
        }
        $this->renderAs = 'mustache';
    }

    /**
     * Checks whether Mustache is configured. Mustache can be rendered in Vanilla by enabling the Mustache plugin.
     *
     * @return bool Whether the mustache engine exists.
     */
    public function hasMustacheConfigured() {
        return class_exists('Mustache_Engine');
    }

    /**
     * Renders mustache template.
     *
     * @return string HTML content of the view.
     */
    private function renderMustache() {
        $m = new Mustache_Engine(array(
                'loader' => new Mustache_Loader_FilesystemLoader(PATH_APPLICATIONS . '/dashboard/views/modules'),
//                'helpers' => $this->helpers
        ));
        return $m->render($this->view, $this);
    }

}
