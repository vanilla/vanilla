<?php if (!defined('APPLICATION')) exit();

/**
 * Regarding entity
 * 
 * Handles relating external actions to comments and discussions. Flagging, Praising, Reporting, etc
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */
class Gdn_RegardingEntity extends Gdn_Pluggable {

   private $Type = NULL;
   private $ForeignType = NULL;
   private $ForeignID = NULL;
   private $SourceElement = NULL;

   private $ParentType = NULL;
   private $ParentID = NULL;
   private $ParentElement = NULL;
   
   private $UserID = NULL;
   private $ForeignURL = NULL;
   private $Comment = NULL;
   private $OriginalContent = NULL;

   private $CollaborativeActions = array();
   private $CollaborativeTitle = NULL;

   public function __construct($ForeignType, $ForeignID) {
      $this->ForeignType = strtolower($ForeignType);
      $this->ForeignID = $ForeignID;
      parent::__construct();
   }
   
   public function VerifiedAs($SourceElement = NULL) {
      if (is_null($SourceElement))
         return $this->SourceElement;
      else
         $this->SourceElement = $SourceElement;
      
      switch ($this->ForeignType) {
         case 'discussion':
            $OCField = "Body";
            break;
            
         case 'comment':
            $OCField = "Body";
            break;
         
         case 'conversation':
            $OCField = NULL;
            break;
            
         case 'conversationmessage':
            $OCField = "Body";
            break;
            
         default:
            $OCField = "Body";
            break;
      }
      
      if (!is_null($OCField) && !is_null($OCData = GetValue($OCField, $this->SourceElement, NULL)))
         $this->OriginalContent = $OCData;
      
      return $this;
   }
   
   public function AutoParent($ParentType, $ParentIDKey = NULL) {
      if (!is_null($this->SourceElement)) {
         if (is_null($ParentIDKey))
            $ParentIDKey = ucfirst($ParentType).'ID';
         $ParentID = GetValue($ParentIDKey, $this->SourceElement, FALSE);
         if ($ParentID !== FALSE)
            $this->WithParent($ParentType, $ParentID);
      }
      
      return $this;
   }

   public function WithParent($ParentType, $ParentID) {
      $ModelName = ucfirst($ParentType).'Model';

      if (!class_exists($ModelName))
         throw new Exception(sprintf(T("Could not find a model for %s objects (parent type for %s objects)."), ucfirst($ParentType), ucfirst($this->ForeignType)));

      // If we can lookup this object, it is verified
      $VerifyModel = new $ModelName;
      $ParentElement = $VerifyModel->GetID($ParentID);
      
      if ($ParentElement !== FALSE) {
         $this->ParentType = $ParentType;
         $this->ParentID = $ParentID;
         $this->ParentElement = $ParentElement;
      }
         
      return $this;
   }

   /* I'd like to... */

   public function ActionIt($ActionType) {
      $this->Type = strtolower($ActionType);
      return $this;
   }

   /* ... */

   public function ForDiscussion($InCategory) {
      return $this->ForCollaboration('discussion', $InCategory);
   }

   public function ForConversation($WithUsers) {
      return $this->ForCollaboration('conversation', $WithUsers);
   }

   public function ForCollaboration($CollaborationType, $CollaborationParameters = NULL) {
      if ($CollaborationType !== FALSE) {
         $this->CollaborativeActions[] = array(
            'Type'         => $CollaborationType,
            'Parameters'   => $CollaborationParameters
         );
      }
      return $this;
   }

   public function Entitled($CollaborativeTitle) {
      $this->CollaborativeTitle = $CollaborativeTitle;
      
      // Figure out how much space we have for the title
      $MaxLength = 90;
      $Stripped = FormatString($CollaborativeTitle,array(
         'RegardingTitle'     => ''
      ));
      $UsedLength = strlen($Stripped);
      $AvailableLength = $MaxLength - $UsedLength;
      
      // Check if the SourceElement contains a 'Name'
      $Name = GetValue('Name', $this->SourceElement, FALSE);
      
      // If not...
      if ($Name === FALSE) {
         // ...and we have a parent element...
         if (!is_null($this->ParentElement)) {
            // ...try to get a 'Name' from the parent
            $Name = GetValue('Name', $this->ParentElement, FALSE);
         }
      }
      
      // If all that failed, use the 'Body' of the source
      if ($Name === FALSE)
         $Name = GetValue('Body', $this->SourceElement, '');
      
      // Trim it if it is too long
      if (strlen($Name) > $AvailableLength)
         $Name = substr($Name, 0, $AvailableLength-3).'...';
      
      $CollaborativeTitle = FormatString($CollaborativeTitle,array(
         'RegardingTitle'     => $Name
      ));
      
      $this->CollaborativeTitle = $CollaborativeTitle;
      return $this;
   }

   /* Meta data */

