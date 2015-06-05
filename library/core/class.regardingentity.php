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
    public function VerifiedAs($SourceElement = null) {
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

        if (!is_null($OCField) && !is_null($OCData = GetValue($OCField, $this->SourceElement, null))) {
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
    public function AutoParent($ParentType, $ParentIDKey = null) {
        if (!is_null($this->SourceElement)) {
            if (is_null($ParentIDKey)) {
                $ParentIDKey = ucfirst($ParentType).'ID';
            }
            $ParentID = GetValue($ParentIDKey, $this->SourceElement, false);
            if ($ParentID !== false) {
                $this->WithParent($ParentType, $ParentID);
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
    public function WithParent($ParentType, $ParentID) {
        $ModelName = ucfirst($ParentType).'Model';

        if (!class_exists($ModelName)) {
            throw new Exception(sprintf(T("Could not find a model for %s objects (parent type for %s objects)."), ucfirst($ParentType), ucfirst($this->ForeignType)));
        }

        // If we can lookup this object, it is verified
        $VerifyModel = new $ModelName;
        $ParentElement = $VerifyModel->GetID($ParentID);

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
    public function ActionIt($ActionType) {
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
    public function ForDiscussion($InCategory) {
        return $this->ForCollaboration('discussion', $InCategory);
    }

    /**
     *
     *
     * @param $WithUsers
     * @return Gdn_RegardingEntity
     */
    public function ForConversation($WithUsers) {
        return $this->ForCollaboration('conversation', $WithUsers);
    }

    /**
     *
     *
     * @param $CollaborationType
     * @param null $CollaborationParameters
     * @return $this
     */
    public function ForCollaboration($CollaborationType, $CollaborationParameters = null) {
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
    public function Entitled($CollaborativeTitle) {
        $this->CollaborativeTitle = $CollaborativeTitle;

        // Figure out how much space we have for the title
        $MaxLength = 90;
        $Stripped = formatString($CollaborativeTitle, array(
            'RegardingTitle' => ''
        ));
        $UsedLength = strlen($Stripped);
        $AvailableLength = $MaxLength - $UsedLength;

        // Check if the SourceElement contains a 'Name'
        $Name = GetValue('Name', $this->SourceElement, false);

        // If not...
        if ($Name === false) {
            // ...and we have a parent element...
            if (!is_null($this->ParentElement)) {
                // ...try to get a 'Name' from the parent
                $Name = GetValue('Name', $this->ParentElement, false);
            }
        }

        // If all that failed, use the 'Body' of the source
        if ($Name === false) {
            $Name = GetValue('Body', $this->SourceElement, '');
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
    public function Located($URL) {
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
    public function From($UserID) {
        $this->UserID = $UserID;
        return $this;
    }

    /**
     *
     *
     * @param $Reason
     * @return $this
     */
    public function Because($Reason) {
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
    public function Commit() {
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
            $this->UserID = Gdn::Session()->UserID;
        }

        $RegardingModel = new RegardingModel();

        $CollapseMode = C('Garden.Regarding.AutoCollapse', true);
        $Collapse = false;
        if ($CollapseMode) {
            // Check for an existing report of this type
            $ExistingRegardingEntity = $RegardingModel->GetRelated($this->Type, $this->ForeignType, $this->ForeignID);
            if ($ExistingRegardingEntity) {
                $Collapse = true;
                $RegardingID = GetValue('RegardingID', $ExistingRegardingEntity);
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

            $RegardingID = $RegardingModel->Save($RegardingPreSend);

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
                            'Name' => $this->CollaborativeTitle,
                            'CategoryID' => $CategoryID,
                            'Body' => $this->OriginalContent,
                            'InsertUserID' => GetValue('InsertUserID', $this->SourceElement),
                            'Announce' => 0,
                            'Close' => 0,
                            'RegardingID' => $RegardingID
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
                            'Body' => $this->Comment,
                            'InsertUserID' => $this->UserID
                        ));

                        $CommentModel->Save2($CommentID, true);
                    }

                    break;

                case 'conversation':

                    $ConversationModel = new ConversationModel();
                    $ConversationMessageModel = new ConversationMessageModel();

                    $Users = GetValue('Parameters', $Action);
                    $UserList = explode(',', $Users);
                    if (!sizeof($UserList)) {
                        throw new Exception(sprintf(T("The userlist provided for collaboration on '%s:%s' is invalid.", $this->Type, $this->ForeignType)));
                    }

                    $ConversationID = $ConversationModel->Save(array(
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
    public function Setup() {
    }
}
