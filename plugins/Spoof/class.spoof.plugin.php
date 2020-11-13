<?php if (!defined('APPLICATION')) exit();

use Garden\Container\Container;
use Garden\Container\Reference;
use Garden\Web\Dispatcher;
use Plugins\Spoof\Library\SpoofMiddleware;

/**
 * 1.2 - mosullivan - 2011-08-30 - Added "Spoof" button to various screens for admins.
 */
class SpoofPlugin implements Gdn_IPlugin {

    /**
     * Add the spoof admin screen to the dashboard menu.
     *
     * @param mixed $sender
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        // Clean out entire menu & re-add everything
        $menu = &$sender->EventArguments['SideMenu'];
        $menu->addLink('Users', t('Spoof'), 'user/spoof', 'Garden.Settings.Manage');
    }

    /**
     * Custom container configuration.
     *
     * @param Container $container
     */
    public function container_init(Container $container): void {
        $container->rule(SpoofMiddleware::class)
            ->setConstructorArgs([
                new Reference(Gdn_Session::class),
                new Reference('@smart-id-middleware'),
            ]);

        $container->rule(Dispatcher::class)
            ->addCall("addMiddleware", [new Reference(SpoofMiddleware::class)]);
    }

    /**
     * Admin screen for spoofing a user.
     *
     * @param UserController $sender
     */
    public function userController_spoof_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->addSideMenu('user/spoof');
        $this->_spoofMethod($sender);
    }

    /**
     * Validates the current user's permissions & transientkey and then spoofs
     * the userid passed as the first arg and redirects to profile.
     *
     * @param UserController $sender
     */
    public function userController_autoSpoof_create($sender) {
        $spoofUserID = getValue('0', $sender->RequestArgs);
        $user = $sender->userModel->getId(intval($spoofUserID));
        $transientKey = getValue('1', $sender->RequestArgs);
        // Validate the transient key && permissions
        if (Gdn::session()->validateTransientKey($transientKey)
            && Gdn::session()->checkPermission('Garden.Settings.Manage')
            && $user->Admin !== 2) {
            Gdn::session()->start($spoofUserID, true, false);
        }
        if ($this->_DeliveryType !== DELIVERY_TYPE_ALL) {
            $sender->setRedirectTo('profile');
            $sender->render('blank', 'utility', 'dashboard');
        } else {
            redirectTo('profile');
        }
    }

    /**
     * Adds a "Spoof" link to the user management list.
     *
     * @param UserController $sender
     */
    public function userController_userListOptions_handler($sender) {
        if (!Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            return;
        }

        $user = getValue('User', $sender->EventArguments);
        if ($user && $user->Admin !== 2) {
            $attr = [
                'aria-label' => t('Spoof'),
                'title' => t('Spoof'),
                'data-follow-link' => 'true',
            ];
            $class = 'js-modal-confirm btn btn-icon';
            echo anchor(dashboardSymbol('spoof'), '/user/autospoof/'.$user->UserID.'/'.Gdn::session()->transientKey(), $class, $attr);
        }
    }

    /**
     * Adds a "Spoof" link to the site management list.
     *
     * @param ManageController $sender
     */
    public function manageController_siteListOptions_handler($sender) {
        if (!Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            return;
        }

        $site = getValue('Site', $sender->EventArguments);
        if ($site) {
            echo anchor(t('Spoof'), '/user/autospoof/' . $site->InsertUserID . '/' . Gdn::session()->transientKey(), 'PopConfirm SmallButton');
        }
    }

    /**
     * Add items to the profile dashboard.
     *
     * @param ProfileController $sender
     */
    public function profileController_afterAddSideMenu_handler($sender) {
        if (!Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            return;
        }

        $sideMenu = $sender->EventArguments['SideMenu'];
        $viewingUserID = Gdn::session()->UserID;

        if ($sender->User->UserID != $viewingUserID) {
            $sideMenu->addLink('Options', t('Spoof User'), '/user/autospoof/' . $sender->User->UserID . '/' . Gdn::session()->transientKey(), '', ['class' => 'PopConfirm']);
        }
    }

    /**
     * Creates a spoof login page.
     *
     * @param EntryController $sender
     */
    public function entryController_spoof_create($sender) {
        $this->_spoofMethod($sender);
    }

    /**
     * Standard method for authenticating an admin and allowing them to spoof a user.
     *
     * @param Gdn_Controller $sender
     */
    private function _spoofMethod($sender) {
        $sender->title('Spoof');
        $sender->Form = new Gdn_Form();
        $userReference = $sender->Form->getValue('UserReference', '');
        $email = $sender->Form->getValue('Email', '');
        $password = $sender->Form->getValue('Password', '');

        if ($userReference != '' && $email != '' && $password != '') {
            $userModel = Gdn::userModel();
            $userData = $userModel->validateCredentials($email, 0, $password);

            if (is_object($userData) && $userModel->checkPermission($userData->UserID, 'Garden.Settings.Manage')) {
                if (is_numeric($userReference)) {
                    $spoofUser = $userModel->getID($userReference);
                } else {
                    $spoofUser = $userModel->getByUsername($userReference);
                }

                if ($spoofUser) {
                    Gdn::session()->start($spoofUser->UserID, true, false);
                    redirectTo('profile');
                } else {
                    $sender->Form->addError('Failed to find requested user.');
                }
            } else {
                $sender->Form->addError('Bad Credentials');
            }
        }

        $sender->render(PATH_PLUGINS . DS . 'Spoof' . DS . 'views' . DS . 'spoof.php');
    }

    /**
     * @inheritDoc
     */
    public function setup() {
        // Only necessary to implement the Gdn_IPlugin interface.
    }
}
