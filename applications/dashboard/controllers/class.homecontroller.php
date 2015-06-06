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
    public function Initialize() {
        $this->Head = new HeadModule($this);
        $this->AddJsFile('jquery.js');
        $this->AddJsFile('jquery.livequery.js');
        $this->AddJsFile('jquery.form.js');
        $this->AddJsFile('jquery.popup.js');
        $this->AddJsFile('jquery.gardenhandleajaxform.js');
        $this->AddJsFile('global.js');
        $this->AddCssFile('admin.css');
        $this->MasterView = 'empty';
        parent::Initialize();
    }

    /**
     * Display dashboard welcome message.
     *
     * @since 2.0.0
     * @access public
     */
    public function Index() {
        $this->View = 'FileNotFound';
        $this->FileNotFound();
    }

    /**
     * Display error page.
     */
    public function Error() {
        $this->RemoveCssFile('admin.css');
        $this->AddCssFile('style.css');
        $this->AddCssFile('vanillicon.css', 'static');
        $this->MasterView = 'default';

        $this->CssClass = 'SplashMessage NoPanel';

        $this->SetData('_NoMessages', true);

        $Code = $this->Data('Code', 400);
        safeheader("HTTP/1.0 $Code ".Gdn_Controller::GetStatusMessage($Code), true, $Code);
        Gdn_Theme::Section('Error');

        $this->Render();
    }

    /**
     * A standard 404 File Not Found error message is delivered when this action
     * is encountered.
     *
     * @since 2.0.0
     * @access public
     */
    public function FileNotFound() {
        $this->RemoveCssFile('admin.css');
        $this->AddCssFile('style.css');
        $this->AddCssFile('vanillicon.css', 'static');

        $this->MasterView = 'default';

        $this->CssClass = 'SplashMessage NoPanel';

        if ($this->Data('ViewPaths')) {
            Trace($this->Data('ViewPaths'), 'View Paths');
        }

        $this->SetData('_NoMessages', true);
        Gdn_Theme::Section('Error');

        if ($this->DeliveryMethod() == DELIVERY_METHOD_XHTML) {
            safeHeader("HTTP/1.0 404", true, 404);
            $this->Render();
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
    public function UpdateMode() {
        safeHeader("HTTP/1.0 503", true, 503);
        $this->SetData('UpdateMode', true);
        $this->Render();
    }

    /**
     * Display 'content deleted' page.
     *
     * @since 2.0.0
     * @access public
     */
    public function Deleted() {
        safeHeader("HTTP/1.0 410", true, 410);
        Gdn_Theme::Section('Error');
        $this->Render();
    }

    /**
     * Display TOS page.
     *
     * @since 2.0.0
     * @access public
     */
    public function TermsOfService() {
        $this->Render();
    }

    /**
     * Display privacy info page.
     *
     * @since 2.0.0
     * @access public
     */
    public function PrivacyPolicy() {
        $this->Render();
    }

    /**
     * Display 'no permission' page.
     *
     * @since 2.0.0
     * @access public
     */
    public function Permission() {
        Gdn_Theme::Section('Error');

        if ($this->DeliveryMethod() == DELIVERY_METHOD_XHTML) {
            safeHeader("HTTP/1.0 401", true, 401);
            $this->Render();
        } else {
            $this->RenderException(PermissionException());
        }
    }
}
