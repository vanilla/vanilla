<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Class AkismetPlugin
 */
class AkismetPlugin extends Gdn_Plugin {

    /**
     * Get an Akismet instance.
     *
     * @return Akismet
     */
    public static function akismet() {
        static $akismet;

        if (!$akismet) {
            $key = c('Plugins.Akismet.Key', c('Plugins.Akismet.MasterKey'));
            $server = c('Plugins.Akismet.Server', 'rest.akismet.com');
            if (!$key || !$server) {
                return null;
            }
            $akismet = self::buildAkismet($key, $server);
        }

        return $akismet;
    }

    /**
     * Build an Akismet object.
     *
     * @param string $key Authentication key.
     * @param string $server Remote URL.
     * @return Aksimet
     */
    private static function buildAkismet($key, $server = false) {
        $akismet = new Akismet(Gdn::request()->url('/', true), $key);

        if ($server) {
            $akismet->setAkismetServer($server);
        }

        return $akismet;
    }

    /**
     * Query the Akismet service.
     *
     * @param array $recordType The recordType.
     * @param string $data The data being passed.
     *
     * @return bool
     * @throws Exception Throws exception.
     */
    public function checkAkismet($recordType, $data) {
        $userID = $this->userID();
        if (!$userID) {
            return false;
        }

        $akismet = self::akismet();
        if (!$akismet) {
            return false;
        }

        $akismet->setCommentAuthor($data['Username']);
        $akismet->setCommentAuthorEmail($data['Email']);

        $body = concatSep("\n\n", val('Name', $data), val('Body', $data), val('Story', $data));
        $akismet->setCommentContent($body);
        $akismet->setUserIP($data['IPAddress']);

        $result = $akismet->isCommentSpam();
        return $result;
    }

    /**
     * Do we have a valid key?
     *
     * @param string $key The config key.
     *
     * @return bool If config key is valid.
     */
    protected function validateKey($key) {
        $server = c('Plugins.Akismet.Server');
        return self::buildAkismet($key, $server)->isKeyValid();
    }

    /**
     * Run once on enable.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Database update.
     */
    public function structure() {
        // Get a user for operations.
        $userID = Gdn::sql()->getWhere('User', ['Name' => 'Akismet', 'Admin' => 2])->value('UserID');

        if (!$userID) {
            $userID = Gdn::sql()->insert('User', [
                'Name' => 'Akismet',
                'Password' => randomString('20'),
                'HashMethod' => 'Random',
                'Email' => 'akismet@domain.com',
                'DateInserted' => Gdn_Format::toDateTime(),
                'Admin' => '2'
            ]);
        }

        saveToConfig('Plugins.Akismet.UserID', $userID);
    }

    /**
     * Get the ID of the Akismet user.
     *
     * @return mixed
     */
    public function userID() {
        return c('Plugins.Akismet.UserID', null);
    }

    /**
     * Hook into Vanilla to run checks.
     *
     * @param Controller $sender The controller firing the event.
     * @param array $args The arguments sent by the event.
     */
    public function base_checkSpam_handler($sender, $args) {
        // Don't check for spam if another plugin has already determined it is.
        if ($sender->EventArguments['IsSpam']) {
            return;
        }

        $recordType = $args['RecordType'];
        $data =& $args['Data'];

        $result = false;
        switch ($recordType) {
            case 'Registration':
                $data['Name'] = '';
                $data['Body'] = val('DiscoveryText', $data);
                if ($data['Body']) {
                    // Only check for spam if there is discovery text.
                    $result = $this->checkAkismet($recordType, $data);
                    if ($result) {
                        $data['Log_InsertUserID'] = $this->userID();
                    }
                }
                break;

            case 'Comment':
            case 'Discussion':
            case 'Activity':
            case 'ActivityComment':
                $result = $this->checkAkismet($recordType, $data);
                if ($result) {
                    $data['Log_InsertUserID'] = $this->userID();
                }
                break;

            default:
                $result = false;
        }
        $sender->EventArguments['IsSpam'] = $result;
    }

    /**
     * Settings page.
     *
     * @param SettingsController $sender SettingsController firing the event.
     */
    public function settingsController_akismet_create($sender) {
        // Allow for master hosted key
        $keyDesc = 'Enter the key you obtained from <a href="http://akismet.com">akismet.com</a>';
        if (c('Plugins.Akismet.MasterKey')) {
            $keyDesc = 'No key is required! You may optionally use your own.';
        }

        $sender->permission('Garden.Settings.Manage');
        $sender->setData('Title', t('Akismet Settings'));

        $cf = new ConfigurationModule($sender);

        // Do key validation so we don't break our entire site.
        // Always allow a blank key, because the plugin turns off in that scenario.
        if (Gdn::request()->isAuthenticatedPostBack()) {
            $key = $cf->form()->getFormValue('Plugins.Akismet.Key');
            if ($key !== '' && !$this->validateKey($key)) {
                $cf->form()->addError('Key is invalid.');
            }
        }

        // Settings to be shown.
        $options = [
            'Plugins.Akismet.Key' => ['Description' => $keyDesc]
        ];

        // Deprecated TypePad option should go away if it's not already set.
        if (c('Plugins.Akismet.Server')) {
            $options['Plugins.Akismet.Server'] = [
                'Description' => 'You can use either Akismet or TypePad antispam.',
                'Control' => 'DropDown',
                'Items' => ['' => 'Akismet', 'api.antispam.typepad.com' => 'TypePad']
            ];
        }

        $cf->initialize($options);

        $sender->addSideMenu('settings/plugins');
        $cf->renderAll();
    }
}
