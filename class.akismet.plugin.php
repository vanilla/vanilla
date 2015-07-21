<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Akismet'] = array(
    'Name' => 'Akismet',
    'Description' => 'Akismet spam protection integration for Vanilla.',
    'Version' => '1.0.3',
    'RequiredApplications' => array('Vanilla' => '2.0.18'),
    'SettingsUrl' => '/settings/akismet',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class AkismetPlugin extends Gdn_Plugin {
    /// PROPERTIES ///

    /// METHODS ///

    /**
     * @return Akismet
     */
    public static function Akismet() {
        static $Akismet;
        if (!$Akismet) {
           $Key = C('Plugins.Akismet.Key', C('Plugins.Akismet.MasterKey'));

            if (!$Key) {
               return null;
            }

            $Akismet = new Akismet(Gdn::Request()->Url('/', true), $Key);

            $Server = C('Plugins.Akismet.Server');
            if ($Server) {
                $Akismet->setAkismetServer($Server);
            }
        }

        return $Akismet;
    }

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

        $Body = concatSep("\n\n", GetValue('Name', $Data), GetValue('Body', $Data), GetValue('Story', $Data));
        $Akismet->setCommentContent($Body);
        $Akismet->setUserIP($Data['IPAddress']);

        $Result = $Akismet->isCommentSpam();

        return $Result;
    }

    public function setup() {
        $this->Structure();
    }

    public function structure() {
        // Get a user for operations.
        $UserID = Gdn::sql()->GetWhere('User', array('Name' => 'Akismet', 'Admin' => 2))->Value('UserID');

        if (!$UserID) {
            $UserID = Gdn::sql()->Insert('User', array(
            'Name' => 'Akismet',
            'Password' => RandomString('20'),
            'HashMethod' => 'Random',
            'Email' => 'akismet@domain.com',
            'DateInserted' => Gdn_Format::toDateTime(),
            'Admin' => '2'
            ));
        }

        saveToConfig('Plugins.Akismet.UserID', $UserID);
   }

   public function userID() {
        return C('Plugins.Akismet.UserID', null);
   }

   /// EVENT HANDLERS ///

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
                $Data['Body'] = GetValue('DiscoveryText', $Data);
                if ($Data['Body']) {
                    // Only check for spam if there is discovery text.
                    $Result = $this->CheckAkismet($RecordType, $Data);
                    if ($Result) {
                        $Data['Log_InsertUserID'] = $this->UserID();
                    }
                }
            break;
            case 'Comment':
            case 'Discussion':
            case 'Activity':
            case 'ActivityComment':
            $Result = $this->checkAkismet($RecordType, $Data);
            if ($Result) {
                $Data['Log_InsertUserID'] = $this->UserID();
            }
            break;
            default:
                $Result = false;
        }
    $Sender->EventArguments['IsSpam'] = $Result;
    }

    public function settingsController_akismet_create($Sender, $Args = array()) {
        // Allow for master hosted key
        $KeyDesc = 'Enter the key you obtained from <a href="http://akismet.com">akismet.com</a>';
        if (C('Plugins.Akismet.MasterKey')) {
            $KeyDesc = 'No key is required! You may optionally use your own.';
        }

        $Sender->Permission('Garden.Settings.Manage');
        $Sender->SetData('Title', T('Akismet Settings'));

        $Cf = new ConfigurationModule($Sender);
        $Cf->Initialize(array(
            'Plugins.Akismet.Key' => array('Description' => $KeyDesc),
            'Plugins.Akismet.Server' => array('Description' => 'You can use either Akismet or TypePad antispam.', 'Control' => 'DropDown',
            'Items' => array('' => 'Aksimet', 'api.antispam.typepad.com' => 'TypePad', 'DefaultValue' => ''))
        ));

        $Sender->AddSideMenu('dashboard/settings/plugins');
        $Cf->RenderAll();
    }
}

