<?php
/**
 * Regarding entity.
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
class Gdn_RegardingEntity extends Gdn_Pluggable {

    private $Type = null;

    private $ForeignType = null;

    private $ForeignID = null;

    private $SourceElement = null;

    private $ParentType = null;

    private $ParentID = null;

    private $ParentElement = null;

    private $UserID = null;

    private $ForeignURL = null;

    private $Comment = null;

    private $OriginalContent = null;

    private $CollaborativeActions = [];

    private $CollaborativeTitle = null;

    /**
     *
     *
     * @param $foreignType
     * @param $foreignID
     */
    public function __construct($foreignType, $foreignID) {
        $this->ForeignType = strtolower($foreignType);
        $this->ForeignID = $foreignID;
        parent::__construct();
    }

    /**
     *
     *
     * @param null $sourceElement
     * @return $this|null
     */
    public function verifiedAs($sourceElement = null) {
        if (is_null($sourceElement)) {
            return $this->SourceElement;
        } else {
            $this->SourceElement = $sourceElement;
        }

        switch ($this->ForeignType) {
            case 'discussion':
                $oCField = "Body";
                break;

            case 'comment':
                $oCField = "Body";
                break;

            case 'conversation':
                $oCField = null;
                break;

            case 'conversationmessage':
                $oCField = "Body";
                break;

            default:
                $oCField = "Body";
                break;
        }

        if (!is_null($oCField) && !is_null($oCData = val($oCField, $this->SourceElement, null))) {
            $this->OriginalContent = $oCData;
        }

        return $this;
    }

    /**
     *
     *
     * @param $parentType
     * @param null $parentIDKey
     * @return $this
     * @throws Exception
     */
    public function autoParent($parentType, $parentIDKey = null) {
        if (!is_null($this->SourceElement)) {
            if (is_null($parentIDKey)) {
                $parentIDKey = ucfirst($parentType).'ID';
            }
            $parentID = val($parentIDKey, $this->SourceElement, false);
            if ($parentID !== false) {
                $this->withParent($parentType, $parentID);
            }
        }

        return $this;
    }

    /**
     *
     *
     * @param $parentType
     * @param $parentID
     * @return $this
     * @throws Exception
     */
    public function withParent($parentType, $parentID) {
        $modelName = ucfirst($parentType).'Model';

        if (!class_exists($modelName)) {
            throw new Exception(sprintf(t("Could not find a model for %s objects (parent type for %s objects)."), ucfirst($parentType), ucfirst($this->ForeignType)));
        }

        // If we can lookup this object, it is verified
        $verifyModel = new $modelName;
        $parentElement = $verifyModel->getID($parentID);

        if ($parentElement !== false) {
            $this->ParentType = $parentType;
            $this->ParentID = $parentID;
            $this->ParentElement = $parentElement;
        }

        return $this;
    }

    /* I'd like to... */

    /**
     *
     *
     * @param $actionType
     * @return $this
     */
    public function actionIt($actionType) {
        $this->Type = strtolower($actionType);
        return $this;
    }

    /* ... */

    /**
     *
     *
     * @param $inCategory
     * @return Gdn_RegardingEntity
     */
    public function forDiscussion($inCategory) {
        return $this->forCollaboration('discussion', $inCategory);
    }

    /**
     *
     *
     * @param $withUsers
     * @return Gdn_RegardingEntity
     */
    public function forConversation($withUsers) {
        return $this->forCollaboration('conversation', $withUsers);
    }

    /**
     *
     *
     * @param $collaborationType
     * @param null $collaborationParameters
     * @return $this
     */
    public function forCollaboration($collaborationType, $collaborationParameters = null) {
        if ($collaborationType !== false) {
            $this->CollaborativeActions[] = [
                'Type' => $collaborationType,
                'Parameters' => $collaborationParameters
            ];
        }
        return $this;
    }

    /**
     *
     *
     * @param $collaborativeTitle
     * @return $this
     */
    public function entitled($collaborativeTitle) {
        $this->CollaborativeTitle = $collaborativeTitle;

        // Figure out how much space we have for the title
        $maxLength = 90;
        $stripped = formatString($collaborativeTitle, [
            'RegardingTitle' => ''
        ]);
        $usedLength = strlen($stripped);
        $availableLength = $maxLength - $usedLength;

        // Check if the SourceElement contains a 'Name'
        $name = val('Name', $this->SourceElement, false);

        // If not...
        if ($name === false) {
            // ...and we have a parent element...
            if (!is_null($this->ParentElement)) {
                // ...try to get a 'Name' from the parent
                $name = val('Name', $this->ParentElement, false);
            }
        }

        // If all that failed, use the 'Body' of the source
        if ($name === false) {
            $name = val('Body', $this->SourceElement, '');
        }

        // Trim it if it is too long
        if (strlen($name) > $availableLength) {
            $name = substr($name, 0, $availableLength - 3).'...';
        }

        $collaborativeTitle = formatString($collaborativeTitle, [
            'RegardingTitle' => $name
        ]);

        $this->CollaborativeTitle = $collaborativeTitle;
        return $this;
    }

    /* Meta data */

    /**
     *
     *
     * @param $uRL
     * @return $this
     */
    public function located($uRL) {
        // Try to auto generate URL from known information
        if ($uRL === true) {
            switch ($this->ForeignType) {
                case 'discussion':
                    $uRL = sprintf('discussion/%d', $this->ForeignID);
                    break;

                case 'comment':
                    $uRL = sprintf('discussion/comment/%d', $this->ForeignID);
                    break;

                case 'conversation':
                    $uRL = sprintf('messages/%d', $this->ForeignID);
                    break;

                case 'conversationmessage':
                    $uRL = sprintf('messages/%d', $this->ParentID);
                    break;

                default:
                    $uRL = "/";
                    break;
            }
            $uRL = url($uRL);
        }

        $this->ForeignURL = $uRL;
        return $this;
    }

    /**
     *
     *
     * @param $userID
     * @return $this
     */
    public function from($userID) {
        $this->UserID = $userID;
        return $this;
    }

    /**
     *
     *
     * @param $reason
     * @return $this
     */
    public function because($reason) {
        $this->Comment = $reason;
        return $this;
    }

    /* Finally... */

    /**
     *
     *
     * @return bool
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function commit() {
        if (is_null($this->Type)) {
            throw new Exception(t("Adding a Regarding event requires a type."));
        }

        if (is_null($this->ForeignType)) {
            throw new Exception(t("Adding a Regarding event requires a foreign association type."));
        }

        if (is_null($this->ForeignID)) {
            throw new Exception(t("Adding a Regarding event requires a foreign association id."));
        }

        if (is_null($this->Comment)) {
            throw new Exception(t("Adding a Regarding event requires a comment."));
        }

        if (is_null($this->UserID)) {
            $this->UserID = Gdn::session()->UserID;
        }

        $regardingModel = new RegardingModel();

        $collapseMode = c('Garden.Regarding.AutoCollapse', true);
        $collapse = false;
        if ($collapseMode) {
            // Check for an existing report of this type
            $existingRegardingEntity = $regardingModel->getRelated($this->Type, $this->ForeignType, $this->ForeignID);
            if ($existingRegardingEntity) {
                $collapse = true;
                $regardingID = val('RegardingID', $existingRegardingEntity);
            }
        }

        if (!$collapse) {
            // Create a new Regarding entry
            $regardingPreSend = [
                'Type' => $this->Type,
                'ForeignType' => $this->ForeignType,
                'ForeignID' => $this->ForeignID,
                'InsertUserID' => $this->UserID,
                'DateInserted' => date('Y-m-d H:i:s'),
                'ParentType' => $this->ParentType,
                'ParentID' => $this->ParentID,
                'ForeignURL' => $this->ForeignURL,
                'Comment' => $this->Comment,
                'OriginalContent' => $this->OriginalContent,
                'Reports' => 1
            ];

            $regardingID = $regardingModel->save($regardingPreSend);

            if (!$regardingID) {
                return false;
            }
        }

        // Handle collaborations

        // Don't error on foreach
        if (!is_array($this->CollaborativeActions)) {
            $this->CollaborativeActions = [];
        }

        foreach ($this->CollaborativeActions as $action) {
            $actionType = val('Type', $action);
            switch ($actionType) {
                case 'discussion':
                    $discussionModel = new DiscussionModel();
                    if ($collapse) {
                        $discussion = Gdn::sql()
                            ->select('*')
                            ->from('Discussion')
                            ->where(['RegardingID' => $regardingID])
                            ->get()->firstRow(DATASET_TYPE_ARRAY);
                    }

                    if (!$collapse || !$discussion) {
                        $categoryID = val('Parameters', $action);

                        // Make a new discussion
                        $discussionID = $discussionModel->save([
                            'Name' => $this->CollaborativeTitle,
                            'CategoryID' => $categoryID,
                            'Body' => $this->OriginalContent,
                            'InsertUserID' => val('InsertUserID', $this->SourceElement),
                            'Announce' => 0,
                            'Close' => 0,
                            'RegardingID' => $regardingID
                        ]);

                        if (!$discussionID) {
                            throw new Gdn_UserException($discussionModel->Validation->resultsText());
                        }

                        $discussionModel->updateDiscussionCount($categoryID);
                    } else {
                        // Add a comment to the existing discussion.
                        $commentModel = new CommentModel();
                        $commentID = $commentModel->save([
                            'DiscussionID' => val('DiscussionID', $discussion),
                            'Body' => $this->Comment,
                            'InsertUserID' => $this->UserID
                        ]);

                        $commentModel->save2($commentID, true);
                    }

                    break;

                case 'conversation':

                    $conversationModel = new ConversationModel();
                    $conversationMessageModel = new ConversationMessageModel();

                    $users = val('Parameters', $action);
                    $userList = explode(',', $users);
                    if (!sizeof($userList)) {
                        throw new Exception(sprintf(t("The userlist provided for collaboration on '%s:%s' is invalid.", $this->Type, $this->ForeignType)));
                    }

                    $conversationID = $conversationModel->save([
                        'To' => 'Admins',
                        'Body' => $this->CollaborativeTitle,
                        'RecipientUserID' => $userList,
                        'RegardingID' => $regardingID
                    ], $conversationMessageModel);

                    break;
            }
        }

        return true;
    }

    /**
     * No setup.
     */
    public function setup() {
    }
}
