<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['Reporting'] = array(
   'Name' => 'Community Reporting',
   'Description' => 'Allows users to report comments and discussions for content violations or awesomeness.',
   'Version' => '1.0.3',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'RegisterPermissions' => FALSE,
   'SettingsUrl' => '/plugin/reporting',
   'SettingsPermission' => 'Garden.Users.Manage',
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class ReportingPlugin extends Gdn_Plugin {

   const BUTTON_TYPE_REPORT = 'report';
   const BUTTON_TYPE_AWESOME = 'awesome';

   public function __construct() {
      parent::__construct();
      $this->ReportEnabled = C('Plugins.Reporting.ReportEnabled', TRUE);
      $this->AwesomeEnabled = C('Plugins.Reporting.AwesomeEnabled', TRUE);
   }

   /*
    * Plugin control
    */
   public function PluginController_Reporting_Create($Sender) {
      $Sender->Form = new Gdn_Form();
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }

   /**
    * Add to dashboard menu.
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Moderation', T('Community Reporting'), 'plugin/reporting', 'Garden.Settings.Manage');
   }

   /**
   * Settings screen placeholder
   *
   * @param mixed $Sender
   */
   public function Controller_Index($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('Community Reporting');
      $Sender->AddSideMenu('dashboard/settings/plugins');
      $Sender->AddCssFile('reporting.css', 'plugins/Reporting');
      
      // Check to see if the admin is toggling a feature
      $Feature = GetValue('1', $Sender->RequestArgs);
      $Command = GetValue('2', $Sender->RequestArgs);
      $TransientKey = GetIncomingValue('TransientKey');
      if (Gdn::Session()->ValidateTransientKey($TransientKey)) {
         if (in_array($Feature, array('awesome', 'report'))) {
            SaveToConfig(
               'Plugins.Reporting.'.ucfirst($Feature).'Enabled',
               $Command == 'disable' ? FALSE : TRUE
            );
            
            Redirect('plugin/reporting');
         }
      }

      $CategoryModel = new CategoryModel();
      $Sender->SetData('Plugins.Reporting.Data', array(
         'ReportEnabled'   => $this->ReportEnabled,
         'AwesomeEnabled'  => $this->AwesomeEnabled
      ));

      $Sender->Render($this->GetView('settings.php'));
   }

   /**
   * Handle report actions
   *
   * @param mixed $Sender
   */
   public function Controller_Report($Sender) {
      if (!($UserID = Gdn::Session()->UserID))
         throw new Exception(T('Cannot report content while not logged in.'));
         
      $UserName = Gdn::Session()->User->Name;

      $Arguments = $Sender->RequestArgs;
      if (sizeof($Arguments) != 4)
         throw new Exception(sprintf(T("Incorrect arg-count. Doesn't look like a legit request. Got %s arguments, expected 4."),sizeof($Arguments)));

      list($EventType, $Context, $ElementID, $EncodedURL) = $Arguments;
      $URL = base64_decode(str_replace('-','=',$EncodedURL));

      $ReportElementModelName = ucfirst($Context).'Model';
      if (!class_exists($ReportElementModelName))
         throw new Exception(T('Cannot report on an entity with no model.'));

      // Ok we're good to go for sure now

      $ReportElementModel = new $ReportElementModelName();
      $ReportElement = $ReportElementModel->GetID($ElementID);

      $ElementTitle = Gdn_Format::Text(GetValue('Name', $ReportElement, NULL), FALSE);
      $ElementExcerpt = Gdn_Format::Text(GetValue('Body', $ReportElement, NULL), FALSE);
      if (!is_null($ElementExcerpt)) {
         $Original = strlen($ElementExcerpt);
         $ElementExcerpt = substr($ElementExcerpt, 0, 140);
         if ($Original > strlen($ElementExcerpt))
            $ElementExcerpt .= "...";
      }
      
      if (is_null($ElementTitle))
         $ElementTitle = $ElementExcerpt;
         
      $ElementShortTitle = (strlen($ElementTitle) <= 143) ? $ElementTitle : substr($ElementTitle, 0, 140).'...';

      $ElementAuthorID = GetValue('InsertUserID', $ReportElement);
      $ElementAuthor = Gdn::UserModel()->GetID($ElementAuthorID);
      $ElementAuthorName = GetValue('Name', $ElementAuthor);

      $RegardingAction = C('Plugins.Reporting.ReportAction', FALSE);
      $RegardingActionSupplement = C('Plugins.Reporting.ReportActionSupplement', FALSE);
      
      $ReportingData = array(
         'Type'            => 'report',
         'Context'         => $Context,
         'Element'         => $ReportElement,
         'ElementID'       => $ElementID,
         'ElementTitle'    => $ElementTitle,
         'ElementExcerpt'  => $ElementExcerpt,
         'ElementAuthor'   => $ElementAuthor,
         'URL'             => $URL,
         'UserID'          => $UserID,
         'UserName'        => $UserName,
         'Action'          => $RegardingAction,
         'Supplement'      => $RegardingActionSupplement
      );

      if ($Sender->Form->AuthenticatedPostBack()) {
         $RegardingTitle = sprintf(T("Reported: '{RegardingTitle}' by %s"), $ElementAuthorName);
         $ReportingData['Title'] = $RegardingTitle;
         $ReportingData['Reason'] = $Sender->Form->GetValue('Plugin.Reporting.Reason');
         
         $this->EventArguments['Report'] = &$ReportingData;
         $this->FireEvent('BeforeRegarding');
         
         $Regarding = Gdn::Regarding()
            ->That($ReportingData['Context'], $ReportingData['ElementID'], $ReportingData['Element'])
            ->ReportIt()
            ->ForCollaboration($ReportingData['Action'], $ReportingData['Supplement'])
            ->Entitled($ReportingData['Title'])
            ->From($ReportingData['UserID'])
            ->Because($ReportingData['Reason'])
            ->Located(TRUE) // build URL automatically
            ->Commit();

         $Sender->InformMessage('<span class="InformSprite Skull"></span>'.T('Your complaint has been registered. Thankyou!'), 'HasSprite Dismissable AutoDismiss');
      }

      $Sender->SetData('Plugin.Reporting.Data', $ReportingData);
      $Sender->Render($this->GetView('report.php'));
   }

   /**
   * Handle awesome actions
   *
   * @param mixed $Sender
   */
   public function Controller_Awesome($Sender) {
      if (!($UserID = Gdn::Session()->UserID))
         throw new Exception(T('Cannot report content while not logged in.'));
         
      $UserName = Gdn::Session()->User->Name;

      $Arguments = $Sender->RequestArgs;
      if (sizeof($Arguments) != 4)
         throw new Exception(sprintf(T("Incorrect arg-count. Doesn't look like a legit request. Got %s arguments, expected 4."),sizeof($Arguments)));

      list($EventType, $Context, $ElementID, $EncodedURL) = $Arguments;
      $URL = base64_decode(str_replace('-','=',$EncodedURL));

      $ReportElementModelName = ucfirst($Context).'Model';
      if (!class_exists($ReportElementModelName))
         throw new Exception(T('Cannot report on an entity with no model.'));

      // Ok we're good to go for sure now

      $ReportElementModel = new $ReportElementModelName();
      $ReportElement = $ReportElementModel->GetID($ElementID);

      $ElementTitle = Gdn_Format::Text(GetValue('Name', $ReportElement, NULL), FALSE);
      $ElementExcerpt = Gdn_Format::Text(GetValue('Body', $ReportElement, NULL), FALSE);
      if (!is_null($ElementExcerpt)) {
         $Original = strlen($ElementExcerpt);
         $ElementExcerpt = substr($ElementExcerpt, 0, 140);
         if ($Original > strlen($ElementExcerpt))
            $ElementExcerpt .= "...";
      }
      
      if (is_null($ElementTitle))
         $ElementTitle = $ElementExcerpt;
      
      $ElementShortTitle = (strlen($ElementTitle) <= 143) ? $ElementTitle : substr($ElementTitle, 0, 140).'...';

      $ElementAuthorID = GetValue('InsertUserID', $ReportElement);
      $ElementAuthor = Gdn::UserModel()->GetID($ElementAuthorID);
      $ElementAuthorName = GetValue('Name', $ElementAuthor);

      $ReportingData = array(
         'Context'         => $Context,
         'ElementID'       => $ElementID,
         'ElementTitle'    => $ElementTitle,
         'ElementExcerpt'  => $ElementExcerpt,
         'ElementAuthor'   => $ElementAuthor,
         'URL'             => $URL,
         'UserID'          => $UserID,
         'UserName'        => $UserName
      );

      $RegardingAction = C('Plugins.Reporting.AwesomeAction', FALSE);
      $RegardingActionSupplement = C('Plugins.Reporting.AwesomeActionSupplement', FALSE);

      if ($Sender->Form->AuthenticatedPostBack()) {
         $RegardingTitle = sprintf(T("Awesome: '{RegardingTitle}' by %s"), $ElementAuthorName);
         $Regarding = Gdn::Regarding()
            ->That($Context, $ElementID, $ReportElement)
            ->ItsAwesome()
            ->ForCollaboration($RegardingAction, $RegardingActionSupplement)
            ->Entitled($RegardingTitle)
            ->From(Gdn::Session()->UserID)
            ->Because($Sender->Form->GetValue('Plugin.Reporting.Reason'))
            ->Located(TRUE) // build URL automatically
            ->Commit();

         $Sender->InformMessage('<span class="InformSprite Heart"></span>'.T('Your suggestion has been registered. Thankyou!'), 'HasSprite Dismissable AutoDismiss');
      }

      $Sender->SetData('Plugin.Reporting.Data', $ReportingData);
      $Sender->Render($this->GetView('awesome.php'));
   }

   /*
    * UI injection
    */
   
   /**
    * Create 'Infraction' link for comments in a discussion.
    * 
    * Clickable for those who can give infractions, otherwise just a UI marker
    * for regular users.
    */
   public function DiscussionController_AfterReactions_Handler($Sender) {
      $Context = $Sender->EventArguments['Type'];
      $Text = FALSE;
      $Style = array();
      
      $Context = strtolower($Sender->EventArguments['Type']);

      if ($this->ReportEnabled)
         $this->OutputButton(self::BUTTON_TYPE_REPORT, $Context, $Sender);
      if ($this->AwesomeEnabled)
         $this->OutputButton(self::BUTTON_TYPE_AWESOME, $Context, $Sender);
      
      if ($this->ReportEnabled || $this->AwesomeEnabled)
         $Sender->AddCssFile('reporting.css', 'plugins/Reporting');
   }

   protected function OutputButton($ButtonType, $Context, $Sender) {
      // Signed in users only. No guest reporting!
      if (!Gdn::Session()->UserID) return;

      // Reporting permission checks

      if (!is_object($Sender->EventArguments['Author'])) {
         $ElementAuthorID = 0;
         $ElementAuthor = 'Unknown';
      } else {
         $ElementAuthorID = $Sender->EventArguments['Author']->UserID;
         $ElementAuthor = $Sender->EventArguments['Author']->Name;
      }

      switch ($Context) {
         case 'comment':
            $ElementID = $Sender->EventArguments['Comment']->CommentID;
            $URL = "/discussion/comment/{$ElementID}/#Comment_{$ElementID}";
            break;

         case 'discussion':
            $ElementID = $Sender->EventArguments['Discussion']->DiscussionID;
            $URL = "/discussion/{$ElementID}/".Gdn_Format::Url($Sender->EventArguments['Discussion']->Name);
            break;

         case 'conversation':
            break;

         default:
            return;
      }

      $ButtonTitle = T(ucfirst($ButtonType));
      $ContainerCSS = $ButtonTitle.'Post';
      $EncodedURL = str_replace('=','-',base64_encode($URL));
      $EventUrl = "plugin/reporting/{$ButtonType}/{$Context}/{$ElementID}/{$EncodedURL}";
      
      //$Sender->EventArguments['CommentOptions'][$ButtonTitle] = array('Label' => $ButtonTitle, 'Url' => "plugin/reporting/{$ButtonType}/{$Context}/{$ElementID}/{$EncodedURL}", $ContainerCSS.' ReportContent Popup');
      
      $SpriteType = "React".ucfirst($ButtonType);
      $Text = Anchor(Sprite($SpriteType, 'ReactSprite').$ButtonTitle, $EventUrl, "ReactButton React {$ContainerCSS} Popup");
      echo Bullet();
      echo $Text;
   }

   /*
    * Regarding handlers
    */

   public function Gdn_Regarding_RegardingDisplay_Handler($Sender) {
      $Event = $Sender->MatchEvent(array('report', 'awesome'), '*');
      if ($Event === FALSE)
         return;
      
      $Entity = GetValue('Entity', $Event);
      $RegardingData = GetValue('RegardingData', $Event);
      $RegardingType = GetValue('Type', $RegardingData);
      $ReportInfo = array(
         'ReportingUser'         => Gdn::UserModel()->GetID(GetValue('InsertUserID', $RegardingData)),
         'EntityType'            => T(ucfirst(GetValue('ForeignType', $RegardingData))),
         'ReportedUser'          => Gdn::UserModel()->GetID(GetValue('InsertUserID', $Entity)),
         'ReportedTime'          => GetValue('DateInserted', $RegardingData),
         'EntityURL'             => GetValue('ForeignURL', $RegardingData, NULL)
      );
      
      if (!is_null($ReportedReason = GetValue('Comment', $RegardingData, NULL)))
         $ReportInfo['ReportedReason'] = $ReportedReason;
         
      if (!is_null($ReportedContent = GetValue('OriginalContent', $RegardingData, NULL)))
         $ReportInfo['OriginalContent'] = $ReportedContent;
      
      Gdn::Controller()->SetData('RegardingSender', $Sender);
      Gdn::Controller()->SetData('Entity', $Entity);
      Gdn::Controller()->SetData('RegardingData', $RegardingData);
      Gdn::Controller()->SetData('ReportInfo', $ReportInfo);
      echo Gdn::Controller()->FetchView("{$RegardingType}-regarding",'','plugins/Reporting');
   }
   
   public function Gdn_Regarding_RegardingActions_Handler($Sender) {
      $Event = $Sender->MatchEvent('report', '*');
      if ($Event === FALSE)
         return;
      
      // Add buttonz hurr?
   }

   /*
    * Regarding extensions
    */

   public function Gdn_RegardingEntity_ReportIt_Create($Sender) {
      return $Sender->ActionIt('Report');
   }
   
   public function Gdn_RegardingEntity_ItsAwesome_Create($Sender) {
      return $Sender->ActionIt('Awesome');
   }

   public function Setup() {

   }

}