   public function Located($URL) {
      // Try to auto generate URL from known information
      if ($URL === TRUE) {
         switch ($this->ForeignType) {
            case 'discussion':
               $URL = sprintf('discussion/%d', $this->ForeignID);
               break;
               
            case 'comment':
               $URL = sprintf('discussion/comment/%d', $this->ForeignID);
               break;
            
            case 'conversation':
               $URL = sprintf('messages/%d', $this->ForeignID);
               break;
               
            case 'conversationmessage':
               $URL = sprintf('messages/%d', $this->ParentID);
               break;
               
            default:
               $URL = "/";
               break;
         }
         $URL = Url($URL);
      }
      
      $this->ForeignURL = $URL;
      return $this;
   }

   public function From($UserID) {
      $this->UserID = $UserID;
      return $this;
   }

   public function Because($Reason) {
      $this->Comment = $Reason;
      return $this;
   }

   /* Finally... */

   public function Commit() {
      if (is_null($this->Type))
         throw new Exception(T("Adding a Regarding event requires a type."));

      if (is_null($this->ForeignType))
         throw new Exception(T("Adding a Regarding event requires a foreign association type."));

      if (is_null($this->ForeignID))
         throw new Exception(T("Adding a Regarding event requires a foreign association id."));

      if (is_null($this->Comment))
         throw new Exception(T("Adding a Regarding event requires a comment."));

      if (is_null($this->UserID))
         $this->UserID = Gdn::Session()->UserID;

      $RegardingModel = new RegardingModel();
      
      $CollapseMode = C('Garden.Regarding.AutoCollapse', TRUE);
      $Collapse = FALSE;
      if ($CollapseMode) {
         // Check for an existing report of this type
         $ExistingRegardingEntity = $RegardingModel->GetRelated($this->Type, $this->ForeignType, $this->ForeignID);
         if ($ExistingRegardingEntity) {
            $Collapse = TRUE;
            $RegardingID = GetValue('RegardingID', $ExistingRegardingEntity);
         }
      }
      
      if (!$Collapse) {
         // Create a new Regarding entry
         $RegardingPreSend = array(
            'Type'            => $this->Type,
            'ForeignType'     => $this->ForeignType,
            'ForeignID'       => $this->ForeignID,
            'InsertUserID'    => $this->UserID,
            'DateInserted'    => date('Y-m-d H:i:s'),

            'ParentType'      => $this->ParentType,
            'ParentID'        => $this->ParentID,
            'ForeignURL'      => $this->ForeignURL,
            'Comment'         => $this->Comment,
            'OriginalContent' => $this->OriginalContent,
            'Reports'         => 1
         );
         
         $RegardingID = $RegardingModel->Save($RegardingPreSend);
         
         if (!$RegardingID)
            return FALSE;
      }
      
      // Handle collaborations
      
      // Don't error on foreach
      if (!is_array($this->CollaborativeActions))
         $this->CollaborativeActions = array();
      
      foreach ($this->CollaborativeActions as $Action) {
         $ActionType = GetValue('Type', $Action);
         switch ($ActionType) {
            case 'discussion':
               $DiscussionModel = new DiscussionModel();
               if ($Collapse) {
                  $Discussion = Gdn::SQL()
                     ->Select('*')
                     ->From('Discussion')
                     ->Where(array('RegardingID' => $RegardingID))
                     ->Get()->FirstRow(DATASET_TYPE_ARRAY);
               }
               
               if (!$Collapse || !$Discussion) {
                  $CategoryID = GetValue('Parameters', $Action);
               
                  // Make a new discussion
                  $DiscussionID = $DiscussionModel->Save(array(
                     'Name'         => $this->CollaborativeTitle,
                     'CategoryID'   => $CategoryID,
                     'Body'         => $this->OriginalContent,
                     'InsertUserID' => GetValue('InsertUserID', $this->SourceElement),
                     'Announce'     => 0,
                     'Close'        => 0,
                     'RegardingID'  => $RegardingID
                  ));
                  
                  if (!$DiscussionID) {
                     throw new Gdn_UserException($DiscussionModel->Validation->ResultsText());
                  }
                  
                  $DiscussionModel->UpdateDiscussionCount($CategoryID);
               } else {
                  // Add a comment to the existing discussion.
                  $CommentModel = new CommentModel();
                  $CommentID = $CommentModel->Save(array(
                     'DiscussionID' => GetValue('DiscussionID', $Discussion),
                     'Body'         => $this->Comment,
                     'InsertUserID' => $this->UserID
                  ));

                  $CommentModel->Save2($CommentID, TRUE);
               }
               
               break;

            case 'conversation':
                  
               $ConversationModel = new ConversationModel();
               $ConversationMessageModel = new ConversationMessageModel();
               
               $Users = GetValue('Parameters', $Action);
               $UserList = explode(',', $Users);
               if (!sizeof($UserList))
                  throw new Exception(sprintf(T("The userlist provided for collaboration on '%s:%s' is invalid.", $this->Type, $this->ForeignType)));
               
               $ConversationID = $ConversationModel->Save(array(
                  'To'              => 'Admins',
                  'Body'            => $this->CollaborativeTitle,
                  'RecipientUserID' => $UserList,
                  'RegardingID'     => $RegardingID
               ), $ConversationMessageModel);
               
               break;
         }
      }

      return TRUE;
   }

   public function Setup(){}

}