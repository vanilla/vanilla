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
        static $Akismet;

        if (!$Akismet) {
            $key = c('Plugins.Akismet.Key', c('Plugins.Akismet.MasterKey'));
            $server = c('Plugins.Akismet.Server', 'rest.akismet.com');
            if (!$key || !$server) {
                return null;
            }
            $Akismet = self::buildAkismet($key, $server);
        }

        return $Akismet;
    }

    /**
     * Build an Akismet object.
     *
     * @param string $key Authentication key.
     * @param string $server Remote URL.
     * @return Akismet
     */
    private static function buildAkismet($key, $server = false) {
        $Akismet = new Akismet(Gdn::request()->url('/', true), $key);

        if ($server) {
            $Akismet->setAkismetServer($server);
        }

        return $Akismet;
    }

    /**
     * Query the Akismet service.
     *
     * @param $RecordType
     * @param $Data
     * @return bool
     * @throws exception
     */
    public function checkAkismet($RecordType, $Data) {
        $UserID = $this->userID();
        if (!$UserID) {
            return false;
        }

        $Akismet = self::akismet();
        if (!$Akismet) {
            return false;
        }

        $Akismet->setCommentAuthor($Data['Username']);
        $Akismet->setCommentAuthorEmail($Data['Email']);

        $Body = concatSep("\n\n", val('Name', $Data), val('Body', $Data), val('Story', $Data));
        $Akismet->setCommentContent($Body);
        $Akismet->setUserIP($Data['IPAddress']);

        $Result = $Akismet->isCommentSpam();
        return $Result;
    }

    /**
     * Do we have a valid key?
     *
     * @return bool
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
        $UserID = Gdn::sql()->getWhere('User', ['Name' => 'Akismet', 'Admin' => 2])->value('UserID');

        if (!$UserID) {
            $UserID = Gdn::sql()->insert('User', [
                'Name' => 'Akismet',
                'Password' => randomString('20'),
                'HashMethod' => 'Random',
                'Email' => 'akismet@domain.com',
                'DateInserted' => Gdn_Format::toDateTime(),
                'Admin' => '2'
            ]);
        }

        saveToConfig('Plugins.Akismet.UserID', $UserID);
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
     * @param $Sender
     * @param $Args
     */
    public function base_checkSpam_handler($Sender, $Args) {
        if ($Args['IsSpam']) {
            return; // don't double check
        }

        $RecordType = $Args['RecordType'];
        $Data =& $Args['Data'];

        $Result = false;
        switch ($RecordType) {
            case 'Registration':
                $Data['Name'] = '';
                $Data['Body'] = val('DiscoveryText', $Data);
                if ($Data['Body']) {
                    // Only check for spam if there is discovery text.
                    $Result = $this->checkAkismet($RecordType, $Data);
                    if ($Result) {
                        $Data['Log_InsertUserID'] = $this->userID();
                    }
                }
                break;

            case 'Comment':
            case 'Discussion':
            case 'Activity':
            case 'ActivityComment':
                $Result = $this->checkAkismet($RecordType, $Data);
                if ($Result) {
                    $Data['Log_InsertUserID'] = $this->userID();
                }
                break;

            default:
                $Result = false;
        }
        $Sender->EventArguments['IsSpam'] = $Result;
    }

    /**
     * Settings page.
     *
     * @param SettingsController $Sender
     */
    public function settingsController_akismet_create($Sender) {
        // Allow for master hosted key
        $KeyDesc = 'Enter the key you obtained from <a href="http://akismet.com">akismet.com</a>';
        if (c('Plugins.Akismet.MasterKey')) {
            $KeyDesc = 'No key is required! You may optionally use your own.';
        }

        $Sender->permission('Garden.Settings.Manage');
        $Sender->setData('Title', t('Akismet Settings'));

        $Cf = new ConfigurationModule($Sender);

        // Do key validation so we don't break our entire site.
        // Always allow a blank key, because the plugin turns off in that scenario.
        if (Gdn::request()->isAuthenticatedPostBack()) {
            $key = $Cf->form()->getFormValue('Plugins.Akismet.Key');
            if ($key !== '' && !$this->validateKey($key)) {
                $Cf->form()->addError('Key is invalid.');
            }
        }

        // Settings to be shown.
        $options = [
            'Plugins.Akismet.Key' => ['Description' => $KeyDesc]
        ];

        // Deprecated TypePad option should go away if it's not already set.
        if (c('Plugins.Akismet.Server')) {
            $options['Plugins.Akismet.Server'] = [
                'Description' => 'You can use either Akismet or TypePad antispam.',
                'Control' => 'DropDown',
                'Items' => ['' => 'Akismet', 'api.antispam.typepad.com' => 'TypePad']
            ];
        }

        $Cf->initialize($options);

        $Sender->addSideMenu('settings/plugins');
        $Cf->renderAll();
    }
}
