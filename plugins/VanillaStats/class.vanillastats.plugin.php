<?php
/**
 * VanillaStats Plugin
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package VanillaStats
 */

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

    /** Upper limit on date ranges for user record querying. */
    const USER_MAX_DAYS = 90;

    /** @var mixed  */
    public $AnalyticsServer;

    /** @var string  */
    public $VanillaID;

    /** @var bool */
    private $dashboardSummariesEnabled;

    /**
     * VanillaStatsPlugin constructor.
     */
    public function __construct() {
        $this->AnalyticsServer = c('Garden.Analytics.Remote', 'analytics.vanillaforums.com');
        $this->VanillaID = Gdn::installationID();

        $isVanillaAnalyticEnabled = Gdn::addonManager()->isEnabled('vanillaanalytics', Vanilla\Addon::TYPE_ADDON);
        $this->dashboardSummariesEnabled = c('Garden.Analytics.DashboardSummaries', !$isVanillaAnalyticEnabled);

        parent::__construct();
    }

    /**
     * Override the default dashboard page with the new stats one.
     *
     * @param Gdn_Dispatcher $sender
     */
    public function gdn_dispatcher_beforeDispatch_handler($sender) {
        $enabled = c('Garden.Analytics.Enabled', true);

        if ($enabled) {
            Gdn::pluginManager()->registerNewMethod('VanillaStatsPlugin', 'StatsDashboard', 'SettingsController', 'home');
        }
    }

    /**
     *
     *
     * @param $jsonResponse
     * @param $rawResponse
     */
    public function securityTokenCallback($jsonResponse, $rawResponse) {
        $securityToken = val('SecurityToken', $jsonResponse, null);
        if (!is_null($securityToken)) {
            $this->securityToken($securityToken);
        }
    }

    /**
     * Get the security token.
     *
     * @param null|string $setSecurityToken
     * @return string
     */
    protected function securityToken($setSecurityToken = null) {
        static $securityToken = null;

        if (!is_null($setSecurityToken)) {
            $securityToken = $setSecurityToken;
        }

        if (is_null($securityToken)) {
            $request = ['VanillaID' => $this->VanillaID];
            Gdn::statistics()->basicParameters($request);
            Gdn::statistics()->analytics('graph/getsecuritytoken.json', $request, [
                'Success' => [$this, 'SecurityTokenCallback']
            ]);
        }
        return $securityToken;
    }

    /**
     * Override the index of the dashboard's settings controller in the to render new statistics.
     *
     * @param SettingsController $sender Instance of the dashboard's settings controller.
     */
    public function settingsController_home_create($sender) {
        $statsUrl = $this->AnalyticsServer;
        if (!stringBeginsWith($statsUrl, 'http:') && !stringBeginsWith($statsUrl, 'https:')) {
            $statsUrl = Gdn::request()->scheme()."://{$statsUrl}";
        }

        Gdn_Theme::section('DashboardHome');
        $sender->setData('IsWidePage', true);

        // Tell the page where to find the Vanilla Analytics provider
        $sender->addDefinition('VanillaStatsUrl', $statsUrl);
        $sender->setData('VanillaStatsUrl', $statsUrl);

        // Load javascript & css, check permissions, and load side menu for this page.
        $sender->addJsFile('settings.js');
        $sender->title(t('Dashboard'));
        $sender->RequiredAdminPermissions = [
            'Garden.Settings.View',
            'Garden.Settings.Manage',
            'Garden.Community.Manage',
        ];
        $sender->fireEvent('DefineAdminPermissions');
        $sender->permission($sender->RequiredAdminPermissions, '', false);
        $sender->setHighlightRoute('dashboard/settings');

        if (!Gdn_Statistics::checkIsEnabled() && Gdn_Statistics::checkIsLocalhost()) {
            $sender->render('dashboardlocalhost', '', 'plugins/VanillaStats');
        } else {
            $sender->addCssFile('vendors/c3.min.css', 'plugins/VanillaStats');
            $sender->addJsFile('vanillastats.js', 'plugins/VanillaStats');
            $sender->addJsFile('d3.min.js');
            $sender->addJsFile('c3.min.js');

            $sender->addDefinition('VanillaID', Gdn::installationID());
            $sender->addDefinition('AuthToken', Gdn_Statistics::generateToken());

            $sender->addDefinition('ExpandText', t('more'));
            $sender->addDefinition('CollapseText', t('less'));

            $sender->addDefinition('DashboardSummaries', $this->dashboardSummariesEnabled);

            // Render the custom dashboard view
            $sender->render('dashboard', '', 'plugins/VanillaStats');
        }
    }

    /**
     * A view containing most active discussions & users during a specific time
     * period. This gets ajaxed into the dashboard homepage as date ranges are defined.
     *
     * @param SettingsController $sender
     */
    public function settingsController_dashboardSummaries_create($sender) {
        $discussionData = [];
        $userData = [];

        if ($this->dashboardSummariesEnabled) {
            $range = Gdn::request()->getValue('range');
            $range['to'] = date(MYSQL_DATE_FORMAT, strtotime($range['to']));
            $range['from'] = date(MYSQL_DATE_FORMAT, strtotime($range['from']));

            $userModel = new UserModel();

            // Load the most active discussions during this date range
            $discussionData = $userModel->SQL
                ->select('d.DiscussionID, d.Name, d.CountBookmarks, d.CountViews, d.CountComments, d.CategoryID, d.DateInserted')
                ->from('Discussion d')
                ->where('d.DateLastComment >=', $range['from'])
                ->where('d.DateLastComment <=', $range['to'])
                ->orderBy('d.CountViews', 'desc')
                ->orderBy('d.CountComments', 'desc')
                ->orderBy('d.CountBookmarks', 'desc')
                ->limit(5, 0)
                ->get();

            // If the date range is greater than 90 days, limit it.
            $toDateTime = new DateTime($range['to']);
            $dateDiff = date_diff($toDateTime, new DateTime($range['from']));
            $daysInRange = intval($dateDiff->format('%a'));
            if ($daysInRange > self::USER_MAX_DAYS) {
                $toDate = $toDateTime->format('m/d/Y');
                $subInterval = new DateInterval('P'.self::USER_MAX_DAYS.'D');
                $range['from'] = $toDateTime->sub($subInterval)->format(MYSQL_DATE_FORMAT);
                $fromDate = $toDateTime->format('m/d/Y');
                $userRangeWarning = sprintf(
                    t('Data limited to %s - %s'),
                    $fromDate,
                    $toDate
                );
                $sender->setData('UserRangeWarning', $userRangeWarning);
            }

            // Load the most active users during the date range.
            $userData = $userModel->SQL
                ->select('InsertUserID as UserID')
                ->select('CommentID', 'count', 'CountComments')
                ->from('Comment')
                ->where('DateInserted >=', $range['from'])
                ->where('DateInserted <=', $range['to'])
                ->groupBy('InsertUserID')
                ->orderBy('CountComments', 'desc')
                ->limit(5, 0)
                ->get();
        }

        $sender->setData('DiscussionData', $discussionData);
        $sender->setData('UserData', $userData);

        // Load javascript & css, check permissions, and load side menu for this page.
        $sender->addJsFile('settings.js');
        $sender->title(t('Dashboard Summaries'));

        $sender->RequiredAdminPermissions = [
            'Garden.Settings.View',
            'Garden.Settings.Manage',
            'Garden.Community.Manage',
        ];

        $sender->fireEvent('DefineAdminPermissions');
        $sender->permission($sender->RequiredAdminPermissions, '', false);
        $sender->setHighlightRoute('dashboard/settings');

        // Render the custom dashboard view
        $sender->render('dashboardsummaries', '', 'plugins/VanillaStats');
    }
}
