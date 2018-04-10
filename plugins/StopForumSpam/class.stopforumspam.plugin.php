<?php
/**
 * StopForumSpam plugin.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package StopForumSpam
 */

/**
 * Class StopForumSpamPlugin
 */
class StopForumSpamPlugin extends Gdn_Plugin {

    /**
     *
     *
     * @param $data
     * @param $options
     * @return bool
     */
    public static function check(&$data, &$options) {
        // Make the request.
        $get = [];


        if (isset($data['IPAddress'])) {
            $addIP = true;
            // Don't check against the localhost.
            foreach ([
                         '127.0.0.1/0',
                         '10.0.0.0/8',
                         '172.16.0.0/12',
                         '192.168.0.0/16'] as $localCIDR) {
                if (Gdn_Statistics::cidrCheck($data['IPAddress'], $localCIDR)) {
                    $addIP = false;
                    break;
                }
            }
            if ($addIP) {
                $get['ip'] = $data['IPAddress'];
            }
        }
        if (isset($data['Username'])) {
            $get['username'] = $data['Username'];
        }
        if (isset($data['Email'])) {
            $get['email'] = $data['Email'];
        }

        if (empty($get)) {
            return false;
        }

        $get['f'] = 'json';

        $url = "http://www.stopforumspam.com/api?".http_build_query($get);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 4);
        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
        $resultString = curl_exec($curl);
        curl_close($curl);

        if ($resultString) {
            $result = json_decode($resultString, true);

            $iPFrequency = valr('ip.frequency', $result, 0);
            $emailFrequency = valr('email.frequency', $result, 0);

            $isSpam = false;

            // Flag registrations as spam above a certain threshold.
            if ($iPFrequency >= c('Plugins.StopForumSpam.IPThreshold1', 5) || $emailFrequency >= c('Plugins.StopForumSpam.EmailThreshold1', 20)) {
                $isSpam = true;
            }

            // Don't even log registrations that are above another threahold.
            if ($iPFrequency >= c('Plugins.StopForumSpam.IPThreshold2', 20) || $emailFrequency >= c('Plugins.StopForumSpam.EmailThreshold2', 50)) {
                $options['Log'] = false;
            }

            if ($result) {
                $data['_Meta']['IP Frequency'] = $iPFrequency;
                $data['_Meta']['Email Frequency'] = $emailFrequency;
            }
            return $isSpam;
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
        $userID = Gdn::sql()->getWhere('User', ['Name' => 'StopForumSpam', 'Admin' => 2])->value('UserID');

        if (!$userID) {
            $userID = Gdn::sql()->insert('User', [
                'Name' => 'StopForumSpam',
                'Password' => randomString('20'),
                'HashMethod' => 'Random',
                'Email' => 'stopforumspam@domain.com',
                'DateInserted' => Gdn_Format::toDateTime(),
                'Admin' => '2'
            ]);
        }
        saveToConfig('Plugins.StopForumSpam.UserID', $userID, ['CheckExisting' => true]);
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
     * @param $sender
     * @param $args
     */
    public function base_checkSpam_handler($sender, $args) {
        // Don't check for spam if another plugin has already determined it is.
        if ($sender->EventArguments['IsSpam']) {
            return;
        }

        $recordType = $args['RecordType'];
        $data =& $args['Data'];
        $options =& $args['Options'];

        // Detect our favorite bot and short-circuit
        if ($reason = val('DiscoveryText', $data)) {
            if (substr($reason, 0, 1) === '{') {
                $sender->EventArguments['IsSpam'] = true;
                $data['Log_InsertUserID'] = $this->userID();
                $data['RecordIPAddress'] = ipEncode(Gdn::request()->ipAddress());
                return;
            }
        }

        $result = false;
        switch ($recordType) {
            case 'Registration':
                $result = self::check($data, $options);
                if ($result) {
                    $data['Log_InsertUserID'] = $this->userID();
                    $data['RecordIPAddress'] = ipEncode(Gdn::request()->ipAddress());
                }
                break;
            case 'Comment':
            case 'Discussion':
            case 'Activity':
//            $Result = $this->checkTest($RecordType, $Data) || $this->checkStopForumSpam($RecordType, $Data) || $this->checkAkismet($RecordType, $Data);
                break;
        }
        $sender->EventArguments['IsSpam'] = $result;
    }

    /**
     *
     *
     * @param $sender
     * @param array $args
     */
    public function settingsController_stopForumSpam_create($sender, $args = []) {
        $sender->permission('Garden.Settings.Manage');
        $conf = new ConfigurationModule($sender);
        $conf->initialize([
            'Plugins.StopForumSpam.IPThreshold1' => ['Type' => 'int', 'Control' => 'TextBox', 'Default' => 5, 'Description' => 'IP addresses reported this many times will be flagged as spam.'],
            'Plugins.StopForumSpam.EmailThreshold1' => ['Type' => 'int', 'Control' => 'TextBox', 'Default' => 20, 'Description' => 'Email addresses reported this many times will be flagged as spam.'],
            'Plugins.StopForumSpam.IPThreshold2' => ['Type' => 'int', 'Control' => 'TextBox', 'Default' => 20, 'Description' => 'IP addresses reported this many times will be completely rejected.'],
            'Plugins.StopForumSpam.EmailThreshold2' => ['Type' => 'int', 'Control' => 'TextBox', 'Default' => 50, 'Description' => 'Email addresses reported this many times will be completely rejected.'],
        ]);

        $sender->setHighlightRoute('dashboard/settings/plugins');
        $sender->setData('Title', sprintf(t('%s Settings'), 'Stop Forum Spam'));
        $sender->ConfigurationModule = $conf;
        $conf->renderAll();
    }
}
