<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

// Define the plugin:
$PluginInfo['QnA'] = array(
   'Name' => 'Q&A',
   'Description' => "Users may designate a discussion as a Question and then officially accept one or more of the comments as the answer.",
   'Version' => '1.2.2',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'MobileFriendly' => TRUE,
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

/**
 * Adds Question & Answer format to Vanilla.
 *
 * You can set Plugins.QnA.UseBigButtons = TRUE in config to separate 'New Discussion'
 * and 'Ask Question' into "separate" forms each with own big button in Panel.
 */
class QnAPlugin extends Gdn_Plugin {
   /// PROPERTIES ///

   protected $Reactions = FALSE;
   protected $Badges = FALSE;

   /// METHODS ///

   public function __construct() {
      parent::__construct();

      if (Gdn::PluginManager()->CheckPlugin('Reactions') && C('Plugins.QnA.Reactions', TRUE)) {
         $this->Reactions = TRUE;
      }

      if (Gdn::ApplicationManager()->CheckApplication('Reputation') && C('Plugins.QnA.Badges', TRUE)) {
         $this->Badges = TRUE;
      }

   }

   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      Gdn::Structure()
         ->Table('Discussion');

      $QnAExists = Gdn::Structure()->ColumnExists('QnA');
      $DateAcceptedExists = Gdn::Structure()->ColumnExists('DateAccepted');

      Gdn::Structure()
         ->Column('QnA', array('Unanswered', 'Answered', 'Accepted', 'Rejected'), NULL)
         ->Column('DateAccepted', 'datetime', TRUE) // The
         ->Column('DateOfAnswer', 'datetime', TRUE) // The time to answer an accepted question.
         ->Set();

      Gdn::Structure()
         ->Table('Comment')
         ->Column('QnA', array('Accepted', 'Rejected'), NULL)
         ->Column('DateAccepted', 'datetime', TRUE)
         ->Column('AcceptedUserID', 'int', TRUE)
         ->Set();

      Gdn::Structure()
         ->Table('User')
         ->Column('CountAcceptedAnswers', 'int', '0')
         ->Set();

      Gdn::SQL()->Replace(
         'ActivityType',
         array('AllowComments' => '0', 'RouteCode' => 'question', 'Notify' => '1', 'Public' => '0', 'ProfileHeadline' => '', 'FullHeadline' => ''),
         array('Name' => 'QuestionAnswer'), TRUE);
      Gdn::SQL()->Replace(
         'ActivityType',
         array('AllowComments' => '0', 'RouteCode' => 'answer', 'Notify' => '1', 'Public' => '0', 'ProfileHeadline' => '', 'FullHeadline' => ''),
         array('Name' => 'AnswerAccepted'), TRUE);

      if ($QnAExists && !$DateAcceptedExists) {
         // Default the date accepted to the accepted answer's date.
         $Px = Gdn::Database()->DatabasePrefix;
         $Sql = "update {$Px}Discussion d set DateAccepted = (select min(c.DateInserted) from {$Px}Comment c where c.DiscussionID = d.DiscussionID and c.QnA = 'Accepted')";
         Gdn::SQL()->Query($Sql, 'update');
         Gdn::SQL()->Update('Discussion')
            ->Set('DateOfAnswer', 'DateAccepted', FALSE, FALSE)
            ->Put();

         Gdn::SQL()->Update('Comment c')
            ->Join('Discussion d', 'c.CommentID = d.DiscussionID')
            ->Set('c.DateAccepted', 'c.DateInserted', FALSE, FALSE)
            ->Set('c.AcceptedUserID', 'd.InsertUserID', FALSE, FALSE)
            ->Where('c.QnA', 'Accepted')
            ->Where('c.DateAccepted', NULL)
            ->Put();
      }

      $this->StructureReactions();
      $this->StructureBadges();
   }

   /**
    * Define all of the structure related to badges.
    */
   public function StructureBadges() {
      // Define 'Answer' badges
      if (!$this->Badges || !class_exists('BadgeModel'))
         return;

      $BadgeModel = new BadgeModel();

      // Answer Counts
      $BadgeModel->Define(array(
          'Name' => 'First Answer',
          'Slug' => 'answer',
          'Type' => 'UserCount',
          'Body' => 'Answering questions is a great way to show your support for a community!',
          'Photo' => 'http://badges.vni.la/100/answer.png',
          'Points' => 2,
          'Attributes' => array('Column' => 'CountAcceptedAnswers'),
          'Threshold' => 1,
          'Class' => 'Answerer',
          'Level' => 1,
          'CanDelete' => 0
      ));
      $BadgeModel->Define(array(
          'Name' => '5 Answers',
          'Slug' => 'answer-5',
          'Type' => 'UserCount',
          'Body' => 'Your willingness to share knowledge has definitely been noticed.',
          'Photo' => 'http://badges.vni.la/100/answer-2.png',
          'Points' => 3,
          'Attributes' => array('Column' => 'CountAcceptedAnswers'),
          'Threshold' => 5,
          'Class' => 'Answerer',
          'Level' => 2,
          'CanDelete' => 0
      ));
      $BadgeModel->Define(array(
          'Name' => '25 Answers',
          'Slug' => 'answer-25',
          'Type' => 'UserCount',
          'Body' => 'Looks like you&rsquo;re starting to make a name for yourself as someone who knows the score!',
          'Photo' => 'http://badges.vni.la/100/answer-3.png',
          'Points' => 5,
          'Attributes' => array('Column' => 'CountAcceptedAnswers'),
          'Threshold' => 25,
          'Class' => 'Answerer',
          'Level' => 3,
          'CanDelete' => 0
      ));
      $BadgeModel->Define(array(
          'Name' => '50 Answers',
          'Slug' => 'answer-50',
          'Type' => 'UserCount',
          'Body' => 'Why use Google when we could just ask you?',
          'Photo' => 'http://badges.vni.la/100/answer-4.png',
          'Points' => 10,
          'Attributes' => array('Column' => 'CountAcceptedAnswers'),
          'Threshold' => 50,
          'Class' => 'Answerer',
          'Level' => 4,
          'CanDelete' => 0
      ));
      $BadgeModel->Define(array(
          'Name' => '100 Answers',
          'Slug' => 'answer-100',
          'Type' => 'UserCount',
          'Body' => 'Admit it, you read Wikipedia in your spare time.',
          'Photo' => 'http://badges.vni.la/100/answer-5.png',
          'Points' => 15,
          'Attributes' => array('Column' => 'CountAcceptedAnswers'),
          'Threshold' => 100,
          'Class' => 'Answerer',
          'Level' => 5,
          'CanDelete' => 0
      ));
      $BadgeModel->Define(array(
          'Name' => '250 Answers',
          'Slug' => 'answer-250',
          'Type' => 'UserCount',
          'Body' => 'Is there *anything* you don&rsquo;t know?',
          'Photo' => 'http://badges.vni.la/100/answer-6.png',
          'Points' => 20,
          'Attributes' => array('Column' => 'CountAcceptedAnswers'),
          'Threshold' => 250,
          'Class' => 'Answerer',
          'Level' => 6,
          'CanDelete' => 0
      ));
   }

   /**
    * Define all of the structure releated to reactions.
    * @return type
    */
   public function StructureReactions() {
      // Define 'Accept' reaction
      if (!$this->Reactions)
         return;

      $Rm = new ReactionModel();

      if (Gdn::Structure()->Table('ReactionType')->ColumnExists('Hidden')) {

         // AcceptAnswer
         $Rm->DefineReactionType(array('UrlCode' => 'AcceptAnswer', 'Name' => 'Accept Answer', 'Sort' => 0, 'Class' => 'Good', 'IncrementColumn' => 'Score', 'IncrementValue' => 5, 'Points' => 3, 'Permission' => 'Garden.Curation.Manage', 'Hidden' => 1,
            'Description' => "When someone correctly answers a question, they are rewarded with this reaction."));

      }

      Gdn::Structure()->Reset();
   }


   /// EVENTS ///

   public function Base_AddonEnabled_Handler($Sender, $Args) {
      switch (strtolower($Args['AddonName'])) {
         case 'reactions':
            $this->Reactions = TRUE;
            $this->StructureReactions();
            break;
         case 'reputation':
            $this->Badges = TRUE;
            $this->StructureBadges();
            break;
      }
   }

   public function Base_BeforeCommentDisplay_Handler($Sender, $Args) {
      $QnA = GetValueR('Comment.QnA', $Args);

      if ($QnA && isset($Args['CssClass'])) {
         $Args['CssClass'] = ConcatSep(' ', $Args['CssClass'], "QnA-Item-$QnA");
      }
   }

   public function Base_DiscussionTypes_Handler($Sender, $Args) {
      $Args['Types']['Question'] = array(
            'Singular' => 'Question',
            'Plural' => 'Questions',
            'AddUrl' => '/post/question',
            'AddText' => 'Ask a Question'
            );
   }

   /**
    *
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
//   public function Base_AfterReactions_Handler($Sender, $Args) {
//   // public function Base_CommentOptions_Handler($Sender, $Args) {
//      $Discussion = GetValue('Discussion', $Args);
//      $Comment = GetValue('Comment', $Args);
//
//      if (!$Comment)
//         return;
//
//      $CommentID = GetValue('CommentID', $Comment);
//      if (!is_numeric($CommentID))
//         return;
//
//      if (!$Discussion) {
//         static $DiscussionModel = NULL;
//         if ($DiscussionModel === NULL)
//            $DiscussionModel = new DiscussionModel();
//         $Discussion = $DiscussionModel->GetID(GetValue('DiscussionID', $Comment));
//      }
//
//      if (!$Discussion || strtolower(GetValue('Type', $Discussion)) != 'question')
//         return;
//
//      // Check permissions.
//      $CanAccept = Gdn::Session()->CheckPermission('Garden.Moderation.Manage');
//      $CanAccept |= Gdn::Session()->UserID == GetValue('InsertUserID', $Discussion) && Gdn::Session()->UserID != GetValue('InsertUserID', $Comment);
//
//      if (!$CanAccept)
//         return;
//
//      $QnA = GetValue('QnA', $Comment);
//      if ($QnA)
//         return;
//
//      // Write the links.
//      $Types = GetValue('ReactionTypes', $Sender->EventArguments);
//      if ($Types)
//         echo Bullet();
//
//      $Query = http_build_query(array('commentid' => $CommentID, 'tkey' => Gdn::Session()->TransientKey()));
//      echo Anchor(Sprite('ReactAccept', 'ReactSprite').T('Accept', 'Accept'), '/discussion/qna/accept?'.$Query, array('class' => 'React QnA-Yes', 'title' => T('Accept this answer.')));
//      echo Anchor(Sprite('ReactReject', 'ReactSprite').T('Reject', 'Reject'), '/discussion/qna/reject?'.$Query, array('class' => 'React QnA-No', 'title' => T('Reject this answer.')));
//
//      static $InformMessage = TRUE;
//
//      if ($InformMessage && Gdn::Session()->UserID == GetValue('InsertUserID', $Discussion) && in_array(GetValue('QnA', $Discussion), array('', 'Answered'))) {
//         $Sender->InformMessage(T('Click accept or reject beside an answer.'), 'Dismissable');
//         $InformMessage = FALSE;
//      }
//   }

   public function Base_CommentInfo_Handler($Sender, $Args) {
      $Type = GetValue('Type', $Args);
      if ($Type != 'Comment')
         return;

      $QnA = GetValueR('Comment.QnA', $Args);

      if ($QnA && ($QnA == 'Accepted' || Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))) {
         $Title = T("QnA $QnA Answer", "$QnA Answer");
         echo ' <span class="Tag QnA-Box QnA-'.$QnA.'" title="'.htmlspecialchars($Title).'"><span>'.$Title.'</span></span> ';
      }
   }

   public function DiscussionController_CommentOptions_Handler($Sender, $Args) {
      $Comment = $Args['Comment'];
      if (!$Comment)
         return;
      $Discussion = Gdn::Controller()->Data('Discussion');

      if (GetValue('Type', $Discussion) != 'Question')
         return;

      if (!Gdn::Session()->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID))
         return;

      $Args['CommentOptions']['QnA'] = array('Label' => T('Q&A').'...', 'Url' => '/discussion/qnaoptions?commentid='.$Comment->CommentID, 'Class' => 'Popup');
   }

   public function Base_DiscussionOptions_Handler($Sender, $Args) {
      $Discussion = $Args['Discussion'];
      if (!Gdn::Session()->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID))
         return;

      if (isset($Args['DiscussionOptions'])) {
         $Args['DiscussionOptions']['QnA'] = array('Label' => T('Q&A').'...', 'Url' => '/discussion/qnaoptions?discussionid='.$Discussion->DiscussionID, 'Class' => 'Popup');
      } elseif (isset($Sender->Options)) {
         $Sender->Options .= '<li>'.Anchor(T('Q&A').'...', '/discussion/qnaoptions?discussionid='.$Discussion->DiscussionID, 'Popup QnAOptions') . '</li>';
      }
   }

   public function CommentModel_BeforeNotification_Handler($Sender, $Args) {
      $ActivityModel = $Args['ActivityModel'];
      $Comment = (array)$Args['Comment'];
      $CommentID = $Comment['CommentID'];
      $Discussion = (array)$Args['Discussion'];

      if ($Comment['InsertUserID'] == $Discussion['InsertUserID'])
         return;
      if (strtolower($Discussion['Type']) != 'question')
         return;
      if (!C('Plugins.QnA.Notifications', TRUE))
         return;

      $HeadlineFormat = T('HeadlingFormat.Answer', '{ActivityUserID,user} answered your question: <a href="{Url,html}">{Data.Name,text}</a>');

      $Activity = array(
         'ActivityType' => 'Comment',
         'ActivityUserID' => $Comment['InsertUserID'],
         'NotifyUserID' => $Discussion['InsertUserID'],
         'HeadlineFormat' => $HeadlineFormat,
         'RecordType' => 'Comment',
         'RecordID' => $CommentID,
         'Route' => "/discussion/comment/$CommentID#Comment_$CommentID",
         'Emailed' => ActivityModel::SENT_PENDING,
         'Notified' => ActivityModel::SENT_PENDING,
         'Data' => array(
            'Name' => GetValue('Name', $Discussion)
         )
      );

      $ActivityModel->Queue($Activity);
   }

   /**
    * @param CommentModel $Sender
    * @param array $Args
    */
   public function CommentModel_BeforeUpdateCommentCount_Handler($Sender, $Args) {
      $Discussion =& $Args['Discussion'];

      // Mark the question as answered.
      if (strtolower($Discussion['Type']) == 'question' && !$Discussion['Sink'] && !in_array($Discussion['QnA'], array('Answered', 'Accepted')) && $Discussion['InsertUserID'] != Gdn::Session()->UserID) {
         $Sender->SQL->Set('QnA', 'Answered');
      }
   }

   /**
    * Modify flow of discussion by pinning accepted answers.
    *
    * @param $Sender
    * @param $Args
    */
   public function DiscussionController_BeforeDiscussionRender_Handler($Sender, $Args) {
      if ($Sender->Data('Discussion.QnA'))
         $Sender->CssClass .= ' Question';

      if (strcasecmp($Sender->Data('Discussion.QnA'), 'Accepted') != 0)
         return;

      // Find the accepted answer(s) to the question.
      $CommentModel = new CommentModel();
      $Answers = $CommentModel->GetWhere(array('DiscussionID' => $Sender->Data('Discussion.DiscussionID'), 'Qna' => 'Accepted'))->Result();

      if (class_exists('ReplyModel')) {
         $ReplyModel = new ReplyModel();
         $Discussion = NULL;
         $ReplyModel->JoinReplies($Discussion, $Answers);
      }

      $Sender->SetData('Answers', $Answers);

      // Remove the accepted answers from the comments.
      // Allow this to be skipped via config.
      if (C('QnA.AcceptedAnswers.Filter', TRUE)) {
         if (isset($Sender->Data['Comments'])) {
            $Comments = $Sender->Data['Comments']->Result();
            $Comments = array_filter($Comments, function($Row) {
               return strcasecmp(GetValue('QnA', $Row), 'accepted');
            });
            $Sender->Data['Comments'] = new Gdn_DataSet(array_values($Comments));
         }
      }
   }

   /**
    * Write the accept/reject buttons.
    * @staticvar null $DiscussionModel
    * @staticvar boolean $InformMessage
    * @param type $Sender
    * @param type $Args
    * @return type
    */
   public function DiscussionController_AfterCommentBody_Handler($Sender, $Args) {
      $Discussion = $Sender->Data('Discussion');
      $Comment = GetValue('Comment', $Args);

      if (!$Comment)
         return;

      $CommentID = GetValue('CommentID', $Comment);
      if (!is_numeric($CommentID))
         return;

      if (!$Discussion) {
         static $DiscussionModel = NULL;
         if ($DiscussionModel === NULL)
            $DiscussionModel = new DiscussionModel();
         $Discussion = $DiscussionModel->GetID(GetValue('DiscussionID', $Comment));
      }

      if (!$Discussion || strtolower(GetValue('Type', $Discussion)) != 'question')
         return;

      // Check permissions.
      $CanAccept = Gdn::Session()->CheckPermission('Garden.Moderation.Manage');
      $CanAccept |= Gdn::Session()->UserID == GetValue('InsertUserID', $Discussion) && Gdn::Session()->UserID != GetValue('InsertUserID', $Comment);

      if (!$CanAccept)
         return;

      $QnA = GetValue('QnA', $Comment);
      if ($QnA)
         return;

      // Write the links.
//      $Types = GetValue('ReactionTypes', $Sender->EventArguments);
//      if ($Types)
//         echo Bullet();

      $Query = http_build_query(array('commentid' => $CommentID, 'tkey' => Gdn::Session()->TransientKey()));

      echo '<div class="ActionBlock QnA-Feedback">';

//      echo '<span class="FeedbackLabel">'.T('Feedback').'</span>';

      echo '<span class="DidThisAnswer">'.T('Did this answer the question?').'</span> ';

      echo '<span class="QnA-YesNo">';

      echo Anchor(T('Yes'), '/discussion/qna/accept?'.$Query, array('class' => 'React QnA-Yes', 'title' => T('Accept this answer.')));
      echo ' '.Bullet().' ';
      echo Anchor(T('No'), '/discussion/qna/reject?'.$Query, array('class' => 'React QnA-No', 'title' => T('Reject this answer.')));

      echo '</span>';

      echo '</div>';

//      static $InformMessage = TRUE;
//
//      if ($InformMessage && Gdn::Session()->UserID == GetValue('InsertUserID', $Discussion) && in_array(GetValue('QnA', $Discussion), array('', 'Answered'))) {
//         $Sender->InformMessage(T('Click accept or reject beside an answer.'), 'Dismissable');
//         $InformMessage = FALSE;
//      }
   }

   /**
    *
    * @param DiscussionController $Sender
    * @param type $Args
    * @return type
    */
   public function DiscussionController_AfterDiscussion_Handler($Sender, $Args) {
      if ($Sender->Data('Answers'))
         include $Sender->FetchViewLocation('Answers', '', 'plugins/QnA');
   }


   /**
    *
    * @param DiscussionController $Sender
    * @param array $Args
    */
   public function DiscussionController_QnA_Create($Sender, $Args = array()) {
      $Comment = Gdn::SQL()->GetWhere('Comment', array('CommentID' => $Sender->Request->Get('commentid')))->FirstRow(DATASET_TYPE_ARRAY);
      if (!$Comment)
         throw NotFoundException('Comment');

      $Discussion = Gdn::SQL()->GetWhere('Discussion', array('DiscussionID' => $Comment['DiscussionID']))->FirstRow(DATASET_TYPE_ARRAY);

      // Check for permission.
      if (!(Gdn::Session()->UserID == GetValue('InsertUserID', $Discussion) || Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))) {
         throw PermissionException('Garden.Moderation.Manage');
      }
      if (!Gdn::Session()->ValidateTransientKey($Sender->Request->Get('tkey')))
         throw PermissionException();

      switch ($Args[0]) {
         case 'accept':
            $QnA = 'Accepted';
            break;
         case 'reject':
            $QnA = 'Rejected';
            break;
      }

      if (isset($QnA)) {
         $DiscussionSet = array('QnA' => $QnA);
         $CommentSet = array('QnA' => $QnA);

         if ($QnA == 'Accepted') {
            $CommentSet['DateAccepted'] = Gdn_Format::ToDateTime();
            $CommentSet['AcceptedUserID'] = Gdn::Session()->UserID;

            if (!$Discussion['DateAccepted']) {
               $DiscussionSet['DateAccepted'] = Gdn_Format::ToDateTime();
               $DiscussionSet['DateOfAnswer'] = $Comment['DateInserted'];
            }
         }

         // Update the comment.
         Gdn::SQL()->Put('Comment', $CommentSet, array('CommentID' => $Comment['CommentID']));

         // Update the discussion.
         if ($Discussion['QnA'] != $QnA && (!$Discussion['QnA'] || in_array($Discussion['QnA'], array('Unanswered', 'Answered', 'Rejected'))))
            Gdn::SQL()->Put(
               'Discussion',
               $DiscussionSet,
               array('DiscussionID' => $Comment['DiscussionID']));

         // Determine QnA change
         if ($Comment['QnA'] != $QnA) {

            $Change = 0;
            switch ($QnA) {
               case 'Rejected':
                  $Change = -1;
                  if ($Comment['QnA'] != 'Accepted') $Change = 0;
                  break;

               case 'Accepted':
                  $Change = 1;
                  break;

               default:
                  if ($Comment['QnA'] == 'Rejected') $Change = 0;
                  if ($Comment['QnA'] == 'Accepted') $Change = -1;
                  break;
            }

         }

         // Apply change effects
         if ($Change) {
            // Update the user
            $UserID = GetValue('InsertUserID', $Comment);
            $this->RecalculateUserQnA($UserID);

            // Update reactions
            if ($this->Reactions) {
               include_once(Gdn::Controller()->FetchViewLocation('reaction_functions', '', 'plugins/Reactions'));
               $Rm = new ReactionModel();

               // If there's change, reactions will take care of it
               $Rm->React('Comment', $Comment['CommentID'], 'AcceptAnswer');
            }
         }

         // Record the activity.
         if ($QnA == 'Accepted') {
            $Activity = array(
               'ActivityType' => 'AnswerAccepted',
               'NotifyUserID' => $Comment['InsertUserID'],
               'HeadlineFormat' => '{ActivityUserID,You} accepted {NotifyUserID,your} answer.',
               'RecordType' => 'Comment',
               'RecordID' => $Comment['CommentID'],
               'Route' => CommentUrl($Comment, '/'),
               'Emailed' => ActivityModel::SENT_PENDING,
               'Notified' => ActivityModel::SENT_PENDING,
            );

            $ActivityModel = new ActivityModel();
            $ActivityModel->Save($Activity);
         }
      }
      Redirect("/discussion/comment/{$Comment['CommentID']}#Comment_{$Comment['CommentID']}");
   }

   public function DiscussionController_QnAOptions_Create($Sender, $DiscussionID = '', $CommentID = '') {
      if ($DiscussionID)
         $this->_DiscussionOptions($Sender, $DiscussionID);
      elseif ($CommentID)
         $this->_CommentOptions($Sender, $CommentID);

   }

   public function RecalculateDiscussionQnA($Discussion) {
      // Find comments in this discussion with a QnA value.
      $Set = array();

      $Row = Gdn::SQL()->GetWhere('Comment',
         array('DiscussionID' => GetValue('DiscussionID', $Discussion), 'QnA is not null' => ''), 'QnA, DateAccepted', 'asc', 1)->FirstRow(DATASET_TYPE_ARRAY);

      if (!$Row) {
         if (GetValue('CountComments', $Discussion) > 0)
            $Set['QnA'] = 'Unanswered';
         else
            $Set['QnA'] = 'Answered';

         $Set['DateAccepted'] = NULL;
         $Set['DateOfAnswer'] = NULL;
      } elseif ($Row['QnA'] == 'Accepted') {
         $Set['QnA'] = 'Accepted';
         $Set['DateAccepted'] = $Row['DateAccepted'];
         $Set['DateOfAnswer'] = $Row['DateInserted'];
      } elseif ($Row['QnA'] == 'Rejected') {
         $Set['QnA'] = 'Rejected';
         $Set['DateAccepted'] = NULL;
         $Set['DateOfAnswer'] = NULL;
      }

      Gdn::Controller()->DiscussionModel->SetField(GetValue('DiscussionID', $Discussion), $Set);
   }

   public function RecalculateUserQnA($UserID) {
      $CountAcceptedAnswers = Gdn::SQL()->GetCount('Comment', array('InsertUserID' => $UserID, 'QnA' => 'Accepted'));
      Gdn::UserModel()->SetField($UserID, 'CountAcceptedAnswers', $CountAcceptedAnswers);
   }

   public function _CommentOptions($Sender, $CommentID) {
      $Sender->Form = new Gdn_Form();

      $Comment = $Sender->CommentModel->GetID($CommentID, DATASET_TYPE_ARRAY);

      if (!$Comment)
         throw NotFoundException('Comment');

      $Discussion = $Sender->DiscussionModel->GetID(GetValue('DiscussionID', $Comment));

      $Sender->Permission('Vanilla.Discussions.Edit', TRUE, 'Category', GetValue('PermissionCategoryID', $Discussion));

      if ($Sender->Form->IsPostBack()) {
         $QnA = $Sender->Form->GetFormValue('QnA');
         if (!$QnA)
            $QnA = NULL;

         $CurrentQnA = GetValue('QnA', $Comment);

//         ->Column('DateAccepted', 'datetime', TRUE)
//         ->Column('AcceptedUserID', 'int', TRUE)

         if ($CurrentQnA != $QnA) {
            $Set = array('QnA' => $QnA);

            if ($QnA == 'Accepted') {
               $Set['DateAccepted'] = Gdn_Format::ToDateTime();
               $Set['AcceptedUserID'] = Gdn::Session()->UserID;
            } else {
               $Set['DateAccepted'] = NULL;
               $Set['AcceptedUserID'] = NULL;
            }

            $Sender->CommentModel->SetField($CommentID, $Set);
            $Sender->Form->SetValidationResults($Sender->CommentModel->ValidationResults());

            // Determine QnA change
            if ($Comment['QnA'] != $QnA) {

               $Change = 0;
               switch ($QnA) {
                  case 'Rejected':
                     $Change = -1;
                     if ($Comment['QnA'] != 'Accepted') $Change = 0;
                     break;

                  case 'Accepted':
                     $Change = 1;
                     break;

                  default:
                     if ($Comment['QnA'] == 'Rejected') $Change = 0;
                     if ($Comment['QnA'] == 'Accepted') $Change = -1;
                     break;
               }

            }

            // Apply change effects
            if ($Change) {

               // Update the user
               $UserID = GetValue('InsertUserID', $Comment);
               $this->RecalculateUserQnA($UserID);

               // Update reactions
               if ($this->Reactions) {
                  include_once(Gdn::Controller()->FetchViewLocation('reaction_functions', '', 'plugins/Reactions'));
                  $Rm = new ReactionModel();

                  // If there's change, reactions will take care of it
                  $Rm->React('Comment', $Comment['CommentID'], 'AcceptAnswer');
               }
            }

         }

         // Recalculate the Q&A status of the discussion.
         $this->RecalculateDiscussionQnA($Discussion);

         Gdn::Controller()->JsonTarget('', '', 'Refresh');
      } else {
         $Sender->Form->SetData($Comment);
      }

      $Sender->SetData('Comment', $Comment);
      $Sender->SetData('Discussion', $Discussion);
      $Sender->SetData('_QnAs', array('Accepted' => T('Yes'), 'Rejected' => T('No'), '' => T("Don't know")));
      $Sender->SetData('Title', T('Q&A Options'));
      $Sender->Render('CommentOptions', '', 'plugins/QnA');
   }

   protected function _DiscussionOptions($Sender, $DiscussionID) {
      $Sender->Form = new Gdn_Form();

      $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);

      if (!$Discussion)
         throw NotFoundException('Discussion');



      // Both '' and 'Discussion' denote a discussion type of discussion.
      if (!GetValue('Type', $Discussion))
         SetValue('Type', $Discussion, 'Discussion');

      if ($Sender->Form->IsPostBack()) {
         $Sender->DiscussionModel->SetField($DiscussionID, 'Type', $Sender->Form->GetFormValue('Type'));
//         $Form = new Gdn_Form();
         $Sender->Form->SetValidationResults($Sender->DiscussionModel->ValidationResults());

//         if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL || $Redirect)
//            $Sender->RedirectUrl = Gdn::Controller()->Request->PathAndQuery();
         Gdn::Controller()->JsonTarget('', '', 'Refresh');
      } else {
         $Sender->Form->SetData($Discussion);
      }

      $Sender->SetData('Discussion', $Discussion);
      $Sender->SetData('_Types', array('Question' => '@'.T('Question Type', 'Question'), 'Discussion' => '@'.T('Discussion Type', 'Discussion')));
      $Sender->SetData('Title', T('Q&A Options'));
      $Sender->Render('DiscussionOptions', '', 'plugins/QnA');
   }

   public function DiscussionModel_BeforeGet_Handler($Sender, $Args) {
      $Unanswered = Gdn::Controller()->ClassName == 'DiscussionsController' && Gdn::Controller()->RequestMethod == 'unanswered';

      if ($Unanswered) {
         $Args['Wheres']['Type'] = 'Question';
         $Sender->SQL->WhereIn('d.QnA', array('Unanswered', 'Rejected'));
         Gdn::Controller()->Title('Unanswered Questions');
      } elseif ($QnA = Gdn::Request()->Get('qna')) {
         $Args['Wheres']['QnA'] = $QnA;
      }
   }

   /**
    *
    * @param DiscussionModel $Sender
    * @param array $Args
    */
   public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender, $Args) {
//      $Sender->Validation->ApplyRule('Type', 'Required', T('Choose whether you want to ask a question or start a discussion.'));

      $Post =& $Args['FormPostValues'];
      if ($Args['Insert'] && GetValue('Type', $Post) == 'Question') {
         $Post['QnA'] = 'Unanswered';
      }
   }

   /* New Html method of adding to discussion filters */
   public function Base_AfterDiscussionFilters_Handler($Sender) {
      $Count = Gdn::Cache()->Get('QnA-UnansweredCount');
      if ($Count === Gdn_Cache::CACHEOP_FAILURE)
         $Count = ' <span class="Aside"><span class="Popin Count" rel="/discussions/unansweredcount"></span>';
      else
         $Count = ' <span class="Aside"><span class="Count">'.$Count.'</span></span>';

      echo '<li class="QnA-UnansweredQuestions '.($Sender->RequestMethod == 'unanswered' ? ' Active' : '').'">'
			.Anchor(Sprite('SpUnansweredQuestions').' '.T('Unanswered').$Count, '/discussions/unanswered', 'UnansweredQuestions')
		.'</li>';
   }

   /* Old Html method of adding to discussion filters */
   public function DiscussionsController_AfterDiscussionTabs_Handler($Sender, $Args) {
      if (StringEndsWith(Gdn::Request()->Path(), '/unanswered', TRUE))
         $CssClass = ' class="Active"';
      else
         $CssClass = '';

      $Count = Gdn::Cache()->Get('QnA-UnansweredCount');
      if ($Count === Gdn_Cache::CACHEOP_FAILURE)
         $Count = ' <span class="Popin Count" rel="/discussions/unansweredcount">';
      else
         $Count = ' <span class="Count">'.$Count.'</span>';

      echo '<li'.$CssClass.'><a class="TabLink QnA-UnansweredQuestions" href="'.Url('/discussions/unanswered').'">'.T('Unanswered Questions', 'Unanswered').$Count.'</span></a></li>';
   }

   /**
    * @param DiscussionsController $Sender
    * @param array $Args
    */
   public function DiscussionsController_Unanswered_Create($Sender, $Args = array()) {
      $Sender->View = 'Index';
      $Sender->SetData('_PagerUrl', 'discussions/unanswered/{Page}');
      $Sender->Index(GetValue(0, $Args, 'p1'));
      $this->InUnanswered = TRUE;
   }

   /**
    *
    * @param DiscussionsController $Sender
    * @param type $Args
    */
   public function DiscussionsController_Unanswered_Render($Sender, $Args) {
      $Sender->SetData('CountDiscussions', FALSE);

      // Add 'Ask a Question' button if using BigButtons.
      if (C('Plugins.QnA.UseBigButtons')) {
         $QuestionModule = new NewQuestionModule($Sender, 'plugins/QnA');
         $Sender->AddModule($QuestionModule);
      }

      // Remove announcements that aren't questions...
      $Sender->Data('Announcements')->Result();
      $Announcements = array();
      foreach ($Sender->Data('Announcements') as $i => $Row) {
         if (GetValue('Type', $Row) == 'Question')
            $Announcements[] = $Row;
      }
      Trace($Announcements);
      $Sender->SetData('Announcements', $Announcements);
      $Sender->AnnounceData = $Announcements;
   }

    /**
    * @param DiscussionsController $Sender
    * @param array $Args
    */
   public function DiscussionsController_UnansweredCount_Create($Sender, $Args = array()) {
      Gdn::SQL()->WhereIn('QnA', array('Unanswered', 'Rejected'));
      $Count = Gdn::SQL()->GetCount('Discussion', array('Type' => 'Question'));
      Gdn::Cache()->Store('QnA-UnansweredCount', $Count, array(Gdn_Cache::FEATURE_EXPIRY => 15 * 60));

      $Sender->SetData('UnansweredCount', $Count);
      $Sender->SetData('_Value', $Count);
      $Sender->Render('Value', 'Utility', 'Dashboard');
   }

   public function Base_BeforeDiscussionMeta_Handler($Sender, $Args) {
      $Discussion = $Args['Discussion'];

      if (strtolower(GetValue('Type', $Discussion)) != 'question')
         return;

      $QnA = GetValue('QnA', $Discussion);
      $Title = '';
      switch ($QnA) {
         case '':
         case 'Unanswered':
         case 'Rejected':
            $Text = 'Question';
            $QnA = 'Question';
            break;
         case 'Answered':
            $Text = 'Answered';
            if (GetValue('InsertUserID', $Discussion) == Gdn::Session()->UserID) {
               $QnA = 'Answered';
               $Title = ' title="'.T("Someone's answered your question. You need to accept/reject the answer.").'"';
            }
            break;
         case 'Accepted':
            $Text = 'Answered';
            $Title = ' title="'.T("This question's answer has been accepted.").'"';
            break;
         default:
            $QnA = FALSE;
      }
      if ($QnA) {
         echo ' <span class="Tag QnA-Tag-'.$QnA.'"'.$Title.'>'.T("Q&A $QnA", $Text).'</span> ';
      }
   }

   /**
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function NotificationsController_BeforeInformNotifications_Handler($Sender, $Args) {
      $Path = trim($Sender->Request->GetValue('Path'), '/');
      if (preg_match('`^(vanilla/)?discussion[^s]`i', $Path))
         return;

      // Check to see if the user has answered questions.
      $Count = Gdn::SQL()->GetCount('Discussion', array('Type' => 'Question', 'InsertUserID' => Gdn::Session()->UserID, 'QnA' => 'Answered'));
      if ($Count > 0) {
         $Sender->InformMessage(FormatString(T("You've asked questions that have now been answered", "<a href=\"{/discussions/mine?qna=Answered,url}\">You've asked questions that now have answers</a>. Make sure you accept/reject the answers.")), 'Dismissable');
      }
   }

   /**
    * Add 'Ask a Question' button if using BigButtons.
    */
   public function CategoriesController_Render_Before($Sender) {
      if (C('Plugins.QnA.UseBigButtons')) {
         $QuestionModule = new NewQuestionModule($Sender, 'plugins/QnA');
         $Sender->AddModule($QuestionModule);
      }
   }

   /**
    * Add 'Ask a Question' button if using BigButtons.
    */
   public function DiscussionController_Render_Before($Sender) {
      if (C('Plugins.QnA.UseBigButtons')) {
         $QuestionModule = new NewQuestionModule($Sender, 'plugins/QnA');
         $Sender->AddModule($QuestionModule);
      }

      if ($Sender->Data('Discussion.Type') == 'Question') {
         $Sender->SetData('_CommentsHeader', T('Answers'));
      }
   }


   /**
    * Add the "new question" option to the new discussion button group dropdown.
    */
