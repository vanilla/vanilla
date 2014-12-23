<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

// Define the plugin:
$PluginInfo['Whispers'] = array(
   'Name' => 'Whispers',
   'Description' => "Users may 'whisper' private comments to each other in the middle of normal discussions. Caution: this can be a confusing feature for some people.",
   'Version' => '1.1.2',
   'RequiredApplications' => array('Vanilla' => '2.0.18', 'Conversations' => '2.0.18'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'RegisterPermissions' => array('Plugins.Whispers.Allow' => 'Garden.Moderation.Manage')
);

class WhispersPlugin extends Gdn_Plugin {
   /// Properties ///
   public $Conversations = NULL;


   /// Methods ///

   public function GetWhispers($DiscussionID, $Comments, $Limit, $Offset) {
      $FirstDate = NULL;
      $LastDate = NULL;

      if (count($Comments) > 0) {
         if ($Offset > 0) {
            $FirstComment = array_shift($Comments);
            $FirstDate = GetValue('DateInserted', $FirstComment);
            array_unshift($Comments, $FirstComment);
         }

         if (count($Comments) < $Limit) {
            $LastComment = array_pop($Comments);
            array_push($Comments, $LastComment);

            $LastCommentID = GetValue('CommentID', $LastComment);

            // We need to grab the comment that is one after the last comment.
            $LastComment = Gdn::SQL()->Limit(1)->GetWhere('Comment', array('DiscussionID' => $DiscussionID, 'CommentID >' => $LastCommentID))->FirstRow();
            if ($LastComment)
               $LastDate = GetValue('DateInserted', $LastComment);
         }
      }

      // Grab the conversations that are associated with this discussion.
      $Sql = Gdn::SQL()
         ->Select('c.ConversationID, c.DateUpdated')
         ->From('Conversation c')
         ->Where('c.DiscussionID', $DiscussionID);

      if (!Gdn::Session()->CheckPermission('Conversations.Moderation.Manage')) {
         $Sql->Join('UserConversation uc', 'c.ConversationID = uc.ConversationID')
            ->Where('uc.UserID', Gdn::Session()->UserID);
      }

      $Conversations = $Sql->Get()->ResultArray();
      $Conversations = Gdn_DataSet::Index($Conversations, 'ConversationID');

      // Join the participants into the conversations.
      $ConversationModel = new ConversationModel();
      $ConversationModel->JoinParticipants($Conversations);
      $this->Conversations = $Conversations;

      $ConversationIDs = array_keys($Conversations);

      // Grab all messages that are between the first and last dates.
      $Sql = Gdn::SQL()
         ->Select('cm.*')
//         ->Select('iu.Name as InsertName, iu.Photo as InsertPhoto')
         ->From('ConversationMessage cm')
//         ->Join('User iu', 'cm.InsertUserID = iu.UserID')
         ->WhereIn('cm.ConversationID', $ConversationIDs)
         ->OrderBy('cm.DateInserted');

      if ($FirstDate)
         $Sql->Where('cm.DateInserted >=', $FirstDate);
      if ($LastDate)
         $Sql->Where('cm.DateInserted <', $LastDate);

      $Whispers = $Sql->Get();

      Gdn::UserModel()->JoinUsers($Whispers->Result(), array('InsertUserID'));

      // Add dummy comment fields to the whispers.
      $WhispersResult =& $Whispers->Result();
      foreach ($WhispersResult as &$Whisper) {
         SetValue('DiscussionID', $Whisper, $DiscussionID);
         SetValue('CommentID', $Whisper, 'w'.GetValue('MessageID', $Whisper));
         SetValue('Type', $Whisper, 'Whisper');
         SetValue('Url', $Whisper, '');

         $Participants = GetValueR(GetValue('ConversationID', $Whisper).'.Participants', $Conversations);
         SetValue('Participants', $Whisper, $Participants);
      }

      return $Whispers;
   }

   public function MergeWhispers($Comments, $Whispers) {
      $Result = array_merge($Comments, $Whispers);
      usort($Result, array('WhispersPlugin', '_MergeWhispersSort'));
      return $Result;
   }

   protected static function _MergeWhispersSort($A, $B) {
      $DateA = Gdn_Format::ToTimestamp(GetValue('DateInserted', $A));
      $DateB = Gdn_Format::ToTimestamp(GetValue('DateInserted', $B));

      if ($DateA > $DateB)
         return 1;
      elseif ($DateB < $DateB)
         return -1;
      else
         0;
   }

   public function Setup() {
      $this->Structure();
      SaveToConfig('Conversations.Moderation.Allow', TRUE);
   }

   public function Structure() {
      Gdn::Structure()
         ->Table('Conversation')
         ->Column('DiscussionID', 'int', NULL, 'index')
         ->Set();
   }

   public function UserRowCompare($A, $B) {
      return strcasecmp($A['Name'], $B['Name']);
   }

   /// Event Handlers ///

   public function CommentModel_AfterGet_Handler($Sender, $Args) {
      // Grab the whispers associated with this discussion.
      $DiscussionID = $Args['DiscussionID'];
      $Comments =& $Args['Comments'];
      $CommentsResult =& $Comments->Result();
      $Whispers = $this->GetWhispers($DiscussionID, $CommentsResult, $Args['Limit'], $Args['Offset']);
      $Whispers->DatasetType($Comments->DatasetType());

      $CommentsResult = $this->MergeWhispers($CommentsResult, $Whispers->Result());

      // Check to see if the whispers are more recent than the last comment in the discussion so that the discussion will update the watch.
      if (isset(Gdn::Controller()->Discussion)) {
         $Discussion =& Gdn::Controller()->Discussion;
         $DateLastComment = Gdn_Format::ToTimestamp(GetValue('DateLastComment', $Discussion));

         foreach ($this->Conversations as $Conversation) {
            if (Gdn_Format::ToTimestamp($Conversation['DateUpdated']) > $DateLastComment) {
               SetValue('DateLastComment', $Discussion, $Conversation['DateUpdated']);
               $DateLastComment = Gdn_Format::ToTimestamp($Conversation['DateUpdated']);
            }
         }
      }
   }

   /**
    * @param Gdn_Controller $Sender
    * @param args $Args
    */
   public function DiscussionController_AfterBodyField_Handler($Sender, $Args) {
      $Sender->AddJsFile('whispers.js', 'plugins/Whispers', array('hint' => 'inline'));
      $Sender->AddJsFile('jquery.autogrow.js');
      $Sender->AddJsFile('jquery.autocomplete.js');

      $this->Form = $Sender->Form;
      include $Sender->FetchViewLocation('WhisperForm', '', 'plugins/Whispers');
   }

   public function DiscussionController_CommentInfo_Handler($Sender, $Args) {
      if (!isset($Args['Comment']))
         return;
      $Comment = $Args['Comment'];
      if (!GetValue('Type', $Comment) == 'Whisper')
         return;

      $Participants = GetValue('Participants', $Comment);
      $ConversationID = GetValue('ConversationID', $Comment);
      $MessageID = GetValue('MessageID', $Comment);
      $MessageUrl = "/messages/$ConversationID#Message_$MessageID";

      echo '<div class="Whisper-Info"><b>'.Anchor(T('Private Between'), $MessageUrl).'</b>: ';
      $First = TRUE;
      foreach ($Participants as $UserID => $User) {
         if ($First)
            $First = FALSE;
         else
            echo ', ';

         echo UserAnchor($User);
      }
      echo '</div>';
   }

   public function DiscussionController_BeforeCommentDisplay_Handler($Sender, $Args) {
      if (!isset($Args['Comment']))
         return;
      $Comment = $Args['Comment'];
      if (!GetValue('Type', $Comment) == 'Whisper')
         return;

      $Args['CssClass'] = ConcatSep(' ', $Args['CssClass'], 'Whisper');
   }

   public function DiscussionController_CommentOptions_Handler($Sender, $Args) {
      if (!isset($Args['Comment']))
         return;
      $Comment = $Args['Comment'];
      if (!GetValue('Type', $Comment) == 'Whisper')
         return;

      $Sender->Options = '';
   }

   public function DiscussionsController_AfterCountMeta_Handler($Sender, $Args) {
      $Discussion = GetValue('Discussion', $Args);
      if (!$Discussion)
         return;

      if ($CountWhispers = GetValue('CountWhispers', $Discussion)) {
         $Str = ' <span class="CommentCount MItem">'.Plural($CountWhispers, '%s whisper', '%s whispers').'</span> ';

         if (GetValue('NewWhispers', $Discussion)) {
            $Str .= ' <strong class="HasNew">'.T('new').'</strong> ';
         }
         echo $Str;
      }
   }

   /**
    * @param DiscussionController $Sender
    */
   public function DiscussionController_Render_Before($Sender, $Args) {
      $ConversationID = $Sender->Data('Discussion.Attributes.WhisperConversationID');
      if (!$ConversationID)
         return;

      if ($ConversationID === TRUE) {
         $UserIDs = $Sender->Data('Discussion.Attributes.WhisperUserIDs');
         // Grab the users that are in the conversaton.
         $WhisperUsers = array();
         foreach ($UserIDs as $UserID) {
            $WhisperUsers[] = array('UserID' => $UserID);
         }
      } else {
         // There is already a conversation so grab its users.
         $WhisperUsers = Gdn::SQL()
            ->Select('UserID')
            ->From('UserConversation')
            ->Where('ConversationID', $ConversationID)
            ->Where('Deleted', 0)
            ->Get()->ResultArray();
         $UserIDs = ConsolidateArrayValuesByKey($WhisperUsers, 'UserID');
      }

      if (!Gdn::Session()->CheckPermission('Conversations.Moderation.Manage') && !in_array(Gdn::Session()->UserID, $UserIDs)) {
         $Sender->Data['Discussion']->Closed = TRUE;
         return;
      }

      Gdn::UserModel()->JoinUsers($WhisperUsers, array('UserID'));
      $Sender->SetData('WhisperUsers', $WhisperUsers);
   }

   /**
    * Join message counts into the discussion list.
    * @param DiscussionModel $Sender
    * @param array $Args
    */
   public function DiscussionModel_AfterAddColumns_Handler($Sender, $Args) {
      if (!Gdn::Session()->UserID)
         return;

      $Data = $Args['Data'];
      $Result =& $Data->Result();

      // Gather the discussion IDs.
      $DiscusisonIDs = array();

      foreach ($Result as $Row) {
         $DiscusisonIDs[] = GetValue('DiscussionID', $Row);
      }

      // Grab all of the whispers associated to the discussions being looked at.
      $Sql = Gdn::SQL()
         ->Select('c.DiscussionID')
         ->Select('c.CountMessages', 'sum', 'CountMessages')
         ->Select('c.DateUpdated', 'max', 'DateLastMessage')
         ->From('Conversation c')
         ->WhereIn('c.DiscussionID', $DiscusisonIDs)
         ->GroupBy('c.DiscussionID');

      if (!Gdn::Session()->CheckPermission('Conversations.Moderation.Manage')) {
         $Sql->Join('UserConversation uc', 'c.ConversationID = uc.ConversationID')
            ->Where('uc.UserID', Gdn::Session()->UserID);
      }

      $Conversations = $Sql->Get()->ResultArray();
      $Conversations = Gdn_DataSet::Index($Conversations, 'DiscussionID');

      foreach ($Result as &$Row) {
         $DiscusisonID = GetValue('DiscussionID', $Row);
         $CRow = GetValue($DiscusisonID, $Conversations);

         if (!$CRow)
            continue;

         $DateLastViewed = GetValue('DateLastViewed', $Row);
         $DateLastMessage = $CRow['DateLastMessage'];
         $NewWhispers = Gdn_Format::ToTimestamp($DateLastViewed) < Gdn_Format::ToTimestamp($DateLastMessage);

         SetValue('CountWhispers', $Row, $CRow['CountMessages']);
         SetValue('DateLastWhisper', $Row, $DateLastMessage);
         SetValue('NewWhispers', $Row, $NewWhispers);
      }

   }

   public function MessagesController_BeforeConversation_Handler($Sender, $Args) {
      $DiscussionID = $Sender->Data('Conversation.DiscussionID');
      if (!$DiscussionID)
         return;

      include $Sender->FetchViewLocation('BeforeConversation', '', 'plugins/Whispers');
   }

   public function MessagesController_BeforeConversationMeta_Handler($Sender, $Args) {
      $DiscussionID = GetValueR('Conversation.DiscussionID', $Args);

      if ($DiscussionID) {
         echo '<span class="MetaItem Tag Whispers-Tag">'.Anchor(T('Whisper'), "/discussion/$DiscussionID/x").'</span>';
      }
   }

   /**
    * @param PostController $Sender
    * @param array $Args
    * @return mixed
    */
   public function PostController_Comment_Create($Sender, $Args = array()) {
      if ($Sender->Form->IsPostBack()) {
         $Sender->Form->SetModel($Sender->CommentModel);

         // Grab the discussion for use later.
         $DiscussionID = $Sender->Form->GetFormValue('DiscussionID');
         $DiscussionModel = new DiscussionModel();
         $Discussion = $DiscussionModel->GetID($DiscussionID);

         // Check to see if the discussion is supposed to be in private...
         $WhisperConversationID = GetValueR('Attributes.WhisperConversationID', $Discussion);
         if ($WhisperConversationID === TRUE) {
            // There isn't a conversation so we want to create one.
            $Sender->Form->SetFormValue('Whisper', TRUE);
            $WhisperUserIDs = GetValueR('Attributes.WhisperUserIDs', $Discussion);
            $Sender->Form->SetFormValue('RecipientUserID', $WhisperUserIDs);
         } elseif ($WhisperConversationID) {
            // There is already a conversation.
            $Sender->Form->SetFormValue('Whisper', TRUE);
            $Sender->Form->SetFormValue('ConversationID', $WhisperConversationID);
         }

         $Whisper = $Sender->Form->GetFormValue('Whisper') && GetIncomingValue('Type') != 'Draft';
         $WhisperTo = trim($Sender->Form->GetFormValue('To'));
         $ConversationID = $Sender->Form->GetFormValue('ConversationID');

         // If this isn't a whisper then post as normal.
         if (!$Whisper)
            return call_user_func_array(array($Sender, 'Comment'), $Args);

         $ConversationModel = new ConversationModel();
         $ConversationMessageModel = new ConversationMessageModel();

         if ($ConversationID > 0) {
            $Sender->Form->SetModel($ConversationMessageModel);
         } else {
            // We have to remove the blank conversation ID or else the model won't validate.
            $FormValues = $Sender->Form->FormValues();
            unset($FormValues['ConversationID']);
            $FormValues['Subject'] = GetValue('Name', $Discussion);
            $Sender->Form->FormValues($FormValues);

            $Sender->Form->SetModel($ConversationModel);
            $ConversationModel->Validation->ApplyRule('DiscussionID', 'Required');
         }

         $ID = $Sender->Form->Save($ConversationMessageModel);

         if ($Sender->Form->ErrorCount() > 0) {
            $Sender->ErrorMessage($Sender->Form->Errors());
         } else {
            if ($WhisperConversationID === TRUE) {
               $Discussion->Attributes['WhisperConversationID'] = $ID;
               $DiscussionModel->SetProperty($DiscussionID, 'Attributes', serialize($Discussion->Attributes));
            }

            $LastCommentID = GetValue('LastCommentID', $Discussion);
            $MessageID = GetValue('LastMessageID', $ConversationMessageModel, FALSE);

            // Randomize the querystring to force the browser to refresh.
            $Rand = mt_rand(10000, 99999);

            if ($LastCommentID) {
               // Link to the last comment.
               $HashID = $MessageID ? 'w'.$MessageID : $LastCommentID;

               $Sender->RedirectUrl = Url("discussion/comment/$LastCommentID?rand=$Rand#Comment_$HashID", TRUE);
            } else {
               // Link to the discussion.
               $Hash = $MessageID ? "Comment_w$MessageID" : 'Item_1';
               $Name = rawurlencode(GetValue('Name', $Discussion, 'x'));
               $Sender->RedirectUrl = Url("discussion/$DiscussionID/$Name?rand=$Rand#$Hash", TRUE);
            }
         }
         $Sender->Render();
      } else {
         return call_user_func_array(array($Sender, 'Comment'), $Args);
      }
   }
}