<?php
/**
 * User model.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles user data.
 */
class UserModel extends Gdn_Model {

    /** Deprecated. */
    const DEFAULT_CONFIRM_EMAIL = 'You need to confirm your email address before you can continue. Please confirm your email address by clicking on the following link: {/entry/emailconfirm,exurl,domain}/{User.UserID,rawurlencode}/{EmailKey,rawurlencode}';

    /** Cache key. */
    const USERID_KEY = 'user.{UserID}';

    /** Cache key. */
    const USERNAME_KEY = 'user.{Name}.name';

    /** Cache key. */
    const USERROLES_KEY = 'user.{UserID}.roles';

    /** Cache key. */
    const USERPERMISSIONS_KEY = 'user.{UserID}.permissions.{PermissionsIncrement}';

    /** Cache key. */
    const INC_PERMISSIONS_KEY = 'permissions.increment';

    /** REDIRECT_APPROVE */
    const REDIRECT_APPROVE = 'REDIRECT_APPROVE';

    /** Minimal regex every username must pass. */
    const USERNAME_REGEX_MIN = '^\/"\\\\#@\t\r\n';

    /** Cache key. */
    const LOGIN_COOLDOWN_KEY = 'user.login.{Source}.cooldown';

    /** Cache key. */
    const LOGIN_RATE_KEY = 'user.login.{Source}.rate';

    /** Seconds between login attempts. */
    const LOGIN_RATE = 1;

    /** @var */
    public $SessionColumns;

    /** @var int The number of users when database optimizations kick in. */
    public $UserThreshold = 10000;

    /** @var int The number of users when extreme database optimizations kick in. */
    public $UserMegaThreshold = 1000000;

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('User');

