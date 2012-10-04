<?php if (!defined('APPLICATION')) exit();

/**
 * Regarding system
 * 
 * Handles relating external actions to comments and discussions. Flagging, Praising, Reporting, etc
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_Regarding extends Gdn_Pluggable implements Gdn_IPlugin {

   public function __construct() {
      parent::__construct();
   }

   /* With regard to... */

   /**
    * Start a RegardingEntity for a comment
    *
    * Able to autoparent to its discussion owner if verfied.
    *
    * @param $CommentID int ID of the comment
    * @param $Verify optional boolean whether or not to verify this. default true.
    * @param $AutoParent optional boolean whether or not to try to autoparent. default true.
    * @return Gdn_RegardingEntity
    */
   public function Comment($CommentID, $Verify = TRUE, $AutoParent = TRUE) {
      $Regarding = $this->Regarding('Comment', $CommentID, $Verify);
      if ($Verify && $AutoParent) $Regarding->AutoParent('discussion');
      return $Regarding;
   }

   /**
    * Start a RegardingEntity for a discussion
    *
    * @param $DiscussionID int ID of the discussion
    * @param $Verify optional boolean whether or not to verify this. default true.
    * @return Gdn_RegardingEntity
    */
   public function Discussion($DiscussionID, $Verify = TRUE) {
      return $this->Regarding('Discussion', $DiscussionID, $Verify);
   }

   /**
    * Start a RegardingEntity for a conversation message
    *
    * Able to autoparent to its conversation owner if verfied.
    *
    * @param $MessageID int ID of the conversation message
    * @param $Verify optional boolean whether or not to verify this. default true.
    * @param $AutoParent optional boolean whether or not to try to autoparent. default true.
    * @return Gdn_RegardingEntity
    */
   public function Message($MessageID, $Verify = TRUE, $AutoParent = TRUE) {
      $Regarding = $this->Regarding('ConversationMessage', $MessageID, $Verify);
      if ($Verify && $AutoParent) $Regarding->AutoParent('conversation');
      return $Regarding;
   }

   /**
    * Start a RegardingEntity for a conversation
    *
    * @param $ConversationID int ID of the conversation
    * @param $Verify optional boolean whether or not to verify this. default true.
    * @return Gdn_RegardingEntity
    */
   public function Conversation($ConversationID, $Verify = TRUE) {
      return $this->Regarding('Conversation', $ConversationID, $Verify);
   }

   protected function Regarding($ThingType, $ThingID, $Verify = TRUE) {
      $Verified = FALSE;
      if ($Verify) {
         $ModelName = ucfirst($ThingType).'Model';

         if (!class_exists($ModelName))
            throw new Exception(sprintf(T("Could not find a model for %s objects."), ucfirst($ThingType)));

         // If we can lookup this object, it is verified
         $VerifyModel = new $ModelName;
         $SourceElement = $VerifyModel->GetID($ThingID);
         if ($SourceElement !== FALSE)
            $Verified = TRUE;

      } else {
         $Verified = NULL;
      }

      if ($Verified !== FALSE) {
         $Regarding = new Gdn_RegardingEntity($ThingType, $ThingID);
         if ($Verify)
            $Regarding->VerifiedAs($SourceElement);

         return $Regarding;
      }

      throw new Exception(sprintf(T("Could not verify entity relationship '%s(%d)' for Regarding call"), $ModelName, $ThingID));
   }

   // Transparent forwarder to built-in starter methods
   public function That() {
      $Args = func_get_args();
      $ThingType = array_shift($Args);

      return call_user_func_array(array($this, $ThingType), $Args);
   }

   /*
    * Event system: Provide information for external hooks
    */
   
   public function MatchEvent($RegardingType, $ForeignType, $ForeignID = NULL) {
      $RegardingData = GetValue('RegardingData', $this->EventArguments);
      
      $FoundRegardingType = strtolower(GetValue('Type', $RegardingData));
      if (!is_array($RegardingType))
         $RegardingType = array($RegardingType);
      $Found = FALSE;
      foreach ($RegardingType as $RegardingTypeInstance)
         if (fnmatch($RegardingTypeInstance, $FoundRegardingType))
            $Found = TRUE;
      if (!$Found) return FALSE;
      
      $FoundForeignType = strtolower(GetValue('ForeignType', $RegardingData));
      if (!is_array($ForeignType))
         $ForeignType = array($ForeignType);
      $Found = FALSE;
      foreach ($ForeignType as $ForeignTypeInstance)
         if (fnmatch($ForeignTypeInstance, $FoundForeignType))
            $Found = TRUE;
      if (!$Found) return FALSE;
      
      if (!is_null($ForeignID)) {
         $FoundForeignID = GetValue('ForeignID', $RegardingData);
         if ($FoundForeignID != $ForeignID)
            return FALSE;
      }
      
      return $this->EventArguments;
   }

   /*
    * Event system: Hook into core events
    */

   // Cache regarding data for displayed comments
//   public function DiscussionController_BeforeDiscussionRender_Handler($Sender) {
//      if (GetValue('RegardingCache', $Sender, NULL) != NULL) return;
//
//      $Comments = $Sender->Data('Comments');
//      $CommentIDList = array();
//      if ($Comments && $Comments instanceof Gdn_DataSet) {
//         $Comments->DataSeek(-1);
//         while ($Comment = $Comments->NextRow()) {
//            if (!isset($Comment->CommentID) || !is_numeric($Comment->CommentID))
//               continue;
//            $CommentIDList[] = $Comment->CommentID;
//         }
//      }
//      $this->CacheRegarding($Sender, 'discussion', $Sender->Discussion->DiscussionID, 'comment', $CommentIDList);
//   }

   protected function CacheRegarding($Sender, $ParentType, $ParentID, $ForeignType, $ForeignIDs) {

      $Sender->RegardingCache = array();

      $ChildRegardingData = $this->RegardingModel()->GetAll($ForeignType, $ForeignIDs);
      $ParentRegardingData = $this->RegardingModel()->Get($ParentType, $ParentID);

/*
      $MediaArray = array();
      if ($MediaData !== FALSE) {
         $MediaData->DataSeek(-1);
         while ($Media = $MediaData->NextRow()) {
            $MediaArray[$Media->ForeignTable.'/'.$Media->ForeignID][] = $Media;
            $this->MediaCacheById[GetValue('MediaID',$Media)] = $Media;
         }
      }
*/

      $this->RegardingCache = array();
   }

   public function DiscussionController_BeforeCommentBody_Handler($Sender) {
      $Context = strtolower($Sender->EventArguments['Type']);

      $RegardingID = GetValue('RegardingID', $Sender->EventArguments['Object'], NULL);
      if (is_null($RegardingID) || $RegardingID < 0) return;

      try {
         $RegardingData = $this->RegardingModel()->GetID($RegardingID);
         $EntityModelName = ucfirst(GetValue('ForeignType',$RegardingData)).'Model';
         if (class_exists($EntityModelName)) {
            $EntityModel = new $EntityModelName();
            $Entity = $EntityModel->GetID(GetValue('ForeignID',$RegardingData));
            $this->EventArguments = array_merge($this->EventArguments,array(
               'EventSender'     => $Sender,
               'Entity'          => $Entity,
               'RegardingData'   => $RegardingData,
               'Options'         => NULL
            ));
            $this->FireEvent('RegardingDisplay');
         }
      } catch (Exception $e) {}
   }

   public function RegardingModel() {
      static $RegardingModel = NULL;
      if (is_null($RegardingModel))
         $RegardingModel = new RegardingModel();
      return $RegardingModel;
   }

   public function Setup(){}

}
