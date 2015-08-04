<?php
/**
 * Messages are used to display (optionally dismissable) information in various parts of the applications.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
        $this->Edit();
    }

    /**
     * Delete a message.
     *
     * @since 2.0.0
     * @access public
     */
    public function delete($MessageID = '', $TransientKey = false) {
        $this->permission('Garden.Community.Manage');
        $this->deliveryType(DELIVERY_TYPE_BOOL);
        $Session = Gdn::session();

        if ($TransientKey !== false && $Session->validateTransientKey($TransientKey)) {
            $Message = $this->MessageModel->delete(array('MessageID' => $MessageID));
            // Reset the message cache
            $this->MessageModel->SetMessageCache();
        }

        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            redirect('dashboard/message');
        }

        $this->render();
    }

    /**
     * Dismiss a message (per user).
     *
     * @since 2.0.0
     * @access public
     */
    public function dismiss($MessageID = '', $TransientKey = false) {
        $Session = Gdn::session();

        if ($TransientKey !== false && $Session->validateTransientKey($TransientKey)) {
            $Prefs = $Session->getPreference('DismissedMessages', array());
            $Prefs[] = $MessageID;
            $Session->setPreference('DismissedMessages', $Prefs);
        }

        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            redirect(getIncomingValue('Target', '/discussions'));
        }

        $this->render();
    }

    /**
     * Form to edit an existing message.
     *
     * @since 2.0.0
     * @access public
     */
    public function edit($MessageID = '') {
        $this->addJsFile('jquery.autosize.min.js');
        $this->addJsFile('messages.js');

        $this->permission('Garden.Community.Manage');
        $this->addSideMenu('dashboard/message');

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
        $this->addSideMenu('dashboard/message');
        $this->addJsFile('jquery.autosize.min.js');
        $this->addJsFile('jquery.tablednd.js');
        $this->addJsFile('jquery-ui.js');
        $this->addJsFile('messages.js');
        $this->title(t('Messages'));

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
     * Get descriptions of asset locations on page.
     *
     * @since 2.0.0
     * @access protected
     */
    protected function _getAssetData() {
        $AssetData = array();
        $AssetData['Content'] = t('Above Main Content');
        $AssetData['Panel'] = t('Below Sidebar');
        $this->EventArguments['AssetData'] = &$AssetData;
        $this->fireEvent('AfterGetAssetData');
        return $AssetData;
    }

    /**
     * Get descriptions of asset locations across site.
     *
     * @since 2.0.0
     * @access protected
     */
    protected function _getLocationData() {
        $ControllerData = array();
        $ControllerData['[Base]'] = t('All Pages');
        $ControllerData['[NonAdmin]'] = t('All Forum Pages');
        // 2011-09-09 - mosullivan - No longer allowing messages in dashboard
        // $ControllerData['[Admin]'] = 'All Dashboard Pages';
        $ControllerData['Dashboard/Profile/Index'] = t('Profile Page');
        $ControllerData['Vanilla/Discussions/Index'] = t('Discussions Page');
        $ControllerData['Vanilla/Discussion/Index'] = t('Comments Page');
        $ControllerData['Vanilla/Post/Discussion'] = t('New Discussion Form');
        $ControllerData['Dashboard/Entry/SignIn'] = t('Sign In');
        $ControllerData['Dashboard/Entry/Register'] = t('Registration');
        // 2011-09-09 - mosullivan - No longer allowing messages in dashboard
        // $ControllerData['Dashboard/Settings/Index'] = 'Dashboard Home';
        $this->EventArguments['ControllerData'] = &$ControllerData;
        $this->fireEvent('AfterGetLocationData');
        return $ControllerData;
    }
}
