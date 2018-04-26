<?php
/**
 * Manages default info, error, and site status pages.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('global.js');
        $this->addCssFile('admin.css');
        $this->MasterView = 'empty';
        $this->canonicalUrl('');
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

        $code = $this->data('Code', 400);
        $this->clearNavigationPreferences();
        safeheader("HTTP/1.0 $code ".Gdn_Controller::getStatusMessage($code), true, $code);
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
            $this->clearNavigationPreferences();
            safeHeader("HTTP/1.0 404", true, 404);
            $this->render();
        } else {
            $this->renderException(notFoundException());
        }
    }

    /**
     * Clears the request uri from the user's navigation preferences. This stops the user from getting locked out of
     * the dashboard if they saved a preference for a page that no longer exists or that they no longer have
     * permission to view.
     */
    private function clearNavigationPreferences() {
        if (Gdn::session()->isValid()) {
            $uri = Gdn::request()->getRequestArguments('server')['REQUEST_URI'];
            $userModel = new UserModel();
            $userModel->clearSectionNavigationPreference($uri);
        }
    }

    /**
     * @param string $target
     */
    public function leaving($target = '') {
        $this->setData('Target', $target);
        $this->title(t('Leaving'));
        $this->removeCssFile('admin.css');
        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');
        $this->MasterView = 'default';
        $this->render();
    }

    /**
     * Display 'site down for maintenance' page.
     *
     * @since 2.0.0
     * @access public
     */
    public function updateMode() {
        $this->clearNavigationPreferences();
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
        $this->clearNavigationPreferences();
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
        $fields = ['Exception', 'Message', 'Description'];

        $method = $this->data('_Filter', 'safe');
        switch ($method) {
            case 'none':
                return;
            case 'filter':
                $callback = ['Gdn_Format', 'htmlFilter'];
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
    public function unauthorized() {
        Gdn_Theme::section('Error');

        if ($this->deliveryMethod() == DELIVERY_METHOD_XHTML) {
            $this->clearNavigationPreferences();
            safeHeader("HTTP/1.0 401", true, 401);
            $this->render();
        } else {
            $this->renderException(permissionException());
        }
    }
}
