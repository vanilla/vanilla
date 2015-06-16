<?php if (!defined('APPLICATION')) exit();

/**
 * A module for all mustache-rendered classes.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @since 2.3
 */
abstract class ComponentModule extends Gdn_Module {

    private $view;

    public $helpers;

    private $isMustacheView = false;

    function __construct($view) {
        $this->view = $view;
    }

    public function badge($badge) {
        return '<span class="badge">'.$badge.'</span>';
    }

    public function popin($rel) {
        return '<span class="badge Popin" rel="'.$rel.'"></span>';
    }

    public function icon($icon) {
        return '<span class="icon icon-'.$icon.'"></span>';
    }

    public function setView($view) {
        $this->view = $view;
    }

    /**
     * Renders menu view
     *
     * @return string
     */
    public function toString() {
        if ($this->prepare()) {
            if ($this->isMustacheView) {
                return $this->renderMustacheView();
            }
            $controller = new Gdn_Controller();
            $controller->setData('sender', $this);
            echo $controller->fetchView($this->view, 'modules', 'dashboard');
        }
        return '';
    }

    public function useMustache() {
        if (!$this->hasMustacheConfigured()) {
            // ErrorMessage('Mustache Engine is not configured.', , 'toString');
            return;
        }
        $this->isMustacheView = true;
    }

    public function hasMustacheConfigured() {
        return class_exists('Mustache_Engine');
    }

    private function renderMustacheView() {
        $m = new Mustache_Engine(array(
                'loader' => new Mustache_Loader_FilesystemLoader(PATH_APPLICATIONS . '/dashboard/views/modules'),
                'helpers' => $this->helpers
        ));
        return $m->render($this->view, $this);
    }

    /**
     * Where each module finalizes its data for output.
     * Must return a boolean value.
     *
     * @return boolean Whether to render module
     */
    abstract function prepare();

}
