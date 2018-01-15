<?php
/**
 * Regarding system.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Handles relating external actions to comments and discussions. Flagging, Praising, Reporting, etc.
 */
class Gdn_Regarding extends Gdn_Pluggable {

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
     * @param $commentID int ID of the comment
     * @param $verify optional boolean whether or not to verify this. default true.
     * @param $autoParent optional boolean whether or not to try to autoparent. default true.
     * @return Gdn_RegardingEntity
     */
    public function comment($commentID, $verify = true, $autoParent = true) {
        $regarding = $this->regarding('Comment', $commentID, $verify);
        if ($verify && $autoParent) {
            $regarding->autoParent('discussion');
        }
        return $regarding;
    }

    /**
     * Start a RegardingEntity for a discussion
     *
     * @param $discussionID int ID of the discussion
     * @param $verify optional boolean whether or not to verify this. default true.
     * @return Gdn_RegardingEntity
     */
    public function discussion($discussionID, $verify = true) {
        return $this->regarding('Discussion', $discussionID, $verify);
    }

    /**
     * Start a RegardingEntity for a conversation message
     *
     * Able to autoparent to its conversation owner if verfied.
     *
     * @param $messageID int ID of the conversation message
     * @param $verify optional boolean whether or not to verify this. default true.
     * @param $autoParent optional boolean whether or not to try to autoparent. default true.
     * @return Gdn_RegardingEntity
     */
    public function message($messageID, $verify = true, $autoParent = true) {
        $regarding = $this->regarding('ConversationMessage', $messageID, $verify);
        if ($verify && $autoParent) {
            $regarding->autoParent('conversation');
        }
        return $regarding;
    }

    /**
     * Start a RegardingEntity for a conversation
     *
     * @param $conversationID int ID of the conversation
     * @param $verify optional boolean whether or not to verify this. default true.
     * @return Gdn_RegardingEntity
     */
    public function conversation($conversationID, $verify = true) {
        return $this->regarding('Conversation', $conversationID, $verify);
    }

    /**
     *
     *
     * @param $thingType
     * @param $thingID
     * @param bool $verify
     * @return Gdn_RegardingEntity
     * @throws Exception
     */
    protected function regarding($thingType, $thingID, $verify = true) {
        $verified = false;
        if ($verify) {
            $modelName = ucfirst($thingType).'Model';

            if (!class_exists($modelName)) {
                throw new Exception(sprintf(t("Could not find a model for %s objects."), ucfirst($thingType)));
            }

            // If we can lookup this object, it is verified
            $verifyModel = new $modelName;
            $sourceElement = $verifyModel->getID($thingID);
            if ($sourceElement !== false) {
                $verified = true;
            }

        } else {
            $verified = null;
        }

        if ($verified !== false) {
            $regarding = new Gdn_RegardingEntity($thingType, $thingID);
            if ($verify) {
                $regarding->verifiedAs($sourceElement);
            }

            return $regarding;
        }

        throw new Exception(sprintf(t("Could not verify entity relationship '%s(%d)' for Regarding call"), $modelName, $thingID));
    }

    /**
     * Transparent forwarder to built-in starter methods.
     *
     * @return mixed
     */
    public function that() {
        $args = func_get_args();
        $thingType = array_shift($args);

        return call_user_func_array([$this, $thingType], $args);
    }

    /**
     *  Event system: Provide information for external hooks.
     *
     * @param $regardingType
     * @param $foreignType
     * @param null $foreignID
     * @return array|bool
     */
    public function matchEvent($regardingType, $foreignType, $foreignID = null) {
        $regardingData = val('RegardingData', $this->EventArguments);

        $foundRegardingType = strtolower(getValue('Type', $regardingData));
        if (!is_array($regardingType)) {
            $regardingType = [$regardingType];
        }
        $found = false;
        foreach ($regardingType as $regardingTypeInstance) {
            if (fnmatch($regardingTypeInstance, $foundRegardingType)) {
                $found = true;
            }
        }
        if (!$found) {
            return false;
        }

        $foundForeignType = strtolower(val('ForeignType', $regardingData));
        if (!is_array($foreignType)) {
            $foreignType = [$foreignType];
        }
        $found = false;
        foreach ($foreignType as $foreignTypeInstance) {
            if (fnmatch($foreignTypeInstance, $foundForeignType)) {
                $found = true;
            }
        }
        if (!$found) {
            return false;
        }

        if (!is_null($foreignID)) {
            $foundForeignID = val('ForeignID', $regardingData);
            if ($foundForeignID != $foreignID) {
                return false;
            }
        }

        return $this->EventArguments;
    }

    /*
     * Event system: Hook into core events
     */

    // Cache regarding data for displayed comments
//   public function discussionController_BeforeDiscussionRender_Handler($Sender) {
//      if (getValue('RegardingCache', $Sender, NULL) != NULL) return;
//
//      $Comments = $Sender->data('Comments');
//      $CommentIDList = array();
//      if ($Comments && $Comments instanceof Gdn_DataSet) {
//         $Comments->dataSeek(-1);
//         while ($Comment = $Comments->nextRow()) {
//            if (!isset($Comment->CommentID) || !is_numeric($Comment->CommentID))
//               continue;
//            $CommentIDList[] = $Comment->CommentID;
//         }
//      }
//      $this->cacheRegarding($Sender, 'discussion', $Sender->Discussion->DiscussionID, 'comment', $CommentIDList);
//   }

    /**
     *
     *
     * @param $sender
     * @param $parentType
     * @param $parentID
     * @param $foreignType
     * @param $foreignIDs
     */
    protected function cacheRegarding($sender, $parentType, $parentID, $foreignType, $foreignIDs) {
        $sender->RegardingCache = [];
        $childRegardingData = $this->regardingModel()->getAll($foreignType, $foreignIDs);
        $parentRegardingData = $this->regardingModel()->get($parentType, $parentID);

        /*
              $MediaArray = array();
              if ($MediaData !== FALSE) {
                 $MediaData->dataSeek(-1);
                 while ($Media = $MediaData->nextRow()) {
                    $MediaArray[$Media->ForeignTable.'/'.$Media->ForeignID][] = $Media;
                    $this->MediaCacheById[GetValue('MediaID',$Media)] = $Media;
                 }
              }
        */

        $this->RegardingCache = [];
    }

    /**
     * @param DiscussionController $sender
     */
    public function beforeCommentBody($sender) {
        $context = strtolower($sender->EventArguments['Type']);

        $regardingID = val('RegardingID', $sender->EventArguments['Object'], null);
        if (is_null($regardingID) || $regardingID < 0) {
            return;
        }

        try {
            $regardingData = $this->regardingModel()->getID($regardingID);
            $entityModelName = ucfirst(val('ForeignType', $regardingData)).'Model';
            if (class_exists($entityModelName)) {
                $entityModel = new $entityModelName();
                $entity = $entityModel->getID(val('ForeignID', $regardingData));
                $this->EventArguments = array_merge($this->EventArguments, [
                    'EventSender' => $sender,
                    'Entity' => $entity,
                    'RegardingData' => $regardingData,
                    'Options' => null
                ]);
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
        static $regardingModel = null;
        if (is_null($regardingModel)) {
            $regardingModel = new RegardingModel();
        }
        return $regardingModel;
    }

    /**
     * Do nothing.
     */
    public function setup() {
    }
}
