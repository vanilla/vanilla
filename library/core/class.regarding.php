<?php
/**
 * Regarding system.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Handles relating external actions to comments and discussions. Flagging, Praising, Reporting, etc.
 */
class Gdn_Regarding extends Gdn_Pluggable implements Gdn_IPlugin {

    /**
     *
     */
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
    public function comment($CommentID, $Verify = true, $AutoParent = true) {
        $Regarding = $this->regarding('Comment', $CommentID, $Verify);
        if ($Verify && $AutoParent) {
            $Regarding->autoParent('discussion');
        }
        return $Regarding;
    }

    /**
     * Start a RegardingEntity for a discussion
     *
     * @param $DiscussionID int ID of the discussion
     * @param $Verify optional boolean whether or not to verify this. default true.
     * @return Gdn_RegardingEntity
     */
    public function discussion($DiscussionID, $Verify = true) {
        return $this->regarding('Discussion', $DiscussionID, $Verify);
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
    public function message($MessageID, $Verify = true, $AutoParent = true) {
        $Regarding = $this->regarding('ConversationMessage', $MessageID, $Verify);
        if ($Verify && $AutoParent) {
            $Regarding->autoParent('conversation');
        }
        return $Regarding;
    }

    /**
     * Start a RegardingEntity for a conversation
     *
     * @param $ConversationID int ID of the conversation
     * @param $Verify optional boolean whether or not to verify this. default true.
     * @return Gdn_RegardingEntity
     */
    public function conversation($ConversationID, $Verify = true) {
        return $this->regarding('Conversation', $ConversationID, $Verify);
    }

    /**
     *
     *
     * @param $ThingType
     * @param $ThingID
     * @param bool $Verify
     * @return Gdn_RegardingEntity
     * @throws Exception
     */
    protected function regarding($ThingType, $ThingID, $Verify = true) {
        $Verified = false;
        if ($Verify) {
            $ModelName = ucfirst($ThingType).'Model';

            if (!class_exists($ModelName)) {
                throw new Exception(sprintf(T("Could not find a model for %s objects."), ucfirst($ThingType)));
            }

            // If we can lookup this object, it is verified
            $VerifyModel = new $ModelName;
            $SourceElement = $VerifyModel->getID($ThingID);
            if ($SourceElement !== false) {
                $Verified = true;
            }

        } else {
            $Verified = null;
        }

        if ($Verified !== false) {
            $Regarding = new Gdn_RegardingEntity($ThingType, $ThingID);
            if ($Verify) {
                $Regarding->verifiedAs($SourceElement);
            }

            return $Regarding;
        }

        throw new Exception(sprintf(T("Could not verify entity relationship '%s(%d)' for Regarding call"), $ModelName, $ThingID));
    }

    /**
     * Transparent forwarder to built-in starter methods.
     *
     * @return mixed
     */
    public function that() {
        $Args = func_get_args();
        $ThingType = array_shift($Args);

        return call_user_func_array(array($this, $ThingType), $Args);
    }

    /**
     *  Event system: Provide information for external hooks.
     *
     * @param $RegardingType
     * @param $ForeignType
     * @param null $ForeignID
     * @return array|bool
     */
    public function matchEvent($RegardingType, $ForeignType, $ForeignID = null) {
        $RegardingData = val('RegardingData', $this->EventArguments);

        $FoundRegardingType = strtolower(GetValue('Type', $RegardingData));
        if (!is_array($RegardingType)) {
            $RegardingType = array($RegardingType);
        }
        $Found = false;
        foreach ($RegardingType as $RegardingTypeInstance) {
            if (fnmatch($RegardingTypeInstance, $FoundRegardingType)) {
                $Found = true;
            }
        }
        if (!$Found) {
            return false;
        }

        $FoundForeignType = strtolower(val('ForeignType', $RegardingData));
        if (!is_array($ForeignType)) {
            $ForeignType = array($ForeignType);
        }
        $Found = false;
        foreach ($ForeignType as $ForeignTypeInstance) {
            if (fnmatch($ForeignTypeInstance, $FoundForeignType)) {
                $Found = true;
            }
        }
        if (!$Found) {
            return false;
        }

        if (!is_null($ForeignID)) {
            $FoundForeignID = val('ForeignID', $RegardingData);
            if ($FoundForeignID != $ForeignID) {
                return false;
            }
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

    /**
     *
     *
     * @param $Sender
     * @param $ParentType
     * @param $ParentID
     * @param $ForeignType
     * @param $ForeignIDs
     */
    protected function cacheRegarding($Sender, $ParentType, $ParentID, $ForeignType, $ForeignIDs) {
        $Sender->RegardingCache = array();
        $ChildRegardingData = $this->regardingModel()->getAll($ForeignType, $ForeignIDs);
        $ParentRegardingData = $this->regardingModel()->get($ParentType, $ParentID);

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

    /**
     *
     *
     * @param $Sender
     */
    public function discussionController_beforeCommentBody_handler($Sender) {
        $Context = strtolower($Sender->EventArguments['Type']);

        $RegardingID = val('RegardingID', $Sender->EventArguments['Object'], null);
        if (is_null($RegardingID) || $RegardingID < 0) {
            return;
        }

        try {
            $RegardingData = $this->regardingModel()->getID($RegardingID);
            $EntityModelName = ucfirst(val('ForeignType', $RegardingData)).'Model';
            if (class_exists($EntityModelName)) {
                $EntityModel = new $EntityModelName();
                $Entity = $EntityModel->getID(val('ForeignID', $RegardingData));
                $this->EventArguments = array_merge($this->EventArguments, array(
                    'EventSender' => $Sender,
                    'Entity' => $Entity,
                    'RegardingData' => $RegardingData,
                    'Options' => null
                ));
                $this->fireEvent('RegardingDisplay');
            }
        } catch (Exception $e) {
        }
    }

    /**
     *
     *
     * @return RegardingModel
     */
    public function regardingModel() {
        static $RegardingModel = null;
        if (is_null($RegardingModel)) {
            $RegardingModel = new RegardingModel();
        }
        return $RegardingModel;
    }

    /**
     * Do nothing.
     */
    public function setup() {
    }
}
