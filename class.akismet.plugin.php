<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

$PluginInfo['Akismet'] = [
    'Name' => 'Akismet',
    'Description' => 'Akismet spam protection for Vanilla.',
    'Version' => '1.1',
    'RequiredApplications' => ['Vanilla' => '2.1'],
    'SettingsUrl' => '/settings/akismet',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
];

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
            $Key = c('Plugins.Akismet.Key', c('Plugins.Akismet.MasterKey'));
            if (!$Key) {
               return null;
            }

            $Akismet = new Akismet(Gdn::request()->url('/', true), $Key);

            $Server = C('Plugins.Akismet.Server');
            if ($Server) {
                $Akismet->setAkismetServer($Server);
            }
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
        $UserID = $this->UserID();
        if (!$UserID) {
            return false;
        }

        $Akismet = self::Akismet();
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
            $UserID = Gdn::sql()->insert('User', array(
            'Name' => 'Akismet',
            'Password' => tandomString('20'),
            'HashMethod' => 'Random',
            'Email' => 'akismet@domain.com',
            'DateInserted' => Gdn_Format::toDateTime(),
            'Admin' => '2'
            ));
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
     * @param $Sender
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
        $Cf->initialize([
            'Plugins.Akismet.Key' => ['Description' => $KeyDesc],
            'Plugins.Akismet.Server' => [
                'Description' => 'You can use either Akismet or TypePad antispam.',
                'Control' => 'DropDown',
                'Items' => ['' => 'Aksimet', 'api.antispam.typepad.com' => 'TypePad', 'DefaultValue' => '']
            ]
        ]);

        $Sender->addSideMenu('settings/plugins');
        $Cf->renderAll();
    }
}

