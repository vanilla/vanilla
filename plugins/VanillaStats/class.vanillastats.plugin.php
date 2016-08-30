<?php
/**
 * VanillaStats Plugin
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package VanillaStats
 */

$PluginInfo['VanillaStats'] = array(
    'Name' => 'Vanilla Statistics',
    'Description' => 'Adds helpful graphs and information about activity on your forum over time (new users, discussions, comments, and pageviews).',
    'Version' => '2.0.6',
    'MobileFriendly' => true,
    'RequiredApplications' => array('Vanilla' => '2.0.18'),
    'Author' => "Vanilla Staff",
    'AuthorEmail' => 'support@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
);

/**
 * This plugin tracks pageviews on the forum and reports them to the central Vanilla
 * Analytics System.
 *
 * Changes:
 *  1.0     Official release
 *  2.0.3   Fix http/https issue
 */
class VanillaStatsPlugin extends Gdn_Plugin {

    /**  */
    const RESOLUTION_DAY = 'day';

    /**  */
    const RESOLUTION_MONTH = 'month';

    /** @var mixed  */
    public $AnalyticsServer;

    /** @var string  */
    public $VanillaID;

    /**
     * Plugin setup.
     */
    public function __construct() {
        $this->AnalyticsServer = c('Garden.Analytics.Remote', 'analytics.vanillaforums.com');
        $this->VanillaID = Gdn::installationID();
    }

    /**
     * Override the default dashboard page with the new stats one.
     */
    public function gdn_dispatcher_beforeDispatch_handler($Sender) {
        $Enabled = c('Garden.Analytics.Enabled', true);

        if ($Enabled && !Gdn::pluginManager()->hasNewMethod('SettingsController', 'Index')) {
            Gdn::pluginManager()->registerNewMethod('VanillaStatsPlugin', 'StatsDashboard', 'SettingsController', 'Index');
        }
    }

    /**
     *
     *
     * @param $JsonResponse
     * @param $RawResponse
     */
    public function securityTokenCallback($JsonResponse, $RawResponse) {
        $SecurityToken = val('SecurityToken', $JsonResponse, null);
        if (!is_null($SecurityToken)) {
            $this->securityToken($SecurityToken);
        }
    }

    /**
     * Get the security token.
     *
     * @param null|string $SetSecurityToken
     * @return string
     */
    protected function securityToken($SetSecurityToken = null) {
        static $SecurityToken = null;

        if (!is_null($SetSecurityToken)) {
            $SecurityToken = $SetSecurityToken;
        }

        if (is_null($SecurityToken)) {
            $Request = array('VanillaID' => $this->VanillaID);
            Gdn::statistics()->basicParameters($Request);
            Gdn::statistics()->analytics('graph/getsecuritytoken.json', $Request, array(
                'Success' => array($this, 'SecurityTokenCallback')
            ));
        }
        return $SecurityToken;
    }

