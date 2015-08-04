<?php
/**
 * StopForumSpam plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package StopForumSpam
 */

// Define the plugin:
$PluginInfo['StopForumSpam'] = array(
    'Name' => 'Stop Forum Spam',
    'Description' => "Got spammer problems? This integrates the spammer blacklist from stopforumspam.com to mitigate the issue.",
    'Version' => '1.0.1',
    'RequiredApplications' => array('Vanilla' => '2.0.18'),
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
    'SettingsUrl' => '/settings/stopforumspam'
);

/**
 * Class StopForumSpamPlugin
 */
class StopForumSpamPlugin extends Gdn_Plugin {

    /**
     *
     *
     * @param $Data
     * @param $Options
     * @return bool
     */
    public static function check(&$Data, &$Options) {
        // Make the request.
        $Get = array();


        if (isset($Data['IPAddress'])) {
            $AddIP = true;
            // Don't check against the localhost.
            foreach (array(
                         '127.0.0.1/0',
                         '10.0.0.0/8',
                         '172.16.0.0/12',
                         '192.168.0.0/16') as $LocalCIDR) {
                if (Gdn_Statistics::cidrCheck($Data['IPAddress'], $LocalCIDR)) {
                    $AddIP = false;
                    break;
                }
            }
            if ($AddIP) {
                $Get['ip'] = $Data['IPAddress'];
            }
        }
        if (isset($Data['Username'])) {
            $Get['username'] = $Data['Username'];
        }
        if (isset($Data['Email'])) {
            $Get['email'] = $Data['Email'];
        }

        if (empty($Get)) {
            return false;
        }

        $Get['f'] = 'json';

        $Url = "http://www.stopforumspam.com/api?".http_build_query($Get);

        $Curl = curl_init();
        curl_setopt($Curl, CURLOPT_URL, $Url);
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($Curl, CURLOPT_TIMEOUT, 4);
        curl_setopt($Curl, CURLOPT_FAILONERROR, 1);
        $ResultString = curl_exec($Curl);
        curl_close($Curl);

        if ($ResultString) {
            $Result = json_decode($ResultString, true);

            $IPFrequency = valr('ip.frequency', $Result, 0);
            $EmailFrequency = valr('email.frequency', $Result, 0);

            $IsSpam = false;

            // Flag registrations as spam above a certain threshold.
            if ($IPFrequency >= c('Plugins.StopForumSpam.IPThreshold1', 5) || $EmailFrequency >= c('Plugins.StopForumSpam.EmailThreshold1', 20)) {
                $IsSpam = true;
            }

            // Don't even log registrations that are above another threahold.
            if ($IPFrequency >= c('Plugins.StopForumSpam.IPThreshold2', 20) || $EmailFrequency >= c('Plugins.StopForumSpam.EmailThreshold2', 50)) {
                $Options['Log'] = false;
            }

            if ($Result) {
                $Data['_Meta']['IP Frequency'] = $IPFrequency;
                $Data['_Meta']['Email Frequency'] = $EmailFrequency;
            }
            return $IsSpam;
        }

        return false;
    }

    /**
     *
     */
    public function setup() {
        $this->structure();
    }

    /**
     *
     */
    public function structure() {
        // Get a user for operations.
        $UserID = Gdn::sql()->getWhere('User', array('Name' => 'StopForumSpam', 'Admin' => 2))->value('UserID');

        if (!$UserID) {
            $UserID = Gdn::sql()->insert('User', array(
                'Name' => 'StopForumSpam',
                'Password' => randomString('20'),
                'HashMethod' => 'Random',
                'Email' => 'stopforumspam@domain.com',
                'DateInserted' => Gdn_Format::toDateTime(),
                'Admin' => '2'
            ));
        }
        saveToConfig('Plugins.StopForumSpam.UserID', $UserID, array('CheckExisting' => true));
    }

    /**
     *
     *
     * @return mixed
     */
    public function userID() {
        return c('Plugins.StopForumSpam.UserID', null);
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function base_checkSpam_handler($Sender, $Args) {
        // Don't check for spam if another plugin has already determined it is.
        if ($Sender->EventArguments['IsSpam']) {
            return;
        }

        $RecordType = $Args['RecordType'];
        $Data =& $Args['Data'];
        $Options =& $Args['Options'];

        // Detect our favorite bot and short-circuit
        if ($Reason = val('DiscoveryText', $Data)) {
            if (substr($Reason, 0, 1) === '{') {
                $Sender->EventArguments['IsSpam'] = true;
                $Data['Log_InsertUserID'] = $this->userID();
                $Data['RecordIPAddress'] = Gdn::request()->ipAddress();
                return;
            }
        }

        $Result = false;
        switch ($RecordType) {
            case 'Registration':
                $Result = self::check($Data, $Options);
                if ($Result) {
                    $Data['Log_InsertUserID'] = $this->userID();
                    $Data['RecordIPAddress'] = Gdn::request()->ipAddress();
                }
                break;
            case 'Comment':
            case 'Discussion':
            case 'Activity':
//            $Result = $this->CheckTest($RecordType, $Data) || $this->CheckStopForumSpam($RecordType, $Data) || $this->CheckAkismet($RecordType, $Data);
                break;
        }
        $Sender->EventArguments['IsSpam'] = $Result;
    }

    /**
     *
     *
     * @param $Sender
     * @param array $Args
     */
    public function settingsController_stopForumSpam_create($Sender, $Args = array()) {
        $Sender->permission('Garden.Settings.Manage');
        $Conf = new ConfigurationModule($Sender);
        $Conf->initialize(array(
            'Plugins.StopForumSpam.IPThreshold1' => array('Type' => 'int', 'Control' => 'TextBox', 'Default' => 5, 'Description' => 'IP addresses reported this many times will be flagged as spam.'),
            'Plugins.StopForumSpam.EmailThreshold1' => array('Type' => 'int', 'Control' => 'TextBox', 'Default' => 20, 'Description' => 'Email addresses reported this many times will be flagged as spam.'),
            'Plugins.StopForumSpam.IPThreshold2' => array('Type' => 'int', 'Control' => 'TextBox', 'Default' => 20, 'Description' => 'IP addresses reported this many times will be completely rejected.'),
            'Plugins.StopForumSpam.EmailThreshold2' => array('Type' => 'int', 'Control' => 'TextBox', 'Default' => 50, 'Description' => 'Email addresses reported this many times will be completely rejected.'),
        ));

        $Sender->addSideMenu('dashboard/settings/plugins');
        $Sender->setData('Title', t('Stop Forum Spam Settings'));
        $Sender->ConfigurationModule = $Conf;
        $Conf->renderAll();
    }
}
