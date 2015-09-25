<?php
/**
 * Manages default info, error, and site status pages.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /home endpoint.
 */
class HomeController extends Gdn_Controller {

    /**
     * JS & CSS includes for all methods in this controller.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        $this->Head = new HeadModule($this);
        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.livequery.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('global.js');
        $this->addCssFile('admin.css');
        $this->MasterView = 'empty';
        parent::initialize();
    }

    /**
     * Display dashboard welcome message.
     *
     * @since 2.0.0
     * @access public
     */
    public function index() {
        $this->View = 'FileNotFound';
        $this->fileNotFound();
    }

    /**
     * Display error page.
     */
    public function error() {
        $this->removeCssFile('admin.css');
        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');
        $this->MasterView = 'default';

        $this->CssClass = 'SplashMessage NoPanel';
        if ($this->data('CssClass')) {
            $this->CssClass .= ' '.$this->data('CssClass');
        }

        $this->setData('_NoMessages', true);

        $Code = $this->data('Code', 400);
        safeheader("HTTP/1.0 $Code ".Gdn_Controller::GetStatusMessage($Code), true, $Code);
        Gdn_Theme::section('Error');

        $this->render();
    }

    /**
     * A standard 404 File Not Found error message is delivered when this action
     * is encountered.
     *
     * @since 2.0.0
     * @access public
     */
    public function fileNotFound() {
        $this->removeCssFile('admin.css');
        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');

        $this->MasterView = 'default';

        $this->CssClass = 'SplashMessage NoPanel';

        if ($this->data('ViewPaths')) {
            trace($this->data('ViewPaths'), 'View Paths');
        }

        $this->setData('_NoMessages', true);
        Gdn_Theme::section('Error');

        if ($this->deliveryMethod() == DELIVERY_METHOD_XHTML) {
            safeHeader("HTTP/1.0 404", true, 404);
            $this->render();
        } else {
            $this->RenderException(NotFoundException());
        }
    }

    /**
     * Display 'site down for maintenance' page.
     *
     * @since 2.0.0
     * @access public
     */
    public function updateMode() {
        safeHeader("HTTP/1.0 503", true, 503);
        $this->setData('UpdateMode', true);
        $this->render();
    }

    /**
     * Display 'content deleted' page.
     *
     * @since 2.0.0
     * @access public
     */
    public function deleted() {
        safeHeader("HTTP/1.0 410", true, 410);
        Gdn_Theme::section('Error');
        $this->render();
    }

    /**
     * Display TOS page.
     *
     * @since 2.0.0
     * @access public
     */
    public function termsOfService() {
        $this->render();
    }

    protected function sanitizeData() {
        $fields = array('Exception', 'Message', 'Description');

        $method = $this->data('_Filter', 'safe');
        switch ($method) {
            case 'filter':
                $callback = array('Gdn_Format', 'htmlFilter');
                break;
            case 'none':
                return;
            case 'safe':
            default:
                $callback = 'htmlspecialchars';
        }

        foreach ($fields as $field) {
            if (isset($this->Data[$field]) && is_string($this->Data[$field])) {
                $this->Data[$field] = call_user_func($callback, $this->Data[$field]);
            }
        }
    }

    /**
     * Display privacy info page.
     *
     * @since 2.0.0
     * @access public
     */
    public function privacyPolicy() {
        $this->render();
    }

    /**
     * Display 'no permission' page.
     *
     * @since 2.0.0
     * @access public
     */
    public function unauthorized() {
        Gdn_Theme::section('Error');

        if ($this->deliveryMethod() == DELIVERY_METHOD_XHTML) {
            safeHeader("HTTP/1.0 401", true, 401);
            $this->render();
        } else {
            $this->RenderException(permissionException());
        }
    }

    /**
     * Sanitize basic error data before rendering it off.
     *
     * By default the main error fields will be passed through htmlspecialchars.
     * To opt out of this the _Filter data key can be set to one of the following:
     *
     * - none: Do not filter.
     * - filter: Just do basic html filtering to remove scripts and other malicious tags.
     *
     * @param string $View
     * @param string $ControllerName
     * @param string $ApplicationFolder
     * @param string $AssetName The name of the asset container that the content should be rendered in.
     */
    public function xRender($View = '', $ControllerName = false, $ApplicationFolder = false, $AssetName = 'Content') {
        $this->sanitizeData();

        return parent::xRender($View, $ControllerName, $ApplicationFolder, $AssetName);
    }
}
