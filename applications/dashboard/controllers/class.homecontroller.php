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

    /**
     * Sanitize a string according to the filter specified _Filter in the data array.
     *
     * The valid values for _Filter are:
     *
     * - none: No sanitization.
     * - filter: Sanitize using {@link Gdn_Format::htmlFilter()}.
     * - safe: Sanitize using {@link htmlspecialchars()}.
     *
     * @param string $string The string to sanitize.
     * @return string Returns the sanitized string.
     */
    protected function sanitize($string) {
        switch ($this->data('_Filter', 'safe')) {
            case 'none':
                return $string;
            case 'filter':
                return Gdn_Format::htmlFilter($string);
            case 'safe':
            default:
                return htmlspecialchars($string);
        }
    }

    /**
     * Sanitize the main exception fields in the data array according to the _Filter key.
     * @see HomeController::sanitize()
     */
    protected function sanitizeData() {
        $fields = array('Exception', 'Message', 'Description');

        $method = $this->data('_Filter', 'safe');
        switch ($method) {
            case 'none':
                return;
            case 'filter':
                $callback = array('Gdn_Format', 'htmlFilter');
                break;
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
    public function permission() {
        Gdn_Theme::section('Error');

        if ($this->deliveryMethod() == DELIVERY_METHOD_XHTML) {
            safeHeader("HTTP/1.0 401", true, 401);
            $this->render();
        } else {
            $this->RenderException(permissionException());
        }
    }
}