//   public function Base_BeforeNewDiscussionButton_Handler($Sender) {
//      $NewDiscussionModule = &$Sender->EventArguments['NewDiscussionModule'];
//
//      $Category = Gdn::Controller()->Data('Category.UrlCode');
//      if ($Category)
//         $Category = '/'.rawurlencode($Category);
//      else
//         $Category = '';
//
//      $NewDiscussionModule->AddButton(T('Ask a Question'), 'post/question'.$Category);
//   }

   /**
    * Add the question form to vanilla's post page.
    */
   public function PostController_AfterForms_Handler($Sender) {
      $Forms = $Sender->Data('Forms');
      $Forms[] = array('Name' => 'Question', 'Label' => Sprite('SpQuestion').T('Ask a Question'), 'Url' => 'post/question');
		$Sender->SetData('Forms', $Forms);
   }

   /**
    * Create the new question method on post controller.
    */
   public function PostController_Question_Create($Sender, $CategoryUrlCode = '') {
      // Create & call PostController->Discussion()
      $Sender->View = PATH_PLUGINS.'/QnA/views/post.php';
      $Sender->SetData('Type', 'Question');
      $Sender->Discussion($CategoryUrlCode);
   }

   /**
    * Override the PostController->Discussion() method before render to use our view instead.
    */
   public function PostController_BeforeDiscussionRender_Handler($Sender) {
      // Override if we are looking at the question url.
      if ($Sender->RequestMethod == 'question') {
         $Sender->Form->AddHidden('Type', 'Question');
         $Sender->Title(T('Ask a Question'));
         $Sender->SetData('Breadcrumbs', array(array('Name' => $Sender->Data('Title'), 'Url' => '/post/question')));
      }
   }

   /**
    * Add 'New Question Form' location to Messages.
    */
   public function MessageController_AfterGetLocationData_Handler($Sender, $Args) {
      $Args['ControllerData']['Vanilla/Post/Question'] = T('New Question Form');
   }
}
