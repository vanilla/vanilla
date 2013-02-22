<?php if (!defined('APPLICATION')) exit();

/**
 * VanillaStats Plugin
 * 
 * This plugin tracks pageviews on the forum and reports them to the central Vanilla
 * Analytics System.
 * 
 * Changes: 
 *  1.0     Official release
 *  2.0.3   Fix http/https issue
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 */

$PluginInfo['VanillaStats'] = array(
   'Name' => 'Vanilla Statistics',
   'Description' => 'Adds helpful graphs and information about activity on your forum over time (new users, discussions, comments, and pageviews).',
   'Version' => '2.0.4',
   'MobileFriendly' => FALSE,
   'RequiredApplications' => array('Vanilla' => '2.0.18b'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'support@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class VanillaStatsPlugin extends Gdn_Plugin {
   
   public $AnalyticsServer;
   public $VanillaID;
   
   const RESOLUTION_DAY = 'day';
   const RESOLUTION_MONTH = 'month';
   
   public function __construct() {
      $this->AnalyticsServer = C('Garden.Analytics.Remote', 'analytics.vanillaforums.com');
      $this->VanillaID = Gdn::InstallationID();
   }
   
   /**
    * Override the default dashboard page with the new stats one.
    */
   public function Gdn_Dispatcher_BeforeDispatch_Handler($Sender) {
      $Enabled = C('Garden.Analytics.Enabled', TRUE);

      if ($Enabled && !Gdn::PluginManager()->HasNewMethod('SettingsController', 'Index')) {
         Gdn::PluginManager()->RegisterNewMethod('VanillaStatsPlugin', 'StatsDashboard', 'SettingsController', 'Index');
      }
   }
   
   public function SecurityTokenCallback($JsonResponse, $RawResponse) {
      $SecurityToken = GetValue('SecurityToken', $JsonResponse, NULL);
      if (!is_null($SecurityToken))
         $this->SecurityToken($SecurityToken);
   }
   
   protected function SecurityToken($SetSecurityToken = NULL) {
      static $SecurityToken = NULL;
   
      if (!is_null($SetSecurityToken))
         $SecurityToken = $SetSecurityToken;
      
      if (is_null($SecurityToken)) {
         $Request = array('VanillaID' => $this->VanillaID);
         Gdn::Statistics()->BasicParameters($Request);
         $Response = Gdn::Statistics()->Analytics('graph/getsecuritytoken.json', $Request, array(
             'Success'  => array($this, 'SecurityTokenCallback')
         ));
      }
      return $SecurityToken;
   }
   
   /**
    * Override the default index method of the settings controller in the
    * dashboard application to render new statistics.
    */
   public function StatsDashboard($Sender) {
      $StatsUrl = $this->AnalyticsServer;
      if (!StringBeginsWith($StatsUrl, 'http:') && !StringBeginsWith($StatsUrl, 'https:'))
         $StatsUrl = Gdn::Request()->Scheme()."://{$StatsUrl}";
      
      // Tell the page where to find the Vanilla Analytics provider
      $Sender->AddDefinition('VanillaStatsUrl', $StatsUrl);
      $Sender->SetData('VanillaStatsUrl', $StatsUrl);
      
      // Load javascript & css, check permissions, and load side menu for this page.
      $Sender->AddJsFile('settings.js');
      $Sender->Title(T('Dashboard'));
      $Sender->RequiredAdminPermissions[] = 'Garden.Settings.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Add';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Edit';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Delete';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Approve';
      $Sender->FireEvent('DefineAdminPermissions');
      $Sender->Permission($Sender->RequiredAdminPermissions, '', FALSE);
      $Sender->AddSideMenu('dashboard/settings');

      if (!Gdn_Statistics::CheckIsEnabled() && Gdn_Statistics::CheckIsLocalhost()) {
         $Sender->Render('dashboardlocalhost', '', 'plugins/VanillaStats');
      } else {
         $Sender->AddJsFile('plugins/VanillaStats/js/vanillastats.js');
         $Sender->AddJsFile('plugins/VanillaStats/js/picker.js');
         $Sender->AddCSSFile('plugins/VanillaStats/design/picker.css');

         $this->ConfigureRange($Sender);
         
         $VanillaID = Gdn::InstallationID();
         $Sender->SetData('VanillaID', $VanillaID);
         $Sender->SetData('VanillaVersion', APPLICATION_VERSION);
         $Sender->SetData('SecurityToken', $this->SecurityToken());
      
         // Render the custom dashboard view
         $Sender->Render('dashboard', '', 'plugins/VanillaStats');
      }
   }
   
   /**
    * A view containing most active discussions & users during a specific time
    * period. This gets ajaxed into the dashboard homepage as date ranges are
    * defined.
    */
   public function SettingsController_DashboardSummaries_Create($Sender) {
      // Load javascript & css, check permissions, and load side menu for this page.
      $Sender->AddJsFile('settings.js');
      $Sender->Title(T('Dashboard Summaries'));
      $Sender->RequiredAdminPermissions[] = 'Garden.Settings.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Add';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Edit';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Delete';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Approve';
      $Sender->FireEvent('DefineAdminPermissions');
      $Sender->Permission($Sender->RequiredAdminPermissions, '', FALSE);
      $Sender->AddSideMenu('dashboard/settings');
      
      $this->ConfigureRange($Sender);

      // Load the most active discussions during this date range
      $UserModel = new UserModel();
      $Sender->SetData('DiscussionData', $UserModel->SQL
         ->Select('d.DiscussionID, d.Name, d.CountBookmarks, d.CountViews, d.CountComments')
         ->From('Discussion d')
         ->Where('d.DateLastComment >=', $Sender->DateStart)
         ->Where('d.DateLastComment <=', $Sender->DateEnd)
         ->OrderBy('d.CountViews', 'desc')
         ->OrderBy('d.CountComments', 'desc')
         ->OrderBy('d.CountBookmarks', 'desc')
         ->Limit(10, 0)
         ->Get()
      );
      
      // Load the most active users during this date range
      $Sender->SetData('UserData', $UserModel->SQL
         ->Select('u.UserID, u.Name')
         ->Select('c.CommentID', 'count', 'CountComments')
         ->From('User u')
         ->Join('Comment c', 'u.UserID = c.InsertUserID', 'inner')
         ->GroupBy('u.UserID, u.Name')
         ->Where('c.DateInserted >=', $Sender->DateStart)
         ->Where('c.DateInserted <=', $Sender->DateEnd)
         ->OrderBy('CountComments', 'desc')
         ->Limit(10, 0)
         ->Get()
      );
      
      // Render the custom dashboard view
      $Sender->Render('dashboardsummaries', '', 'plugins/VanillaStats');
   }
   
   private function ConfigureRange($Sender) {
      // Grab the range resolution from the url or form. Default to "day" range.
      $Sender->Range = GetIncomingValue('Range');
      if (!in_array($Sender->Range, array(
            VanillaStatsPlugin::RESOLUTION_DAY,
            VanillaStatsPlugin::RESOLUTION_MONTH)))
         $Sender->Range = VanillaStatsPlugin::RESOLUTION_DAY;
         
      // Define default values for start & end dates
      $Sender->DayStampStart = strtotime('1 month ago'); // Default to 1 month ago
      $Sender->MonthStampStart = strtotime('12 months ago'); // Default to 24 months ago
      
      $Sender->DayDateStart = Gdn_Format::ToDate($Sender->DayStampStart);
      $Sender->MonthDateStart = Gdn_Format::ToDate($Sender->MonthStampStart);
      
      // Validate that any values coming from the url or form are valid
      $Sender->DateRange = GetIncomingValue('DateRange');
      $DateRangeParts = explode('-', $Sender->DateRange);
      $Sender->StampStart = strtotime(GetValue(0, $DateRangeParts));
      $Sender->StampEnd = strtotime(GetValue(1, $DateRangeParts));
      if (!$Sender->StampEnd)
         $Sender->StampEnd = strtotime('yesterday');
         
      // If no date was provided, or the provided values were invalid, use defaults
      if (!$Sender->StampStart) {
         $Sender->StampEnd = time();
         if ($Sender->Range == 'day') $Sender->StampStart = $Sender->DayStampStart;
         if ($Sender->Range == 'month') $Sender->StampStart = $Sender->MonthStampStart;
      }
      
      // Assign the variables used in the page with the validated values.
      $Sender->DateStart = Gdn_Format::ToDate($Sender->StampStart);
      $Sender->DateEnd = Gdn_Format::ToDate($Sender->StampEnd);
      $Sender->DateRange = $Sender->DateStart . ' - ' . $Sender->DateEnd;
      
      // Define the range boundaries.
      $Database = Gdn::Database();
      // We use the User table as the boundary start b/c users are always inserted before discussions or comments.
      // We have to put a little kludge in here b/c an older version of Vanilla hard-inserted the admin user with an insert date of Sept 16, 1975.
      $Data = $Database->SQL()->Select('DateInserted')->From('User')->Where('DateInserted >', '1975-09-17')->OrderBy('DateInserted', 'asc')->Limit(1)->Get()->FirstRow();
      $Sender->BoundaryStart = Gdn_Format::Date($Data ? $Data->DateInserted : $Sender->DateStart, '%Y-%m-%d');
      $Sender->BoundaryEnd = Gdn_Format::Date($Sender->DateEnd, '%Y-%m-%d');
   }
   
   protected function _Enable() {
   }
   
   protected function _Disable() {
   }
   
}