<?php
/**
 * Regarding entity.
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

    private $CollaborativeActions = array();

    private $CollaborativeTitle = null;

    /**
     *
     *
     * @param $ForeignType
     * @param $ForeignID
     */
    public function __construct($ForeignType, $ForeignID) {
        $this->ForeignType = strtolower($ForeignType);
        $this->ForeignID = $ForeignID;
        parent::__construct();
    }

    /**
     *
     *
     * @param null $SourceElement
     * @return $this|null
     */
    public function verifiedAs($SourceElement = null) {
        if (is_null($SourceElement)) {
            return $this->SourceElement;
        } else {
            $this->SourceElement = $SourceElement;
        }

        switch ($this->ForeignType) {
            case 'discussion':
                $OCField = "Body";
                break;

            case 'comment':
                $OCField = "Body";
                break;

            case 'conversation':
                $OCField = null;
                break;

            case 'conversationmessage':
                $OCField = "Body";
                break;

            default:
                $OCField = "Body";
                break;
        }

        if (!is_null($OCField) && !is_null($OCData = val($OCField, $this->SourceElement, null))) {
            $this->OriginalContent = $OCData;
        }

        return $this;
    }

    /**
     *
     *
     * @param $ParentType
     * @param null $ParentIDKey
     * @return $this
     * @throws Exception
     */
    public function autoParent($ParentType, $ParentIDKey = null) {
        if (!is_null($this->SourceElement)) {
            if (is_null($ParentIDKey)) {
                $ParentIDKey = ucfirst($ParentType).'ID';
            }
            $ParentID = val($ParentIDKey, $this->SourceElement, false);
            if ($ParentID !== false) {
                $this->withParent($ParentType, $ParentID);
            }
        }

        return $this;
    }

    /**
     *
     *
     * @param $ParentType
     * @param $ParentID
     * @return $this
     * @throws Exception
     */
    public function withParent($ParentType, $ParentID) {
        $ModelName = ucfirst($ParentType).'Model';

        if (!class_exists($ModelName)) {
            throw new Exception(sprintf(T("Could not find a model for %s objects (parent type for %s objects)."), ucfirst($ParentType), ucfirst($this->ForeignType)));
        }

        // If we can lookup this object, it is verified
        $VerifyModel = new $ModelName;
        $ParentElement = $VerifyModel->getID($ParentID);

        if ($ParentElement !== false) {
            $this->ParentType = $ParentType;
            $this->ParentID = $ParentID;
            $this->ParentElement = $ParentElement;
        }

        return $this;
    }

    /* I'd like to... */

    /**
     *
     *
     * @param $ActionType
     * @return $this
     */
    public function actionIt($ActionType) {
        $this->Type = strtolower($ActionType);
        return $this;
    }

    /* ... */

    /**
     *
     *
     * @param $InCategory
     * @return Gdn_RegardingEntity
     */
    public function forDiscussion($InCategory) {
        return $this->forCollaboration('discussion', $InCategory);
    }

    /**
     *
     *
     * @param $WithUsers
     * @return Gdn_RegardingEntity
     */
    public function forConversation($WithUsers) {
        return $this->forCollaboration('conversation', $WithUsers);
    }

    /**
     *
     *
     * @param $CollaborationType
     * @param null $CollaborationParameters
     * @return $this
     */
    public function forCollaboration($CollaborationType, $CollaborationParameters = null) {
        if ($CollaborationType !== false) {
            $this->CollaborativeActions[] = array(
                'Type' => $CollaborationType,
                'Parameters' => $CollaborationParameters
            );
        }
        return $this;
    }

    /**
     *
     *
     * @param $CollaborativeTitle
     * @return $this
     */
    public function entitled($CollaborativeTitle) {
        $this->CollaborativeTitle = $CollaborativeTitle;

        // Figure out how much space we have for the title
        $MaxLength = 90;
        $Stripped = formatString($CollaborativeTitle, array(
            'RegardingTitle' => ''
        ));
        $UsedLength = strlen($Stripped);
        $AvailableLength = $MaxLength - $UsedLength;

        // Check if the SourceElement contains a 'Name'
        $Name = val('Name', $this->SourceElement, false);

        // If not...
        if ($Name === false) {
            // ...and we have a parent element...
            if (!is_null($this->ParentElement)) {
                // ...try to get a 'Name' from the parent
                $Name = val('Name', $this->ParentElement, false);
            }
        }

        // If all that failed, use the 'Body' of the source
        if ($Name === false) {
            $Name = val('Body', $this->SourceElement, '');
        }

        // Trim it if it is too long
        if (strlen($Name) > $AvailableLength) {
            $Name = substr($Name, 0, $AvailableLength - 3).'...';
        }

        $CollaborativeTitle = formatString($CollaborativeTitle, array(
            'RegardingTitle' => $Name
        ));

        $this->CollaborativeTitle = $CollaborativeTitle;
        return $this;
    }

    /* Meta data */

    /**
     *
     *
     * @param $URL
     * @return $this
     */
    public function located($URL) {
        // Try to auto generate URL from known information
        if ($URL === true) {
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

    /**
     *
     *
     * @param $UserID
     * @return $this
     */
    public function from($UserID) {
        $this->UserID = $UserID;
        return $this;
    }

    /**
     *
     *
     * @param $Reason
     * @return $this
     */
    public function because($Reason) {
        $this->Comment = $Reason;
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
            throw new Exception(T("Adding a Regarding event requires a type."));
        }

        if (is_null($this->ForeignType)) {
            throw new Exception(T("Adding a Regarding event requires a foreign association type."));
        }

        if (is_null($this->ForeignID)) {
            throw new Exception(T("Adding a Regarding event requires a foreign association id."));
        }

        if (is_null($this->Comment)) {
            throw new Exception(T("Adding a Regarding event requires a comment."));
        }

        if (is_null($this->UserID)) {
            $this->UserID = Gdn::session()->UserID;
        }

        $RegardingModel = new RegardingModel();

        $CollapseMode = C('Garden.Regarding.AutoCollapse', true);
        $Collapse = false;
        if ($CollapseMode) {
            // Check for an existing report of this type
            $ExistingRegardingEntity = $RegardingModel->getRelated($this->Type, $this->ForeignType, $this->ForeignID);
            if ($ExistingRegardingEntity) {
                $Collapse = true;
                $RegardingID = val('RegardingID', $ExistingRegardingEntity);
            }
        }

        if (!$Collapse) {
            // Create a new Regarding entry
            $RegardingPreSend = array(
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
            );

            $RegardingID = $RegardingModel->save($RegardingPreSend);

            if (!$RegardingID) {
                return false;
            }
        }

        // Handle collaborations

        // Don't error on foreach
        if (!is_array($this->CollaborativeActions)) {
            $this->CollaborativeActions = array();
        }

        foreach ($this->CollaborativeActions as $Action) {
            $ActionType = val('Type', $Action);
            switch ($ActionType) {
                case 'discussion':
                    $DiscussionModel = new DiscussionModel();
                    if ($Collapse) {
                        $Discussion = Gdn::SQL()
                            ->select('*')
                            ->from('Discussion')
                            ->where(array('RegardingID' => $RegardingID))
                            ->get()->firstRow(DATASET_TYPE_ARRAY);
                    }

                    if (!$Collapse || !$Discussion) {
                        $CategoryID = val('Parameters', $Action);

                        // Make a new discussion
                        $DiscussionID = $DiscussionModel->save(array(
                            'Name' => $this->CollaborativeTitle,
                            'CategoryID' => $CategoryID,
                            'Body' => $this->OriginalContent,
                            'InsertUserID' => val('InsertUserID', $this->SourceElement),
                            'Announce' => 0,
                            'Close' => 0,
                            'RegardingID' => $RegardingID
                        ));

                        if (!$DiscussionID) {
                            throw new Gdn_UserException($DiscussionModel->Validation->resultsText());
                        }

                        $DiscussionModel->updateDiscussionCount($CategoryID);
                    } else {
                        // Add a comment to the existing discussion.
                        $CommentModel = new CommentModel();
                        $CommentID = $CommentModel->save(array(
                            'DiscussionID' => val('DiscussionID', $Discussion),
                            'Body' => $this->Comment,
                            'InsertUserID' => $this->UserID
                        ));

                        $CommentModel->save2($CommentID, true);
                    }

                    break;

                case 'conversation':

                    $ConversationModel = new ConversationModel();
                    $ConversationMessageModel = new ConversationMessageModel();

                    $Users = val('Parameters', $Action);
                    $UserList = explode(',', $Users);
                    if (!sizeof($UserList)) {
                        throw new Exception(sprintf(T("The userlist provided for collaboration on '%s:%s' is invalid.", $this->Type, $this->ForeignType)));
                    }

                    $ConversationID = $ConversationModel->save(array(
                        'To' => 'Admins',
                        'Body' => $this->CollaborativeTitle,
                        'RecipientUserID' => $UserList,
                        'RegardingID' => $RegardingID
                    ), $ConversationMessageModel);

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
