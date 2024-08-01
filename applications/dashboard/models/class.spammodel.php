<?php
/**
 * Spam model.
 *
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles spam data.
 */
class SpamModel extends Gdn_Pluggable
{
    /** @var SpamModel */
    protected static $_Instance;

    /** @var bool */
    public static $Disabled = false;

    /**
     *
     *
     * @return SpamModel
     */
    protected static function _Instance()
    {
        if (!self::$_Instance) {
            self::$_Instance = new SpamModel();
        }

        return self::$_Instance;
    }

    /**
     * Return whether or not the spam model is disabled.
     *
     * @param bool|null $value
     * @return bool
     */
    public static function disabled($value = null)
    {
        if ($value !== null) {
            self::$Disabled = $value;
        }
        return self::$Disabled;
    }

    /**
     * Check whether or not the record is spam.
     *
     * @param string $recordType By default, this should be one of the following:
     *  - Comment: A comment.
     *  - Discussion: A discussion.
     *  - User: A user registration.
     * @param array $data The record data.
     * @param array $options Options for fine-tuning this method call.
     *  - Log: Log the record if it is found to be spam.
     *  - Operation: The log operation to use.
     * @return bool Returns **true** if the record is spam or **false** otherwise.
     */
    public static function isSpam($recordType, $data, $options = [])
    {
        if (self::$Disabled) {
            return false;
        }

        if (\Gdn::session()->isUserVerified()) {
            // Verified user's don't run through spam checks.
            return false;
        }

        $options += [
            "Log" => true,
            "Operation" => LogModel::TYPE_SPAM,
        ];

        // Set some information about the user in the data.
        if ($recordType == "Registration") {
            touchValue("Username", $data, $data["Name"]);
        } else {
            $data += ["InsertUserID" => Gdn::session()->UserID];

            // Check moderation permissions for the user in session.
            if (
                Gdn::session()
                    ->getPermissions()
                    ->hasRanked("Garden.Moderation.Manage")
            ) {
                // The user has moderation permissions and isn't a spammer.
                return false;
            }

            $user = Gdn::userModel()->getID($data["InsertUserID"], DATASET_TYPE_ARRAY);

            if ($user) {
                $verified = val("Verified", $user);
                $admin = val("Admin", $user);

                if ($verified || $admin) {
                    // The user has been verified or is an admin and isn't a spammer.
                    return false;
                }
                $data += [
                    "Username" => $user["Name"],
                    "Email" => $user["Email"],
                    "IPAddress" => $user["LastIPAddress"],
                ];
            }
        }

        if (!isset($data["Body"]) && isset($data["Story"])) {
            $data["Body"] = $data["Story"];
        }

        // Make sure all IP addresses are unpacked.
        $data = ipDecodeRecursive($data);

        touchValue("IPAddress", $data, Gdn::request()->ipAddress());

        $sp = self::_Instance();

        $sp->EventArguments["RecordType"] = $recordType;
        $sp->EventArguments["Data"] = &$data;
        $sp->EventArguments["Options"] = &$options;
        $sp->EventArguments["IsSpam"] = false;

        $sp->fireEvent("CheckSpam");
        $spam = $sp->EventArguments["IsSpam"];

        // Log the spam entry.
        if ($spam && $options["Log"]) {
            // Make sure all IP addresses are packed before insertion
            $data = ipEncodeRecursive($data);

            $logOptions = [];
            switch ($recordType) {
                case "Registration":
                    $logOptions["GroupBy"] = ["RecordIPAddress"];
                    break;
                case "Comment":
                case "Discussion":
                case "Activity":
                case "ActivityComment":
                    $logOptions["GroupBy"] = ["RecordID"];
                    break;
            }

            // If this is a discussion or a comment, it needs some special handling.
            if ($recordType == "Comment" || $recordType == "Discussion") {
                // Grab the record ID, if available.
                $recordID = intval(val("{$recordType}ID", $data));

                /**
                 * If we have a valid record ID, run it through flagForReview.  This will allow us to purge existing
                 * discussions and comments that have been flagged as SPAM after being edited.  If there's no valid ID,
                 * just treat it with regular SPAM logging.
                 */
                if (!empty($recordID) && $options["Operation"] === LogModel::TYPE_SPAM) {
                    // Pass the source as a $data field, so we can propagate it to the LogPostEvent created in flagForReview()..
                    $data["Source"] = $sp->EventArguments["Source"] ?? "unknown";
                    self::flagForReview($recordType, $recordID, $data);
                } else {
                    LogModel::insert($options["Operation"], $recordType, $data, $logOptions);

                    $logPostEvent = LogModel::createLogPostEvent(
                        $options["Operation"],
                        $recordType,
                        $data,
                        $sp->EventArguments["Source"] ?? "unknown",
                        $data["Log_InsertUserID"] ?? Gdn::session()->UserID,
                        "negative",
                        $data["InsertUserID"],
                        ["recordID" => false]
                    );
                    Gdn::eventManager()->dispatch($logPostEvent);
                }
            } else {
                LogModel::insert($options["Operation"], $recordType, $data, $logOptions);
            }
        }

        return $spam;
    }

    /**
     * Insert a SPAM Queue entry for the specified record and delete the record, if possible.
     *
     * @param string $recordType The type of record we're flagging: Discussion or Comment.
     * @param int $id ID of the record we're flagging.
     * @param object|array $data Properties used for updating/overriding the record's current values.
     *
     * @throws Exception If an invalid record type is specified, throw an exception.
     */
    protected static function flagForReview($recordType, $id, $data)
    {
        // We're planning to purge the spammy record.
        $deleteRow = true;

        /**
         * We're only handling two types of content: discussions and comments.  Both need some special setup.
         * Error out if we're not dealing with a discussion or comment.
         */
        switch ($recordType) {
            case "Comment":
                $model = new CommentModel();
                $row = $model->getID($id, DATASET_TYPE_ARRAY);
                break;
            case "Discussion":
                $model = new DiscussionModel();
                $row = $model->getID($id, DATASET_TYPE_ARRAY);

                /**
                 * If our discussion meets or exceeds our comment threshold, just flag it for review. Otherwise, save
                 * it and its comments in the log for review and delete the original record.
                 */
                if ($row["CountComments"] >= DiscussionModel::DELETE_COMMENT_THRESHOLD) {
                    $deleteRow = false;
                } elseif ($row["CountComments"] > 0) {
                    $comments = Gdn::database()
                        ->sql()
                        ->getWhere("Comment", ["DiscussionID" => $id])
                        ->resultArray();

                    if (!array_key_exists("_Data", $row)) {
                        $row["_Data"] = [];
                    }

                    $row["_Data"]["Comment"] = $comments;
                }
                break;
            default:
                throw notFoundException($recordType);
        }

        $overrideFields = ["Name", "Body"];
        foreach ($overrideFields as $fieldName) {
            if (($fieldValue = val($fieldName, $data, false)) !== false) {
                $row[$fieldName] = $fieldValue;
            }
        }

        $logOptions = ["GroupBy" => ["RecordID"]];

        if ($deleteRow) {
            // Remove the record to the log.
            $model->deleteID($id);
        }

        LogModel::insert(LogModel::TYPE_SPAM, $recordType, $row, $logOptions);

        $logPostEvent = LogModel::createLogPostEvent(
            LogModel::TYPE_SPAM,
            $recordType,
            $row,
            $data["Source"] ?? "unknown",
            $data["Log_InsertUserID"] ?? Gdn::session()->UserID,
            "negative",
            $data["InsertUserID"]
        );
        Gdn::eventManager()->dispatch($logPostEvent);
    }
}