        $this->addFilterField([
            'Admin', 'Deleted', 'CountVisits', 'CountInvitations', 'CountNotifications', 'Preferences', 'Permissions',
            'LastIPAddress', 'AllIPAddresses', 'DateFirstVisit', 'DateLastActive', 'CountDiscussions', 'CountComments',
            'Score'
        ]);
    }

    /**
     * Whether or not we are past the user threshold.
     *
     * This is a useful indication that some database operations on the User table will be painfully long.
     *
     * @return bool
     */
    public function pastUserThreshold() {
        $estimate = $this->countEstimate();
        return $estimate > $this->UserThreshold;
    }

    /**
     * Whether we're wandered into extreme database optimization territory with our user count.
     *
     * @return bool
     */
    public function pastUserMegaThreshold() {
        $estimate = $this->countEstimate();
        return $estimate > $this->UserMegaThreshold;
    }

    /**
     * Approximate the number of users by checking the database table status.
     *
     * @return int
     */
    public function countEstimate() {
        $px = Gdn::database()->DatabasePrefix;
        return Gdn::database()->query("show table status like '{$px}User'")->value('Rows', 0);
    }

    /**
     * Deprecated.
     *
     * @param string $Message Deprecated.
     * @param array $Data Deprecated.
     * @return string Deprecated.
     */
    private function addEmailHeaderFooter($Message, $Data) {
        $Header = t('EmailHeader', '');
        if ($Header) {
            $Message = formatString($Header, $Data)."\n".$Message;
        }

        $Footer = t('EmailFooter', '');
        if ($Footer) {
            $Message .= "\n".formatString($Footer, $Data);
        }

        return $Message;
    }

    /**
     * Set password strength meter on a form.
     *
     * @param Gdn_Controller $Controller The controller to add the password strength information to.
     */
    public function addPasswordStrength($Controller) {
        $Controller->addJsFile('password.js');
        $Controller->addDefinition('MinPassLength', c('Garden.Registration.MinPasswordLength'));
        $Controller->addDefinition(
            'PasswordTranslations',
            implode(',', [
                t('Password Too Short', 'Too Short'),
                t('Password Contains Username', 'Contains Username'),
                t('Password Very Weak', 'Very Weak'),
                t('Password Weak', 'Weak'),
                t('Password Ok', 'OK'),
                t('Password Good', 'Good'),
                t('Password Strong', 'Strong')])
        );
    }

    /**
     * Reliably get the attributes from any user array or object.
     *
     * @param array|object $user The user to get the attributes for.
     * @return array Returns an attribute array.
     */
    public static function attributes($user) {
        $user = (array)$user;
        $attributes = $user['Attributes'];
        if (is_string($attributes)) {
            $attributes = dbdecode($attributes);
        }
        if (!is_array($attributes)) {
            $attributes = [];
        }
        return $attributes;
    }

    /**
     * Manually ban a user.
     *
     * @param int $UserID The ID of the user to ban.
     * @param array $Options Additional options for the ban.
     * @throws Exception Throws an exception if something goes wrong during the banning.
     */
    public function ban($UserID, $Options) {
        $User = $this->getID($UserID);
        $Banned = val('Banned', $User, 0);

        $this->setField($UserID, 'Banned', BanModel::setBanned($Banned, true, BanModel::BAN_MANUAL));

        $LogID = false;
        if (val('DeleteContent', $Options)) {
            $Options['Log'] = 'Ban';
            $LogID = $this->deleteContent($UserID, $Options);
        }

        if ($LogID) {
            $this->saveAttribute($UserID, 'BanLogID', $LogID);
        }

        $this->EventArguments['UserID'] = $UserID;
        $this->EventArguments['Options'] = $Options;
        $this->fireEvent('Ban');

        if (val('AddActivity', $Options, true)) {
            switch (val('Reason', $Options, '')) {
                case '':
                    $Story = null;
                    break;
                case 'Spam':
                    $Story = t('Banned for spamming.');
                    break;
                case 'Abuse':
                    $Story = t('Banned for being abusive.');
                    break;
                default:
                    $Story = $Options['Reason'];
                    break;
            }

            $Activity = [
                'ActivityType' => 'Ban',
                'NotifyUserID' => ActivityModel::NOTIFY_MODS,
                'ActivityUserID' => $UserID,
                'RegardingUserID' => Gdn::session()->UserID,
                'HeadlineFormat' => t('HeadlineFormat.Ban', '{RegardingUserID,You} banned {ActivityUserID,you}.'),
                'Story' => $Story,
                'Data' => ['LogID' => $LogID]];

            $ActivityModel = new ActivityModel();
            $ActivityModel->save($Activity);
        }
    }

    /**
     * Checks the specified user's for the given permission. Returns a boolean value indicating if the action is permitted.
     *
     * @param mixed $User The user to check.
     * @param mixed $Permission The permission (or array of permissions) to check.
     * @param array $Options Not used.
     * @return boolean
     */
    public function checkPermission($User, $Permission, $Options = []) {
        if (is_numeric($User)) {
            $User = $this->getID($User);
        }
        $User = (object)$User;

        if ($User->Banned || $User->Deleted) {
            return false;
        }

        if ($User->Admin) {
            return true;
        }

        // Grab the permissions for the user.
        if ($User->UserID == 0) {
            $Permissions = $this->definePermissions(0, false);
        } elseif (is_array($User->Permissions)) {
            $Permissions = $User->Permissions;
        } else {
            $Permissions = $this->definePermissions($User->UserID, false);
        }

        // TODO: Check for junction table permissions.
        $Result = in_array($Permission, $Permissions) || array_key_exists($Permission, $Permissions);
        return $Result;
    }

    /**
     * Merge the old user into the new user.
     *
     * @param int $OldUserID The ID of the old user.
     * @param int $NewUserID The ID of the new user.
     */
    public function merge($OldUserID, $NewUserID) {
        $OldUser = $this->getID($OldUserID, DATASET_TYPE_ARRAY);
        $NewUser = $this->getID($NewUserID, DATASET_TYPE_ARRAY);

        if (!$OldUser || !$NewUser) {
            throw new Gdn_UserException("Could not find one or both users to merge.");
        }

        $Map = ['UserID', 'Name', 'Email', 'CountVisits', 'CountDiscussions', 'CountComments'];

        $Result = ['MergeID' => null, 'Before' => [
            'OldUser' => arrayTranslate($OldUser, $Map),
            'NewUser' => arrayTranslate($NewUser, $Map)]];

        // Start the merge.
        $MergeID = $this->mergeStart($OldUserID, $NewUserID);

        // Copy all discussions from the old user to the new user.
        $this->mergeCopy($MergeID, 'Discussion', 'InsertUserID', $OldUserID, $NewUserID);

        // Copy all the comments from the old user to the new user.
        $this->mergeCopy($MergeID, 'Comment', 'InsertUserID', $OldUserID, $NewUserID);

        // Update the last comment user ID.
        $this->SQL->put('Discussion', ['LastCommentUserID' => $NewUserID], ['LastCommentUserID' => $OldUserID]);

        // Clear the categories cache.
        CategoryModel::clearCache();

        // Copy all of the activities.
        $this->mergeCopy($MergeID, 'Activity', 'NotifyUserID', $OldUserID, $NewUserID);
        $this->mergeCopy($MergeID, 'Activity', 'InsertUserID', $OldUserID, $NewUserID);
        $this->mergeCopy($MergeID, 'Activity', 'ActivityUserID', $OldUserID, $NewUserID);

        // Copy all of the activity comments.
        $this->mergeCopy($MergeID, 'ActivityComment', 'InsertUserID', $OldUserID, $NewUserID);

        // Copy all conversations.
        $this->mergeCopy($MergeID, 'Conversation', 'InsertUserID', $OldUserID, $NewUserID);
        $this->mergeCopy($MergeID, 'ConversationMessage', 'InsertUserID', $OldUserID, $NewUserID, 'MessageID');
        $this->mergeCopy($MergeID, 'UserConversation', 'UserID', $OldUserID, $NewUserID, 'ConversationID');

        $this->EventArguments['MergeID'] = $MergeID;
        $this->EventArguments['OldUser'] = $OldUser;
        $this->EventArguments['NewUser'] = $NewUser;
        $this->fireEvent('Merge');

        $this->mergeFinish($MergeID);

        $OldUser = $this->getID($OldUserID, DATASET_TYPE_ARRAY);
        $NewUser = $this->getID($NewUserID, DATASET_TYPE_ARRAY);

        $Result['MergeID'] = $MergeID;
        $Result['After'] = [
            'OldUser' => arrayTranslate($OldUser, $Map),
            'NewUser' => arrayTranslate($NewUser, $Map)];

        return $Result;
    }

    /**
     * Backup user before merging.
     *
     * @param int $MergeID The ID of the merge table entry.
     * @param string $Table The name of the table being backed up.
     * @param string $Column The name of the column being backed up.
     * @param int $OldUserID The ID of the old user.
     * @param int $NewUserID The ID of the new user.
     * @param string $PK The primary key column name of the table.
     */
    private function mergeCopy($MergeID, $Table, $Column, $OldUserID, $NewUserID, $PK = '') {
        if (!$PK) {
            $PK = $Table.'ID';
        }

        // Insert the columns to the bak table.
        $Sql = "insert ignore GDN_UserMergeItem(`MergeID`, `Table`, `Column`, `RecordID`, `OldUserID`, `NewUserID`)
         select :MergeID, :Table, :Column, `$PK`, :OldUserID, :NewUserID
         from `GDN_$Table` t
         where t.`$Column` = :OldUserID2";
        Gdn::sql()->Database->query(
            $Sql,
            [':MergeID' => $MergeID, ':Table' => $Table, ':Column' => $Column,
                ':OldUserID' => $OldUserID, ':NewUserID' => $NewUserID, ':OldUserID2' => $OldUserID]
        );

        Gdn::sql()->options('Ignore', true)->put(
            $Table,
            [$Column => $NewUserID],
            [$Column => $OldUserID]
        );
    }

    /**
     * Start merging user accounts.
     *
     * @param int $OldUserID The ID of the old user.
     * @param int $NewUserID The ID of the new user.
     * @return int|null Returns the merge table ID of the merge.
     * @throws Gdn_UserException Throws an exception of there is a data validation error.
     */
    private function mergeStart($OldUserID, $NewUserID) {
        $Model = new Gdn_Model('UserMerge');

        // Grab the users.
        $OldUser = $this->getID($OldUserID, DATASET_TYPE_ARRAY);
        $NewUser = $this->getID($NewUserID, DATASET_TYPE_ARRAY);

        // First see if there is a record with the same merge.
        $Row = $Model->getWhere(['OldUserID' => $OldUserID, 'NewUserID' => $NewUserID])->firstRow(DATASET_TYPE_ARRAY);
        if ($Row) {
            $MergeID = $Row['MergeID'];

            // Save this merge in the log.
            if ($Row['Attributes']) {
                $Attributes = dbdecode($Row['Attributes']);
            } else {
                $Attributes = [];
            }

            $Attributes['Log'][] = ['UserID' => Gdn::session()->UserID, 'Date' => Gdn_Format::toDateTime()];
            $Row = ['MergeID' => $MergeID, 'Attributes' => $Attributes];
        } else {
            $Row = [
                'OldUserID' => $OldUserID,
                'NewUserID' => $NewUserID];
        }

        $UserSet = [];
        $OldUserSet = [];
        if (dateCompare($OldUser['DateFirstVisit'], $NewUser['DateFirstVisit']) < 0) {
            $UserSet['DateFirstVisit'] = $OldUser['DateFirstVisit'];
        }

        if (!isset($Row['Attributes']['User']['CountVisits'])) {
            $UserSet['CountVisits'] = $OldUser['CountVisits'] + $NewUser['CountVisits'];
            $OldUserSet['CountVisits'] = 0;
        }

        if (!empty($UserSet)) {
            // Save the user information on the merge record.
            foreach ($UserSet as $Key => $Value) {
                // Only save changed values that aren't already there from a previous merge.
                if ($NewUser[$Key] != $Value && !isset($Row['Attributes']['User'][$Key])) {
                    $Row['Attributes']['User'][$Key] = $NewUser[$Key];
                }
            }
        }

        $MergeID = $Model->save($Row);
        if (val('MergeID', $Row)) {
            $MergeID = $Row['MergeID'];
        }

        if (!$MergeID) {
            throw new Gdn_UserException($Model->Validation->resultsText());
        }

        // Update the user with the new user-level data.
        $this->setField($NewUserID, $UserSet);
        if (!empty($OldUserSet)) {
            $this->setField($OldUserID, $OldUserSet);
        }

        return $MergeID;
    }

    /**
     * Finish merging user accounts.
     *
     * @param int $MergeID The merge table ID.
     */
    protected function mergeFinish($MergeID) {
        $Row = Gdn::sql()->getWhere('UserMerge', ['MergeID' => $MergeID])->firstRow(DATASET_TYPE_ARRAY);

        if (isset($Row['Attributes']) && !empty($Row['Attributes'])) {
            trace(dbdecode($Row['Attributes']), 'Merge Attributes');
        }

        $UserIDs = [
            $Row['OldUserID'],
            $Row['NewUserID']];

        foreach ($UserIDs as $UserID) {
            $this->counts('countdiscussions', $UserID);
            $this->counts('countcomments', $UserID);
        }
    }

    /**
     * User counts.
     *
     * @param string $Column The name of the count column. (ex. CountDiscussions, CountComments).
     * @param int|null $UserID The user ID to get the counts for or **null** for the current user.
     */
    public function counts($Column, $UserID = null) {
        if ($UserID > 0) {
            $Where = ['UserID' => $UserID];
        } else {
            $Where = null;
        }

        switch (strtolower($Column)) {
            case 'countdiscussions':
                Gdn::database()->query(
                    DBAModel::getCountSQL('count', 'User', 'Discussion', 'CountDiscussions', 'DiscussionID', 'UserID', 'InsertUserID', $Where)
                );
                break;
            case 'countcomments':
                Gdn::database()->query(
                    DBAModel::getCountSQL('count', 'User', 'Comment', 'CountComments', 'CommentID', 'UserID', 'InsertUserID', $Where)
                );
                break;
        }

        if ($UserID > 0) {
            $this->clearCache($UserID);
        }
    }

    /**
     * Whether or not the application requires email confirmation.
     *
     * @return bool
     */
    public static function requireConfirmEmail() {
        return c('Garden.Registration.ConfirmEmail') && !self::noEmail();
    }

    /**
     * Whether or not users have email addresses.
     *
     * @return bool
     */
    public static function noEmail() {
        return c('Garden.Registration.NoEmail');
    }

    /**
     * Unban a user.
     *
     * @param int $UserID The user to unban.
     * @param array $Options Options for the unban.
     * @since 2.1
     */
    public function unBan($UserID, $Options = []) {
        $User = $this->getID($UserID, DATASET_TYPE_ARRAY);
        if (!$User) {
            throw notFoundException();
        }

        $Banned = $User['Banned'];
        if (!BanModel::isBanned($Banned, BanModel::BAN_AUTOMATIC | BanModel::BAN_MANUAL)) {
            throw new Gdn_UserException(t("The user isn't banned.", "The user isn't banned or is banned by some other function."));
        }

        // Unban the user.
        $NewBanned = BanModel::setBanned($Banned, false, BanModel::BAN_AUTOMATIC | BanModel::BAN_MANUAL);
        $this->setField($UserID, 'Banned', $NewBanned);

        // Restore the user's content.
        if (val('RestoreContent', $Options)) {
            $BanLogID = $this->getAttribute($UserID, 'BanLogID');

            if ($BanLogID) {
                $LogModel = new LogModel();

                try {
                    $LogModel->restore($BanLogID);
                } catch (Exception $Ex) {
                    if ($Ex->getCode() != 404) {
                        throw $Ex;
                    }
                }
                $this->saveAttribute($UserID, 'BanLogID', null);
            }
        }

        // Add an activity for the unbanning.
        if (val('AddActivity', $Options, true)) {
            $ActivityModel = new ActivityModel();

            $Story = val('Story', $Options, null);

            // Notify the moderators of the unban.
            $Activity = [
                'ActivityType' => 'Ban',
                'NotifyUserID' => ActivityModel::NOTIFY_MODS,
                'ActivityUserID' => $UserID,
                'RegardingUserID' => Gdn::session()->UserID,
                'HeadlineFormat' => t('HeadlineFormat.Unban', '{RegardingUserID,You} unbanned {ActivityUserID,you}.'),
                'Story' => $Story,
                'Data' => [
                    'Unban' => true
                ]
            ];

            $ActivityModel->queue($Activity);

            // Notify the user of the unban.
            $Activity['NotifyUserID'] = $UserID;
            $Activity['Emailed'] = ActivityModel::SENT_PENDING;
            $Activity['HeadlineFormat'] = t('HeadlineFormat.Unban.Notification', "You've been unbanned.");
            $ActivityModel->queue($Activity, false, ['Force' => true]);

            $ActivityModel->saveQueue();
        }
    }

    /**
     * Users respond to confirmation emails by clicking a link that takes them here.
     *
     * @param array|object $User The user confirming their email.
     * @param string $EmailKey The token that was emailed to the user.
     * @return bool Returns **true** if the email was confirmed.
     */
    public function confirmEmail($User, $EmailKey) {
        $Attributes = val('Attributes', $User);
        $StoredEmailKey = val('EmailKey', $Attributes);
        $UserID = val('UserID', $User);

        if (!$StoredEmailKey || $EmailKey != $StoredEmailKey) {
            $this->Validation->addValidationResult('EmailKey', '@'.t(
                'Couldn\'t confirm email.',
                'We couldn\'t confirm your email. Check the link in the email we sent you or try sending another confirmation email.'
            ));
            return false;
        }

        $confirmRoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_UNCONFIRMED);
        $defaultRoles = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);

        // Update the user's roles.
        $UserRoles = $this->getRoles($UserID);
        $UserRoleIDs = [];
        while ($UserRole = $UserRoles->nextRow(DATASET_TYPE_ARRAY)) {
            $UserRoleIDs[] = $UserRole['RoleID'];
        }

        // Sanitize result roles
        $Roles = array_diff($UserRoleIDs, $confirmRoleIDs);
        if (!sizeof($Roles)) {
            $Roles = $defaultRoles;
        }

        $this->EventArguments['ConfirmUserID'] = $UserID;
        $this->EventArguments['ConfirmUserRoles'] = &$Roles;
        $this->fireEvent('BeforeConfirmEmail');
        $this->saveRoles($UserID, $Roles, false);

        // Remove the email confirmation attributes.
        $this->saveAttribute($UserID, ['EmailKey' => null]);
        $this->setField($UserID, 'Confirmed', 1);
        return true;
    }

    /**
     * Initiate an SSO connection.
     *
     * @param string $String
     * @param bool $ThrowError
     * @return int|void
     */
    public function sso($String, $ThrowError = false) {
        if (!$String) {
            return null;
        }

        $Parts = explode(' ', $String);

        $String = $Parts[0];
        trace($String, "SSO String");
        $Data = json_decode(base64_decode($String), true);
        trace($Data, 'RAW SSO Data');

        if (!isset($Parts[1])) {
            $this->Validation->addValidationResult('sso', 'Missing SSO signature.');
        }
        if (!isset($Parts[2])) {
            $this->Validation->addValidationResult('sso', 'Missing SSO timestamp.');
        }
        if (count($this->Validation->results()) > 0) {
            $msg = $this->Validation->resultsText();
            if ($ThrowError) {
                throw new Gdn_UserException($msg, 400);
            }
            return false;
        }

        $Signature = $Parts[1];
        $Timestamp = $Parts[2];
        $HashMethod = val(3, $Parts, 'hmacsha1');
        $ClientID = val('client_id', $Data);
        if (!$ClientID) {
            $this->Validation->addValidationResult('sso', 'Missing SSO client_id');
            return false;
        }

        $Provider = Gdn_AuthenticationProviderModel::getProviderByKey($ClientID);

        if (!$Provider) {
            $this->Validation->addValidationResult('sso', "Unknown SSO Provider: $ClientID");
            return false;
        }

        $Secret = $Provider['AssociationSecret'];
        if (!trim($Secret, '.')) {
            $this->Validation->addValidationResult('sso', 'Missing client secret');
            return false;
        }

        // Check the signature.
        switch ($HashMethod) {
            case 'hmacsha1':
                $CalcSignature = hash_hmac('sha1', "$String $Timestamp", $Secret);
                break;
            default:
                $this->Validation->addValidationResult('sso', "Invalid SSO hash method $HashMethod.");
                return false;
        }
        if ($CalcSignature != $Signature) {
            $this->Validation->addValidationResult('sso', "Invalid SSO signature: $Signature");
            return false;
        }

        $UniqueID = $Data['uniqueid'];
        $User = arrayTranslate($Data, [
            'name' => 'Name',
            'email' => 'Email',
            'photourl' => 'Photo',
            'roles' => 'Roles',
            'uniqueid' => null,
            'client_id' => null], true);

        // Remove important missing keys.
        if (!array_key_exists('photourl', $Data)) {
            unset($User['Photo']);
        }
        if (!array_key_exists('roles', $Data)) {
            unset($User['Roles']);
        }

        trace($User, 'SSO User');

        $UserID = Gdn::userModel()->connect($UniqueID, $ClientID, $User);
        return $UserID;
    }

    /**
     * Sync user data.
     *
     * @param array|int $CurrentUser
     * @param array $NewUser Data to overwrite user with.
     * @param bool $Force
     * @since 2.1
     * @deprecated since 2.2.
     */
    public function synchUser($CurrentUser, $NewUser, $Force = false) {
        deprecated('UserModel::synchUser', 'UserModel::syncUser');
        $this->syncUser($CurrentUser, $NewUser, $Force);
    }

    /**
     * Sync user data.
     *
     * @param array|int $CurrentUser
     * @param array $NewUser Data to overwrite user with.
     * @param bool $Force
     * @since 2.1
     */
    public function syncUser($CurrentUser, $NewUser, $Force = false) {
        // Don't synchronize the user if we are configured not to.
        if (!$Force && !c('Garden.Registration.ConnectSynchronize', true)) {
            return;
        }

        if (is_numeric($CurrentUser)) {
            $CurrentUser = $this->getID($CurrentUser, DATASET_TYPE_ARRAY);
        }

        // Don't sync the user photo if they've uploaded one already.
        $Photo = val('Photo', $NewUser);
        $CurrentPhoto = val('Photo', $CurrentUser);
        if (false
            || ($CurrentPhoto && !stringBeginsWith($CurrentPhoto, 'http'))
            || !is_string($Photo)
            || ($Photo && !stringBeginsWith($Photo, 'http'))
            || strpos($Photo, '.gravatar.') !== false
            || stringBeginsWith($Photo, url('/', true))
        ) {
            unset($NewUser['Photo']);
            trace('Not setting photo.');
        }

        if (c('Garden.SSO.SyncRoles') && c('Garden.SSO.SyncRolesBehavior') !== 'register') {
            // Translate the role names to IDs.
            $Roles = val('Roles', $NewUser, '');
            $RoleIDs = $this->lookupRoleIDs($Roles);
            if (empty($RoleIDs)) {
                $RoleIDs = $this->newUserRoleIDs();
            }
            $NewUser['RoleID'] = $RoleIDs;
        } else {
            unset($NewUser['Roles']);
            unset($NewUser['RoleID']);
        }

        // Save the user information.
        $NewUser['UserID'] = $CurrentUser['UserID'];
        trace($NewUser);

        $Result = $this->save($NewUser, ['NoConfirmEmail' => true, 'FixUnique' => true, 'SaveRoles' => isset($NewUser['RoleID'])]);
        if (!$Result) {
            trace($this->Validation->resultsText());
        }
    }

    /**
     * Connect a user with a foreign authentication system.
     *
     * @param string $UniqueID The user's unique key in the other authentication system.
     * @param string $ProviderKey The key of the system providing the authentication.
     * @param array $UserData Data to go in the user table.
     * @param array $Options Additional connect options.
     * @return int The new/existing user ID.
     */
    public function connect($UniqueID, $ProviderKey, $UserData, $Options = []) {
        trace('UserModel->Connect()');
        $provider = Gdn_AuthenticationProviderModel::getProviderByKey($ProviderKey);

        // Trusted providers can sync roles.
        if (val('Trusted', $provider) && (!empty($UserData['Roles']) || !empty($UserData['Roles']))) {
            saveToConfig('Garden.SSO.SyncRoles', true, false);
        }

        $UserID = false;
        if (!isset($UserData['UserID'])) {
            // Check to see if the user already exists.
            $Auth = $this->getAuthentication($UniqueID, $ProviderKey);
            $UserID = val('UserID', $Auth);

            if ($UserID) {
                $UserData['UserID'] = $UserID;
            }
        }

        if ($UserID) {
            // Save the user.
            $this->syncUser($UserID, $UserData);
            return $UserID;
        } else {
            // The user hasn't already been connected. We want to see if we can't find the user based on some critera.

            // Check to auto-connect based on email address.
            if (c('Garden.SSO.AutoConnect', c('Garden.Registration.AutoConnect')) && isset($UserData['Email'])) {
                $User = $this->getByEmail($UserData['Email']);
                trace($User, "Autoconnect User");
                if ($User) {
                    $User = (array)$User;
                    // Save the user.
                    $this->syncUser($User, $UserData);
                    $UserID = $User['UserID'];
                }
            }

            if (!$UserID) {
                // Create a new user.
                $UserData['Password'] = md5(microtime());
                $UserData['HashMethod'] = 'Random';

                touchValue('CheckCaptcha', $Options, false);
                touchValue('NoConfirmEmail', $Options, true);
                touchValue('NoActivity', $Options, true);

                // Translate SSO style roles to an array of role IDs suitable for registration.
                if (!empty($UserData['Roles']) && !isset($UserData['RoleID'])) {
                    $UserData['RoleID'] = $this->lookupRoleIDs($UserData['Roles']);
                }
                touchValue('SaveRoles', $Options, !empty($UserData['RoleID']) && c('Garden.SSO.SyncRoles', false));

                trace($UserData, 'Registering User');
                $UserID = $this->register($UserData, $Options);
            }

            if ($UserID) {
                // Save the authentication.
                $this->saveAuthentication([
                    'UniqueID' => $UniqueID,
                    'Provider' => $ProviderKey,
                    'UserID' => $UserID
                ]);
            } else {
                trace($this->Validation->resultsText(), TRACE_ERROR);
            }
        }

        return $UserID;
    }

    /**
     * Filter dangerous fields out of user-submitted data.
     *
     * @param array $data The data to filter.
     * @param bool $register Whether or not this is a registration.
     * @return array Returns a filtered version of {@link $data}.
     */
    public function filterForm($data, $register = false) {
        if (!$register && !Gdn::session()->checkPermission('Garden.Users.Edit') && !c("Garden.Profile.EditUsernames")) {
            $this->removeFilterField('Name');
        }

        if (!Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            $this->addFilterField(['Banned', 'Verified', 'Confirmed', 'RankID']);
        }

        $data = parent::filterForm($data);
        return $data;

    }

    /**
     * Force gender to be a verified value.
     *
     * @param string $Value The gender string.
     * @return string
     */
    public static function fixGender($Value) {
        if (!$Value || !is_string($Value)) {
            return 'u';
        }

        if ($Value) {
            $Value = strtolower(substr(trim($Value), 0, 1));
        }

        if (!in_array($Value, ['u', 'm', 'f'])) {
            $Value = 'u';
        }

        return $Value;
    }

    /**
     * A convenience method to be called when inserting users.
     *
     * Users are inserted in various methods depending on registration setups.
     *
     * @param array $Fields The user to insert.
     * @param array $Options Insert options.
     * @return int|false Returns the new ID of the user or **false** if there was an error.
     */
    private function insertInternal($Fields, $Options = []) {
        $this->EventArguments['InsertFields'] =& $Fields;
        $this->fireEvent('BeforeInsertUser');

        if (!val('Setup', $Options)) {
            unset($Fields['Admin']);
        }

        // Massage the roles for email confirmation.
        if (self::requireConfirmEmail() && !val('NoConfirmEmail', $Options)) {
            $ConfirmRoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_UNCONFIRMED);

            if (!empty($ConfirmRoleID)) {
                touchValue('Attributes', $Fields, []);
                $ConfirmationCode = randomString(8);
                $Fields['Attributes']['EmailKey'] = $ConfirmationCode;
                $Fields['Confirmed'] = 0;
            }
        }

        // Make sure to encrypt the password for saving...
        if (array_key_exists('Password', $Fields) && !val('HashMethod', $Fields)) {
            $PasswordHash = new Gdn_PasswordHash();
            $Fields['Password'] = $PasswordHash->hashPassword($Fields['Password']);
            $Fields['HashMethod'] = 'Vanilla';
        }

        // Certain configurations can allow blank email addresses.
        if (val('Email', $Fields, null) === null) {
            $Fields['Email'] = '';
        }

        $Roles = val('Roles', $Fields);
        unset($Fields['Roles']);

        if (array_key_exists('Attributes', $Fields) && !is_string($Fields['Attributes'])) {
            $Fields['Attributes'] = dbencode($Fields['Attributes']);
        }

        $UserID = $this->SQL->insert($this->Name, $Fields);
        if (is_array($Roles)) {
            $this->saveRoles($UserID, $Roles, false);
        }

        // Approval registration requires an email confirmation.
        if ($UserID && isset($ConfirmationCode) && strtolower(c('Garden.Registration.Method')) == 'approval') {
            // Send the confirmation email.
            $this->sendEmailConfirmationEmail($UserID);
        }

        // Fire an event for user inserts
        $this->EventArguments['InsertUserID'] = $UserID;
        $this->EventArguments['InsertFields'] = $Fields;
        $this->fireEvent('AfterInsertUser');

        return $UserID;
    }

    /**
     * Add user data to a result set.
     *
     * @param array|Gdn_DataSet $Data Results we need to associate user data with.
     * @param array $Columns Database columns containing UserIDs to get data for.
     * @param array $Options Optionally pass list of user data to collect with key 'Join'.
     */
    public function joinUsers(&$Data, $Columns, $Options = []) {
        if ($Data instanceof Gdn_DataSet) {
            $Data2 = $Data->result();
        } else {
            $Data2 = &$Data;
        }

        // Grab all of the user fields that need to be joined.
        $UserIDs = [];
        foreach ($Data as $Row) {
            foreach ($Columns as $ColumnName) {
                $ID = val($ColumnName, $Row);
                if (is_numeric($ID)) {
                    $UserIDs[$ID] = 1;
                }
            }
        }

        // Get the users.
        $Users = $this->getIDs(array_keys($UserIDs));

        // Get column name prefix (ex: 'Insert' from 'InsertUserID')
        $Prefixes = [];
        foreach ($Columns as $ColumnName) {
            $Prefixes[] = StringEndsWith($ColumnName, 'UserID', true, true);
        }

        // Join the user data using prefixes (ex: 'Name' for 'InsertUserID' becomes 'InsertName')
        $Join = val('Join', $Options, ['Name', 'Email', 'Photo']);

        foreach ($Data2 as &$Row) {
            foreach ($Prefixes as $Px) {
                $ID = val($Px.'UserID', $Row);
                if (is_numeric($ID)) {
                    $User = val($ID, $Users, false);
                    foreach ($Join as $Column) {
                        $Value = $User[$Column];
                        if ($Column == 'Photo') {
                            if ($Value && !isUrl($Value)) {
                                $Value = Gdn_Upload::url(changeBasename($Value, 'n%s'));
                            } elseif (!$Value) {
                                $Value = UserModel::getDefaultAvatarUrl($User);
                            }
                        }
                        setValue($Px.$Column, $Row, $Value);
                    }
                } else {
                    foreach ($Join as $Column) {
                        setValue($Px.$Column, $Row, null);
                    }
                }


            }
        }
    }

    /**
     * Returns the url to the default avatar for a user.
     *
     * @param array $user The user to get the default avatar for.
     * @param string $size The size of avatar to return (only respected for dashboard-uploaded default avatars).
     * @return string The url to the default avatar image.
     */
    public static function getDefaultAvatarUrl($user = [], $size = 'thumbnail') {
        if (!empty($user) && function_exists('UserPhotoDefaultUrl')) {
            return userPhotoDefaultUrl($user);
        }
        if ($avatar = c('Garden.DefaultAvatar', false)) {
            if (strpos($avatar, 'defaultavatar/') !== false) {
                if ($size == 'thumbnail') {
                    return Gdn_UploadImage::url(changeBasename($avatar, 'n%s'));
                } elseif ($size == 'profile') {
                    return Gdn_UploadImage::url(changeBasename($avatar, 'p%s'));
                }
            }
            return $avatar;
        }
        return asset('applications/dashboard/design/images/defaulticon.png', true);
    }

    /**
     * Query the user table.
     *
     * @param bool $SafeData Makes sure that the query does not return any sensitive information about the user.
     * (password, attributes, preferences, etc).
     */
    public function userQuery($SafeData = false) {
        if ($SafeData) {
            $this->SQL->select('u.UserID, u.Name, u.Photo, u.CountVisits, u.DateFirstVisit, u.DateLastActive, u.DateInserted, u.DateUpdated, u.Score, u.Deleted, u.CountDiscussions, u.CountComments');
        } else {
            $this->SQL->select('u.*');
        }
        $this->SQL->from('User u');
    }

    /**
     * Load and compile user permissions
     *
     * @param integer $UserID
     * @param boolean $Serialize
     * @return array
     */
    public function definePermissions($UserID, $Serialize = true) {
        $UserPermissionsKey = '';
        if (Gdn::cache()->activeEnabled()) {
            $PermissionsIncrement = $this->getPermissionsIncrement();
            $UserPermissionsKey = formatString(self::USERPERMISSIONS_KEY, [
                'UserID' => $UserID,
                'PermissionsIncrement' => $PermissionsIncrement
            ]);

            $CachePermissions = Gdn::cache()->get($UserPermissionsKey);
            if ($CachePermissions !== Gdn_Cache::CACHEOP_FAILURE) {
                if ($Serialize) {
                    return dbencode($CachePermissions);
                } else {
                    return $CachePermissions;
                }
            }
        }

        $Data = Gdn::permissionModel()->cachePermissions($UserID);
        $Permissions = UserModel::compilePermissions($Data);

        $PermissionsSerialized = dbencode($Permissions);
        if (Gdn::cache()->activeEnabled()) {
            Gdn::cache()->store($UserPermissionsKey, $Permissions);
        } else {
            // Save the permissions to the user table
            if ($UserID > 0) {
                $this->SQL->put('User', ['Permissions' => $PermissionsSerialized], ['UserID' => $UserID]);
            }
        }

        return $Serialize ? $PermissionsSerialized : $Permissions;
    }

    /**
     * Take raw permission definitions and create.
     *
     * @param array $Permissions
     * @return array Compiled permissions
     */
    public static function compilePermissions($Permissions) {
        $Compiled = [];
        foreach ($Permissions as $i => $Row) {
            $JunctionID = array_key_exists('JunctionID', $Row) ? $Row['JunctionID'] : null;
            unset($Row['JunctionColumn'], $Row['JunctionColumn'], $Row['JunctionID'], $Row['RoleID'], $Row['PermissionID']);

            foreach ($Row as $PermissionName => $Value) {
                if ($Value == 0) {
                    continue;
                }

                if (is_numeric($JunctionID) && $JunctionID !== null) {
                    if (!array_key_exists($PermissionName, $Compiled)) {
                        $Compiled[$PermissionName] = [];
                    }

                    if (!is_array($Compiled[$PermissionName])) {
                        $Compiled[$PermissionName] = [];
                    }

                    $Compiled[$PermissionName][] = $JunctionID;
                } else {
                    $Compiled[] = $PermissionName;
                }
            }
        }

        return $Compiled;
    }

    /**
     * Default Gdn_Model::get() behavior.
     *
     * Prior to 2.0.18 it incorrectly behaved like GetID.
     * This method can be deleted entirely once it's been deprecated long enough.
     *
     * @return object DataSet
     */
    public function get($OrderFields = '', $OrderDirection = 'asc', $Limit = false, $Offset = false) {
        if (is_numeric($OrderFields)) {
            // They're using the old version that was a misnamed GetID()
            deprecated('UserModel->get()', 'UserModel->getID()');
            $Result = $this->getID($OrderFields);
        } else {
            $Result = parent::get($OrderFields, $OrderDirection, $Limit, $Offset);
        }
        return $Result;
    }

    /**
     * Get a user by their username.
     *
     * @param string $Username The username of the user.
     * @return bool|object Returns the user or **false** if they don't exist.
     */
    public function getByUsername($Username) {
        if ($Username == '') {
            return false;
        }

        // Check page cache, then memcached
        $User = $this->getUserFromCache($Username, 'name');

        if ($User === Gdn_Cache::CACHEOP_FAILURE) {
            $this->userQuery();
            $User = $this->SQL->where('u.Name', $Username)->get()->firstRow(DATASET_TYPE_ARRAY);
            if ($User) {
                // If success, cache user
                $this->userCache($User);
            }
        }

        // Apply calculated fields
        $this->setCalculatedFields($User);

        // By default, FirstRow() gives stdClass
        if ($User !== false) {
            $User = (object)$User;
        }

        return $User;
    }

    /**
     * Get user by email address.
     *
     * @param string $Email The email address of the user.
     * @return array|bool|stdClass Returns the user or **false** if they don't exist.
     */
    public function getByEmail($Email) {
        $this->userQuery();
        $User = $this->SQL->where('u.Email', $Email)->get()->firstRow();
        $this->setCalculatedFields($User);
        return $User;
    }

    /**
     * Get users by role.
     *
     * @param int|string $Role The ID or name of the role.
     * @return Gdn_DataSet Returns the users with the given role.
     */
    public function getByRole($Role) {
        $RoleID = $Role; // Optimistic
        if (is_string($Role)) {
            $RoleModel = new RoleModel();
            $Roles = $RoleModel->getArray();
            $RolesByName = array_flip($Roles);

            $RoleID = val($Role, $RolesByName, null);

            // No such role
            if (is_null($RoleID)) {
                return new Gdn_DataSet();
            }
        }

        return $this->SQL->select('u.*')
            ->from('User u')
            ->join('UserRole ur', 'u.UserID = ur.UserID')
            ->where('ur.RoleID', $RoleID, true, false)
            ->orderBy('DateInserted', 'desc')
            ->get();
    }

    /**
     * Get the most recently active users.
     *
     * @param int $Limit The number of users to return.
     * @return Gdn_DataSet Returns a list of users.
     */
    public function getActiveUsers($Limit = 5) {
        $UserIDs = $this->SQL
            ->select('UserID')
            ->from('User')
            ->orderBy('DateLastActive', 'desc')
            ->limit($Limit, 0)
            ->get()->resultArray();
        $UserIDs = array_column($UserIDs, 'UserID');

        $Data = $this->SQL->getWhere('User', ['UserID' => $UserIDs], 'DateLastActive', 'desc');
        return $Data;
    }

    /**
     * Get the current number of applicants waiting to be approved.
     *
     * @return int Returns the number of applicants or 0 if the registration method isn't set to approval.
     */
    public function getApplicantCount() {
        $roleModel = new RoleModel();
        $result = $roleModel->getApplicantCount();
        return $result;
    }

    /**
     * Returns all users in the applicant role.
     *
     * @return Gdn_DataSet Returns a data set of the users who are applicants.
     */
    public function getApplicants() {
        $applicantRoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_APPLICANT);

        if (empty($applicantRoleIDs)) {
            return new Gdn_DataSet();
        }

        return $this->SQL->select('u.*')
            ->from('User u')
            ->join('UserRole ur', 'u.UserID = ur.UserID')
            ->where('ur.RoleID', $applicantRoleIDs)
            ->orderBy('DateInserted', 'desc')
            ->get();
    }

    /**
     * Get the a user authentication row.
     *
     * @param string $UniqueID The unique ID of the user in the foreign authentication scheme.
     * @param string $Provider The key of the provider.
     * @return array|false
     */
    public function getAuthentication($UniqueID, $Provider) {
        return $this->SQL->getWhere(
            'UserAuthentication',
            ['ForeignUserKey' => $UniqueID, 'ProviderKey' => $Provider]
        )->firstRow(DATASET_TYPE_ARRAY);
    }

    /**
     *
     *
     * @param array|bool $Like
     * @return int
     */
    public function getCountLike($Like = false) {
        $this->SQL
            ->select('u.UserID', 'count', 'UserCount')
            ->from('User u');

        if (is_array($Like)) {
            $this->SQL
                ->beginWhereGroup()
                ->orLike($Like, '', 'right')
                ->endWhereGroup();
        }
        $this->SQL
            ->where('u.Deleted', 0);

        $Data = $this->SQL->get()->firstRow();

        return $Data === false ? 0 : $Data->UserCount;
    }

    /**
     *
     *
     * @param array|false $Where
     * @return int
     */
    public function getCountWhere($Where = false) {
        $this->SQL
            ->select('u.UserID', 'count', 'UserCount')
            ->from('User u');

        if (is_array($Where)) {
            $this->SQL->where($Where);
        }

        $Data = $this->SQL
            ->where('u.Deleted', 0)
            ->get()
            ->firstRow();

        return $Data === false ? 0 : $Data->UserCount;
    }

    /**
     * Get a user by ID.
     *
     * @param int $ID The ID of the user.
     * @param string|false $DatasetType Whether to return an array or object.
     * @param array $Options Additional options to affect fetching. Currently unused.
     * @return array|object|false Returns the user or **false** if the user wasn't found.
     */
    public function getID($ID, $DatasetType = false, $Options = []) {
        if (!$ID) {
            return false;
        }
        $DatasetType = $DatasetType ?: DATASET_TYPE_OBJECT;

        // Check page cache, then memcached
        $User = $this->getUserFromCache($ID, 'userid');

        // If not, query DB
        if ($User === Gdn_Cache::CACHEOP_FAILURE) {
            $User = parent::getID($ID, DATASET_TYPE_ARRAY);

            // We want to cache a non-existent user no-matter what.
            if (!$User) {
                $User = null;
            }

            $this->userCache($User, $ID);
        } elseif (!$User) {
            return false;
        }

        // Apply calculated fields
        $this->setCalculatedFields($User);

        // Allow FALSE returns
        if ($User === false || is_null($User)) {
            return false;
        }

        if (is_array($User) && $DatasetType == DATASET_TYPE_OBJECT) {
            $User = (object)$User;
        }

        if (is_object($User) && $DatasetType == DATASET_TYPE_ARRAY) {
            $User = (array)$User;
        }

        $this->EventArguments['LoadedUser'] = &$User;
        $this->fireEvent('AfterGetID');

        return $User;
    }

    /**
     *
     *
     * @param array $IDs
     * @param bool $SkipCacheQuery
     * @return array
     * @throws Exception
     */
    public function getIDs($IDs, $SkipCacheQuery = false) {
        $DatabaseIDs = $IDs;
        $Data = [];

        if (!$SkipCacheQuery) {
            $Keys = [];
            // Make keys for cache query
            foreach ($IDs as $UserID) {
                if (!$UserID) {
                    continue;
                }
                $Keys[] = formatString(self::USERID_KEY, ['UserID' => $UserID]);
            }

            // Query cache layer
            $CacheData = Gdn::cache()->get($Keys);
            if (!is_array($CacheData)) {
                $CacheData = [];
            }

            foreach ($CacheData as $RealKey => $User) {
                if ($User === null) {
                    $ResultUserID = trim(strrchr($RealKey, '.'), '.');
                } else {
                    $ResultUserID = val('UserID', $User);
                }
                $this->setCalculatedFields($User);
                $Data[$ResultUserID] = $User;
            }

            //echo "from cache:\n";
            //print_r($Data);

            $DatabaseIDs = array_diff($DatabaseIDs, array_keys($Data));
            unset($CacheData);
        }

        // Clean out bogus blank entries
        $DatabaseIDs = array_diff($DatabaseIDs, [null, '']);

        // If we are missing any users from cache query, fill em up here
        if (sizeof($DatabaseIDs)) {
            $DatabaseData = $this->SQL->whereIn('UserID', $DatabaseIDs)->getWhere('User')->result(DATASET_TYPE_ARRAY);
            $DatabaseData = Gdn_DataSet::index($DatabaseData, 'UserID');

            //echo "from DB:\n";
            //print_r($DatabaseData);

            foreach ($DatabaseIDs as $ID) {
                if (isset($DatabaseData[$ID])) {
                    $User = $DatabaseData[$ID];
                    $this->userCache($User, $ID);
                    // Apply calculated fields
                    $this->setCalculatedFields($User);
                    $Data[$ID] = $User;
                } else {
                    $User = null;
                    $this->userCache($User, $ID);
                }
            }
        }

        $this->EventArguments['RequestedIDs'] = $IDs;
        $this->EventArguments['LoadedUsers'] = &$Data;
        $this->fireEvent('AfterGetIDs');

        return $Data;
    }

    /**
     *
     *
     * @param bool $Like
     * @param string $OrderFields
     * @param string $OrderDirection
     * @param bool $Limit
     * @param bool $Offset
     * @return Gdn_DataSet
     * @throws Exception
     */
    public function getLike($Like = false, $OrderFields = '', $OrderDirection = 'asc', $Limit = false, $Offset = false) {
        $this->userQuery();
        $this->SQL
            ->join('UserRole ur', "u.UserID = ur.UserID", 'left');

        if (is_array($Like)) {
            $this->SQL
                ->beginWhereGroup()
                ->orLike($Like, '', 'right')
                ->endWhereGroup();
        }

        return $this->SQL
            ->where('u.Deleted', 0)
            ->orderBy($OrderFields, $OrderDirection)
            ->limit($Limit, $Offset)
            ->get();
    }

    /**
     * Retries UserMeta information for a UserID / Key pair.
     *
     * This method takes a $UserID or array of $UserIDs, and a $Key. It converts the
     * $Key to fully qualified format and then queries for the associated value(s). $Key
     * can contain SQL wildcards, in which case multiple results can be returned.
     *
     * If $UserID is an array, the return value will be a multi dimensional array with the first
     * axis containing UserIDs and the second containing fully qualified UserMetaKeys, associated with
     * their values.
     *
     * If $UserID is a scalar, the return value will be a single dimensional array of $UserMetaKey => $Value
     * pairs.
     *
     * @param int $UserID UserID or array of UserIDs.
     * @param string $Key Relative user meta key.
     * @param string $Prefix
     * @param string $Default
     * @return array results or $Default
     */
    public static function getMeta($UserID, $Key, $Prefix = '', $Default = '') {
        $Sql = Gdn::sql()
            ->select('*')
            ->from('UserMeta u');

        if (is_array($UserID)) {
            $Sql->whereIn('u.UserID', $UserID);
        } else {
            $Sql->where('u.UserID', $UserID);
        }

        if (strpos($Key, '%') !== false) {
            $Sql->like('u.Name', $Key, 'none');
        } else {
            $Sql->where('u.Name', $Key);
        }

        $Data = $Sql->get()->resultArray();

        if (is_array($UserID)) {
            $Result = array_fill_keys($UserID, []);
        } else {
            if (strpos($Key, '%') === false) {
                $Result = [stringBeginsWith($Key, $Prefix, false, true) => $Default];
            } else {
                $Result = [];
            }
        }

        foreach ($Data as $Row) {
            $Name = stringBeginsWith($Row['Name'], $Prefix, false, true);

            if (is_array($UserID)) {
                $Result[$Row['UserID']][$Name] = $Row['Value'];
            } else {
                $Result[$Name] = $Row['Value'];
            }
        }

        return $Result;
    }

    /**
     * Get the roles for a user.
     *
     * @param int $userID The user to get the roles for.
     * @return Gdn_DataSet Returns the roles as a dataset (with array values).
     */
    public function getRoles($userID) {
        $userRolesKey = formatString(self::USERROLES_KEY, ['UserID' => $userID]);
        $rolesDataArray = Gdn::cache()->get($userRolesKey);

        if ($rolesDataArray === Gdn_Cache::CACHEOP_FAILURE) {
            $rolesDataArray = $this->SQL->getWhere('UserRole', ['UserID' => $userID])->resultArray();
            $rolesDataArray = array_column($rolesDataArray, 'RoleID');
            // Add result to cache
            $this->userCacheRoles($userID, $rolesDataArray);
        }

        $result = [];
        foreach ($rolesDataArray as $roleID) {
            $result[] = RoleModel::roles($roleID, true);
        }

        return new Gdn_DataSet($result, DATASET_TYPE_ARRAY);
    }

    /**
     *
     *
     * @param int $UserID
     * @param bool $Refresh
     * @return array|object|false
     */
    public function getSession($UserID, $Refresh = false) {
        // Ask for the user. This will check cache first.
        $User = $this->getID($UserID, DATASET_TYPE_OBJECT);

        if (!$User) {
            return false;
        }

        // If we require confirmation and user is not confirmed
        $ConfirmEmail = self::requireConfirmEmail();
        $Confirmed = val('Confirmed', $User);
        if ($ConfirmEmail && !$Confirmed) {
            // Replace permissions with those of the ConfirmEmailRole
            $ConfirmEmailRoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_UNCONFIRMED);
            $RoleModel = new RoleModel();
            $RolePermissions = $RoleModel->getPermissions($ConfirmEmailRoleID);
            $Permissions = UserModel::compilePermissions($RolePermissions);

            // Ensure Confirm Email role can always sign in
            if (!in_array('Garden.SignIn.Allow', $Permissions)) {
                $Permissions[] = 'Garden.SignIn.Allow';
            }

            $User->Permissions = $Permissions;

            // Otherwise normal loadings!
        } else {
            if ($User && ($User->Permissions == '' || Gdn::cache()->activeEnabled())) {
                $User->Permissions = $this->definePermissions($UserID, false);
            }
        }

        // Remove secret info from session
        unset($User->Password, $User->HashMethod);

        return $User;
    }

    /**
     * Retrieve a summary of "safe" user information for external API calls.
     *
     * @param string $OrderFields
     * @param string $OrderDirection
     * @param bool $Limit
     * @param bool $Offset
     * @return array|null
     * @throws Exception
     */
    public function getSummary($OrderFields = '', $OrderDirection = 'asc', $Limit = false, $Offset = false) {
        $this->userQuery(true);
        $Data = $this->SQL
            ->where('u.Deleted', 0)
            ->orderBy($OrderFields, $OrderDirection)
            ->limit($Limit, $Offset)
            ->get();

        // Set corrected PhotoUrls.
        $Result = &$Data->result();
        foreach ($Result as &$Row) {
            if ($Row->Photo && !isUrl($Row->Photo)) {
                $Row->Photo = Gdn_Upload::url($Row->Photo);
            }
        }

        return $Result;
    }

    /**
     * Retrieves a "system user" id that can be used to perform non-real-person tasks.
     * 
     * @return int Returns a user ID.
     */
    public function getSystemUserID() {
        $SystemUserID = c('Garden.SystemUserID');
        if ($SystemUserID) {
            return $SystemUserID;
        }

        $SystemUser = [
            'Name' => t('System'),
            'Photo' => asset('/applications/dashboard/design/images/usericon.png', true),
            'Password' => randomString('20'),
            'HashMethod' => 'Random',
            'Email' => 'system@example.com',
            'DateInserted' => Gdn_Format::toDateTime(),
            'Admin' => '2'
        ];

        $this->EventArguments['SystemUser'] = &$SystemUser;
        $this->fireEvent('BeforeSystemUser');

        $SystemUserID = $this->SQL->insert($this->Name, $SystemUser);

        saveToConfig('Garden.SystemUserID', $SystemUserID);
        return $SystemUserID;
    }

    /**
     * Add points to a user's total.
     *
     * @param int $UserID
     * @param int $Points
     * @param string $Source
     * @param int|false $Timestamp
     * @since 2.1.0
     */
    public static function givePoints($UserID, $Points, $Source = 'Other', $Timestamp = false) {
        if (!$Timestamp === false) {
            $Timestamp = time();
        }

        if (is_array($Source)) {
            $CategoryID = val('CategoryID', $Source, 0);
            $Source = $Source[0];
        } else {
            $CategoryID = 0;
        }

        if ($CategoryID > 0) {
            $CategoryIDs = [$CategoryID, 0];
        } else {
            $CategoryIDs = [$CategoryID];
        }

        foreach ($CategoryIDs as $ID) {
            // Increment source points for the user.
            self::givePointsInternal($UserID, $Points, 'a', $Source, $ID);

            // Increment total points for the user.
            self::givePointsInternal($UserID, $Points, 'w', 'Total', $ID, $Timestamp);
            self::givePointsInternal($UserID, $Points, 'm', 'Total', $ID, $Timestamp);
            self::givePointsInternal($UserID, $Points, 'a', 'Total', $ID, $Timestamp);

            // Increment global daily points.
            self::givePointsInternal(0, $Points, 'd', 'Total', $ID, $Timestamp);
        }

        // Grab the user's total points.
        $Points = Gdn::sql()->getWhere('UserPoints', ['UserID' => $UserID, 'SlotType' => 'a', 'Source' => 'Total', 'CategoryID' => 0])->value('Points');

        Gdn::userModel()->setField($UserID, 'Points', $Points);

        // Fire a give points event.
        Gdn::userModel()->EventArguments['UserID'] = $UserID;
        Gdn::userModel()->EventArguments['CategoryID'] = $CategoryID;
        Gdn::userModel()->EventArguments['Points'] = $Points;
        Gdn::userModel()->fireEvent('GivePoints');
    }

    /**
     * Add points to a user's total in a specific time slot.
     *
     * @param int $UserID
     * @param int $Points
     * @param string $SlotType
     * @param string $Source
     * @param int $CategoryID
     * @param int|false $Timestamp
     * @since 2.1.0
     * @see UserModel::GivePoints()
     */
    private static function givePointsInternal($UserID, $Points, $SlotType, $Source = 'Total', $CategoryID = 0, $Timestamp = false) {
        $TimeSlot = gmdate('Y-m-d', Gdn_Statistics::timeSlotStamp($SlotType, $Timestamp));

        $Px = Gdn::database()->DatabasePrefix;
        $Sql = "insert {$Px}UserPoints (UserID, SlotType, TimeSlot, Source, CategoryID, Points)
         values (:UserID, :SlotType, :TimeSlot, :Source, :CategoryID, :Points)
         on duplicate key update Points = Points + :Points1";

        Gdn::database()->query($Sql, [
            ':UserID' => $UserID,
            ':Points' => $Points,
            ':SlotType' => $SlotType,
            ':Source' => $Source,
            ':CategoryID' => $CategoryID,
            ':TimeSlot' => $TimeSlot,
            ':Points1' => $Points]);
    }

    /**
     * Register a new user.
     *
     * @param array $FormPostValues
     * @param array $Options
     * @return bool|int|string
     */
    public function register($FormPostValues, $Options = []) {
        $FormPostValues['LastIPAddress'] = Gdn::request()->ipAddress();

        // Check for banning first.
        $Valid = BanModel::checkUser($FormPostValues, null, true);
        if (!$Valid) {
            $this->Validation->addValidationResult('UserID', 'Sorry, permission denied.');
        }

        // Throw an event to allow plugins to block the registration.
        unset($this->EventArguments['User']);
        $this->EventArguments['RegisteringUser'] =& $FormPostValues;
        $this->EventArguments['Valid'] =& $Valid;
        $this->fireEvent('BeforeRegister');

        if (!$Valid) {
            return false; // plugin blocked registration
        }
        if (array_key_exists('Gender', $FormPostValues)) {
            $FormPostValues['Gender'] = self::fixGender($FormPostValues['Gender']);
        }

        $Method = strtolower(val('Method', $Options, c('Garden.Registration.Method')));

        switch ($Method) {
            case 'basic':
            case 'captcha': // deprecated
                $UserID = $this->insertForBasic($FormPostValues, val('CheckCaptcha', $Options, true), $Options);
                break;
            case 'approval':
                $UserID = $this->insertForApproval($FormPostValues, $Options);
                break;
            case 'invitation':
                $UserID = $this->insertForInvite($FormPostValues, $Options);
                break;
            case 'closed':
                $UserID = false;
                $this->Validation->addValidationResult('Registration', 'Registration is closed.');
                break;
            default:
                $UserID = $this->insertForBasic($FormPostValues, val('CheckCaptcha', $Options, false), $Options);
                break;
        }

        if ($UserID) {
            $this->EventArguments['UserID'] = $UserID;
            $this->fireEvent('AfterRegister');
        }
        return $UserID;
    }

    /**
     * Remove the photo from a user.
     *
     * @param int $UserID
     */
    public function removePicture($UserID) {
        // Grab the current photo.
        $User = $this->getID($UserID, DATASET_TYPE_ARRAY);
        $Photo = $User['Photo'];

        // Only attempt to delete a physical file, not a URL.
        if (!isUrl($Photo)) {
            $ProfilePhoto = changeBasename($Photo, 'p%s');
            $Upload = new Gdn_Upload();
            $Upload->delete($ProfilePhoto);
        }

        // Wipe the Photo field.
        $this->setField($UserID, 'Photo', null);
    }

    /**
     * Get a user's counter.
     *
     * @param int|string|object $User
     * @param string $Column
     * @return int|false
     */
    public function profileCount($User, $Column) {
        if (is_numeric($User)) {
            $User = $this->SQL->getWhere('User', ['UserID' => $User])->firstRow(DATASET_TYPE_ARRAY);
        } elseif (is_string($User)) {
            $User = $this->SQL->getWhere('User', ['Name' => $User])->firstRow(DATASET_TYPE_ARRAY);
        } elseif (is_object($User)) {
            $User = (array)$User;
        }

        if (!$User) {
            return false;
        }

        if (array_key_exists($Column, $User) && $User[$Column] === null) {
            $UserID = $User['UserID'];
            switch ($Column) {
                case 'CountComments':
                    $Count = $this->SQL->getCount('Comment', ['InsertUserID' => $UserID]);
                    $this->setField($UserID, 'CountComments', $Count);
                    break;
                case 'CountDiscussions':
                    $Count = $this->SQL->getCount('Discussion', ['InsertUserID' => $UserID]);
                    $this->setField($UserID, 'CountDiscussions', $Count);
                    break;
                case 'CountBookmarks':
                    $Count = $this->SQL->getCount('UserDiscussion', ['UserID' => $UserID, 'Bookmarked' => '1']);
                    $this->setField($UserID, 'CountBookmarks', $Count);
                    break;
                default:
                    $Count = false;
                    break;
            }
            return $Count;
        } elseif ($User[$Column]) {
            return $User[$Column];
        } else {
            return false;
        }
    }

    /**
     * Generic save procedure.
     *
     * @param array $FormPostValues The user to save.
     * @param array $Settings Controls certain save functionality.
     *
     * - SaveRoles - Save 'RoleID' field as user's roles. Default false.
     * - HashPassword - Hash the provided password on update. Default true.
     * - FixUnique - Try to resolve conflicts with unique constraints on Name and Email. Default false.
     * - ValidateEmail - Make sure the provided email addresses is formatted properly. Default true.
     * - NoConfirmEmail - Disable email confirmation. Default false.
     *
     */
    public function save($FormPostValues, $Settings = []) {
        // See if the user's related roles should be saved or not.
        $SaveRoles = val('SaveRoles', $Settings);

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Custom Rule: This will make sure that at least one role was selected if saving roles for this user.
        if ($SaveRoles) {
            $this->Validation->addRule('OneOrMoreArrayItemRequired', 'function:ValidateOneOrMoreArrayItemRequired');
            $this->Validation->applyRule('RoleID', 'OneOrMoreArrayItemRequired');
        } else {
            $this->Validation->unapplyRule('RoleID', 'OneOrMoreArrayItemRequired');
        }

        // Make sure that checkbox values are saved as the appropriate value.
        if (array_key_exists('ShowEmail', $FormPostValues)) {
            $FormPostValues['ShowEmail'] = forceBool($FormPostValues['ShowEmail'], '0', '1', '0');
        }

        if (array_key_exists('Banned', $FormPostValues)) {
            $FormPostValues['Banned'] = intval($FormPostValues['Banned']);
        }

        if (array_key_exists('Confirmed', $FormPostValues)) {
            $FormPostValues['Confirmed'] = forceBool($FormPostValues['Confirmed'], '0', '1', '0');
        }

        if (array_key_exists('Verified', $FormPostValues)) {
            $FormPostValues['Verified'] = forceBool($FormPostValues['Verified'], '0', '1', '0');
        }

        unset($FormPostValues['Admin']);

        // Validate the form posted values

        if (array_key_exists('Gender', $FormPostValues)) {
            $FormPostValues['Gender'] = self::fixGender($FormPostValues['Gender']);
        }

        if (array_key_exists('DateOfBirth', $FormPostValues) && $FormPostValues['DateOfBirth'] == '0-00-00') {
            $FormPostValues['DateOfBirth'] = null;
        }

        $UserID = val('UserID', $FormPostValues);
        $User = [];
        $Insert = $UserID > 0 ? false : true;
        if ($Insert) {
            $this->addInsertFields($FormPostValues);
        } else {
            $this->addUpdateFields($FormPostValues);
            $User = $this->getID($UserID, DATASET_TYPE_ARRAY);
            if (!$User) {
                $User = [];
            }

            // Block banning the superadmin or System accounts
            if (val('Admin', $User) == 2 && val('Banned', $FormPostValues)) {
                $this->Validation->addValidationResult('Banned', 'You may not ban a System user.');
            } elseif (val('Admin', $User) && val('Banned', $FormPostValues)) {
                $this->Validation->addValidationResult('Banned', 'You may not ban a user with the Admin flag set.');
            }
        }

        $this->EventArguments['FormPostValues'] = $FormPostValues;
        $this->fireEvent('BeforeSaveValidation');

        $RecordRoleChange = true;

        if ($UserID && val('FixUnique', $Settings)) {
            $UniqueValid = $this->validateUniqueFields(val('Name', $FormPostValues), val('Email', $FormPostValues), $UserID, true);
            if (!$UniqueValid['Name']) {
                unset($FormPostValues['Name']);
            }
            if (!$UniqueValid['Email']) {
                unset($FormPostValues['Email']);
            }
            $UniqueValid = true;
        } else {
            $UniqueValid = $this->validateUniqueFields(val('Name', $FormPostValues), val('Email', $FormPostValues), $UserID);
        }

        // Add & apply any extra validation rules:
        if (array_key_exists('Email', $FormPostValues) && val('ValidateEmail', $Settings, true)) {
            $this->Validation->applyRule('Email', 'Email');
        }

        // AllIPAdresses is stored as a CSV, so handle the case where an array is submitted.
        if (array_key_exists('AllIPAddresses', $FormPostValues) && is_array($FormPostValues['AllIPAddresses'])) {
            $FormPostValues['AllIPAddresses'] = implode(',', $FormPostValues['AllIPAddresses']);
        }

        if ($this->validate($FormPostValues, $Insert) && $UniqueValid) {
            // All fields on the form that need to be validated (including non-schema field rules defined above)
            $Fields = $this->Validation->validationFields();
            $RoleIDs = val('RoleID', $Fields, 0);
            $Username = val('Name', $Fields);
            $Email = val('Email', $Fields);

            // Only fields that are present in the schema
            $Fields = $this->Validation->schemaValidationFields();

            // Remove the primary key from the fields collection before saving.
            unset($Fields[$this->PrimaryKey]);

            if (!$Insert && array_key_exists('Password', $Fields) && val('HashPassword', $Settings, true)) {
                // Encrypt the password for saving only if it won't be hashed in _Insert()
                $PasswordHash = new Gdn_PasswordHash();
                $Fields['Password'] = $PasswordHash->hashPassword($Fields['Password']);
                $Fields['HashMethod'] = 'Vanilla';
            }

            // Check for email confirmation.
            if (self::requireConfirmEmail() && !val('NoConfirmEmail', $Settings)) {
                // Email address has changed
                if (isset($Fields['Email']) && (
                        array_key_exists('Confirmed', $Fields) &&
                        $Fields['Confirmed'] == 0 ||
                        (
                            $UserID == Gdn::session()->UserID &&
                            $Fields['Email'] != Gdn::session()->User->Email &&
                            !Gdn::session()->checkPermission('Garden.Users.Edit')
                        )
                    )
                ) {
                    $Attributes = val('Attributes', Gdn::session()->User);
                    if (is_string($Attributes)) {
                        $Attributes = dbdecode($Attributes);
                    }

                    $ConfirmEmailRoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_UNCONFIRMED);
                    if (!empty($ConfirmEmailRoleID)) {
                        // The confirm email role is set and it exists so go ahead with the email confirmation.
                        $NewKey = randomString(8);
                        $EmailKey = touchValue('EmailKey', $Attributes, $NewKey);
                        $Fields['Attributes'] = dbencode($Attributes);
                        $Fields['Confirmed'] = 0;
                    }
                }
            }

            $this->EventArguments['SaveRoles'] = &$SaveRoles;
            $this->EventArguments['RoleIDs'] = &$RoleIDs;
            $this->EventArguments['Fields'] = &$Fields;
            $this->fireEvent('BeforeSave');
            $User = array_merge($User, $Fields);

            // Check the validation results again in case something was added during the BeforeSave event.
            if (count($this->Validation->results()) == 0) {
                // If the primary key exists in the validated fields and it is a
                // numeric value greater than zero, update the related database row.
                if ($UserID > 0) {
                    // If they are changing the username & email, make sure they aren't
                    // already being used (by someone other than this user)
                    if (val('Name', $Fields, '') != '' || val('Email', $Fields, '') != '') {
                        if (!$this->validateUniqueFields($Username, $Email, $UserID)) {
                            return false;
                        }
                    }

                    if (array_key_exists('Attributes', $Fields) && !is_string($Fields['Attributes'])) {
                        $Fields['Attributes'] = dbencode($Fields['Attributes']);
                    }

                    // Perform save DB operation
                    $this->SQL->put($this->Name, $Fields, [$this->PrimaryKey => $UserID]);

                    // Record activity if the person changed his/her photo.
                    $Photo = val('Photo', $FormPostValues);
                    if ($Photo !== false) {
                        if (val('CheckExisting', $Settings)) {
                            $User = $this->getID($UserID);
                            $OldPhoto = val('Photo', $User);
                        }

                        if (isset($OldPhoto) && $OldPhoto != $Photo) {
                            if (isUrl($Photo)) {
                                $PhotoUrl = $Photo;
                            } else {
                                $PhotoUrl = Gdn_Upload::url(changeBasename($Photo, 'n%s'));
                            }

                            $ActivityModel = new ActivityModel();
                            if ($UserID == Gdn::session()->UserID) {
                                $HeadlineFormat = t('HeadlineFormat.PictureChange', '{RegardingUserID,You} changed {ActivityUserID,your} profile picture.');
                            } else {
                                $HeadlineFormat = t('HeadlineFormat.PictureChange.ForUser', '{RegardingUserID,You} changed the profile picture for {ActivityUserID,user}.');
                            }

                            $ActivityModel->save([
                                'ActivityUserID' => $UserID,
                                'RegardingUserID' => Gdn::session()->UserID,
                                'ActivityType' => 'PictureChange',
                                'HeadlineFormat' => $HeadlineFormat,
                                'Story' => img($PhotoUrl, ['alt' => t('Thumbnail')])
                            ]);
                        }
                    }

                } else {
                    $RecordRoleChange = false;
                    if (!$this->validateUniqueFields($Username, $Email)) {
                        return false;
                    }

                    // Define the other required fields:
                    $Fields['Email'] = $Email;

                    $Fields['Roles'] = $RoleIDs;
                    // Make sure that the user is assigned to one or more roles:
                    $SaveRoles = false;

                    // And insert the new user.
                    $UserID = $this->insertInternal($Fields, $Settings);

                    if ($UserID > 0) {
                        // Report that the user was created.
                        $ActivityModel = new ActivityModel();
                        $ActivityModel->save(
                            [
                            'ActivityType' => 'Registration',
                            'ActivityUserID' => $UserID,
                            'HeadlineFormat' => t('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                                'Story' => t('Welcome Aboard!')],
                            false,
                            ['GroupBy' => 'ActivityTypeID']
                        );

                        // Report the creation for mods.
                        $ActivityModel->save([
                            'ActivityType' => 'Registration',
                            'ActivityUserID' => Gdn::session()->UserID,
                            'RegardingUserID' => $UserID,
                            'NotifyUserID' => ActivityModel::NOTIFY_MODS,
                            'HeadlineFormat' => t('HeadlineFormat.AddUser', '{ActivityUserID,user} added an account for {RegardingUserID,user}.')]);
                    }
                }
                // Now update the role settings if necessary.
                if ($SaveRoles) {
                    // If no RoleIDs were provided, use the system defaults
                    if (!is_array($RoleIDs)) {
                        $RoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);
                    }

                    $this->saveRoles($UserID, $RoleIDs, $RecordRoleChange);
                }

                // Send the confirmation email.
                if (isset($EmailKey)) {
                    if (!is_array($User)) {
                        $User = $this->getID($UserID, DATASET_TYPE_ARRAY);
                    }
                    $this->sendEmailConfirmationEmail($User, true);
                }

                $this->EventArguments['UserID'] = $UserID;
                $this->fireEvent('AfterSave');
            } else {
                $UserID = false;
            }
        } else {
            $UserID = false;
        }

        // Clear cached user data
        if (!$Insert && $UserID) {
            $this->clearCache($UserID, ['user']);
        }

        return $UserID;
    }

    /**
     * Create an admin user account.
     *
     * @param array $FormPostValues
     */
    public function saveAdminUser($FormPostValues) {
        $UserID = 0;

        // Add & apply any extra validation rules:
        $Name = val('Name', $FormPostValues, '');
        $FormPostValues['Email'] = val('Email', $FormPostValues, strtolower($Name.'@'.Gdn_Url::host()));
        $FormPostValues['ShowEmail'] = '0';
        $FormPostValues['TermsOfService'] = '1';
        $FormPostValues['DateOfBirth'] = '1975-09-16';
        $FormPostValues['DateLastActive'] = Gdn_Format::toDateTime();
        $FormPostValues['DateUpdated'] = Gdn_Format::toDateTime();
        $FormPostValues['Gender'] = 'u';
        $FormPostValues['Admin'] = '1';

        $this->addInsertFields($FormPostValues);

        if ($this->validate($FormPostValues, true) === true) {
            $Fields = $this->Validation->schemaValidationFields(); // Only fields that are present in the schema

            // Insert the new user
            $UserID = $this->insertInternal($Fields, ['NoConfirmEmail' => true, 'Setup' => true]);

            if ($UserID > 0) {
                $ActivityModel = new ActivityModel();
                $ActivityModel->save(
                    [
                    'ActivityUserID' => $UserID,
                    'ActivityType' => 'Registration',
                    'HeadlineFormat' => t('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                    'Story' => t('Welcome Aboard!')
                    ],
                    false,
                    ['GroupBy' => 'ActivityTypeID']
                );
            }

            $this->saveRoles($UserID, [16], false);
        }
        return $UserID;
    }

    /**
     *
     *
     * @param int $UserID
     * @param array $RoleIDs
     * @param bool $RecordEvent
     */
    public function saveRoles($UserID, $RoleIDs, $RecordEvent) {
        if (is_string($RoleIDs) && !is_numeric($RoleIDs)) {
            // The $RoleIDs are a comma delimited list of role names.
            $RoleNames = array_map('trim', explode(',', $RoleIDs));
            $RoleIDs = $this->SQL
                ->select('r.RoleID')
                ->from('Role r')
                ->whereIn('r.Name', $RoleNames)
                ->get()->resultArray();
            $RoleIDs = array_column($RoleIDs, 'RoleID');
        }

        if (!is_array($RoleIDs)) {
            $RoleIDs = [$RoleIDs];
        }

        // Get the current roles.
        $OldRoleIDs = [];
        $OldRoleData = $this->SQL
            ->select('ur.RoleID, r.Name')
            ->from('UserRole ur')
            ->join('Role r', 'r.RoleID = ur.RoleID', 'left')
            ->where('ur.UserID', $UserID)
            ->get()
            ->resultArray();

        if ($OldRoleData !== false) {
            $OldRoleIDs = array_column($OldRoleData, 'RoleID');
        }

        // 1a) Figure out which roles to delete.
        $DeleteRoleIDs = [];
        foreach ($OldRoleData as $row) {
            // The role should be deleted if it is an orphan or the user has not been assigned the role.
            if ($row['Name'] === null || !in_array($row['RoleID'], $RoleIDs)) {
                $DeleteRoleIDs[] = $row['RoleID'];
            }
        }

        // 1b) Remove old role associations for this user.
        if (!empty($DeleteRoleIDs)) {
            $this->SQL->whereIn('RoleID', $DeleteRoleIDs)->delete('UserRole', ['UserID' => $UserID]);
        }

        // 2a) Figure out which roles to insert.
        $InsertRoleIDs = array_diff($RoleIDs, $OldRoleIDs);
        // 2b) Insert the new role associations for this user.
        foreach ($InsertRoleIDs as $InsertRoleID) {
            if (is_numeric($InsertRoleID)) {
                $this->SQL->insert('UserRole', ['UserID' => $UserID, 'RoleID' => $InsertRoleID]);
            }
        }

        $this->clearCache($UserID, ['roles', 'permissions']);

        if ($RecordEvent) {
            $User = $this->getID($UserID);

            $OldRoles = [];
            foreach ($DeleteRoleIDs as $deleteRoleID) {
                $role = RoleModel::roles($deleteRoleID);
                $OldRoles[] = val('Name', $role, t('Unknown').' ('.$deleteRoleID.')');
            }

            $NewRoles = [];
            foreach ($InsertRoleIDs as $insertRoleID) {
                $role = RoleModel::roles($insertRoleID);
                $NewRoles[] = val('Name', $role, t('Unknown').' ('.$insertRoleID.')');
            }

            $RemovedRoles = array_diff($OldRoles, $NewRoles);
            $NewRoles = array_diff($NewRoles, $OldRoles);

            foreach ($RemovedRoles as $RoleName) {
                Logger::event(
                    'role_remove',
                    Logger::INFO,
                    "{username} removed {toUsername} from the {role} role.",
                    ['touserid' => $User->UserID, 'toUsername' => $User->Name, 'role' => $RoleName]
                );
            }

            foreach ($NewRoles as $RoleName) {
                Logger::event(
                    'role_add',
                    Logger::INFO,
                    "{username} added {toUsername} to the {role} role.",
                    ['touserid' => $User->UserID, 'toUsername' => $User->Name, 'role' => $RoleName]
                );
            }
        }
    }

    /**
     * Search users.
     *
     * @param array|string $Filter
     * @param string $OrderFields
     * @param string $OrderDirection
     * @param bool $Limit
     * @param bool $Offset
     * @return Gdn_DataSet
     */
    public function search($Filter, $OrderFields = '', $OrderDirection = 'asc', $Limit = false, $Offset = false) {
        $Optimize = false;

        if (is_array($Filter)) {
            $Where = $Filter;
            $Keywords = $Where['Keywords'];
            $Optimize = val('Optimize', $Filter);
            unset($Where['Keywords'], $Where['Optimize']);
        } else {
            $Keywords = $Filter;
        }
        $Keywords = trim($Keywords);

        // Check for an IP address.
        if (preg_match('`\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}`', $Keywords)) {
            $IPAddress = $Keywords;
        } elseif (strtolower($Keywords) == 'banned') {
            $this->SQL->where('u.Banned >', 0);
            $Keywords = '';
        } elseif (preg_match('/^\d+$/', $Keywords)) {
            $UserID = $Keywords;
            $Keywords = '';
        } else {
            // Check to see if the search exactly matches a role name.
            $RoleID = $this->SQL->getWhere('Role', ['Name' => $Keywords])->value('RoleID');
        }

        $this->userQuery();

        if (isset($Where)) {
            $this->SQL->where($Where);
        }

        if (!empty($RoleID)) {
            $this->SQL->join('UserRole ur2', "u.UserID = ur2.UserID and ur2.RoleID = $RoleID");
        } elseif (isset($IPAddress)) {
            $this->SQL
                ->orOp()
                ->beginWhereGroup()
                ->orWhere('u.LastIPAddress', $IPAddress);

            // An or is expensive so only do it if the query isn't optimized.
            if (!$Optimize) {
                $this->SQL->orWhere('u.InsertIPAddress', $IPAddress);
            }

            $this->SQL->endWhereGroup();
        } elseif (isset($UserID)) {
            $this->SQL->where('u.UserID', $UserID);
        } elseif ($Keywords) {
            if ($Optimize) {
                // An optimized search should only be done against name OR email.
                if (strpos($Keywords, '@') !== false) {
                    $this->SQL->like('u.Email', $Keywords, 'right');
                } else {
                    $this->SQL->like('u.Name', $Keywords, 'right');
                }
            } else {
                // Search on the user table.
                $Like = ['u.Name' => $Keywords, 'u.Email' => $Keywords];

                $this->SQL
                    ->orOp()
                    ->beginWhereGroup()
                    ->orLike($Like, '', 'right')
                    ->endWhereGroup();
            }
        }

        // Optimized searches need at least some criteria before performing a query.
        if ($Optimize && $this->SQL->whereCount() == 0 && empty($RoleID)) {
            $this->SQL->reset();
            return new Gdn_DataSet([]);
        }

        $Data = $this->SQL
            ->where('u.Deleted', 0)
            ->orderBy($OrderFields, $OrderDirection)
            ->limit($Limit, $Offset)
            ->get();

        $Result = &$Data->result();

        foreach ($Result as &$Row) {
            if ($Row->Photo && !isUrl($Row->Photo)) {
                $Row->Photo = Gdn_Upload::url($Row->Photo);
            }

            $Row->Attributes = dbdecode($Row->Attributes);
            $Row->Preferences = dbdecode($Row->Preferences);
        }

        return $Data;
    }

    /**
     * Count search results.
     *
     * @param array|string $Filter
     * @return int
     */
    public function searchCount($Filter = '') {
        if (is_array($Filter)) {
            $Where = $Filter;
            $Keywords = $Where['Keywords'];
            unset($Where['Keywords'], $Where['Optimize']);
        } else {
            $Keywords = $Filter;
        }
        $Keywords = trim($Keywords);

        // Check to see if the search exactly matches a role name.
        $RoleID = false;
        if (strtolower($Keywords) == 'banned') {
            $this->SQL->where('u.Banned >', 0);
        } else {
            $RoleID = $this->SQL->getWhere('Role', ['Name' => $Keywords])->value('RoleID');
        }

        if (isset($Where)) {
            $this->SQL->where($Where);
        }

        $this->SQL
            ->select('u.UserID', 'count', 'UserCount')
            ->from('User u');

        if ($RoleID) {
            $this->SQL->join('UserRole ur2', "u.UserID = ur2.UserID and ur2.RoleID = $RoleID");
        } else {
            // Search on the user table.
            $Like = trim($Keywords) == '' ? false : ['u.Name' => $Keywords, 'u.Email' => $Keywords];

            if (is_array($Like)) {
                $this->SQL
                    ->orOp()
                    ->beginWhereGroup()
                    ->orLike($Like, '', 'right')
                    ->endWhereGroup();
            }
        }

        $this->SQL
            ->where('u.Deleted', 0);

        $Data = $this->SQL->get()->firstRow();

        return $Data === false ? 0 : $Data->UserCount;
    }

    /**
     *
     *
     * @return string
     */
    public static function signinLabelCode() {
        return UserModel::noEmail() ? 'Username' : 'Email/Username';
    }

    /**
     * A simple search for tag queries.
     *
     * @param string $Search
     * @param int $Limit
     * @since 2.2
     */
    public function tagSearch($Search, $Limit = 10) {
        $Search = trim(str_replace(['%', '_'], ['\%', '\_'], $Search));

        return $this->SQL
            ->select('UserID', '', 'id')
            ->select('Name', '', 'name')
            ->from('User')
            ->like('Name', $Search, 'right')
            ->where('Deleted', 0)
            ->orderBy('CountComments', 'desc')
            ->limit($Limit)
            ->get()
            ->resultArray();
    }

    /**
     * To be used for invitation registration.
     *
     * @param array $FormPostValues
     * @param array $Options
     * @return int UserID.
     */
    public function insertForInvite($FormPostValues, $Options = []) {
        $RoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);
        if (!is_array($RoleIDs) || count($RoleIDs) == 0) {
            throw new Exception(t('The default role has not been configured.'), 400);
        }

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules:
        $this->Validation->applyRule('Email', 'Email');

        // Make sure that the checkbox val for email is saved as the appropriate enum
        if (array_key_exists('ShowEmail', $FormPostValues)) {
            $FormPostValues['ShowEmail'] = forceBool($FormPostValues['ShowEmail'], '0', '1', '0');
        }

        if (array_key_exists('Banned', $FormPostValues)) {
            $FormPostValues['Banned'] = forceBool($FormPostValues['Banned'], '0', '1', '0');
        }

        $this->addInsertFields($FormPostValues);

        // Make sure that the user has a valid invitation code, and also grab
        // the user's email from the invitation:
        $InvitationCode = val('InvitationCode', $FormPostValues, '');

        $Invitation = $this->SQL->getWhere('Invitation', ['Code' => $InvitationCode])->firstRow();

        // If there is no invitation then bail out.
        if (!$Invitation) {
            $this->Validation->addValidationResult('InvitationCode', 'Invitation not found.');
            return false;
        }

        // Get expiration date in timestamp. If nothing set, grab config default.
        $InviteExpiration = $Invitation->DateExpires;
        if ($InviteExpiration != null) {
            $InviteExpiration = Gdn_Format::toTimestamp($InviteExpiration);
        } else {
            $DefaultExpire = '1 week';
            $InviteExpiration = strtotime(c('Garden.Registration.InviteExpiration', '1 week'), Gdn_Format::toTimestamp($Invitation->DateInserted));
            if ($InviteExpiration === false) {
                $InviteExpiration = strtotime($DefaultExpire);
            }
        }

        if ($InviteExpiration <= time()) {
            $this->Validation->addValidationResult('DateExpires', 'The invitation has expired.');
        }

        $InviteUserID = $Invitation->InsertUserID;
        $FormPostValues['Email'] = $Invitation->Email;

        if ($this->validate($FormPostValues, true)) {
            // Check for spam.
            $Spam = SpamModel::isSpam('Registration', $FormPostValues);
            if ($Spam) {
                $this->Validation->addValidationResult('Spam', 'You are not allowed to register at this time.');
                return;
            }

            $Fields = $this->Validation->validationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
            $Username = val('Name', $Fields);
            $Email = val('Email', $Fields);
            $Fields = $this->Validation->schemaValidationFields(); // Only fields that are present in the schema
            unset($Fields[$this->PrimaryKey]);

            // Make sure the username & email aren't already being used
            if (!$this->validateUniqueFields($Username, $Email)) {
                return false;
            }

            // Define the other required fields:
            if ($InviteUserID > 0) {
                $Fields['InviteUserID'] = $InviteUserID;
            }

            // And insert the new user.
            if (!isset($Options['NoConfirmEmail'])) {
                $Options['NoConfirmEmail'] = true;
            }

            // Use RoleIDs from Invitation table, if any. They are stored as a
            // serialized array of the Role IDs.
            $InvitationRoleIDs = $Invitation->RoleIDs;
            if (strlen($InvitationRoleIDs)) {
                $InvitationRoleIDs = dbdecode($InvitationRoleIDs);

                if (is_array($InvitationRoleIDs)
                    && count(array_filter($InvitationRoleIDs))
                ) {
                    // Overwrite default RoleIDs set at top of method.
                    $RoleIDs = $InvitationRoleIDs;
                }
            }

            $Fields['Roles'] = $RoleIDs;
            $UserID = $this->insertInternal($Fields, $Options);

            // Associate the new user id with the invitation (so it cannot be used again)
            $this->SQL
                ->update('Invitation')
                ->set('AcceptedUserID', $UserID)
                ->where('InvitationID', $Invitation->InvitationID)
                ->put();

            // Report that the user was created.
            $ActivityModel = new ActivityModel();
            $ActivityModel->save(
                [
                'ActivityUserID' => $UserID,
                'ActivityType' => 'Registration',
                'HeadlineFormat' => t('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                'Story' => t('Welcome Aboard!')
                ],
                false,
                ['GroupBy' => 'ActivityTypeID']
            );
        } else {
            $UserID = false;
        }
        return $UserID;
    }

    /**
     * To be used for approval registration.
     *
     * @param array $FormPostValues
     * @param array $Options
     * @return int UserID.
     */
    public function insertForApproval($FormPostValues, $Options = []) {
        $RoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_APPLICANT);
        if (empty($RoleIDs)) {
            throw new Exception(t('The default role has not been configured.'), 400);
        }

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules:
        $this->Validation->applyRule('Email', 'Email');

        // Make sure that the checkbox val for email is saved as the appropriate enum
        if (array_key_exists('ShowEmail', $FormPostValues)) {
            $FormPostValues['ShowEmail'] = forceBool($FormPostValues['ShowEmail'], '0', '1', '0');
        }

        if (array_key_exists('Banned', $FormPostValues)) {
            $FormPostValues['Banned'] = forceBool($FormPostValues['Banned'], '0', '1', '0');
        }

        $this->addInsertFields($FormPostValues);

        if ($this->validate($FormPostValues, true)) {
            // Check for spam.
            $Spam = SpamModel::isSpam('Registration', $FormPostValues);
            if ($Spam) {
                $this->Validation->addValidationResult('Spam', 'You are not allowed to register at this time.');
                return;
            }

            $Fields = $this->Validation->validationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
            $Username = val('Name', $Fields);
            $Email = val('Email', $Fields);
            $Fields = $this->Validation->schemaValidationFields(); // Only fields that are present in the schema
            unset($Fields[$this->PrimaryKey]);

            if (!$this->validateUniqueFields($Username, $Email)) {
                return false;
            }

            // If in Captcha registration mode, check the captcha value.
            if (val('CheckCaptcha', $Options, true) && Captcha::enabled()) {
                $captchaIsValid = Captcha::validate();
                if ($captchaIsValid !== true) {
                    $this->Validation->addValidationResult('Garden.Registration.CaptchaPublicKey', 'The captcha was not completed correctly. Please try again.');
                    return false;
                }
            }

            // Define the other required fields:
            $Fields['Email'] = $Email;
            $Fields['Roles'] = (array)$RoleIDs;

            // And insert the new user
            $UserID = $this->insertInternal($Fields, $Options);
        } else {
            $UserID = false;
        }
        return $UserID;
    }

    /**
     * To be used for basic registration, and captcha registration.
     *
     * @param array $FormPostValues
     * @param bool $CheckCaptcha
     * @param array $Options
     * @return bool|int|string
     * @throws Exception
     */
    public function insertForBasic($FormPostValues, $CheckCaptcha = true, $Options = []) {
        $RoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);
        if (!is_array($RoleIDs) || count($RoleIDs) == 0) {
            throw new Exception(t('The default role has not been configured.'), 400);
        }

        if (val('SaveRoles', $Options)) {
            $RoleIDs = val('RoleID', $FormPostValues);
        }

        $UserID = false;

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules.
        if (val('ValidateEmail', $Options, true)) {
            $this->Validation->applyRule('Email', 'Email');
        }

        // TODO: DO I NEED THIS?!
        // Make sure that the checkbox val for email is saved as the appropriate enum
        if (array_key_exists('ShowEmail', $FormPostValues)) {
            $FormPostValues['ShowEmail'] = forceBool($FormPostValues['ShowEmail'], '0', '1', '0');
        }

        if (array_key_exists('Banned', $FormPostValues)) {
            $FormPostValues['Banned'] = forceBool($FormPostValues['Banned'], '0', '1', '0');
        }

        $this->addInsertFields($FormPostValues);

        if ($this->validate($FormPostValues, true) === true) {
            $Fields = $this->Validation->validationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
            $Username = val('Name', $Fields);
            $Email = val('Email', $Fields);
            $Fields = $this->Validation->schemaValidationFields(); // Only fields that are present in the schema
            $Fields['Roles'] = $RoleIDs;
            unset($Fields[$this->PrimaryKey]);

            // If in Captcha registration mode, check the captcha value.
            if ($CheckCaptcha && Captcha::enabled()) {
                $captchaIsValid = Captcha::validate();
                if ($captchaIsValid !== true) {
                    $this->Validation->addValidationResult('Garden.Registration.CaptchaPublicKey', 'The captcha was not completed correctly. Please try again.');
                    return false;
                }
            }

            if (!$this->validateUniqueFields($Username, $Email)) {
                return false;
            }

            // Check for spam.
            if (val('ValidateSpam', $Options, true)) {
                $ValidateSpam = $this->validateSpamRegistration($FormPostValues);
                if ($ValidateSpam !== true) {
                    return $ValidateSpam;
                }
            }

            // Define the other required fields:
            $Fields['Email'] = $Email;

            // And insert the new user
            $UserID = $this->insertInternal($Fields, $Options);
            if ($UserID > 0 && !val('NoActivity', $Options)) {
                $ActivityModel = new ActivityModel();
                $ActivityModel->save(
                    [
                    'ActivityUserID' => $UserID,
                    'ActivityType' => 'Registration',
                    'HeadlineFormat' => t('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                    'Story' => t('Welcome Aboard!')
                    ],
                    false,
                    ['GroupBy' => 'ActivityTypeID']
                );
            }
        }
        return $UserID;
    }

    /**
     * Parent override.
     *
     * @param array &$Fields
     */
    public function addInsertFields(&$Fields) {
        $this->defineSchema();

        // Set the hour offset based on the client's clock.
        $ClientHour = val('ClientHour', $Fields, '');
        if (is_numeric($ClientHour) && $ClientHour >= 0 && $ClientHour < 24) {
            $HourOffset = $ClientHour - date('G', time());
            $Fields['HourOffset'] = $HourOffset;
        }

        // Set some required dates.
        $Now = Gdn_Format::toDateTime();
        $Fields[$this->DateInserted] = $Now;
        touchValue('DateFirstVisit', $Fields, $Now);
        $Fields['DateLastActive'] = $Now;
        $Fields['InsertIPAddress'] = Gdn::request()->ipAddress();
        $Fields['LastIPAddress'] = Gdn::request()->ipAddress();
    }

    /**
     * Updates visit level information such as date last active and the user's ip address.
     *
     * @param int $UserID
     * @param string|int|float $ClientHour
     */
    public function updateVisit($UserID, $ClientHour = false) {
        $UserID = (int)$UserID;
        if (!$UserID) {
            throw new Exception('A valid User ID is required.');
        }

        $User = Gdn::userModel()->getID($UserID, DATASET_TYPE_ARRAY);

        $Fields = [];

        if (Gdn_Format::toTimestamp($User['DateLastActive']) < strtotime('5 minutes ago')) {
            // We only update the last active date once every 5 minutes to cut down on DB activity.
            $Fields['DateLastActive'] = Gdn_Format::toDateTime();
        }

        // Update session level information if necessary.
        if ($UserID == Gdn::session()->UserID) {
            $IP = Gdn::request()->ipAddress();
            $Fields['LastIPAddress'] = $IP;

            if (Gdn::session()->newVisit()) {
                $Fields['CountVisits'] = val('CountVisits', $User, 0) + 1;
                $this->fireEvent('Visit');
            }
        }

        // Generate the AllIPs field.
        $AllIPs = val('AllIPAddresses', $User, []);
        if (is_string($AllIPs)) {
            $AllIPs = explode(',', $AllIPs);
            setValue('AllIPAddresses', $User, $AllIPs);
        }
        if (!is_array($AllIPs)) {
            $AllIPs = [];
        }
        if ($IP = val('InsertIPAddress', $User)) {
            array_unshift($AllIPs, forceIPv4($IP));
        }
        if ($IP = val('LastIPAddress', $User)) {
            array_unshift($AllIPs, $IP);
        }
        // This will be a unique list of IPs, most recently used first. array_unique keeps the first key found.
        $AllIPs = array_unique($AllIPs);
        $Fields['AllIPAddresses'] = $AllIPs;

        // Set the hour offset based on the client's clock.
        if (is_numeric($ClientHour) && $ClientHour >= 0 && $ClientHour < 24) {
            $HourOffset = $ClientHour - date('G', time());
            $Fields['HourOffset'] = $HourOffset;
        }

        // See if the fields have changed.
        $Set = [];
        foreach ($Fields as $Name => $Value) {
            if (val($Name, $User) != $Value) {
                $Set[$Name] = $Value;
            }
        }

        if (!empty($Set)) {
            $this->EventArguments['Fields'] = &$Set;
            $this->fireEvent('UpdateVisit');

            $this->setField($UserID, $Set);
        }

        if ($User['LastIPAddress'] != $Fields['LastIPAddress']) {
            $User = $this->getID($UserID, DATASET_TYPE_ARRAY);
            if (!BanModel::checkUser($User, null, true, $Bans)) {
                $BanModel = new BanModel();
                $Ban = array_pop($Bans);
                $BanModel->saveUser($User, true, $Ban);
                $BanModel->setCounts($Ban);
            }
        }
    }

    /**
     * Validate submitted user data.
     *
     * @param array $FormPostValues
     * @param bool $Insert
     * @return bool|array
     */
    public function validate($FormPostValues, $Insert = false) {
        $this->defineSchema();

        if (self::noEmail()) {
            // Remove the email requirement.
            $this->Validation->unapplyRule('Email', 'Required');
        }

        if (!$Insert && !isset($FormPostValues['Name'])) {
            $this->Validation->unapplyRule('Name');
        }

        return $this->Validation->validate($FormPostValues, $Insert);
    }

    /**
     * Validate User Credential.
     *
     * Fetches a user row by email (or name) and compare the password.
     * If the password was not stored as a blowfish hash, the password will be saved again.
     * Return the user's id, admin status and attributes.
     *
     * @param string $Email
     * @param string $Password
     * @return object|false Returns the user matching the credentials or **false** if the user doesn't validate.
     */
    public function validateCredentials($Email = '', $ID = 0, $Password) {
        $this->EventArguments['Credentials'] = ['Email' => $Email, 'ID' => $ID, 'Password' => $Password];
        $this->fireEvent('BeforeValidateCredentials');

        if (!$Email && !$ID) {
            throw new Exception('The email or id is required');
        }

        try {
            $this->SQL->select('UserID, Name, Attributes, Admin, Password, HashMethod, Deleted, Banned')
                ->from('User');

            if ($ID) {
                $this->SQL->where('UserID', $ID);
            } else {
                if (strpos($Email, '@') > 0) {
                    $this->SQL->where('Email', $Email);
                } else {
                    $this->SQL->where('Name', $Email);
                }
            }

            $DataSet = $this->SQL->get();
        } catch (Exception $Ex) {
            $this->SQL->reset();

            // Try getting the user information without the new fields.
            $this->SQL->select('UserID, Name, Attributes, Admin, Password')
                ->from('User');

            if ($ID) {
                $this->SQL->where('UserID', $ID);
            } else {
                if (strpos($Email, '@') > 0) {
                    $this->SQL->where('Email', $Email);
                } else {
                    $this->SQL->where('Name', $Email);
                }
            }

            $DataSet = $this->SQL->get();
        }

        if ($DataSet->numRows() < 1) {
            return false;
        }

        $UserData = $DataSet->firstRow();
        // Check for a deleted user.
        if (val('Deleted', $UserData)) {
            return false;
        }

        $PasswordHash = new Gdn_PasswordHash();
        $HashMethod = val('HashMethod', $UserData);
        if (!$PasswordHash->checkPassword($Password, $UserData->Password, $HashMethod, $UserData->Name)) {
            return false;
        }

        if ($PasswordHash->Weak || ($HashMethod && strcasecmp($HashMethod, 'Vanilla') != 0)) {
            $Pw = $PasswordHash->hashPassword($Password);
            $this->SQL->update('User')
                ->set('Password', $Pw)
                ->set('HashMethod', 'Vanilla')
                ->where('UserID', $UserData->UserID)
                ->put();
        }

        $UserData->Attributes = dbdecode($UserData->Attributes);
        return $UserData;
    }

    /**
     *
     *
     * @param array $User
     * @return bool|string
     * @since 2.1
     */
    public function validateSpamRegistration($User) {
        $DiscoveryText = val('DiscoveryText', $User);
        $Log = validateRequired($DiscoveryText);
        $Spam = SpamModel::isSpam('Registration', $User, ['Log' => $Log]);

        if ($Spam) {
            if ($Log) {
                // The user entered discovery text.
                return self::REDIRECT_APPROVE;
            } else {
                $this->Validation->addValidationResult('DiscoveryText', 'Tell us why you want to join!');
                return false;
            }
        }
        return true;
    }

    /**
     * Checks to see if $Username and $Email are already in use by another member.
     *
     * @param string $Username
     * @param string $Email
     * @param string $UserID
     * @param bool $Return
     * @return array|bool
     */
    public function validateUniqueFields($Username, $Email, $UserID = '', $Return = false) {
        $Valid = true;
        $Where = [];
        if (is_numeric($UserID)) {
            $Where['UserID <> '] = $UserID;
        }

        $Result = ['Name' => true, 'Email' => true];

        // Make sure the username & email aren't already being used
        if (c('Garden.Registration.NameUnique', true) && $Username) {
            $Where['Name'] = $Username;
            $TestData = $this->getWhere($Where);
            if ($TestData->numRows() > 0) {
                $Result['Name'] = false;
                $Valid = false;
            }
            unset($Where['Name']);
        }

        if (c('Garden.Registration.EmailUnique', true) && $Email) {
            $Where['Email'] = $Email;
            $TestData = $this->getWhere($Where);
            if ($TestData->numRows() > 0) {
                $Result['Email'] = false;
                $Valid = false;
            }
        }

        if ($Return) {
            return $Result;
        } else {
            if (!$Result['Name']) {
                $this->Validation->addValidationResult('Name', 'The name you entered is already in use by another member.');
            }
            if (!$Result['Email']) {
                $this->Validation->addValidationResult('Email', 'The email you entered is in use by another member.');
            }
            return $Valid;
        }
    }

    /**
     * Approve a membership applicant.
     *
     * @param int $UserID
     * @param string $Email
     * @return bool
     * @throws Exception
     */
    public function approve($UserID, $Email) {
        $applicantRoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_APPLICANT);

        // Make sure the $UserID is an applicant
        $RoleData = $this->getRoles($UserID);
        if ($RoleData->numRows() == 0) {
            throw new Exception(t('ErrorRecordNotFound'));
        } else {
            $AppRoles = $RoleData->result(DATASET_TYPE_ARRAY);
            $ApplicantFound = false;
            foreach ($AppRoles as $AppRole) {
                if (in_array(val('RoleID', $AppRole), $applicantRoleIDs)) {
                    $ApplicantFound = true;
                }
            }
        }

        if ($ApplicantFound) {
            // Retrieve the default role(s) for new users
            $RoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);

            // Wipe out old & insert new roles for this user
            $this->saveRoles($UserID, $RoleIDs, false);

            // Send out a notification to the user
            $User = $this->getID($UserID);
            if ($User) {
                $Email = new Gdn_Email();
                $Email->subject(sprintf(t('[%1$s] Membership Approved'), c('Garden.Title')));
                $Email->to($User->Email);

                $message = sprintf(t('Hello %s!'), val('Name', $User)).' '.t('You have been approved for membership.');
                $emailTemplate = $Email->getEmailTemplate()
                    ->setMessage($message)
                    ->setButton(externalUrl(signInUrl()), t('Sign In Now'))
                    ->setTitle(t('Membership Approved'));

                $Email->setEmailTemplate($emailTemplate);
                $Email->send();

                // Report that the user was approved.
                $ActivityModel = new ActivityModel();
                $ActivityModel->save(
                    [
                    'ActivityUserID' => $UserID,
                    'ActivityType' => 'Registration',
                    'HeadlineFormat' => t('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                    'Story' => t('Welcome Aboard!')
                    ],
                    false,
                    ['GroupBy' => 'ActivityTypeID']
                );

                // Report the approval for moderators.
                $ActivityModel->save(
                    [
                    'ActivityType' => 'Registration',
                    'ActivityUserID' => Gdn::session()->UserID,
                    'RegardingUserID' => $UserID,
                    'NotifyUserID' => ActivityModel::NOTIFY_MODS,
                        'HeadlineFormat' => t('HeadlineFormat.RegistrationApproval', '{ActivityUserID,user} approved the applications for {RegardingUserID,user}.')],
                    false,
                    ['GroupBy' => ['ActivityTypeID', 'ActivityUserID']]
                );

                Gdn::userModel()->saveAttribute($UserID, 'ApprovedByUserID', Gdn::session()->UserID);
            }


        }
        return true;
    }

    /**
     * Delete a user.
     *
     * {@inheritdoc}
     */
    public function delete($where = [], $options = []) {
        if (is_numeric($where)) {
            deprecated('UserModel->delete(int)', 'UserModel->deleteID(int)');

            $result = $this->deleteID($where, $options);
            return $result;
        }

        throw new \BadMethodCallException("UserModel->delete() is not supported.", 400);
    }

    /**
     * Delete a single user.
     *
     * @param int $userID The user to delete.
     * @param array $options See {@link UserModel::deleteContent()}, and {@link UserModel::getDelete()}.
     */
    public function deleteID($userID, $options = []) {
        if ($userID == $this->getSystemUserID()) {
            $this->Validation->addValidationResult('', 'You cannot delete the system user.');
            return false;
        }

        $Content = [];

        // Remove shared authentications.
        $this->getDelete('UserAuthentication', ['UserID' => $userID], $Content);

        // Remove role associations.
        $this->getDelete('UserRole', ['UserID' => $userID], $Content);

        $this->deleteContent($userID, $options, $Content);

        // Remove the user's information
        $this->SQL->update('User')
            ->set([
                'Name' => t('[Deleted User]'),
                'Photo' => null,
                'Password' => randomString('10'),
                'About' => '',
                'Email' => 'user_'.$userID.'@deleted.email',
                'ShowEmail' => '0',
                'Gender' => 'u',
                'CountVisits' => 0,
                'CountInvitations' => 0,
                'CountNotifications' => 0,
                'InviteUserID' => null,
                'DiscoveryText' => '',
                'Preferences' => null,
                'Permissions' => null,
                'Attributes' => dbencode(['State' => 'Deleted']),
                'DateSetInvitations' => null,
                'DateOfBirth' => null,
                'DateUpdated' => Gdn_Format::toDateTime(),
                'HourOffset' => '0',
                'Score' => null,
                'Admin' => 0,
                'Deleted' => 1
            ])
            ->where('UserID', $userID)
            ->put();

        // Remove user's cache rows
        $this->clearCache($userID);

        return true;
    }

    /**
     * Delete a user's content across many contexts.
     *
     * @param int $UserID
     * @param array $Options
     * @param array $Content
     * @return bool|int
     */
    public function deleteContent($UserID, $Options = [], $Content = []) {
        $Log = val('Log', $Options);
        if ($Log === true) {
            $Log = 'Delete';
        }

        $Result = false;

        // Fire an event so applications can remove their associated user data.
        $this->EventArguments['UserID'] = $UserID;
        $this->EventArguments['Options'] = $Options;
        $this->EventArguments['Content'] = &$Content;
        $this->fireEvent('BeforeDeleteUser');

        $User = $this->getID($UserID, DATASET_TYPE_ARRAY);

        if (!$Log) {
            $Content = null;
        }

        // Remove invitations
        $this->getDelete('Invitation', ['InsertUserID' => $UserID], $Content);
        $this->getDelete('Invitation', ['AcceptedUserID' => $UserID], $Content);

        // Remove activities
        $this->getDelete('Activity', ['InsertUserID' => $UserID], $Content);

        // Remove activity comments.
        $this->getDelete('ActivityComment', ['InsertUserID' => $UserID], $Content);

        // Remove comments in moderation queue
        $this->getDelete('Log', ['RecordUserID' => $UserID, 'Operation' => 'Pending'], $Content);

        // Clear out information on the user.
        $this->setField($UserID, [
            'About' => null,
            'Title' => null,
            'Location' => null]);

        if ($Log) {
            $User['_Data'] = $Content;
            unset($Content); // in case data gets copied

            $Result = LogModel::insert($Log, 'User', $User, val('LogOptions', $Options, []));
        }

        return $Result;
    }

    /**
     * Decline a user's application to join the forum.
     *
     * @param int $UserID
     * @return bool
     * @throws Exception
     */
    public function decline($UserID) {
        $applicantRoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_APPLICANT);

        // Make sure the user is an applicant
        $RoleData = $this->getRoles($UserID);
        if ($RoleData->numRows() == 0) {
            throw new Exception(t('ErrorRecordNotFound'));
        } else {
            $AppRoles = $RoleData->result(DATASET_TYPE_ARRAY);
            $ApplicantFound = false;
            foreach ($AppRoles as $AppRole) {
                if (in_array(val('RoleID', $AppRole), $applicantRoleIDs)) {
                    $ApplicantFound = true;
                }
            }
        }

        if ($ApplicantFound) {
            $this->delete($UserID);
        }
        return true;
    }

    /**
     * Get number of available invites a user has.
     *
     * @param int $UserID
     * @return int
     */
    public function getInvitationCount($UserID) {
        // If this user is master admin, they should have unlimited invites.
        if ($this->SQL
                ->select('UserID')
                ->from('User')
                ->where('UserID', $UserID)
                ->where('Admin', '1')
                ->get()
                ->numRows() > 0
        ) {
            return -1;
        }

        // Get the Registration.InviteRoles settings:
        $InviteRoles = Gdn::config('Garden.Registration.InviteRoles', []);
        if (!is_array($InviteRoles) || count($InviteRoles) == 0) {
            return 0;
        }

        // Build an array of roles that can send invitations
        $CanInviteRoles = [];
        foreach ($InviteRoles as $RoleID => $Invites) {
            if ($Invites > 0 || $Invites == -1) {
                $CanInviteRoles[] = $RoleID;
            }
        }

        if (count($CanInviteRoles) == 0) {
            return 0;
        }

        // See which matching roles the user has
        $UserRoleData = $this->SQL->select('RoleID')
            ->from('UserRole')
            ->where('UserID', $UserID)
            ->whereIn('RoleID', $CanInviteRoles)
            ->get();

        if ($UserRoleData->numRows() == 0) {
            return 0;
        }

        // Define the maximum number of invites the user is allowed to send
        $InviteCount = 0;
        foreach ($UserRoleData->result() as $UserRole) {
            $Count = $InviteRoles[$UserRole->RoleID];
            if ($Count == -1) {
                $InviteCount = -1;
            } elseif ($InviteCount != -1 && $Count > $InviteCount) {
                $InviteCount = $Count;
            }
        }

        // If the user has unlimited invitations, return that value
        if ($InviteCount == -1) {
            return -1;
        }

        // Get the user's current invitation settings from their profile
        $User = $this->SQL->select('CountInvitations, DateSetInvitations')
            ->from('User')
            ->where('UserID', $UserID)
            ->get()
            ->firstRow();

        // If CountInvitations is null (ie. never been set before) or it is a new month since the DateSetInvitations
        if ($User->CountInvitations == '' || is_null($User->DateSetInvitations) || Gdn_Format::date($User->DateSetInvitations, '%m %Y') != Gdn_Format::date('', '%m %Y')) {
            // Reset CountInvitations and DateSetInvitations
            $this->SQL->put(
                $this->Name,
                [
                    'CountInvitations' => $InviteCount,
                    'DateSetInvitations' => Gdn_Format::date('', '%Y-%m-01') // The first day of this month
                ],
                ['UserID' => $UserID]
            );
            return $InviteCount;
        } else {
            // Otherwise return CountInvitations
            return $User->CountInvitations;
        }
    }

    /**
     * Get rows from a table then delete them.
     *
     * @param string $Table The name of the table.
     * @param array $Where The where condition for the delete.
     * @param array $Data The data to put the result.
     * @since 2.1
     */
    public function getDelete($Table, $Where, &$Data) {
        if (is_array($Data)) {
            // Grab the records.
            $Result = $this->SQL->getWhere($Table, $Where)->resultArray();

            if (empty($Result)) {
                return;
            }

            // Put the records in the result array.
            if (isset($Data[$Table])) {
                $Data[$Table] = array_merge($Data[$Table], $Result);
            } else {
                $Data[$Table] = $Result;
            }
        }

        $this->SQL->delete($Table, $Where);
    }

    /**
     * Reduces the user's CountInvitations value by the specified amount.
     *
     * @param int $UserID The unique id of the user being affected.
     * @param int $ReduceBy The number to reduce CountInvitations by.
     */
    public function reduceInviteCount($UserID, $ReduceBy = 1) {
        $CurrentCount = $this->getInvitationCount($UserID);

        // Do not reduce if the user has unlimited invitations
        if ($CurrentCount == -1) {
            return true;
        }

        // Do not reduce the count below zero.
        if ($ReduceBy > $CurrentCount) {
            $ReduceBy = $CurrentCount;
        }

        $this->SQL->update($this->Name)
            ->set('CountInvitations', 'CountInvitations - '.$ReduceBy, false)
            ->where('UserID', $UserID)
            ->put();
    }

    /**
     * Increases the user's CountInvitations value by the specified amount.
     *
     * @param int $UserID The unique id of the user being affected.
     * @param int $IncreaseBy The number to increase CountInvitations by.
     */
    public function increaseInviteCount($UserID, $IncreaseBy = 1) {
        $CurrentCount = $this->getInvitationCount($UserID);

        // Do not alter if the user has unlimited invitations
        if ($CurrentCount == -1) {
            return true;
        }

        $this->SQL->update($this->Name)
            ->set('CountInvitations', 'CountInvitations + '.$IncreaseBy, false)
            ->where('UserID', $UserID)
            ->put();
    }

    /**
     * Saves the user's About field.
     *
     * @param int $UserID The UserID to save.
     * @param string $About The about message being saved.
     */
    public function saveAbout($UserID, $About) {
        $About = substr($About, 0, 1000);
        $this->setField($UserID, 'About', $About);
    }

    /**
     * Saves a name/value to the user's specified $Column.
     *
     * This method throws exceptions when errors are encountered. Use try catch blocks to capture these exceptions.
     *
     * @param string $Column The name of the serialized column to save to. At the time of this writing there are three serialized columns on the user table: Permissions, Preferences, and Attributes.
     * @param int $UserID The UserID to save.
     * @param mixed $Name The name of the value being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
     * @param mixed $Value The value being saved.
     */
    public function saveToSerializedColumn($Column, $UserID, $Name, $Value = '') {
        // Load the existing values
        $UserData = $this->getID($UserID, DATASET_TYPE_OBJECT);

        if (!$UserData) {
            throw new Exception(sprintf('User %s not found.', $UserID));
        }

        $Values = val($Column, $UserData);

        if (!is_array($Values) && !is_object($Values)) {
            $Values = dbdecode($UserData->$Column);
        }

        // Throw an exception if the field was not empty but is also not an object or array
        if (is_string($Values) && $Values != '') {
            throw new Exception(sprintf(t('Serialized column "%s" failed to be unserialized.'), $Column));
        }

        if (!is_array($Values)) {
            $Values = [];
        }

        // Hook for plugins
        $this->EventArguments['CurrentValues'] = &$Values;
        $this->EventArguments['Column'] = &$Column;
        $this->EventArguments['UserID'] = &$UserID;
        $this->EventArguments['Name'] = &$Name;
        $this->EventArguments['Value'] = &$Value;
        $this->fireEvent('BeforeSaveSerialized');

        // Assign the new value(s)
        if (!is_array($Name)) {
            $Name = [$Name => $Value];
        }


        $RawValues = array_merge($Values, $Name);
        $Values = [];
        foreach ($RawValues as $Key => $RawValue) {
            if (!is_null($RawValue)) {
                $Values[$Key] = $RawValue;
            }
        }

        $Values = dbencode($Values);

        // Save the values back to the db
        $SaveResult = $this->SQL->put('User', [$Column => $Values], ['UserID' => $UserID]);
        $this->clearCache($UserID, ['user']);

        return $SaveResult;
    }

    /**
     * Saves a user preference to the database.
     *
     * This is a convenience method that uses $this->SaveToSerializedColumn().
     *
     * @param int $UserID The UserID to save.
     * @param mixed $Preference The name of the preference being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
     * @param mixed $Value The value being saved.
     */
    public function savePreference($UserID, $Preference, $Value = '') {
        // Make sure that changes to the current user become effective immediately.
        $Session = Gdn::session();
        if ($UserID == $Session->UserID) {
            $Session->setPreference($Preference, $Value, false);
        }

        return $this->saveToSerializedColumn('Preferences', $UserID, $Preference, $Value);
    }

    /**
     * Saves a user attribute to the database.
     *
     * This is a convenience method that uses $this->SaveToSerializedColumn().
     *
     * @param int $UserID The UserID to save.
     * @param mixed $Attribute The name of the attribute being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
     * @param mixed $Value The value being saved.
     */
    public function saveAttribute($UserID, $Attribute, $Value = '') {
        // Make sure that changes to the current user become effective immediately.
        $Session = Gdn::session();
        if ($UserID == $Session->UserID) {
            $Session->setAttribute($Attribute, $Value);
        }

        return $this->saveToSerializedColumn('Attributes', $UserID, $Attribute, $Value);
    }

    /**
     *
     *
     * @param array $Data
     * @return Gdn_DataSet|string
     */
    public function saveAuthentication($Data) {
        $Cn = $this->Database->connection();
        $Px = $this->Database->DatabasePrefix;

        $UID = $Cn->quote($Data['UniqueID']);
        $Provider = $Cn->quote($Data['Provider']);
        $UserID = $Cn->quote($Data['UserID']);

        $Sql = "insert {$Px}UserAuthentication (ForeignUserKey, ProviderKey, UserID) values ($UID, $Provider, $UserID) on duplicate key update UserID = $UserID";
        $Result = $this->Database->query($Sql);
        return $Result;
    }

    /**
     * Set fields that need additional manipulation after retrieval.
     *
     * @param array|object &$User
     * @throws Exception
     */
    public function setCalculatedFields(&$User) {
        if ($v = val('Attributes', $User)) {
            if (is_string($v)) {
                setValue('Attributes', $User, dbdecode($v));
            }
        }
        if ($v = val('Permissions', $User)) {
            if (is_string($v)) {
                setValue('Permissions', $User, dbdecode($v));
            }
        }
        if ($v = val('Preferences', $User)) {
            if (is_string($v)) {
                setValue('Preferences', $User, dbdecode($v));
            }
        }
        if ($v = val('Photo', $User)) {
            if (!isUrl($v)) {
                $PhotoUrl = Gdn_Upload::url(changeBasename($v, 'n%s'));
            } else {
                $PhotoUrl = $v;
            }

            setValue('PhotoUrl', $User, $PhotoUrl);
        }
        if ($v = val('AllIPAddresses', $User)) {
            if (is_string($v)) {
                $IPAddresses = explode(',', $v);
                foreach ($IPAddresses as $i => $IPAddress) {
                    $IPAddresses[$i] = forceIPv4($IPAddress);
                }
                setValue('AllIPAddresses', $User, $IPAddresses);
            }
        }

        setValue('_CssClass', $User, '');
        if (val('Banned', $User)) {
            setValue('_CssClass', $User, 'Banned');
        }

        $this->EventArguments['User'] = &$User;
        $this->fireEvent('SetCalculatedFields');
    }

    /**
     *
     *
     * @param int $UserID
     * @param array $Meta
     * @param string $Prefix
     */
    public static function setMeta($UserID, $Meta, $Prefix = '') {
        $Deletes = [];
        $Px = Gdn::database()->DatabasePrefix;
        $Sql = "insert {$Px}UserMeta (UserID, Name, Value) values(:UserID, :Name, :Value) on duplicate key update Value = :Value1";

        foreach ($Meta as $Name => $Value) {
            $Name = $Prefix.$Name;
            if ($Value === null || $Value == '') {
                $Deletes[] = $Name;
            } else {
                Gdn::database()->query($Sql, [':UserID' => $UserID, ':Name' => $Name, ':Value' => $Value, ':Value1' => $Value]);
            }
        }
        if (count($Deletes)) {
            Gdn::sql()->whereIn('Name', $Deletes)->where('UserID', $UserID)->delete('UserMeta');
        }
    }

    /**
     * Set the TransientKey attribute on a user.
     *
     * @param int $UserID
     * @param string $ExplicitKey
     * @return string
     */
    public function setTransientKey($UserID, $ExplicitKey = '') {
        $Key = $ExplicitKey == '' ? betterRandomString(16, 'Aa0') : $ExplicitKey;
        $this->saveAttribute($UserID, 'TransientKey', $Key);
        return $Key;
    }

    /**
     * Get an Attribute from a single user.
     *
     * @param int $UserID
     * @param string $Attribute
     * @param mixed $DefaultValue
     * @return mixed
     */
    public function getAttribute($UserID, $Attribute, $DefaultValue = false) {
        $User = $this->getID($UserID, DATASET_TYPE_ARRAY);
        $Result = val($Attribute, $User['Attributes'], $DefaultValue);

        return $Result;
    }

    /**
     * Send the confirmation email.
     *
     * @param int|string|null $User
     * @param bool $Force
     * @throws Exception
     */
    public function sendEmailConfirmationEmail($User = null, $Force = false) {

        if (!$User) {
            $User = Gdn::session()->User;
        } elseif (is_numeric($User)) {
            $User = $this->getID($User);
        } elseif (is_string($User)) {
            $User = $this->getByEmail($User);
        }

        if (!$User) {
            throw notFoundException('User');
        }

        $User = (array)$User;

        if (is_string($User['Attributes'])) {
            $User['Attributes'] = dbdecode($User['Attributes']);
        }

        // Make sure the user needs email confirmation.
        if ($User['Confirmed'] && !$Force) {
            $this->Validation->addValidationResult('Role', 'Your email doesn\'t need confirmation.');

            // Remove the email key.
            if (isset($User['Attributes']['EmailKey'])) {
                unset($User['Attributes']['EmailKey']);
                $this->saveAttribute($User['UserID'], $User['Attributes']);
            }

            return;
        }

        // Make sure there is a confirmation code.
        $Code = valr('Attributes.EmailKey', $User);
        if (!$Code) {
            $Code = randomString(8);
            $Attributes = $User['Attributes'];
            if (!is_array($Attributes)) {
                $Attributes = ['EmailKey' => $Code];
            } else {
                $Attributes['EmailKey'] = $Code;
            }

            $this->saveAttribute($User['UserID'], $Attributes);
        }

        $AppTitle = Gdn::config('Garden.Title');
        $Email = new Gdn_Email();
        $Email->subject(sprintf(t('[%s] Confirm Your Email Address'), $AppTitle));
        $Email->to($User['Email']);

        $EmailUrlFormat = '{/entry/emailconfirm,exurl,domain}/{User.UserID,rawurlencode}/{EmailKey,rawurlencode}';
        $Data = [];
        $Data['EmailKey'] = $Code;
        $Data['User'] = arrayTranslate((array)$User, ['UserID', 'Name', 'Email']);

        $url = formatString($EmailUrlFormat, $Data);
        $message = formatString(t('Hello {User.Name}!'), $Data).' '.t('You need to confirm your email address before you can continue.');

        $emailTemplate = $Email->getEmailTemplate()
            ->setTitle(t('Confirm Your Email Address'))
            ->setMessage($message)
            ->setButton($url, t('Confirm My Email Address'));

        $Email->setEmailTemplate($emailTemplate);
        $Email->send();
    }

    /**
     * Send welcome email to user.
     *
     * @param int $UserID
     * @param string $Password
     * @param string $RegisterType
     * @param array|null $AdditionalData
     * @throws Exception
     */
    public function sendWelcomeEmail($UserID, $Password, $RegisterType = 'Add', $AdditionalData = null) {
        $Session = Gdn::session();
        $Sender = $this->getID($Session->UserID);
        $User = $this->getID($UserID);

        if (!ValidateEmail($User->Email)) {
            return;
        }

        $AppTitle = Gdn::config('Garden.Title');
        $Email = new Gdn_Email();
        $Email->subject(sprintf(t('[%s] Welcome Aboard!'), $AppTitle));
        $Email->to($User->Email);
        $emailTemplate = $Email->getEmailTemplate();

        $Data = [];
        $Data['User'] = arrayTranslate((array)$User, ['UserID', 'Name', 'Email']);
        $Data['Sender'] = arrayTranslate((array)$Sender, ['Name', 'Email']);
        $Data['Title'] = $AppTitle;
        if (is_array($AdditionalData)) {
            $Data = array_merge($Data, $AdditionalData);
        }

        $Data['EmailKey'] = valr('Attributes.EmailKey', $User);

        $message = '<p>'.formatString(t('Hello {User.Name}!'), $Data).' ';

        $message .= $this->getEmailWelcome($RegisterType, $User, $Data, $Password);

        // Add the email confirmation key.
        if ($Data['EmailKey']) {
            $emailUrlFormat = '{/entry/emailconfirm,exurl,domain}/{User.UserID,rawurlencode}/{EmailKey,rawurlencode}';
            $url = formatString($emailUrlFormat, $Data);
            $message .= '<p>'.t('You need to confirm your email address before you can continue.').'</p>';
            $emailTemplate->setButton($url, t('Confirm My Email Address'));
        } else {
            $emailTemplate->setButton(externalUrl('/'), t('Access the Site'));
        }

        $emailTemplate->setMessage($message);
        $emailTemplate->setTitle(t('Welcome Aboard!'));

        $Email->setEmailTemplate($emailTemplate);
        $Email->send();
    }

    /**
     * Resolves the welcome email format. Maintains backwards compatibility with the 'EmailWelcome*' translations
     * for overriding.
     *
     * @param string $registerType The registration type. One of 'Connect', 'Register' or 'Add'.
     * @param object|array $user The user to send the email to.
     * @param array $data The email data.
     * @param string $password The user's password.
     * @return string The welcome email for the registration type.
     */
    protected function getEmailWelcome($registerType, $user, $data, $password = '') {
        $appTitle = c('Garden.Title');

        // Backwards compatability. See if anybody has overridden the EmailWelcome string.
        if (($emailFormat = t('EmailWelcome'.$registerType, ''))) {
            $welcome = formatString($emailFormat, $data);
        } elseif (t('EmailWelcome', '')) {
            $welcome = sprintf(
                t('EmailWelcome'),
                val('Name', $user),
                val('Name', val('Sender', $data)),
                $appTitle,
                externalUrl('/'),
                $password,
                val('Email', $user)
            );
        } else {
            switch ($registerType) {
                case 'Connect' :
                    $welcome = formatString(t('You have successfully connected to {Title}.'), $data).' '.
                        t('Find your account information below.').'<br></p>'.
                        '<p>'.sprintf(t('%s: %s'), t('Username'), val('Name', $user)).'<br>'.
                        formatString(t('Connected With: {ProviderName}'), $data).'</p>';
                    break;
                case 'Register' :
                    $welcome = formatString(t('You have successfully registered for an account at {Title}.'), $data).' '.
                        t('Find your account information below.').'<br></p>'.
                        '<p>'.sprintf(t('%s: %s'), t('Username'), val('Name', $user)).'<br>'.
                        sprintf(t('%s: %s'), t('Email'), val('Email', $user)).'</p>';
                    break;
                default :
                    $welcome = sprintf(t('%s has created an account for you at %s.'), val('Name', val('Sender', $data)), $appTitle).' '.
                        t('Find your account information below.').'<br></p>'.
                        '<p>'.sprintf(t('%s: %s'), t('Email'), val('Email', $user)).'<br>'.
                        sprintf(t('%s: %s'), t('Password'), $password).'</p>';
            }
        }
        return $welcome;
    }

    /**
     * Send password email.
     *
     * @param int $UserID
     * @param string $Password
     */
    public function sendPasswordEmail($UserID, $Password) {
        $Session = Gdn::session();
        $Sender = $this->getID($Session->UserID);
        $User = $this->getID($UserID);
        $AppTitle = Gdn::config('Garden.Title');
        $Email = new Gdn_Email();
        $Email->subject('['.$AppTitle.'] '.t('Reset Password'));
        $Email->to($User->Email);
        $greeting = formatString(t('Hello %s!'), val('Name', $User));
        $message = '<p>'.$greeting.' '.sprintf(t('%s has reset your password at %s.'), val('Name', $Sender), $AppTitle).' '.
            t('Find your account information below.').'<br></p>'.
            '<p>'.sprintf(t('%s: %s'), t('Email'), val('Email', $User)).'<br>'.
            sprintf(t('%s: %s'), t('Password'), $Password).'</p>';

        $emailTemplate = $Email->getEmailTemplate()
            ->setTitle(t('Reset Password'))
            ->setMessage($message)
            ->setButton(externalUrl('/'), t('Access the Site'));

        $Email->setEmailTemplate($emailTemplate);
        $Email->send();
    }

    /**
     * Synchronizes the user based on a given UserKey.
     *
     * @param string $UserKey A string that uniquely identifies this user.
     * @param array $Data Information to put in the user table.
     * @return int The ID of the user.
     */
    public function synchronize($UserKey, $Data) {
        $UserID = 0;

        $Attributes = val('Attributes', $Data);
        if (is_string($Attributes)) {
            $Attributes = dbdecode($Attributes);
        }

        if (!is_array($Attributes)) {
            $Attributes = [];
        }

        // If the user didnt log in, they won't have a UserID yet. That means they want a new
        // account. So create one for them.
        if (!isset($Data['UserID']) || $Data['UserID'] <= 0) {
            // Prepare the user data.
            $UserData = [];
            $UserData['Name'] = $Data['Name'];
            $UserData['Password'] = randomString(16);
            $UserData['Email'] = val('Email', $Data, 'no@email.com');
            $UserData['Gender'] = strtolower(substr(val('Gender', $Data, 'u'), 0, 1));
            $UserData['HourOffset'] = val('HourOffset', $Data, 0);
            $UserData['DateOfBirth'] = val('DateOfBirth', $Data, '');
            $UserData['CountNotifications'] = 0;
            $UserData['Attributes'] = $Attributes;
            $UserData['InsertIPAddress'] = Gdn::request()->ipAddress();
            if ($UserData['DateOfBirth'] == '') {
                $UserData['DateOfBirth'] = '1975-09-16';
            }

            // Make sure there isn't another user with this username.
            if ($this->validateUniqueFields($UserData['Name'], $UserData['Email'])) {
                if (!BanModel::checkUser($UserData, $this->Validation, true)) {
                    throw permissionException('Banned');
                }

                // Insert the new user.
                $this->addInsertFields($UserData);
                $UserID = $this->insertInternal($UserData);
            }

            if ($UserID > 0) {
                $NewUserRoleIDs = $this->newUserRoleIDs();

                // Save the roles.
                $Roles = val('Roles', $Data, false);
                if (empty($Roles)) {
                    $Roles = $NewUserRoleIDs;
                }

                $this->saveRoles($UserID, $Roles, false);
            }
        } else {
            $UserID = $Data['UserID'];
        }

        // Synchronize the transientkey from the external user data source if it is present (eg. WordPress' wpnonce).
        if (array_key_exists('TransientKey', $Attributes) && $Attributes['TransientKey'] != '' && $UserID > 0) {
            $this->setTransientKey($UserID, $Attributes['TransientKey']);
        }

        return $UserID;
    }

    /**
     *
     *
     * @return array
     * @throws Gdn_UserException
     */
    public function newUserRoleIDs() {
        // Registration method
        $RegistrationMethod = c('Garden.Registration.Method', 'Basic');
        $DefaultRoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);
        switch ($RegistrationMethod) {

            case 'Approval':
                $RoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_APPLICANT);
                break;

            case 'Invitation':
                throw new Gdn_UserException(t('This forum is currently set to invitation only mode.'));
            case 'Basic':
            case 'Captcha':
            default:
                $RoleID = $DefaultRoleID;
                break;
        }

        if (empty($RoleID)) {
            trace("You don't have any default roles defined.", TRACE_WARNING);
        }
        return $RoleID;
    }

    /**
     * Send forgot password email.
     *
     * @param string $Email
     * @return bool
     */
    public function passwordRequest($Email) {
        if (!$Email) {
            return false;
        }

        $Users = $this->getWhere(['Email' => $Email])->resultObject();
        if (count($Users) == 0) {
            // Check for the username.
            $Users = $this->getWhere(['Name' => $Email])->resultObject();
        }

        $this->EventArguments['Users'] =& $Users;
        $this->EventArguments['Email'] = $Email;
        $this->fireEvent('BeforePasswordRequest');

        if (count($Users) == 0) {
            $this->Validation->addValidationResult('Name', "Couldn't find an account associated with that email/username.");
            return false;
        }

        $NoEmail = true;

        foreach ($Users as $User) {
            if (!$User->Email) {
                continue;
            }
            $Email = new Gdn_Email(); // Instantiate in loop to clear previous settings
            $PasswordResetKey = betterRandomString(20, 'Aa0');
            $PasswordResetExpires = strtotime('+1 hour');
            $this->saveAttribute($User->UserID, 'PasswordResetKey', $PasswordResetKey);
            $this->saveAttribute($User->UserID, 'PasswordResetExpires', $PasswordResetExpires);
            $AppTitle = c('Garden.Title');
            $Email->subject('['.$AppTitle.'] '.t('Reset Your Password'));
            $Email->to($User->Email);

            $emailTemplate = $Email->getEmailTemplate()
                ->setTitle(t('Reset Your Password'))
                ->setMessage(sprintf(t('We\'ve received a request to change your password.'), $AppTitle))
                ->setButton(externalUrl('/entry/passwordreset/'.$User->UserID.'/'.$PasswordResetKey), t('Change My Password'));
            $Email->setEmailTemplate($emailTemplate);

            $Email->send();
            $NoEmail = false;
        }

        if ($NoEmail) {
            $this->Validation->addValidationResult('Name', 'There is no email address associated with that account.');
            return false;
        }
        return true;
    }

    /**
     * Do a password reset.
     *
     * @param int $UserID
     * @param string $Password
     * @return array|false Returns the user or **false** if the user doesn't exist.
     */
    public function passwordReset($UserID, $Password) {
        // Encrypt the password before saving
        $PasswordHash = new Gdn_PasswordHash();
        $Password = $PasswordHash->hashPassword($Password);

        $this->SQL->update('User')->set('Password', $Password)->set('HashMethod', 'Vanilla')->where('UserID', $UserID)->put();
        $this->saveAttribute($UserID, 'PasswordResetKey', '');
        $this->saveAttribute($UserID, 'PasswordResetExpires', '');

        $this->EventArguments['UserID'] = $UserID;
        $this->fireEvent('AfterPasswordReset');

        return $this->getID($UserID);
    }

    /**
     * Check and apply login rate limiting
     *
     * @param array $User
     * @param bool $PasswordOK
     */
    public static function rateLimit($User, $PasswordOK) {
        if (Gdn::cache()->activeEnabled()) {
            // Rate limit using Gdn_Cache.
            $UserRateKey = formatString(self::LOGIN_RATE_KEY, ['Source' => $User->UserID]);
            $UserRate = (int)Gdn::cache()->get($UserRateKey);
            $UserRate += 1;
            Gdn::cache()->store($UserRateKey, 1, [
                Gdn_Cache::FEATURE_EXPIRY => self::LOGIN_RATE
            ]);

            $SourceRateKey = formatString(self::LOGIN_RATE_KEY, ['Source' => Gdn::request()->ipAddress()]);
            $SourceRate = (int)Gdn::cache()->get($SourceRateKey);
            $SourceRate += 1;
            Gdn::cache()->store($SourceRateKey, 1, [
                Gdn_Cache::FEATURE_EXPIRY => self::LOGIN_RATE
            ]);

        } elseif (c('Garden.Apc', false) && function_exists('apc_store')) {
            // Rate limit using the APC data store.
            $UserRateKey = formatString(self::LOGIN_RATE_KEY, ['Source' => $User->UserID]);
            $UserRate = (int)apc_fetch($UserRateKey);
            $UserRate += 1;
            apc_store($UserRateKey, 1, self::LOGIN_RATE);

            $SourceRateKey = formatString(self::LOGIN_RATE_KEY, ['Source' => Gdn::request()->ipAddress()]);
            $SourceRate = (int)apc_fetch($SourceRateKey);
            $SourceRate += 1;
            apc_store($SourceRateKey, 1, self::LOGIN_RATE);

        } else {
            // Rate limit using user attributes.
            $Now = time();
            $UserModel = Gdn::userModel();
            $LastLoginAttempt = $UserModel->getAttribute($User->UserID, 'LastLoginAttempt', 0);
            $UserRate = $UserModel->getAttribute($User->UserID, 'LoginRate', 0);
            $UserRate += 1;

            if ($LastLoginAttempt + self::LOGIN_RATE < $Now) {
                $UserRate = 0;
            }

            $UserModel->saveToSerializedColumn(
                'Attributes',
                $User->UserID,
                ['LastLoginAttempt' => $Now, 'LoginRate' => 1]
            );

            // IP rate limiting is not available without an active cache.
            $SourceRate = 0;

        }

        // Put user into cooldown mode.
        if ($UserRate > 1) {
            throw new Gdn_UserException(t('LoginUserCooldown', 'You are trying to log in too often. Slow down!.'));
        }
        if ($SourceRate > 1) {
            throw new Gdn_UserException(t('LoginSourceCooldown', 'Your IP is trying to log in too often. Slow down!'));
        }

        return true;
    }

    /**
     * Set a single user property.
     *
     * @param int $RowID
     * @param array|string $Property
     * @param bool $Value
     * @return bool
     */
    public function setField($RowID, $Property, $Value = false) {
        if (!is_array($Property)) {
            $Property = [$Property => $Value];
        }

        $this->defineSchema();
        $Fields = $this->Schema->fields();

        if (isset($Property['AllIPAddresses'])) {
            if (is_array($Property['AllIPAddresses'])) {
                $IPs = array_map('ForceIPv4', $Property['AllIPAddresses']);
                $IPs = array_unique($IPs);
                $Property['AllIPAddresses'] = implode(',', $IPs);
                // Ensure this isn't too big for our column
                while (strlen($Property['AllIPAddresses']) > $Fields['AllIPAddresses']->Length) {
                    array_pop($IPs);
                    $Property['AllIPAddresses'] = implode(',', $IPs);
                }
            }
        }

        $Set = array_intersect_key($Property, $Fields);
        self::serializeRow($Set);

        $this->SQL
            ->update($this->Name)
            ->set($Set)
            ->where('UserID', $RowID)
            ->put();

        if (in_array($Property, ['Permissions'])) {
            $this->clearCache($RowID, ['permissions']);
        } else {
            $this->updateUserCache($RowID, $Property, $Value);
        }

        if (!is_array($Property)) {
            $Property = [$Property => $Value];
        }

        $this->EventArguments['UserID'] = $RowID;
        $this->EventArguments['Fields'] = $Property;
        $this->fireEvent('AfterSetField');

        return $Value;
    }

    /**
     * Get a user from the cache by name or ID
     *
     * @param string|int $UserToken either a userid or a username
     * @param string $TokenType either 'userid' or 'name'
     * @return array|false Returns a user array or **false** if the user isn't in the cache.
     */
    public function getUserFromCache($UserToken, $TokenType) {
        if ($TokenType == 'name') {
            $UserNameKey = formatString(self::USERNAME_KEY, ['Name' => md5($UserToken)]);
            $UserID = Gdn::cache()->get($UserNameKey);

            if ($UserID === Gdn_Cache::CACHEOP_FAILURE) {
                return false;
            }
            $UserToken = $UserID;
            $TokenType = 'userid';
        }

        if ($TokenType != 'userid') {
            return false;
        }

        // Get from memcached
        $UserKey = formatString(self::USERID_KEY, ['UserID' => $UserToken]);
        $User = Gdn::cache()->get($UserKey);

        return $User;
    }

    /**
     *
     *
     * @param int $UserID
     * @param string|array $Field
     * @param mixed|null $Value
     */
    public function updateUserCache($UserID, $Field, $Value = null) {
        // Try and get the user from the cache.
        $User = $this->getUserFromCache($UserID, 'userid');

        if (!$User) {
            return;
        }

        if (!is_array($Field)) {
            $Field = [$Field => $Value];
        }

        foreach ($Field as $f => $v) {
            $User[$f] = $v;
        }
        $this->userCache($User);
    }

    /**
     * Cache a user.
     *
     * @param array $User The user to cache.
     * @return bool Returns **true** if the user was cached or **false** otherwise.
     */
    public function userCache($User, $UserID = null) {
        if (!$UserID) {
            $UserID = val('UserID', $User, null);
        }
        if (is_null($UserID) || !$UserID) {
            return false;
        }

        $Cached = true;

        $UserKey = formatString(self::USERID_KEY, ['UserID' => $UserID]);
        $Cached = $Cached & Gdn::cache()->store($UserKey, $User, [
                Gdn_Cache::FEATURE_EXPIRY => 3600
            ]);

        $UserNameKey = formatString(self::USERNAME_KEY, ['Name' => md5(val('Name', $User))]);
        $Cached = $Cached & Gdn::cache()->store($UserNameKey, $UserID, [
                Gdn_Cache::FEATURE_EXPIRY => 3600
            ]);
        return $Cached;
    }

    /**
     * Cache a user's roles.
     *
     * @param int $userID The ID of a user to cache roles for.
     * @param array $roleIDs A collection of role IDs with the specified user.
     * @return bool Was the caching operation successful?
     */
    public function userCacheRoles($userID, $roleIDs) {
        if ($userID !== 0 && !$userID) {
            return false;
        }

        $userRolesKey = formatString(self::USERROLES_KEY, ['UserID' => $userID]);
        $cached = Gdn::cache()->store(
            $userRolesKey,
            $roleIDs,
            [Gdn_Cache::FEATURE_EXPIRY => 3600]
        );
        return $cached;
    }

    /**
     * Delete cached data for user.
     *
     * @param int|null $UserID The user to clear the cache for.
     * @return bool Returns **true** if the cache was cleared or **false** otherwise.
     */
    public function clearCache($UserID, $CacheTypesToClear = null) {
        if (is_null($UserID) || !$UserID) {
            return false;
        }

        if (is_null($CacheTypesToClear)) {
            $CacheTypesToClear = ['user', 'roles', 'permissions'];
        }

        if (in_array('user', $CacheTypesToClear)) {
            $UserKey = formatString(self::USERID_KEY, ['UserID' => $UserID]);
            Gdn::cache()->remove($UserKey);
        }

        if (in_array('roles', $CacheTypesToClear)) {
            $UserRolesKey = formatString(self::USERROLES_KEY, ['UserID' => $UserID]);
            Gdn::cache()->remove($UserRolesKey);
        }

        if (in_array('permissions', $CacheTypesToClear)) {
            Gdn::sql()->put('User', ['Permissions' => ''], ['UserID' => $UserID]);

            $PermissionsIncrement = $this->getPermissionsIncrement();
            $UserPermissionsKey = formatString(self::USERPERMISSIONS_KEY, ['UserID' => $UserID, 'PermissionsIncrement' => $PermissionsIncrement]);
            Gdn::cache()->remove($UserPermissionsKey);
        }
        return true;
    }

    /**
     * Clear the permission cache.
     */
    public function clearPermissions() {
        if (!Gdn::cache()->activeEnabled()) {
            $this->SQL->put('User', ['Permissions' => ''], ['Permissions <>' => '']);
        }

        $PermissionsIncrementKey = self::INC_PERMISSIONS_KEY;
        $PermissionsIncrement = $this->getPermissionsIncrement();
        if ($PermissionsIncrement == 0) {
            Gdn::cache()->store($PermissionsIncrementKey, 1);
        } else {
            Gdn::cache()->increment($PermissionsIncrementKey);
        }
    }

    /**
     *
     *
     * @return bool|int|mixed
     */
    public function getPermissionsIncrement() {
        $PermissionsIncrementKey = self::INC_PERMISSIONS_KEY;
        $PermissionsKeyValue = Gdn::cache()->get($PermissionsIncrementKey);

        if (!$PermissionsKeyValue) {
            $Stored = Gdn::cache()->store($PermissionsIncrementKey, 1);
            return $Stored ? 1 : false;
        }

        return $PermissionsKeyValue;
    }

    /**
     *
     *
     * @param array $Roles
     * @return array
     */
    protected function lookupRoleIDs($Roles) {
        if (is_string($Roles)) {
            $Roles = explode(',', $Roles);
        } elseif (!is_array($Roles)) {
            $Roles = [];
        }
        $Roles = array_map('trim', $Roles);
        $Roles = array_map('strtolower', $Roles);

        $AllRoles = RoleModel::roles();
        $RoleIDs = [];
        foreach ($AllRoles as $RoleID => $Role) {
            $Name = strtolower($Role['Name']);
            if (in_array($Name, $Roles) || in_array($RoleID, $Roles)) {
                $RoleIDs[] = $RoleID;
            }
        }
        return $RoleIDs;
    }
}
