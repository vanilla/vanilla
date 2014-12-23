<?php if (!defined('APPLICATION')) exit();

/**
 * Ignore Plugin
 *
 * This plugin allows users to maintain an ignore list that filters out other
 * users' comments.
 *
 * Changes:
 *  1.0     Initial release
 *  1.0.1   Fix guest mode bug
 *  1.0.2   Change Plugin.Ignore.MaxIgnores to Plugins.Ignore.MaxIgnores
 *  1.0.3   Fix usage of T() (or lack of usage in some cases)
 *  1.1     Add SimpleAPI hooks
 *  1.2     Hook into conversations application and block ignored PMs
 *  1.3     Mobile Friendly and improved CSS
 *  1.3.2   Enable revoke JS
 *  1.4     Change revoke to use hijack.  Prevent forum admins from being ignored
 *          Added optional setting to prevent moderators from being ignored
 *          Added check to username when adding
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Addons
 */

$PluginInfo['Ignore'] = array(
   'Description' => 'This plugin allows users to ignore others, filtering their comments out of discussions.',
   'Version' => '1.4.0',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
   'MobileFriendly' => TRUE,
   'HasLocale' => FALSE,
   'SettingsUrl' => FALSE,
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class IgnorePlugin extends Gdn_Plugin {

   const IGNORE_SELF = 'self';
   const IGNORE_GOD = 'god';
   const IGNORE_LIMIT = 'limit';
   const IGNORE_RESTRICTED = 'restricted';
   const IGNORE_FORUM_ADMIN = 'forumadmin';
   const IGNORE_FORUM_MOD = 'forummods';

   public $allowModeratorIgnore;

   public function __construct() {
      parent::__construct();
      $this->allowModeratorIgnore = C('Plugins.Ignore.AllowModeratorIgnore', TRUE);
      $this->FireEvent('Init');
   }

   /**
    * Add mapper methods
    *
    * @param SimpleApiPlugin $Sender
    */
   public function SimpleApiPlugin_Mapper_Handler($Sender) {
      switch ($Sender->Mapper->Version) {
         case '1.0':
            $Sender->Mapper->AddMap(array(
               'ignore/list'           => 'dashboard/profile/ignore',
               'ignore/add'            => 'dashboard/profile/ignore/add',
               'ignore/remove'         => 'dashboard/profile/ignore/remove',
               'ignore/restrict'       => 'dashboard/profile/ignore/restrict'
            ), NULL, array(
               'ignore/list'           => array('IgnoreList', 'IgnoreLimit', 'IgnoreRestricted'),
               'ignore/add'            => array('Success'),
               'ignore/remove'         => array('Success'),
               'ignore/restrict'       => array('Success')
            ));
            break;
      }
   }

   public function ProfileController_AfterAddSideMenu_Handler($Sender) {
      if (!Gdn::Session()->CheckPermission('Garden.SignIn.Allow'))
         return;

      $SideMenu = $Sender->EventArguments['SideMenu'];
      $ViewingUserID = Gdn::Session()->UserID;

      if ($Sender->User->UserID == $ViewingUserID) {
         $SideMenu->AddLink('Options', Sprite('SpIgnoreList').' '.T('Ignore List'), '/profile/ignore', FALSE, array('class' => 'Popup'));
      } else {
         $SideMenu->AddLink('Options', Sprite('SpIgnoreList').' '.T('Ignore List'), "/profile/ignore/{$Sender->User->UserID}/".Gdn_Format::Url($Sender->User->Name), 'Garden.Users.Edit', array('class' => 'Popup'));
      }
   }

   /**
    * Profile settings
    *
    * @param ProfileController $Sender
    */
   public function ProfileController_Ignore_Create($Sender) {
      $Sender->Permission('Garden.SignIn.Allow');
      $Sender->Title(T('Ignore List'));

      $this->Dispatch($Sender);
   }

   public function Controller_Index($Sender) {

      $Args = $Sender->RequestArgs;
      if (sizeof($Args) < 2)
         $Args = array_merge($Args, array(0,0));
      elseif (sizeof($Args) > 2)
         $Args = array_slice($Args, 0, 2);

      list($UserReference, $Username) = $Args;

      $Sender->GetUserInfo($UserReference, $Username);
      $Sender->_SetBreadcrumbs(T('Ignore List'), '/profile/ignore');

      $UserID = $ViewingUserID = Gdn::Session()->UserID;
      if ($Sender->User->UserID != $ViewingUserID) {
         $Sender->Permission('Garden.Users.Edit');
         $UserID = $Sender->User->UserID;
      }

      $Sender->SetData('ForceEditing', ($UserID == Gdn::Session()->UserID) ? FALSE : $Sender->User->Name);

      if ($Sender->Form->IsMyPostBack()) {
         $IgnoreUsername = $Sender->Form->GetFormValue('AddIgnore');
         try {
            $AddIgnoreUser = Gdn::UserModel()->GetByUsername($IgnoreUsername);
            $AddRestricted = $this->IgnoreRestricted($AddIgnoreUser->UserID);
            if (empty($IgnoreUsername)) {
               throw new Exception(T("You must enter a username to ignore."));
            }
            if ($AddIgnoreUser === FALSE) {
               throw new Exception(sprintf(T("User '%s' can not be found."), $IgnoreUsername));
            }
            switch ($AddRestricted) {

               case self::IGNORE_LIMIT:
                  throw new Exception(T("You have reached the maximum number of ignores."));

               case self::IGNORE_RESTRICTED:
                  throw new Exception(T("Your ignore privileges have been revoked."));

               case self::IGNORE_SELF:
                  throw new Exception(T("You can't put yourself on ignore."));

               case self::IGNORE_GOD:
               case self::IGNORE_FORUM_ADMIN:
               case self::IGNORE_FORUM_MOD:
                  throw new Exception(T("You can't ignore that person."));

               default:
                  $this->AddIgnore($UserID, $AddIgnoreUser->UserID);
                  $Sender->InformMessage(
                     '<span class="InformSprite Contrast"></span>'.sprintf(T("%s is now on ignore."), $AddIgnoreUser->Name),
                     'AutoDismiss HasSprite'
                  );
                  $Sender->Form->SetFormValue('AddIgnore', '');
                  break;
            }
         } catch (Exception $Ex) {
            $Sender->Form->AddError($Ex);
         }
      }

      $IgnoredUsersRaw = $this->GetUserMeta($UserID, 'Blocked.User.%');
      $IgnoredUsersIDs = array();
      foreach ($IgnoredUsersRaw as $IgnoredUsersKey => $IgnoredUsersIgnoreDate) {
         $IgnoredUsersKeyArray = explode('.', $IgnoredUsersKey);
         $IgnoredUsersID = array_pop($IgnoredUsersKeyArray);
         $IgnoredUsersIDs[$IgnoredUsersID] = $IgnoredUsersIgnoreDate;
      }

      $IgnoredUsers = Gdn::UserModel()->GetIDs(array_keys($IgnoredUsersIDs));

      // Add ignore date to each user
      foreach ($IgnoredUsers as $IgnoredUsersID => &$IgnoredUser)
         $IgnoredUser['IgnoreDate'] = $IgnoredUsersIDs[$IgnoredUsersID];

      $IgnoredUsers = array_values($IgnoredUsers);
      $Sender->SetData('IgnoreList', $IgnoredUsers);

      $MaxIgnores = C('Plugins.Ignore.MaxIgnores', 5);
      $Sender->SetData('IgnoreLimit', ($Sender->User->Admin) ? 'infinite' : $MaxIgnores);

      $IgnoreIsRestricted = $this->IgnoreIsRestricted($UserID);
      $Sender->SetData('IgnoreRestricted', $IgnoreIsRestricted);

      $Sender->Render('ignore','','plugins/Ignore');
   }

   /*
    * API METHODS
    */

   public function Controller_Add($Sender) {
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_DATA);

      if (!$Sender->Form->IsPostBack())
         throw new Exception(405);

      $UserID = Gdn::Request()->Get('UserID');
      if ($UserID != Gdn::Session()->UserID)
         $Sender->Permission('Garden.Users.Edit');

      $User = Gdn::UserModel()->GetID($UserID);
      if (!$User)
         throw new Exception(sprintf(T("No such user '%s'"), $UserID), 404);

      $IgnoreUserID = Gdn::Request()->Get('IgnoreUserID');
      $IgnoreUser = Gdn::UserModel()->GetID($IgnoreUserID);
      if (!$IgnoreUser)
         throw new Exception(sprintf(T("No such user '%s'"), $IgnoreUserID), 404);

      $AddRestricted = $this->IgnoreRestricted($IgnoreUserID, $UserID);

      switch ($AddRestricted) {
         case self::IGNORE_GOD:
            throw new Exception(T("You can't ignore that person."), 403);

         case self::IGNORE_LIMIT:
            throw new Exception(T("You have reached the maximum number of ignores."), 406);

         case self::IGNORE_RESTRICTED:
            throw new Exception(T("Your ignore privileges have been revoked."), 403);

         case self::IGNORE_SELF:
            throw new Exception(T("You can't put yourself on ignore."), 406);

         default:
            $this->AddIgnore($UserID, $IgnoreUserID);
            $this->SetData('Success', sprintf(T("Added %s to ignore list."), $IgnoreUser->Name));
            break;
      }

      $Sender->Render();
   }

   public function Controller_Remove($Sender) {
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_DATA);

      $UserID = Gdn::Request()->Get('UserID');
      if ($UserID != Gdn::Session()->UserID)
         $Sender->Permission('Garden.Users.Edit');

      $User = Gdn::UserModel()->GetID($UserID);
      if (!$User)
         throw new Exception(sprintf(T("No such user '%s'"), $UserID), 404);

      $IgnoreUserID = Gdn::Request()->Get('IgnoreUserID');
      $IgnoreUser = Gdn::UserModel()->GetID($IgnoreUserID);
      if (!$IgnoreUser)
         throw new Exception(sprintf(T("No such user '%s'"), $IgnoreUserID), 404);

      $this->RemoveIgnore($UserID, $IgnoreUserID);
      $Sender->SetData('Success', sprintf(T("Removed %s from ignore list."), $IgnoreUser->Name));

      $Sender->Render();
   }

   public function Controller_Restrict($Sender) {
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_DATA);

      $UserID = Gdn::Request()->Get('UserID');
      if ($UserID != Gdn::Session()->UserID)
         $Sender->Permission('Garden.Users.Edit');

      $User = Gdn::UserModel()->GetID($UserID);
      if (!$User)
         throw new Exception("No such user '{$UserID}'", 404);

      $Restricted = strtolower(Gdn::Request()->Get('Restricted', 'no'));
      $Restricted = in_array($Restricted, array('yes', 'true', 'on', TRUE)) ? TRUE : NULL;
      $this->SetUserMeta($UserID, 'Forbidden', $Restricted);

      $Sender->SetData('Success', sprintf(T($Restricted ?
         "%s's ignore privileges have been disabled." :
         "%s's ignore privileges have been enabled."
      ), $User->Name));

      $Sender->Render();
   }

   public function ProfileController_Render_Before($Sender) {
      $Sender->AddJsFile('ignore.js', 'plugins/Ignore');
   }

   public function AssetModel_StyleCss_Handler($Sender) {
      $Sender->AddCssFile('ignore.css', 'plugins/Ignore');
   }

   /**
    * Add "Ignore" option to profile options.
    */
   public function ProfileController_BeforeProfileOptions_Handler($Sender, $Args) {
      if (!$Sender->EditMode && Gdn::Session()->IsValid()) {
         // Only show option if allowed
         $IgnoreRestricted = $this->IgnoreRestricted($Sender->User->UserID);
         if ($IgnoreRestricted && $IgnoreRestricted != self::IGNORE_LIMIT)
            return;

         // Add to dropdown
         $UserIgnored = $this->Ignored($Sender->User->UserID);
         $Label = ($UserIgnored) ? Sprite('SpUnignore').' '.T('Unignore') : Sprite('SpIgnore').' '.T('Ignore');
         $Args['ProfileOptions'][] = array('Text' => $Label,
            'Url' => "/user/ignore/toggle/{$Sender->User->UserID}/".Gdn_Format::Url($Sender->User->Name),
            'CssClass' => 'Popup');
      }
   }

   public function DiscussionController_BeforeDiscussionRender_Handler($Sender) {
      $Sender->AddJsFile('ignore.js', 'plugins/Ignore');
   }

   public function DiscussionController_BeforeCommentDisplay_Handler($Sender) {
      if ($this->IgnoreIsRestricted()) return;
      $UserID = GetValue('InsertUserID',$Sender->EventArguments['Object']);
      if ($this->Ignored($UserID)) {
         $Classes = explode(" ",$Sender->EventArguments['CssClass']);
         $Classes[] = 'Ignored';
         $Classes = array_fill_keys($Classes, NULL);
         $Classes = implode(' ',array_keys($Classes));
         $Sender->EventArguments['CssClass'] = $Classes;
      }
   }

   /**
    *
    *
    * @param MessageController $Sender
    */
   public function MessagesController_BeforeAddConversation_Handler($Sender) {

      $Recipients = $Sender->EventArguments['Recipients'];
      if (!is_array($Recipients) || !sizeof($Recipients)) return;

      $UserID = Gdn::Session()->UserID;
      foreach ($Recipients as $RecipientID) {
         if ($this->Ignored($UserID, $RecipientID)) {
            $User = Gdn::UserModel()->GetID($RecipientID, DATASET_TYPE_ARRAY);
            $Sender->Form->AddError(sprintf(T("Unable to create conversation, %s is ignoring you."), $User['Name']));
         }
      }
   }

   /**
    * Add a new message to a conversatio
    *
    * @param MessageController $Sender
    */
   public function MessagesController_BeforeAddMessage_Handler($Sender) {

      $ConversationID = $Sender->EventArguments['ConversationID'];
      $ConversationModel = new ConversationModel();
      $Recipients = $ConversationModel->GetRecipients($ConversationID);
      if (!$Recipients->NumRows()) return;

      $Recipients = $Recipients->ResultArray();
      $Recipients = ConsolidateArrayValuesByKey($Recipients, 'UserID');

      $UserID = Gdn::Session()->UserID;
      foreach ($Recipients as $RecipientID => $Recipient) {
         if ($this->Ignored($UserID, $RecipientID)) {
            $Sender->Form->AddError(sprintf(T('Unable to send message, %s is ignoring you.'), $User['Name']));
         }
      }

   }

   /**
    *
    * @param Controller $Sender
    */
   public function UserController_Ignore_Create($Sender) {
      $Sender->Permission('Garden.SignIn.Allow');

      $Args = $Sender->RequestArgs;
      if (sizeof($Args) < 3)
         $Args = array_merge($Args, array(0,0));
      elseif (sizeof($Args) > 2)
         $Args = array_slice($Args, 1, 3);

      list($UserReference, $Username) = $Args;

      // Set user
      $User = $this->GetUserInfo($UserReference, $Username);
      $Sender->SetData('User', $User);
      $UserID = GetValue('UserID', $User);

      // Set title and mode
      $IgnoreRestricted = $this->IgnoreIsRestricted($UserID);
      $UserIgnored = $this->Ignored($UserID);
      $Mode = $UserIgnored ? 'unset' : 'set';
      $ActionText = T($Mode == 'set' ? 'Ignore' : 'Unignore');
      $Sender->Title($ActionText);
      $Sender->SetData('Mode', $Mode);
      if ($Mode == 'set') {
         // Check is Ignore is allowed.
         $IgnoreRestricted = $this->IgnoreRestricted($UserID);
      }
      try {
         // Check for prevented states
         switch ($IgnoreRestricted) {
            case self::IGNORE_GOD:
               $Sender->InformMessage('<span class="InformSprite Lightbulb"></span>'.T("You can't ignore that person."),
                  'AutoDismiss HasSprite'
               );
               break;

            case self::IGNORE_LIMIT:
               $Sender->InformMessage('<span class="InformSprite Lightbulb"></span>'.T("You have reached the maximum number of ignores."),
                  'AutoDismiss HasSprite'
               );
               break;

            case self::IGNORE_RESTRICTED:
               $Sender->InformMessage('<span class="InformSprite Lightbulb"></span>'.T("Your ignore privileges have been revoked."),
                  'AutoDismiss HasSprite'
               );
               break;

            case self::IGNORE_SELF:
               $Sender->InformMessage('<span class="InformSprite Lightbulb"></span>'.T("You can't put yourself on ignore."),
                  'AutoDismiss HasSprite'
               );
               break;
         }

         // Get conversation intersects
         $Conversations = $this->IgnoreConversations($UserID);
         $Sender->SetData('Conversations', $Conversations);

         if ($Sender->Form->AuthenticatedPostBack()) {
            switch ($Mode) {
               case 'set':

                  if (!$IgnoreRestricted) {
                     $Sender->JsonTarget('a.IgnoreButton', T('Unignore'), 'Text');
                     $this->AddIgnore(Gdn::Session()->UserID, $UserID);
                     $Sender->InformMessage(
                        '<span class="InformSprite Contrast"></span>'.sprintf(T("%s is now on ignore."), $User->Name),
                        'AutoDismiss HasSprite'
                     );
                  }

                  break;

               case 'unset':

                  if (!$IgnoreRestricted) {
                     $Sender->JsonTarget('a.IgnoreButton', T('Ignore'), 'Text');
                     $this->RemoveIgnore(Gdn::Session()->UserID, $UserID);
                     $Sender->InformMessage(
                        '<span class="InformSprite Brightness"></span>'.sprintf(T("%s is no longer on ignore."), $User->Name),
                        'AutoDismiss HasSprite'
                     );
                     $Sender->RedirectUrl = Url('/profile/ignore');
                  }

                  break;

               default:
                  $Sender->InformMessage(T("Unsupported operation."));
                  $Sender->SetJson('Status',400);
                  break;
            }
         }

      } catch (Exception $Ex) {
         $Sender->InformMessage(T("Could not find that person! - ".$Ex->getMessage()));
         $Sender->SetJson('Status', 404);
      }

      $Sender->Render('confirm', '', 'plugins/Ignore');
   }

   /**
    * @param UserController $Sender
    */
   public function UserController_IgnoreList_Create($Sender) {
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);

      if (!Gdn::Session()->CheckPermission('Garden.Users.Edit')) {
         $Sender->SetJson('Status', 401);
         $Sender->Render('blank', 'utility', 'dashboard');
      }

      $Sender->SetJson('Status',200);

      $Args = $Sender->RequestArgs;
      if (sizeof($Args) < 3)
         $Args = array_merge($Args, array(0,0));
      elseif (sizeof($Args) > 2)
         $Args = array_slice($Args, 1, 3);

      list($UserReference, $Username) = $Args;

      $User = $this->GetUserInfo($UserReference, $Username);
      $UserID = GetValue('UserID', $User);

      if ($User->Admin) {
         $Sender->InformMessage(sprintf(T("You can't do that to %s!", $User->Name)));
         $Sender->SetJson('Status', 401);
         $Sender->Render('blank', 'utility', 'dashboard');
      }

      $Mode = $Sender->RequestArgs[0];

      try {

         switch ($Mode) {
            case 'allow':
               $this->SetUserMeta($UserID, 'Forbidden', NULL);
               $Sender->JsonTarget('#revoke', T('Restored'));
               $Sender->JsonTarget('', '', 'Refresh');
               break;

            case 'revoke':
               $this->SetUserMeta($UserID, 'Forbidden', TRUE);
               $Sender->JsonTarget('#revoke', T('Revoked'));
               $Sender->JsonTarget('', '', 'Refresh');
               break;

            default:
               $Sender->InformMessage(T("Unsupported operation."));
               $Sender->SetJson('Status',400);
               break;
         }

      } catch (Exception $Ex) {
         $Sender->InformMessage(T("Could not find that person! - ".$Ex->getMessage()));
         $Sender->SetJson('Status', 404);
      }
      $Sender->Render('blank', 'utility', 'dashboard');
   }

   protected function GetUserInfo($UserReference = '', $Username = '', $UserID = '') {
      // If a UserID was provided as a querystring parameter, use it over anything else:
		if ($UserID) {
			$UserReference = $UserID;
			$Username = 'Unknown'; // Fill this with a value so the $UserReference is assumed to be an integer/userid.
		}

      if ($UserReference == '') {
         $User = Gdn::UserModel()->GetID(Gdn::Session()->UserID);
      } else if (is_numeric($UserReference) && $Username != '') {
         $User = Gdn::UserModel()->GetID($UserReference);
      } else {
         $User = Gdn::UserModel()->GetByUsername($UserReference);
      }

      if ($User === FALSE) {
         throw NotFoundException();
      } else if ($User->Deleted == 1) {
         throw NotFoundException();
      } else if (GetValue('UserID', $User) == Gdn::Session()->UserID) {
         throw NotFoundException();
      } else {
         return $User;
      }
   }

   protected function AddIgnore($ForUserID, $IgnoreUserID) {
      $this->SetUserMeta($ForUserID, "Blocked.User.{$IgnoreUserID}", date('Y-m-d H:i:s'));

      // Remove from conversations
      $Conversations = $this->IgnoreConversations($IgnoreUserID, $ForUserID);
      Gdn::SQL()->Delete('UserConversation', array(
         'UserID'          => $ForUserID,
         'ConversationID'  => $Conversations
      ));
   }

   protected function RemoveIgnore($ForUserID, $IgnoreUserID) {
      $this->SetUserMeta($ForUserID, "Blocked.User.{$IgnoreUserID}", NULL);
   }

   public function Ignored($UserID = NULL, $SessionUserID = NULL) {
      static $BlockedUsers = NULL;

      if (is_null($SessionUserID))
         $SessionUserID = Gdn::Session()->UserID;

      if (is_null($BlockedUsers))
         $BlockedUsers = $this->GetUserMeta($SessionUserID, 'Blocked.User.%');

      if (is_null($UserID)) return $BlockedUsers;

      $BlockKey = $this->MakeMetaKey("Blocked.User.{$UserID}");
      if (array_key_exists($BlockKey, $BlockedUsers))
         return TRUE;

      return FALSE;
   }

   public function IgnoreRestricted($UserID, $SessionUserID = NULL) {
      if (is_null($SessionUserID))
         $SessionUserID = Gdn::Session()->UserID;

      // Noone can ignore themselves
      if ($UserID == $SessionUserID) return self::IGNORE_SELF;

      // Admins can't be ignored
      $IgnoreUser = Gdn::UserModel()->GetID($UserID);
      if ($IgnoreUser->Admin) return self::IGNORE_GOD;

      // Forum admins can;t be ignored.
      if (Gdn::UserModel()->CheckPermission($IgnoreUser, 'Garden.Settings.Manage')) {
         return self::IGNORE_FORUM_ADMIN;
      }

      if (!$this->allowModeratorIgnore && Gdn::UserModel()->CheckPermission($IgnoreUser, 'Garden.Moderation.Manage')) {
         return self::IGNORE_FORUM_MOD;
      }

      // Admins can ignore anyone
      if (Gdn::Session()->CheckPermission('Garden.Settings.Manage')) return FALSE;

      // Ignore has been restricted for you
      $IgnoreRestricted = $this->GetUserMeta($SessionUserID, 'Plugin.Ignore.Forbidden');
      $IgnoreRestricted = GetValue('Plugin.Ignore.Forbidden', $IgnoreRestricted, FALSE);
      if ($IgnoreRestricted) return self::IGNORE_RESTRICTED;

      $IgnoredUsers = $this->GetUserMeta($SessionUserID, 'Blocked.User.%');
      $NumIgnoredUsers = sizeof($IgnoredUsers);
      $MaxIgnores = C('Plugins.Ignore.MaxIgnores', 5);
      if ($NumIgnoredUsers >= $MaxIgnores) return self::IGNORE_LIMIT;

      return FALSE;
   }

   /**
    * Is this user forbidden from using ignore?
    */
   public function IgnoreIsRestricted($UserID = NULL) {
      // Guests cant ignore
      if (!Gdn::Session()->IsValid()) return TRUE;

      if (is_null($UserID))
         $UserID = Gdn::Session()->UserID;

      if (is_null($UserID)) return TRUE;

      $IgnoreRestricted = $this->GetUserMeta($UserID, 'Plugin.Ignore.Forbidden');
      $IgnoreRestricted = GetValue('Plugin.Ignore.Forbidden', $IgnoreRestricted, FALSE);
      if ($IgnoreRestricted) return TRUE;

      return FALSE;
   }

   public function IgnoreConversations($IgnoreUserID, $SessionUserID = NULL) {
      // Guests cant ignore
      if (!Gdn::Session()->IsValid()) return FALSE;

      if (is_null($SessionUserID))
         $SessionUserID = Gdn::Session()->UserID;

      // Noone can ignore themselves
      if ($IgnoreUserID == $SessionUserID) return self::IGNORE_SELF;

      // Get ignore user's conversation IDs
      $IgnoreConversations = Gdn::SQL()
         ->Select('ConversationID')
         ->From('UserConversation')
         ->Where('UserID', $IgnoreUserID)
         ->Where('Deleted', 0)
         ->Get()->ResultArray();
      $IgnoreConversationIDs = ConsolidateArrayValuesByKey($IgnoreConversations, 'ConversationID','ConversationID');
      unset($IgnoreConversations);

      // Get session user's conversation IDs
      $SessionConversations = Gdn::SQL()
         ->Select('ConversationID')
         ->From('UserConversation')
         ->Where('UserID', $SessionUserID)
         ->Where('Deleted', 0)
         ->Get()->ResultArray();
      $SessionConversationIDs = ConsolidateArrayValuesByKey($SessionConversations, 'ConversationID','ConversationID');
      unset($SessionConversations);

      $CommonConversations = array_intersect($IgnoreConversationIDs, $SessionConversationIDs);
      $CommonConversationIDs = array_values($CommonConversations);
      $CommonConversationIDs = array_unique($CommonConversationIDs);

      return $CommonConversationIDs;
   }

}
