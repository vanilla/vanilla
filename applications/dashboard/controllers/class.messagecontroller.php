<?php
/**
 * Messages are used to display (optionally dismissable) information in various parts of the applications.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /message endpoint.
 */
class MessageController extends DashboardController {

    /** @var array Objects to prep. */
    public $Uses = array('Form', 'MessageModel');

    /**
     * Form to create a new message.
     *
     * @since 2.0.0
     * @access public
     */
    public function add() {
        $this->permission('Garden.Community.Manage');
        // Use the edit form with no MessageID specified.
        $this->View = 'Edit';
        $this->edit();
    }

    /**
     * Delete a message.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int|string $MessageID
     */
    public function delete($MessageID = '') {
        $this->permission('Garden.Community.Manage');

        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $this->MessageModel->delete(array('MessageID' => $MessageID));

        // Reset the message cache
        $this->MessageModel->setMessageCache();

        $this->informMessage(sprintf(t('%s deleted'), t('Message')));
        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * Dismiss a message (per user).
     *
     * @since 2.0.0
     * @access public
     *
     * @param int|string $MessageID
     * @param mixed $TransientKey
     */
    public function dismiss($MessageID = '', $TransientKey = false) {
        $Session = Gdn::session();

        if ($TransientKey !== false && $Session->validateTransientKey($TransientKey)) {
            $Prefs = $Session->getPreference('DismissedMessages', array());
            $Prefs[] = $MessageID;
            $Session->setPreference('DismissedMessages', $Prefs);
        }

        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            redirectTo(getIncomingValue('Target', '/discussions'), 302, false);
        }

        $this->render();
    }

    /**
     * Form to edit an existing message.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int|string $MessageID
     */
    public function edit($MessageID = '') {
        $this->addJsFile('jquery.autosize.min.js');

        $this->permission('Garden.Community.Manage');
        $this->setHighlightRoute('dashboard/message');

        // Generate some Controller & Asset data arrays
        $this->setData('Locations', $this->_getLocationData());
        $this->AssetData = $this->_getAssetData();

        // Set the model on the form.
        $this->Form->setModel($this->MessageModel);
        $this->Message = $this->MessageModel->getID($MessageID);
        $this->Message = $this->MessageModel->defineLocation($this->Message);

        // Make sure the form knows which item we are editing.
        if (is_numeric($MessageID) && $MessageID > 0) {
            $this->Form->addHidden('MessageID', $MessageID);
        }

        $CategoriesData = CategoryModel::categories();
        $Categories = array();
        foreach ($CategoriesData as $Row) {
            if ($Row['CategoryID'] < 0) {
                continue;
            }

            $Categories[$Row['CategoryID']] = str_repeat('&nbsp;&nbsp;&nbsp;', max(0, $Row['Depth'] - 1)).$Row['Name'];
        }
        $this->setData('Categories', $Categories);

        // If seeing the form for the first time...
        if (!$this->Form->authenticatedPostBack()) {
            $this->Form->setData($this->Message);
        } else {
            if ($MessageID = $this->Form->save()) {
                // Reset the message cache
                $this->MessageModel->setMessageCache();

                // Redirect
                $this->informMessage(t('Your changes have been saved.'));
                //$this->RedirectUrl = url('dashboard/message');
            }
        }
        $this->render();
    }

    /**
     * Main page. Show all messages.
     *
     * @since 2.0.0
     * @access public
     */
    public function index() {
        $this->permission('Garden.Community.Manage');
        $this->setHighlightRoute('dashboard/message');
        $this->addJsFile('jquery.autosize.min.js');
        $this->addJsFile('jquery.tablednd.js');
        $this->title(t('Messages'));
        Gdn_Theme::section('Moderation');

        // Load all messages from the db
        $this->MessageData = $this->MessageModel->get('Sort');
        $this->render();
    }

    /**
     * Always triggers first. Highlight path.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        parent::initialize();
        Gdn_Theme::section('Dashboard');
        if ($this->Menu) {
            $this->Menu->highlightRoute('/dashboard/settings');
        }
    }

    /**
     * Enable a message.
     *
     * @param $messageID
     * @throws Exception
     */
    public function enable($messageID) {
        $this->permission('Garden.Community.Manage');
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        if ($messageID && is_numeric($messageID)) {
            $this->setEnabled($messageID, 1);
        }
    }

    /**
     * Disable a message.
     *
     * @param $messageID
     * @throws Exception
     */
    public function disable($messageID) {
        $this->permission('Garden.Community.Manage');
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        if ($messageID && is_numeric($messageID)) {
            $this->setEnabled($messageID, 0);
        }
    }

    /**
     * Generic method to set message state on/off.
     *
     * @param $messageID
     * @param $enabled
     */
    protected function setEnabled($messageID, $enabled) {
        $messageModel = new MessageModel();
        $enabled = forceBool($enabled, '0', '1', '0');
        $messageModel->setProperty($messageID, 'Enabled', $enabled);
        $this->MessageModel->setMessageCache();
        if ($enabled === '1') {
            $newToggle = wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/message/disable/'.$messageID, 'Hijack'), 'span', array('class' => "toggle-wrap toggle-wrap-on"));
        } else {
            $newToggle = wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/message/enable/'.$messageID, 'Hijack'), 'span', array('class' => "toggle-wrap toggle-wrap-off"));
        }
        $this->jsonTarget("#toggle-".$messageID, $newToggle);
        if ($enabled === '1') {
            $this->informMessage(sprintf(t('%s enabled.'), t('Message')));
        } else {
            $this->informMessage(sprintf(t('%s disabled.'), t('Message')));
        }
        Gdn::cache()->remove('Messages');
        $this->render('Blank', 'Utility');
    }

    /**
     * Get descriptions of asset locations on page.
     *
     * @since 2.0.0
     * @access protected
     *
     * @return array
     */
    protected function _getAssetData() {
        $AssetData = [
            'Content' => t('Above Main Content'),
            'Panel' => t('Below Sidebar')
        ];

        $this->EventArguments['AssetData'] = &$AssetData;
        $this->fireEvent('AfterGetAssetData');

        return $AssetData;
    }

    /**
     * Get descriptions of asset locations across site.
     *
     * @since 2.0.0
     * @access protected
     *
     * @return array
     */
    protected function _getLocationData() {
        $ControllerData = [
            '[Base]' => t('All Pages'),
            '[NonAdmin]' => t('All Forum Pages'),
            'Dashboard/Profile/Index' => t('Profile Page'),
            'Vanilla/Discussions/Index' => t('Discussions Page'),
            'Vanilla/Categories/Index' => t('Categories Page'),
            'Vanilla/Discussion/Index' => t('Comments Page'),
            'Vanilla/Post/Discussion' => t('New Discussion Form'),
            'Dashboard/Entry/SignIn' => t('Sign In'),
            'Dashboard/Entry/Register' => t('Registration')
        ];

        $this->EventArguments['ControllerData'] = &$ControllerData;
        $this->fireEvent('AfterGetLocationData');

        return $ControllerData;
    }
}