    /**
     * Override the index of the dashboard's settings controller in the to render new statistics.
     *
     * @param SettingsController $sender Instance of the dashboard's settings controller.
     */
    public function statsDashboard($sender) {
        $statsUrl = $this->AnalyticsServer;
        if (!stringBeginsWith($statsUrl, 'http:') && !stringBeginsWith($statsUrl, 'https:')) {
            $statsUrl = Gdn::request()->scheme()."://{$statsUrl}";
        }

        Gdn_Theme::section('DashboardHome');

        // Tell the page where to find the Vanilla Analytics provider
        $sender->addDefinition('VanillaStatsUrl', $statsUrl);
        $sender->setData('VanillaStatsUrl', $statsUrl);

        // Load javascript & css, check permissions, and load side menu for this page.
        $sender->addJsFile('settings.js');
        $sender->title(t('Dashboard'));
        $sender->RequiredAdminPermissions[] = 'Garden.Settings.View';
        $sender->RequiredAdminPermissions[] = 'Garden.Settings.Manage';
        $sender->RequiredAdminPermissions[] = 'Garden.Community.Manage';
        $sender->RequiredAdminPermissions[] = 'Garden.Users.Add';
        $sender->RequiredAdminPermissions[] = 'Garden.Users.Edit';
        $sender->RequiredAdminPermissions[] = 'Garden.Users.Delete';
        $sender->RequiredAdminPermissions[] = 'Garden.Users.Approve';
        $sender->fireEvent('DefineAdminPermissions');
        $sender->permission($sender->RequiredAdminPermissions, '', false);
        $sender->setHighlightRoute('dashboard/settings');

        if (!Gdn_Statistics::checkIsEnabled() && Gdn_Statistics::checkIsLocalhost()) {
            $sender->render('dashboardlocalhost', '', 'plugins/VanillaStats');
        } else {
            $sender->addCssFile('picker.css', 'plugins/VanillaStats');
            $sender->addCssFile('vendors.min.css', 'plugins/vanillaanalytics');

            $sender->addJsFile('vanillastats.js', 'plugins/VanillaStats');
            $sender->addJsFile('picker.js', 'plugins/VanillaStats');
            $sender->addJsFile('d3.min.js');
            $sender->addJsFile('c3.min.js');

            $sender->addDefinition('VanillaID', Gdn::installationID());
            $sender->addDefinition('AuthToken', Gdn_Statistics::generateToken());

            // Render the custom dashboard view
            $sender->render('dashboard', '', 'plugins/VanillaStats');
        }
    }

    /**
     * A view containing most active discussions & users during a specific time
     * period. This gets ajaxed into the dashboard homepage as date ranges are defined.
     */
    public function settingsController_dashboardSummaries_create($Sender) {
        // Load javascript & css, check permissions, and load side menu for this page.
        $Sender->addJsFile('settings.js');
        $Sender->title(t('Dashboard Summaries'));

        $Sender->RequiredAdminPermissions[] = 'Garden.Settings.Manage';
        $Sender->RequiredAdminPermissions[] = 'Garden.Users.Add';
        $Sender->RequiredAdminPermissions[] = 'Garden.Users.Edit';
        $Sender->RequiredAdminPermissions[] = 'Garden.Users.Delete';
        $Sender->RequiredAdminPermissions[] = 'Garden.Users.Approve';

        $Sender->fireEvent('DefineAdminPermissions');
        $Sender->permission($Sender->RequiredAdminPermissions, '', false);
        $Sender->addSideMenu('dashboard/settings');

        $range = Gdn::request()->getValue('range');
        $range['to'] = date('Y-m-d H:i:s', strtotime($range['to']));
        $range['from'] = date('Y-m-d H:i:s', strtotime($range['from']));

        // Load the most active discussions during this date range
        $UserModel = new UserModel();
        $Sender->setData('DiscussionData', $UserModel->SQL
            ->select('d.DiscussionID, d.Name, d.CountBookmarks, d.CountViews, d.CountComments, d.CategoryID, d.DateInserted')
            ->from('Discussion d')
            ->where('d.DateLastComment >=', $range['from'])
            ->where('d.DateLastComment <=', $range['to'])
            ->orderBy('d.CountViews', 'desc')
            ->orderBy('d.CountComments', 'desc')
            ->orderBy('d.CountBookmarks', 'desc')
            ->limit(5, 0)
            ->get());

        // Load the most active users during this date range
        $Sender->setData('UserData', $UserModel->SQL
            ->select('u.UserID, u.Name, u.DateLastActive')
            ->select('c.CommentID', 'count', 'CountComments')
            ->from('User u')
            ->join('Comment c', 'u.UserID = c.InsertUserID', 'inner')
            ->groupBy('u.UserID, u.Name')
            ->where('c.DateInserted >=', $range['from'])
            ->where('c.DateInserted <=', $range['to'])
            ->orderBy('CountComments', 'desc')
            ->limit(5, 0)
            ->get());

        // Render the custom dashboard view
        $Sender->render('dashboardsummaries', '', 'plugins/VanillaStats');
    }
}
