<?php
/**
 * User model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('User');
    }

    /**
     *
     *
     * @param $Message
     * @param $Data
     * @return string
     */
    protected function _AddEmailHeaderFooter($Message, $Data) {
        $Header = t('EmailHeader', '');
        if ($Header) {
            $Message = formatString($Header, $Data)."\n".$Message;
        }

        $Footer = t('EmailFooter', '');
        if ($Footer) {
            $Message .= "\n".FormatString($Footer, $Data);
        }

        return $Message;
    }

    /**
     *
     *
     * @param Gdn_Controller $Controller
     */
    public function addPasswordStrength($Controller) {
        $Controller->addJsFile('password.js');
        $Controller->addDefinition('MinPassLength', c('Garden.Registration.MinPasswordLength'));
        $Controller->addDefinition(
            'PasswordTranslations',
            implode(',', array(
                t('Password Too Short', 'Too Short'),
                t('Password Contains Username', 'Contains Username'),
                t('Password Very Weak', 'Very Weak'),
                t('Password Weak', 'Weak'),
                t('Password Ok', 'OK'),
                t('Password Good', 'Good'),
                t('Password Strong', 'Strong')))
        );
    }

    /**
     * Reliably get the attributes from any user array or object
     *
     * @param array|object $user
     * @return array
     */
    public static function attributes($user) {
        $user = (array)$user;
        $attributes = $user['Attributes'];
        if (is_string($attributes)) {
            $attributes = unserialize($attributes);
        }
        if (!is_array($attributes)) {
            $attributes = array();
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
            $LogID = $this->DeleteContent($UserID, $Options);
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

            $Activity = array(
                'ActivityType' => 'Ban',
                'NotifyUserID' => ActivityModel::NOTIFY_MODS,
                'ActivityUserID' => $UserID,
                'RegardingUserID' => Gdn::session()->UserID,
                'HeadlineFormat' => t('HeadlineFormat.Ban', '{RegardingUserID,You} banned {ActivityUserID,you}.'),
                'Story' => $Story,
                'Data' => array('LogID' => $LogID));

            $ActivityModel = new ActivityModel();
            $ActivityModel->save($Activity);
        }
    }

    /**
     * Checks the specified user's for the given permission. Returns a boolean
     * value indicating if the action is permitted.
     *
     * @param mixed $User The user to check
     * @param mixed $Permission The permission (or array of permissions) to check.
     * @param int $JunctionID The JunctionID associated with $Permission (ie. A discussion category identifier).
     * @return boolean
     */
    public function checkPermission($User, $Permission, $Options = array()) {
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
            $Permissions = $this->DefinePermissions(0, false);
        } elseif (is_array($User->Permissions))
            $Permissions = $User->Permissions;
        else {
            $Permissions = $this->DefinePermissions($User->UserID, false);
        }

        // TODO: Check for junction table permissions.
        $Result = in_array($Permission, $Permissions) || array_key_exists($Permission, $Permissions);
        return $Result;
    }

    /**
     * Merge the old user into the new user.
     *
     * @param int $OldUserID
     * @param int $NewUserID
     */
    public function merge($OldUserID, $NewUserID) {
        $OldUser = $this->getID($OldUserID, DATASET_TYPE_ARRAY);
        $NewUser = $this->getID($NewUserID, DATASET_TYPE_ARRAY);

        if (!$OldUser || !$NewUser) {
            throw new Gdn_UserException("Could not find one or both users to merge.");
        }

        $Map = array('UserID', 'Name', 'Email', 'CountVisits', 'CountDiscussions', 'CountComments');

        $Result = array('MergeID' => null, 'Before' => array(
            'OldUser' => arrayTranslate($OldUser, $Map),
            'NewUser' => arrayTranslate($NewUser, $Map)));

        // Start the merge.
        $MergeID = $this->MergeStart($OldUserID, $NewUserID);

        // Copy all discussions from the old user to the new user.
        $this->MergeCopy($MergeID, 'Discussion', 'InsertUserID', $OldUserID, $NewUserID);

        // Copy all the comments from the old user to the new user.
        $this->MergeCopy($MergeID, 'Comment', 'InsertUserID', $OldUserID, $NewUserID);

        // Copy all of the activities.
        $this->MergeCopy($MergeID, 'Activity', 'NotifyUserID', $OldUserID, $NewUserID);
        $this->MergeCopy($MergeID, 'Activity', 'InsertUserID', $OldUserID, $NewUserID);
        $this->MergeCopy($MergeID, 'Activity', 'ActivityUserID', $OldUserID, $NewUserID);

        // Copy all of the activity comments.
        $this->MergeCopy($MergeID, 'ActivityComment', 'InsertUserID', $OldUserID, $NewUserID);

        // Copy all conversations.
        $this->MergeCopy($MergeID, 'Conversation', 'InsertUserID', $OldUserID, $NewUserID);
        $this->MergeCopy($MergeID, 'ConversationMessage', 'InsertUserID', $OldUserID, $NewUserID, 'MessageID');
        $this->MergeCopy($MergeID, 'UserConversation', 'UserID', $OldUserID, $NewUserID, 'ConversationID');

        $this->MergeFinish($MergeID);

        $OldUser = $this->getID($OldUserID, DATASET_TYPE_ARRAY);
        $NewUser = $this->getID($NewUserID, DATASET_TYPE_ARRAY);

        $Result['MergeID'] = $MergeID;
        $Result['After'] = array(
            'OldUser' => arrayTranslate($OldUser, $Map),
            'NewUser' => arrayTranslate($NewUser, $Map));

        return $Result;
    }

    /**
     * Backup user before merging.
     *
     * @param $MergeID
     * @param $Table
     * @param $Column
     * @param $OldUserID
     * @param $NewUserID
     * @param null $PK
     */
    protected function mergeCopy($MergeID, $Table, $Column, $OldUserID, $NewUserID, $PK = null) {
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
            array(':MergeID' => $MergeID, ':Table' => $Table, ':Column' => $Column,
                ':OldUserID' => $OldUserID, ':NewUserID' => $NewUserID, ':OldUserID2' => $OldUserID)
        );

        Gdn::sql()->Options('Ignore', true)->put(
            $Table,
            array($Column => $NewUserID),
            array($Column => $OldUserID)
        );
    }

    /**
     * Start merging user accounts.
     *
     * @param $OldUserID
     * @param $NewUserID
     * @return unknown
     * @throws Gdn_UserException
     */
    protected function mergeStart($OldUserID, $NewUserID) {
        $Model = new Gdn_Model('UserMerge');

        // Grab the users.
        $OldUser = $this->getID($OldUserID, DATASET_TYPE_ARRAY);
        $NewUser = $this->getID($NewUserID, DATASET_TYPE_ARRAY);

        // First see if there is a record with the same merge.
        $Row = $Model->getWhere(array('OldUserID' => $OldUserID, 'NewUserID' => $NewUserID))->firstRow(DATASET_TYPE_ARRAY);
        if ($Row) {
            $MergeID = $Row['MergeID'];

            // Save this merge in the log.
            if ($Row['Attributes']) {
                $Attributes = unserialize($Row['Attributes']);
            } else {
                $Attributes = array();
            }

            $Attributes['Log'][] = array('UserID' => Gdn::session()->UserID, 'Date' => Gdn_Format::toDateTime());
            $Row = array('MergeID' => $MergeID, 'Attributes' => $Attributes);
        } else {
            $Row = array(
                'OldUserID' => $OldUserID,
                'NewUserID' => $NewUserID);
        }

        $UserSet = array();
        $OldUserSet = array();
        if (DateCompare($OldUser['DateFirstVisit'], $NewUser['DateFirstVisit']) < 0) {
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
     * @param $MergeID
     */
    protected function mergeFinish($MergeID) {
        $Row = Gdn::sql()->getWhere('UserMerge', array('MergeID' => $MergeID))->firstRow(DATASET_TYPE_ARRAY);

        if (isset($Row['Attributes']) && !empty($Row['Attributes'])) {
            trace(unserialize($Row['Attributes']), 'Merge Attributes');
        }

        $UserIDs = array(
            $Row['OldUserID'],
            $Row['NewUserID']);

        foreach ($UserIDs as $UserID) {
            $this->counts('countdiscussions', $UserID);
            $this->counts('countcomments', $UserID);
        }
    }

    /**
     * User counts.
     *
     * @param $Column
     * @param null $UserID
     */
    public function counts($Column, $UserID = null) {
        if ($UserID) {
            $Where = array('UserID' => $UserID);
        } else {
            $Where = null;
        }

        switch (strtolower($Column)) {
            case 'countdiscussions':
                Gdn::database()->query(DBAModel::GetCountSQL('count', 'User', 'Discussion', 'CountDiscussions', 'DiscussionID', 'UserID', 'InsertUserID', $Where));
                break;
            case 'countcomments':
                Gdn::database()->query(DBAModel::GetCountSQL('count', 'User', 'Comment', 'CountComments', 'CommentID', 'UserID', 'InsertUserID', $Where));
                break;
        }

        if ($UserID) {
            $this->ClearCache($UserID);
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
     * @since 2.1
     *
     * @param int $UserID
     * @param array $Options
     */
    public function unBan($UserID, $Options = array()) {
        $User = $this->getID($UserID, DATASET_TYPE_ARRAY);
        if (!$User) {
            throw notFoundException();
        }

        $Banned = $User['Banned'];
        if (!BanModel::isBanned($Banned, BanModel::BAN_MANUAL)) {
            throw new Gdn_UserException(t("The user isn't banned.", "The user isn't banned or is banned by some other function."));
        }

        // Unban the user.
        $NewBanned = BanModel::setBanned($Banned, false, BanModel::BAN_MANUAL);
        $this->setField($UserID, 'Banned', $NewBanned);

        // Restore the user's content.
        if (val('RestoreContent', $Options)) {
            $BanLogID = $this->getAttribute($UserID, 'BanLogID');

            if ($BanLogID) {
                $LogModel = new LogModel();

                try {
                    $LogModel->Restore($BanLogID);
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
            $Activity = array(
                'ActivityType' => 'Ban',
                'NotifyUserID' => ActivityModel::NOTIFY_MODS,
                'ActivityUserID' => $UserID,
                'RegardingUserID' => Gdn::session()->UserID,
                'HeadlineFormat' => t('HeadlineFormat.Unban', '{RegardingUserID,You} unbanned {ActivityUserID,you}.'),
                'Story' => $Story,
                'Data' => array(
                    'Unban' => true
                )
            );

            $ActivityModel->Queue($Activity);

            // Notify the user of the unban.
            $Activity['NotifyUserID'] = $UserID;
            $Activity['Emailed'] = ActivityModel::SENT_PENDING;
            $Activity['HeadlineFormat'] = t('HeadlineFormat.Unban.Notification', "You've been unbanned.");
            $ActivityModel->Queue($Activity, false, array('Force' => true));

            $ActivityModel->SaveQueue();
        }
    }

    /**
     *
     *
     * @param $User
     * @param $EmailKey
     * @return bool
     * @throws Exception
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
        $UserRoles = $this->GetRoles($UserID);
        $UserRoleIDs = array();
        while ($UserRole = $UserRoles->NextRow(DATASET_TYPE_ARRAY)) {
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
        $this->SaveRoles($UserID, $Roles, false);

        // Remove the email confirmation attributes.
        $this->saveAttribute($UserID, array('EmailKey' => null));
        $this->setField($UserID, 'Confirmed', 1);
        return true;
    }

    /**
     *
     *
     * @param $String
     * @param bool $ThrowError
     * @return int|void
     */
    public function sso($String, $ThrowError = false) {
        if (!$String) {
            return;
        }

        $Parts = explode(' ', $String);

        $String = $Parts[0];
        trace($String, "SSO String");
        $Data = json_decode(base64_decode($String), true);
        trace($Data, 'RAW SSO Data');
        $Errors = array();

        if (!isset($Parts[1])) {
            $Errors[] = 'Missing SSO signature';
        }
        if (!isset($Parts[2])) {
            $Errors[] = 'Missing SSO timestamp';
        }
        if (!empty($Errors)) {
            return;
        }

        $Signature = $Parts[1];
        $Timestamp = $Parts[2];
        $HashMethod = val(3, $Parts, 'hmacsha1');
        $ClientID = val('client_id', $Data);
        if (!$ClientID) {
            trace('Missing SSO client_id', TRACE_ERROR);
            return;
        }

        $Provider = Gdn_AuthenticationProviderModel::getProviderByKey($ClientID);

        if (!$Provider) {
            trace("Unknown SSO Provider: $ClientID", TRACE_ERROR);
            return;
        }

        $Secret = $Provider['AssociationSecret'];
        if (!trim($Secret, '.')) {
            trace('Missing client secret', TRACE_ERROR);
            return;
        }

        // Check the signature.
        switch ($HashMethod) {
            case 'hmacsha1':
                $CalcSignature = hash_hmac('sha1', "$String $Timestamp", $Secret);
                break;
            default:
                trace("Invalid SSO hash method $HashMethod.", TRACE_ERROR);
                return;
        }
        if ($CalcSignature != $Signature) {
            trace("Invalid SSO signature: $Signature", TRACE_ERROR);
            return;
        }

        $UniqueID = $Data['uniqueid'];
        $User = arrayTranslate($Data, array(
            'name' => 'Name',
            'email' => 'Email',
            'photourl' => 'Photo',
            'roles' => 'Roles',
            'uniqueid' => null,
            'client_id' => null), true);

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
        return $this->syncUser($CurrentUser, $NewUser, $Force);
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

        // Don't synch the user photo if they've uploaded one already.
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

        if (c('Garden.SSO.SyncRoles')) {
            // Translate the role names to IDs.
            $Roles = val('Roles', $NewUser, '');
            if (is_string($Roles)) {
                $Roles = explode(',', $Roles);
            } elseif (!is_array($Roles)) {
                $Roles = array();
            }
            $Roles = array_map('trim', $Roles);
            $Roles = array_map('strtolower', $Roles);

            $AllRoles = RoleModel::roles();
            $RoleIDs = array();
            foreach ($AllRoles as $RoleID => $Role) {
                $Name = strtolower($Role['Name']);
                if (in_array($Name, $Roles) || in_array($RoleID, $Roles)) {
                    $RoleIDs[] = $RoleID;
                }
            }
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

        $Result = $this->save($NewUser, array('NoConfirmEmail' => true, 'FixUnique' => true, 'SaveRoles' => isset($NewUser['RoleID'])));
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
     * @return int The new/existing user ID.
     */
    public function connect($UniqueID, $ProviderKey, $UserData, $Options = array()) {
        trace('UserModel->Connect()');

        $UserID = false;
        if (!isset($UserData['UserID'])) {
            // Check to see if the user already exists.
            $Auth = $this->getAuthentication($UniqueID, $ProviderKey);
            $UserID = val('UserID', $Auth);

            if ($UserID) {
                $UserData['UserID'] = $UserID;
            }
        }

        $UserInserted = false;

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
//            $UserID = $this->InsertForBasic($UserData, FALSE, array('ValidateEmail' => false, 'NoConfirmEmail' => TRUE));
                $UserData['Password'] = md5(microtime());
                $UserData['HashMethod'] = 'Random';

                touchValue('CheckCaptcha', $Options, false);
                touchValue('NoConfirmEmail', $Options, true);
                touchValue('NoActivity', $Options, true);
                touchValue('SaveRoles', $Options, c('Garden.SSO.SyncRoles', false));

                trace($UserData, 'Registering User');
                $UserID = $this->register($UserData, $Options);
                $UserInserted = true;
            }

            if ($UserID) {
                // Save the authentication.
                $this->saveAuthentication(array(
                    'UniqueID' => $UniqueID,
                    'Provider' => $ProviderKey,
                    'UserID' => $UserID
                ));

                if ($UserInserted && c('Garden.Registration.SendConnectEmail', true)) {
                    $Provider = $this->SQL->getWhere('UserAuthenticationProvider', array('AuthenticationKey' => $ProviderKey))->firstRow(DATASET_TYPE_ARRAY);
                }
            } else {
                trace($this->Validation->resultsText(), TRACE_ERROR);
            }
        }

        return $UserID;
    }

    /**
     *
     *
     * @param array $Data
     * @param bool $Register
     * @return array
     */
    public function filterForm($Data, $Register = false) {
        $Data = parent::FilterForm($Data);
        $Data = array_diff_key(
            $Data,
            array('Admin' => 0, 'Deleted' => 0, 'CountVisits' => 0, 'CountInvitations' => 0, 'CountNotifications' => 0, 'Preferences' => 0,
                'Permissions' => 0, 'LastIPAddress' => 0, 'AllIPAddresses' => 0, 'DateFirstVisit' => 0, 'DateLastActive' => 0, 'CountDiscussions' => 0, 'CountComments' => 0,
                'Score' => 0)
        );
        if (!Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            $Data = array_diff_key($Data, array('Banned' => 0, 'Verified' => 0, 'Confirmed' => 0));
        }
        if (!Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            unset($Data['RankID']);
        }
        if (!$Register && !Gdn::session()->checkPermission('Garden.Users.Edit') && !c("Garden.Profile.EditUsernames")) {
            unset($Data['Name']);
        }

        return $Data;

    }

    /**
     * Force gender to be a verified value.
     *
     * @param $Value
     * @return string
     */
    public static function fixGender($Value) {
        if (!$Value || !is_string($Value)) {
            return 'u';
        }

        if ($Value) {
            $Value = strtolower(substr(trim($Value), 0, 1));
        }

        if (!in_array($Value, array('u', 'm', 'f'))) {
            $Value = 'u';
        }

        return $Value;
    }

    /**
     * A convenience method to be called when inserting users (because users
     * are inserted in various methods depending on registration setups).
     *
     * @param array $Fields
     * @param array $Options
     * @return int
     */
    protected function _insert($Fields, $Options = array()) {
        $this->EventArguments['InsertFields'] =& $Fields;
        $this->fireEvent('BeforeInsertUser');

        if (!val('Setup', $Options)) {
            unset($Fields['Admin']);
        }

        // Massage the roles for email confirmation.
        if (self::requireConfirmEmail() && !val('NoConfirmEmail', $Options)) {
            $ConfirmRoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_UNCONFIRMED);

            if (!empty($ConfirmRoleID)) {
                touchValue('Attributes', $Fields, array());
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
            $Fields['Attributes'] = serialize($Fields['Attributes']);
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
     * @param object $Data Results we need to associate user data with.
     * @param array $Columns Database columns containing UserIDs to get data for.
     * @param array $Options Optionally pass list of user data to collect with key 'Join'.
     */
    public function joinUsers(&$Data, $Columns, $Options = array()) {
        if ($Data instanceof Gdn_DataSet) {
            $Data2 = $Data->result();
        } else {
            $Data2 =& $Data;
        }

        // Grab all of the user fields that need to be joined.
        $UserIDs = array();
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
        $Prefixes = array();
        foreach ($Columns as $ColumnName) {
            $Prefixes[] = StringEndsWith($ColumnName, 'UserID', true, true);
        }

        // Join the user data using prefixes (ex: 'Name' for 'InsertUserID' becomes 'InsertName')
        $Join = val('Join', $Options, array('Name', 'Email', 'Photo'));
        $UserPhotoDefaultUrl = function_exists('UserPhotoDefaultUrl');

        foreach ($Data2 as &$Row) {
            foreach ($Prefixes as $Px) {
                $ID = val($Px.'UserID', $Row);
                if (is_numeric($ID)) {
                    $User = val($ID, $Users, false);
                    foreach ($Join as $Column) {
                        $Value = $User[$Column];
                        if ($Column == 'Photo') {
                            if (!$Value) {
                                if ($UserPhotoDefaultUrl) {
                                    $Value = UserPhotoDefaultUrl($User);
                                }
                            } elseif (!isUrl($Value)) {
                                $Value = Gdn_Upload::url(changeBasename($Value, 'n%s'));
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
     * $SafeData makes sure that the query does not return any sensitive
     * information about the user (password, attributes, preferences, etc).
     *
     * @param bool $SafeData
     */
    public function userQuery($SafeData = false) {
        if ($SafeData) {
            $this->SQL->select('u.UserID, u.Name, u.Photo, u.CountVisits, u.DateFirstVisit, u.DateLastActive, u.DateInserted, u.DateUpdated, u.Score, u.Deleted, u.CountDiscussions, u.CountComments');
        } else {
            $this->SQL->select('u.*');
        }
        $this->SQL->from('User u');
//      $this->SQL->select('i.Name', '', 'InviteName')
//         ->from('User u')
//         ->join('User as i', 'u.InviteUserID = i.UserID', 'left');
    }

    /**
     * Load and compile user permissions
     *
     * @param integer $UserID
     * @param boolean $Serialize
     * @return array
     */
    public function definePermissions($UserID, $Serialize = true) {
        if (Gdn::cache()->activeEnabled()) {
            $PermissionsIncrement = $this->GetPermissionsIncrement();
            $UserPermissionsKey = formatString(self::USERPERMISSIONS_KEY, array(
                'UserID' => $UserID,
                'PermissionsIncrement' => $PermissionsIncrement
            ));

            $CachePermissions = Gdn::cache()->get($UserPermissionsKey);
            if ($CachePermissions !== Gdn_Cache::CACHEOP_FAILURE) {
                return $CachePermissions;
            }
        }

        $Data = Gdn::permissionModel()->CachePermissions($UserID);
        $Permissions = UserModel::CompilePermissions($Data);

        $PermissionsSerialized = null;
        if (Gdn::cache()->activeEnabled()) {
            Gdn::cache()->store($UserPermissionsKey, $Permissions);
        } else {
            // Save the permissions to the user table
            $PermissionsSerialized = Gdn_Format::Serialize($Permissions);
            if ($UserID > 0) {
                $this->SQL->put('User', array('Permissions' => $PermissionsSerialized), array('UserID' => $UserID));
            }
        }

        if ($Serialize && is_null($PermissionsSerialized)) {
            $PermissionsSerialized = Gdn_Format::serialize($Permissions);
        }

        return $Serialize ? $PermissionsSerialized : $Permissions;
    }

    /**
     * Take raw permission definitions and create
     *
     * @param array $Permissions
     * @return array Compiled permissions
     */
    public static function compilePermissions($Permissions) {
        $Compiled = array();
        foreach ($Permissions as $i => $Row) {
            $JunctionID = array_key_exists('JunctionID', $Row) ? $Row['JunctionID'] : null;
            //$JunctionTable = array_key_exists('JunctionColumn', $Row) ? $Row['JunctionTable'] : null;
            //$JunctionColumn = array_key_exists('JunctionColumn', $Row) ? $Row['JunctionColumn'] : null;
            unset($Row['JunctionColumn'], $Row['JunctionColumn'], $Row['JunctionID'], $Row['RoleID'], $Row['PermissionID']);

            foreach ($Row as $PermissionName => $Value) {
                if ($Value == 0) {
                    continue;
                }

                if (is_numeric($JunctionID) && $JunctionID !== null) {
                    if (!array_key_exists($PermissionName, $Compiled)) {
                        $Compiled[$PermissionName] = array();
                    }

                    if (!is_array($Compiled[$PermissionName])) {
                        $Compiled[$PermissionName] = array();
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
     * @since 2.0.0
     * @return object DataSet
     */
    public function get($OrderFields = '', $OrderDirection = 'asc', $Limit = false, $Offset = false) {
        if (is_numeric($OrderFields)) {
            // They're using the old version that was a misnamed GetID()
            Deprecated('UserModel->get()', 'UserModel->getID()');
            $Result = $this->getID($OrderFields);
        } else {
            $Result = parent::get($OrderFields, $OrderDirection, $Limit, $Offset);
        }
        return $Result;
    }

    /**
     *
     *
     * @param $Username
     * @return bool|object
     */
    public function getByUsername($Username) {
        if ($Username == '') {
            return false;
        }

        // Check page cache, then memcached
        $User = $this->GetUserFromCache($Username, 'name');

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
     * @param $Email
     * @return array|bool|stdClass
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
     * @param $Role
     * @return Gdn_DataSet
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
//         ->groupBy('UserID')
            ->orderBy('DateInserted', 'desc')
            ->get();
    }

    /**
     * Most recently active users.
     *
     * @param int $Limit
     * @return Gdn_DataSet
     * @throws Exception
     */
    public function getActiveUsers($Limit = 5) {
        $UserIDs = $this->SQL
            ->select('UserID')
            ->from('User')
            ->orderBy('DateLastActive', 'desc')
            ->limit($Limit, 0)
            ->get();
        $UserIDs = consolidateArrayValuesByKey($UserIDs, 'UserID');

        $Data = $this->SQL->getWhere('User', array('UserID' => $UserIDs), 'DateLastActive', 'desc');
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
//         ->groupBy('UserID')
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
            array('ForeignUserKey' => $UniqueID, 'ProviderKey' => $Provider)
        )->firstRow(DATASET_TYPE_ARRAY);
    }

    /**
     *
     *
     * @param bool $Like
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
     * @param bool $Where
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
     *
     *
     * @param mixed $ID
     * @param bool|string $DatasetType
     * @return array|bool|null|object|type
     * @throws Exception
     */
    public function getID($ID, $DatasetType = DATASET_TYPE_OBJECT) {
        if (!$ID) {
            return false;
        }

        // Check page cache, then memcached
        $User = $this->getUserFromCache($ID, 'userid');

        // If not, query DB
        if ($User === Gdn_Cache::CACHEOP_FAILURE) {
            $User = parent::getID($ID, DATASET_TYPE_ARRAY);

            // We want to cache a non-existant user no-matter what.
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
     * @param $IDs
     * @param bool $SkipCacheQuery
     * @return array
     * @throws Exception
     */
    public function getIDs($IDs, $SkipCacheQuery = false) {
        $DatabaseIDs = $IDs;
        $Data = array();

        if (!$SkipCacheQuery) {
            $Keys = array();
            // Make keys for cache query
            foreach ($IDs as $UserID) {
                if (!$UserID) {
                    continue;
                }
                $Keys[] = formatString(self::USERID_KEY, array('UserID' => $UserID));
            }

            // Query cache layer
            $CacheData = Gdn::cache()->get($Keys);
            if (!is_array($CacheData)) {
                $CacheData = array();
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
        $DatabaseIDs = array_diff($DatabaseIDs, array(null, ''));

        // If we are missing any users from cache query, fill em up here
        if (sizeof($DatabaseIDs)) {
            $DatabaseData = $this->SQL->whereIn('UserID', $DatabaseIDs)->getWhere('User')->result(DATASET_TYPE_ARRAY);
            $DatabaseData = Gdn_DataSet::Index($DatabaseData, 'UserID');

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
     * @param $UserID integer UserID or array of UserIDs.
     * @param $Key string relative user meta key.
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
            $Result = array_fill_keys($UserID, array());
        } else {
            if (strpos($Key, '%') === false) {
                $Result = array(stringBeginsWith($Key, $Prefix, false, true) => $Default);
            } else {
                $Result = array();
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
     *
     *
     * @param $UserID
     * @return Gdn_DataSet
     */
    public function getRoles($UserID) {
        $UserRolesKey = formatString(self::USERROLES_KEY, array('UserID' => $UserID));
        $RolesDataArray = Gdn::cache()->get($UserRolesKey);

        if ($RolesDataArray === Gdn_Cache::CACHEOP_FAILURE) {
            $RolesDataArray = $this->SQL->getWhere('UserRole', array('UserID' => $UserID))->resultArray();
            $RolesDataArray = consolidateArrayValuesByKey($RolesDataArray, 'RoleID');
        }

        $Result = array();
        foreach ($RolesDataArray as $RoleID) {
            $Result[] = RoleModel::roles($RoleID, true);
        }
        return new Gdn_DataSet($Result);
    }

    /**
     *
     *
     * @param $UserID
     * @param bool $Refresh
     * @return array|bool|null|object|type
     */
    public function getSession($UserID, $Refresh = false) {
        // Ask for the user. This will check cache first.
        $User = $this->getID($UserID, DATASET_TYPE_OBJECT);

        if (!$User) {
            return false;
        }

        // If we require confirmation and user is not confirmed
        $ConfirmEmail = c('Garden.Registration.ConfirmEmail', false);
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
                $User->Permissions = $this->definePermissions($UserID);
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
        $Result =& $Data->result();
        foreach ($Result as &$Row) {
            if ($Row->Photo && !isUrl($Row->Photo)) {
                $Row->Photo = Gdn_Upload::url($Row->Photo);
            }
        }

        return $Result;
    }

    /**
     * Retrieves a "system user" id that can be used to perform non-real-person tasks.
     */
    public function getSystemUserID() {
        $SystemUserID = c('Garden.SystemUserID');
        if ($SystemUserID) {
            return $SystemUserID;
        }

        $SystemUser = array(
            'Name' => t('System'),
            'Photo' => Asset('/applications/dashboard/design/images/usericon.png', true),
            'Password' => RandomString('20'),
            'HashMethod' => 'Random',
            'Email' => 'system@domain.com',
            'DateInserted' => Gdn_Format::toDateTime(),
            'Admin' => '2'
        );

        $this->EventArguments['SystemUser'] = &$SystemUser;
        $this->fireEvent('BeforeSystemUser');

        $SystemUserID = $this->SQL->insert($this->Name, $SystemUser);

        saveToConfig('Garden.SystemUserID', $SystemUserID);
        return $SystemUserID;
    }

    /**
     * Add points to a user's total.
     *
     * @since 2.1.0
     * @access public
     */
    public static function givePoints($UserID, $Points, $Source = 'Other', $Timestamp = false) {
        if (!$Timestamp) {
            $Timestamp = time();
        }

        if (is_array($Source)) {
            $CategoryID = val('CategoryID', $Source, 0);
            $Source = $Source[0];
        } else {
            $CategoryID = 0;
        }

        if ($CategoryID > 0) {
            $CategoryIDs = array($CategoryID, 0);
        } else {
            $CategoryIDs = array($CategoryID);
        }

        foreach ($CategoryIDs as $ID) {
            // Increment source points for the user.
            self::_givePoints($UserID, $Points, 'a', $Source, $ID);

            // Increment total points for the user.
            self::_givePoints($UserID, $Points, 'w', 'Total', $ID, $Timestamp);
            self::_givePoints($UserID, $Points, 'm', 'Total', $ID, $Timestamp);
            self::_givePoints($UserID, $Points, 'a', 'Total', $ID, $Timestamp);

            // Increment global daily points.
            self::_givePoints(0, $Points, 'd', 'Total', $ID, $Timestamp);
        }

        // Grab the user's total points.
        $Points = Gdn::sql()->getWhere('UserPoints', array('UserID' => $UserID, 'SlotType' => 'a', 'Source' => 'Total', 'CategoryID' => 0))->value('Points');

//      Gdn::controller()->informMessage('Points: '.$Points);
        Gdn::userModel()->setField($UserID, 'Points', $Points);

        // Fire a give points event.
        Gdn::userModel()->EventArguments['UserID'] = $UserID;
        Gdn::userModel()->EventArguments['CategoryID'] = $CategoryID;
        Gdn::userModel()->EventArguments['Points'] = $Points;
        Gdn::userModel()->fireEvent('GivePoints');
    }

    /**
     * Add points to a user's total in a specific timeslot.
     *
     * @since 2.1.0
     * @access protected
     * @see self::GivePoints
     */
    protected static function _givePoints($UserID, $Points, $SlotType, $Source = 'Total', $CategoryID = 0, $Timestamp = false) {
        $TimeSlot = gmdate('Y-m-d', Gdn_Statistics::timeSlotStamp($SlotType, $Timestamp));

        $Px = Gdn::database()->DatabasePrefix;
        $Sql = "insert {$Px}UserPoints (UserID, SlotType, TimeSlot, Source, CategoryID, Points)
         values (:UserID, :SlotType, :TimeSlot, :Source, :CategoryID, :Points)
         on duplicate key update Points = Points + :Points1";

        Gdn::database()->query($Sql, array(
            ':UserID' => $UserID,
            ':Points' => $Points,
            ':SlotType' => $SlotType,
            ':Source' => $Source,
            ':CategoryID' => $CategoryID,
            ':TimeSlot' => $TimeSlot,
            ':Points1' => $Points));
    }

    /**
     * Register a new user.
     *
     * @param $FormPostValues
     * @param array $Options
     * @return bool|int|string
     * @throws Exception
     */
    public function register($FormPostValues, $Options = array()) {
        $Valid = true;
        $FormPostValues['LastIPAddress'] = Gdn::request()->ipAddress();

        // Throw an error if the registering user has an active session
//      if (Gdn::session()->isValid())
//         $this->Validation->addValidationResult('Name', 'You are already registered.');

        // Check for banning first.
        $Valid = BanModel::checkUser($FormPostValues, null, true);
        if (!$Valid) {
            $this->Validation->addValidationResult('UserID', 'Sorry, permission denied.');
        }

        // Throw an event to allow plugins to block the registration.
        unset($this->EventArguments['User']);
        $this->EventArguments['User'] = $FormPostValues;
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
            case 'captcha':
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
            case 'basic':
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
     * @param $UserID
     */
    public function removePicture($UserID) {
        // Grab the current photo.
        $User = $this->getID($UserID, DATASET_TYPE_ARRAY);
        if ($Photo = $User['Photo']) {
            $ProfilePhoto = changeBasename($Photo, 'p%s');
            $Upload = new Gdn_Upload();
            $Upload->delete($ProfilePhoto);

            $this->setField($UserID, 'Photo', null);
        }
    }

    /**
     * Get a user's counter.
     *
     * @param $User
     * @param $Column
     * @return bool
     */
    public function profileCount($User, $Column) {
        if (is_numeric($User)) {
            $User = $this->SQL->getWhere('User', array('UserID' => $User))->firstRow(DATASET_TYPE_ARRAY);
        } elseif (is_string($User))
            $User = $this->SQL->getWhere('User', array('Name' => $User))->firstRow(DATASET_TYPE_ARRAY);
        elseif (is_object($User))
            $User = (array)$User;

        if (!$User) {
            return false;
        }

        if (array_key_exists($Column, $User) && $User[$Column] === null) {
            $UserID = $User['UserID'];
            switch ($Column) {
                case 'CountComments':
                    $Count = $this->SQL->getCount('Comment', array('InsertUserID' => $UserID));
                    $this->setField($UserID, 'CountComments', $Count);
                    break;
                case 'CountDiscussions':
                    $Count = $this->SQL->getCount('Discussion', array('InsertUserID' => $UserID));
                    $this->setField($UserID, 'CountDiscussions', $Count);
                    break;
                case 'CountBookmarks':
                    $Count = $this->SQL->getCount('UserDiscussion', array('UserID' => $UserID, 'Bookmarked' => '1'));
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
     * $Settings controls certain save functionality
     *
     *  SaveRoles - Save 'RoleID' field as user's roles. Default false.
     *  HashPassword - Hash the provided password on update. Default true.
     *  FixUnique - Try to resolve conflicts with unique constraints on Name and Email. Default false.
     *  ValidateEmail - Make sure the provided email addresses is formattted properly. Default true.
     *  NoConfirmEmail - Disable email confirmation. Default false.
     *
     */
    public function save($FormPostValues, $Settings = false) {
        // See if the user's related roles should be saved or not.
        $SaveRoles = val('SaveRoles', $Settings);

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Custom Rule: This will make sure that at least one role was selected if saving roles for this user.
        if ($SaveRoles) {
            $this->Validation->addRule('OneOrMoreArrayItemRequired', 'function:ValidateOneOrMoreArrayItemRequired');
            // $this->Validation->AddValidationField('RoleID', $FormPostValues);
            $this->Validation->applyRule('RoleID', 'OneOrMoreArrayItemRequired');
        }

        // Make sure that checkbox vals are saved as the appropriate value

        if (array_key_exists('ShowEmail', $FormPostValues)) {
            $FormPostValues['ShowEmail'] = ForceBool($FormPostValues['ShowEmail'], '0', '1', '0');
        }

        if (array_key_exists('Banned', $FormPostValues)) {
            $FormPostValues['Banned'] = ForceBool($FormPostValues['Banned'], '0', '1', '0');
        }

        if (array_key_exists('Confirmed', $FormPostValues)) {
            $FormPostValues['Confirmed'] = ForceBool($FormPostValues['Confirmed'], '0', '1', '0');
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
        $User = array();
        $Insert = $UserID > 0 ? false : true;
        if ($Insert) {
            $this->addInsertFields($FormPostValues);
        } else {
            $this->addUpdateFields($FormPostValues);
            $User = $this->getID($UserID, DATASET_TYPE_ARRAY);
            if (!$User) {
                $User = array();
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

        if ($this->validate($FormPostValues, $Insert) && $UniqueValid) {
            $Fields = $this->Validation->validationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
            $RoleIDs = val('RoleID', $Fields, 0);
            $Username = val('Name', $Fields);
            $Email = val('Email', $Fields);
            $Fields = $this->Validation->schemaValidationFields(); // Only fields that are present in the schema
            // Remove the primary key from the fields collection before saving
            $Fields = removeKeyFromArray($Fields, $this->PrimaryKey);
            if (array_key_exists('AllIPAddresses', $Fields) && is_array($Fields['AllIPAddresses'])) {
                $Fields['AllIPAddresses'] = implode(',', $Fields['AllIPAddresses']);
            }

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
                        $Attributes = @unserialize($Attributes);
                    }

                    $ConfirmEmailRoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_UNCONFIRMED);
                    if (!empty($ConfirmEmailRoleID)) {
                        // The confirm email role is set and it exists so go ahead with the email confirmation.
                        $NewKey = randomString(8);
                        $EmailKey = touchValue('EmailKey', $Attributes, $NewKey);
                        $Fields['Attributes'] = serialize($Attributes);
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
                    if (ArrayValue('Name', $Fields, '') != '' || arrayValue('Email', $Fields, '') != '') {
                        if (!$this->validateUniqueFields($Username, $Email, $UserID)) {
                            return false;
                        }
                    }

                    if (array_key_exists('Attributes', $Fields) && !is_string($Fields['Attributes'])) {
                        $Fields['Attributes'] = serialize($Fields['Attributes']);
                    }

                    // Perform save DB operation
                    $this->SQL->put($this->Name, $Fields, array($this->PrimaryKey => $UserID));

                    // Record activity if the person changed his/her photo.
                    $Photo = arrayValue('Photo', $FormPostValues);
                    if ($Photo !== false) {
                        if (val('CheckExisting', $Settings)) {
                            $User = $this->getID($UserID);
                            $OldPhoto = val('Photo', $User);
                        }

                        if (isset($OldPhoto) && $OldPhoto != $Photo) {
                            if (IsUrl($Photo)) {
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

                            $ActivityModel->save(array(
                                'ActivityUserID' => $UserID,
                                'RegardingUserID' => Gdn::session()->UserID,
                                'ActivityType' => 'PictureChange',
                                'HeadlineFormat' => $HeadlineFormat,
                                'Story' => img($PhotoUrl, array('alt' => t('Thumbnail')))
                            ));
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
                    $UserID = $this->_insert($Fields, $Settings);

                    if ($UserID) {
                        // Report that the user was created.
                        $ActivityModel = new ActivityModel();
                        $ActivityModel->save(
                            array(
                            'ActivityType' => 'Registration',
                            'ActivityUserID' => $UserID,
                            'HeadlineFormat' => t('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                            'Story' => t('Welcome Aboard!')),
                            false,
                            array('GroupBy' => 'ActivityTypeID')
                        );

                        // Report the creation for mods.
                        $ActivityModel->save(array(
                            'ActivityType' => 'Registration',
                            'ActivityUserID' => Gdn::session()->UserID,
                            'RegardingUserID' => $UserID,
                            'NotifyUserID' => ActivityModel::NOTIFY_MODS,
                            'HeadlineFormat' => t('HeadlineFormat.AddUser', '{ActivityUserID,user} added an account for {RegardingUserID,user}.')));
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
            $this->clearCache($UserID, array('user'));
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
        $FormPostValues['Email'] = val('Email', $FormPostValues, strtolower($Name.'@'.Gdn_Url::Host()));
        $FormPostValues['ShowEmail'] = '0';
        $FormPostValues['TermsOfService'] = '1';
        $FormPostValues['DateOfBirth'] = '1975-09-16';
        $FormPostValues['DateLastActive'] = Gdn_Format::toDateTime();
        $FormPostValues['DateUpdated'] = Gdn_Format::toDateTime();
        $FormPostValues['Gender'] = 'u';
        $FormPostValues['Admin'] = '1';

        $this->addInsertFields($FormPostValues);

        if ($this->validate($FormPostValues, true) === true) {
            $Fields = $this->Validation->validationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
            $Username = val('Name', $Fields);
            $Email = val('Email', $Fields);
            $Fields = $this->Validation->schemaValidationFields(); // Only fields that are present in the schema

            // Insert the new user
            $UserID = $this->_insert($Fields, array('NoConfirmEmail' => true, 'Setup' => true));

            if ($UserID) {
                $ActivityModel = new ActivityModel();
                $ActivityModel->save(
                    array(
                    'ActivityUserID' => $UserID,
                    'ActivityType' => 'Registration',
                    'HeadlineFormat' => t('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                    'Story' => t('Welcome Aboard!')
                    ),
                    false,
                    array('GroupBy' => 'ActivityTypeID')
                );
            }

            $this->saveRoles($UserID, array(16), false);
        }
        return $UserID;
    }

    /**
     *
     *
     * @param $UserID
     * @param $RoleIDs
     * @param $RecordEvent
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
            $RoleIDs = consolidateArrayValuesByKey($RoleIDs, 'RoleID');
        }

        if (!is_array($RoleIDs)) {
            $RoleIDs = array($RoleIDs);
        }

        // Get the current roles.
        $OldRoleIDs = array();
        $OldRoleData = $this->SQL
            ->select('ur.RoleID, r.Name')
            ->from('Role r')
            ->join('UserRole ur', 'r.RoleID = ur.RoleID')
            ->where('ur.UserID', $UserID)
            ->get()
            ->resultArray();

        if ($OldRoleData !== false) {
            $OldRoleIDs = consolidateArrayValuesByKey($OldRoleData, 'RoleID');
        }

        // 1a) Figure out which roles to delete.
        $DeleteRoleIDs = array_diff($OldRoleIDs, $RoleIDs);
        // 1b) Remove old role associations for this user.
        if (count($DeleteRoleIDs) > 0) {
            $this->SQL->whereIn('RoleID', $DeleteRoleIDs)->delete('UserRole', array('UserID' => $UserID));
        }

        // 2a) Figure out which roles to insert.
        $InsertRoleIDs = array_diff($RoleIDs, $OldRoleIDs);
        // 2b) Insert the new role associations for this user.
        foreach ($InsertRoleIDs as $InsertRoleID) {
            if (is_numeric($InsertRoleID)) {
                $this->SQL->insert('UserRole', array('UserID' => $UserID, 'RoleID' => $InsertRoleID));
            }
        }

        $this->clearCache($UserID, array('roles', 'permissions'));

        if ($RecordEvent && (count($DeleteRoleIDs) > 0 || count($InsertRoleIDs) > 0)) {
            $User = $this->getID($UserID);
            $Session = Gdn::session();

            $OldRoles = false;
            if ($OldRoleData !== false) {
                $OldRoles = consolidateArrayValuesByKey($OldRoleData, 'Name');
            }

            $NewRoles = false;
            $NewRoleData = $this->SQL
                ->select('r.RoleID, r.Name')
                ->from('Role r')
                ->join('UserRole ur', 'r.RoleID = ur.RoleID')
                ->where('ur.UserID', $UserID)
                ->get()
                ->resultArray();
            if ($NewRoleData !== false) {
                $NewRoles = consolidateArrayValuesByKey($NewRoleData, 'Name');
            }


            $RemovedRoles = array_diff($OldRoles, $NewRoles);
            $NewRoles = array_diff($NewRoles, $OldRoles);

            foreach ($RemovedRoles as $RoleName) {
                Logger::event(
                    'role_remove',
                    Logger::INFO,
                    "{username} removed {toUsername} from the {role} role.",
                    array('toUsername' => $User->Name, 'role' => $RoleName)
                );
            }

            foreach ($NewRoles as $RoleName) {
                Logger::event(
                    'role_add',
                    Logger::INFO,
                    "{username} added {toUsername} to the {role} role.",
                    array('toUsername' => $User->Name, 'role' => $RoleName)
                );
            }

            $RemovedCount = count($RemovedRoles);
            $NewCount = count($NewRoles);
            $Story = '';
            if ($RemovedCount > 0 && $NewCount > 0) {
                $Story = sprintf(
                    t('%1$s was removed from the %2$s %3$s and added to the %4$s %5$s.'),
                    $User->Name,
                    implode(', ', $RemovedRoles),
                    plural($RemovedCount, 'role', 'roles'),
                    implode(', ', $NewRoles),
                    plural($NewCount, 'role', 'roles')
                );
            } elseif ($RemovedCount > 0) {
                $Story = sprintf(
                    t('%1$s was removed from the %2$s %3$s.'),
                    $User->Name,
                    implode(', ', $RemovedRoles),
                    plural($RemovedCount, 'role', 'roles')
                );
            } elseif ($NewCount > 0) {
                $Story = sprintf(
                    t('%1$s was added to the %2$s %3$s.'),
                    $User->Name,
                    implode(', ', $NewRoles),
                    plural($NewCount, 'role', 'roles')
                );
            }
        }
    }

    /**
     * Search users.
     *
     * @param $Filter
     * @param string $OrderFields
     * @param string $OrderDirection
     * @param bool $Limit
     * @param bool $Offset
     * @return Gdn_DataSet
     * @throws Exception
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
            $RoleID = $this->SQL->getWhere('Role', array('Name' => $Keywords))->value('RoleID');
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
                $Like = array('u.Name' => $Keywords, 'u.Email' => $Keywords);

                $this->SQL
                    ->orOp()
                    ->beginWhereGroup()
                    ->orLike($Like, '', 'right')
                    ->endWhereGroup();
            }
        }

        // Optimized searches need at least some criteria before performing a query.
        if ($Optimize && $this->SQL->WhereCount() == 0 && !$RoleID) {
            $this->SQL->reset();
            return new Gdn_DataSet(array());
        }

        $Data = $this->SQL
            ->where('u.Deleted', 0)
            ->orderBy($OrderFields, $OrderDirection)
            ->limit($Limit, $Offset)
            ->get();

        $Result =& $Data->result();

        foreach ($Result as &$Row) {
            if ($Row->Photo && !isUrl($Row->Photo)) {
                $Row->Photo = Gdn_Upload::url($Row->Photo);
            }

            $Row->Attributes = @unserialize($Row->Preferences);
            $Row->Preferences = @unserialize($Row->Preferences);
        }

        return $Data;
    }

    /**
     * Count search results.
     *
     * @param bool $Filter
     * @return int
     */
    public function searchCount($Filter = false) {
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
        } elseif (isset($UserID)) {
            $this->SQL->where('u.UserID', $UserID);
        } else {
            $RoleID = $this->SQL->getWhere('Role', array('Name' => $Keywords))->value('RoleID');
        }

        if (isset($Where)) {
            $this->SQL->where($Where);
        }

        $this->SQL
            ->select('u.UserID', 'count', 'UserCount')
            ->from('User u');

        if ($RoleID) {
            $this->SQL->join('UserRole ur2', "u.UserID = ur2.UserID and ur2.RoleID = $RoleID");
        } elseif (isset($UserID)) {
            $this->SQL->where('u.UserID', $UserID);
        } else {
            // Search on the user table.
            $Like = trim($Keywords) == '' ? false : array('u.Name' => $Keywords, 'u.Email' => $Keywords);

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
     * @param string $Search
     * @since 2.2
     */
    public function tagSearch($Search, $Limit = 10) {
        $Search = trim(str_replace(array('%', '_'), array('\%', '\_'), $Search));

        $Results = $this->SQL
            ->select('UserID', '', 'id')
            ->select('Name', '', 'name')
            ->from('User')
            ->like('Name', $Search, 'right')
            ->where('Deleted', 0)
            ->limit($Limit)
            ->get()->resultArray();
        return $Results;
    }

    /**
     * To be used for invitation registration.
     *
     * @param array $FormPostValues
     * @param array $Options
     * @return int UserID.
     */
    public function insertForInvite($FormPostValues, $Options = array()) {
        $RoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);
        if (!is_array($RoleIDs) || count($RoleIDs) == 0) {
            throw new Exception(t('The default role has not been configured.'), 400);
        }

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules:
        $this->Validation->applyRule('Email', 'Email');

        // Make sure that the checkbox val for email is saved as the appropriate enum
        // TODO: DO I REALLY NEED THIS???
        if (array_key_exists('ShowEmail', $FormPostValues)) {
            $FormPostValues['ShowEmail'] = forceBool($FormPostValues['ShowEmail'], '0', '1', '0');
        }

        if (array_key_exists('Banned', $FormPostValues)) {
            $FormPostValues['Banned'] = forceBool($FormPostValues['Banned'], '0', '1', '0');
        }

        $this->AddInsertFields($FormPostValues);

        // Make sure that the user has a valid invitation code, and also grab
        // the user's email from the invitation:
        $InviteUserID = 0;
        $InviteUsername = '';
        $InvitationCode = arrayValue('InvitationCode', $FormPostValues, '');

        $Invitation = $this->SQL->getWhere('Invitation', array('Code' => $InvitationCode))->firstRow();

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
            $Spam = SpamModel::IsSpam('Registration', $FormPostValues);
            if ($Spam) {
                $this->Validation->addValidationResult('Spam', 'You are not allowed to register at this time.');
                return;
            }

            $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
            $Username = arrayValue('Name', $Fields);
            $Email = arrayValue('Email', $Fields);
            $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
            $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);

            // Make sure the username & email aren't already being used
            if (!$this->ValidateUniqueFields($Username, $Email)) {
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
                $InvitationRoleIDs = unserialize($InvitationRoleIDs);

                if (is_array($InvitationRoleIDs)
                    && count(array_filter($InvitationRoleIDs))
                ) {
                    // Overwrite default RoleIDs set at top of method.
                    $RoleIDs = $InvitationRoleIDs;
                }
            }

            $Fields['Roles'] = $RoleIDs;
            $UserID = $this->_Insert($Fields, $Options);

            // Associate the new user id with the invitation (so it cannot be used again)
            $this->SQL
                ->update('Invitation')
                ->set('AcceptedUserID', $UserID)
                ->where('InvitationID', $Invitation->InvitationID)
                ->put();

            // Report that the user was created.
            $ActivityModel = new ActivityModel();
            $ActivityModel->save(
                array(
                'ActivityUserID' => $UserID,
                'ActivityType' => 'Registration',
                'HeadlineFormat' => t('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                'Story' => t('Welcome Aboard!')
                ),
                false,
                array('GroupBy' => 'ActivityTypeID')
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
    public function insertForApproval($FormPostValues, $Options = array()) {
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
            $FormPostValues['ShowEmail'] = ForceBool($FormPostValues['ShowEmail'], '0', '1', '0');
        }

        if (array_key_exists('Banned', $FormPostValues)) {
            $FormPostValues['Banned'] = ForceBool($FormPostValues['Banned'], '0', '1', '0');
        }

        $this->AddInsertFields($FormPostValues);

        if ($this->validate($FormPostValues, true)) {
            // Check for spam.
            $Spam = SpamModel::IsSpam('Registration', $FormPostValues);
            if ($Spam) {
                $this->Validation->addValidationResult('Spam', 'You are not allowed to register at this time.');
                return;
            }

            $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
            $Username = arrayValue('Name', $Fields);
            $Email = arrayValue('Email', $Fields);
            $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
            $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);

            if (!$this->ValidateUniqueFields($Username, $Email)) {
                return false;
            }

            // If in Captcha registration mode, check the captcha value.
            if (val('CheckCaptcha', $Options, true)) {
                $CaptchaValid = ValidateCaptcha();
                if ($CaptchaValid !== true) {
                    $this->Validation->addValidationResult('Garden.Registration.CaptchaPublicKey', 'The reCAPTCHA value was not entered correctly. Please try again.');
                    return false;
                }
            }

            // Define the other required fields:
            $Fields['Email'] = $Email;
            $Fields['Roles'] = (array)$RoleIDs;

            // And insert the new user
            $UserID = $this->_Insert($Fields, $Options);
        } else {
            $UserID = false;
        }
        return $UserID;
    }

    /**
     * To be used for basic registration, and captcha registration.
     *
     * @param $FormPostValues
     * @param bool $CheckCaptcha
     * @param array $Options
     * @return bool|int|string
     * @throws Exception
     */
    public function insertForBasic($FormPostValues, $CheckCaptcha = true, $Options = array()) {
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
            $FormPostValues['ShowEmail'] = ForceBool($FormPostValues['ShowEmail'], '0', '1', '0');
        }

        if (array_key_exists('Banned', $FormPostValues)) {
            $FormPostValues['Banned'] = ForceBool($FormPostValues['Banned'], '0', '1', '0');
        }

        $this->AddInsertFields($FormPostValues);

        if ($this->validate($FormPostValues, true) === true) {
            $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
            $Username = arrayValue('Name', $Fields);
            $Email = arrayValue('Email', $Fields);
            $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
            $Fields['Roles'] = $RoleIDs;
            $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);

            // If in Captcha registration mode, check the captcha value
            if ($CheckCaptcha && Gdn::config('Garden.Registration.Method') == 'Captcha') {
                $CaptchaPublicKey = arrayValue('Garden.Registration.CaptchaPublicKey', $FormPostValues, '');
                $CaptchaValid = ValidateCaptcha($CaptchaPublicKey);
                if ($CaptchaValid !== true) {
                    $this->Validation->addValidationResult('Garden.Registration.CaptchaPublicKey', 'The reCAPTCHA value was not entered correctly. Please try again.');
                    return false;
                }
            }

            if (!$this->ValidateUniqueFields($Username, $Email)) {
                return false;
            }

            // Check for spam.
            if (val('ValidateSpam', $Options, true)) {
                $ValidateSpam = $this->ValidateSpamRegistration($FormPostValues);
                if ($ValidateSpam !== true) {
                    return $ValidateSpam;
                }
            }

            // Define the other required fields:
            $Fields['Email'] = $Email;

            // And insert the new user
            $UserID = $this->_Insert($Fields, $Options);
            if ($UserID && !val('NoActivity', $Options)) {
                $ActivityModel = new ActivityModel();
                $ActivityModel->save(
                    array(
                    'ActivityUserID' => $UserID,
                    'ActivityType' => 'Registration',
                    'HeadlineFormat' => t('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                    'Story' => t('Welcome Aboard!')
                    ),
                    false,
                    array('GroupBy' => 'ActivityTypeID')
                );
            }
        }
        return $UserID;
    }

    /**
     * Parent override.
     *
     * @param array $Fields
     */
    public function addInsertFields(&$Fields) {
        $this->defineSchema();

        // Set the hour offset based on the client's clock.
        $ClientHour = arrayValue('ClientHour', $Fields, '');
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
    function updateVisit($UserID, $ClientHour = false) {
        $UserID = (int)$UserID;
        if (!$UserID) {
            throw new Exception('A valid User ID is required.');
        }

        $User = Gdn::userModel()->getID($UserID, DATASET_TYPE_ARRAY);

        $Fields = array();

        if (Gdn_Format::toTimestamp($User['DateLastActive']) < strtotime('5 minutes ago')) {
            // We only update the last active date once every 5 minutes to cut down on DB activity.
            $Fields['DateLastActive'] = Gdn_Format::toDateTime();
        }

        // Update session level information if necessary.
        if ($UserID == Gdn::session()->UserID) {
            $IP = Gdn::request()->ipAddress();
            $Fields['LastIPAddress'] = $IP;

            if (Gdn::session()->NewVisit()) {
                $Fields['CountVisits'] = val('CountVisits', $User, 0) + 1;
            }
        }

        // Generate the AllIPs field.
        $AllIPs = val('AllIPAddresses', $User, array());
        if (is_string($AllIPs)) {
            $AllIPs = explode(',', $AllIPs);
            setValue('AllIPAddresses', $User, $AllIPs);
        }
        if (!is_array($AllIPs)) {
            $AllIPs = array();
        }
        if ($IP = val('InsertIPAddress', $User)) {
            array_unshift($AllIPs, ForceIPv4($IP));
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
        $Set = array();
        foreach ($Fields as $Name => $Value) {
            if (val($Name, $User) != $Value) {
                $Set[$Name] = $Value;
            }
        }

        if (!empty($Set)) {
            $this->EventArguments['Fields'] =& $Set;
            $this->fireEvent('UpdateVisit');

            $this->setField($UserID, $Set);
        }

        if ($User['LastIPAddress'] != $Fields['LastIPAddress']) {
            $User = $this->getID($UserID, DATASET_TYPE_ARRAY);
            if (!BanModel::CheckUser($User, null, true, $Bans)) {
                $BanModel = new BanModel();
                $Ban = array_pop($Bans);
                $BanModel->SaveUser($User, true, $Ban);
                $BanModel->SetCounts($Ban);
            }
        }
    }

    /**
     *
     *
     * @param array $FormPostValues
     * @param bool $Insert
     * @return bool|array
     */
    public function validate($FormPostValues, $Insert = false) {
        $this->defineSchema();

        if (self::noEmail()) {
            // Remove the email requirement.
            $this->Validation->UnapplyRule('Email', 'Required');
        }

        if (!$Insert && !isset($FormPostValues['Name'])) {
            $this->Validation->UnapplyRule('Name');
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
     * @return object
     */
    public function validateCredentials($Email = '', $ID = 0, $Password) {
        $this->EventArguments['Credentials'] = array('Email' => $Email, 'ID' => $ID, 'Password' => $Password);
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
        if (!$PasswordHash->CheckPassword($Password, $UserData->Password, $HashMethod, $UserData->Name)) {
            return false;
        }

        if ($PasswordHash->Weak || ($HashMethod && strcasecmp($HashMethod, 'Vanilla') != 0)) {
            $Pw = $PasswordHash->HashPassword($Password);
            $this->SQL->update('User')
                ->set('Password', $Pw)
                ->set('HashMethod', 'Vanilla')
                ->where('UserID', $UserData->UserID)
                ->put();
        }

        $UserData->Attributes = Gdn_Format::Unserialize($UserData->Attributes);
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
        $Log = ValidateRequired($DiscoveryText);
        $Spam = SpamModel::IsSpam('Registration', $User, array('Log' => $Log));

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
     * @param $Username
     * @param $Email
     * @param string $UserID
     * @param bool $Return
     * @return array|bool
     */
    public function validateUniqueFields($Username, $Email, $UserID = '', $Return = false) {
        $Valid = true;
        $Where = array();
        if (is_numeric($UserID)) {
            $Where['UserID <> '] = $UserID;
        }

        $Result = array('Name' => true, 'Email' => true);

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
     * @param $UserID
     * @param $Email
     * @return bool
     * @throws Exception
     */
    public function approve($UserID, $Email) {
        $applicantRoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_APPLICANT);

        // Make sure the $UserID is an applicant
        $RoleData = $this->GetRoles($UserID);
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
            $this->SaveRoles($UserID, $RoleIDs, false);

            // Send out a notification to the user
            $User = $this->getID($UserID);
            if ($User) {
                $Email->subject(sprintf(t('[%1$s] Membership Approved'), c('Garden.Title')));
                $Email->message(sprintf(t('EmailMembershipApproved'), $User->Name, ExternalUrl(SignInUrl())));
                $Email->to($User->Email);
                //$Email->from(c('Garden.SupportEmail'), c('Garden.SupportName'));
                $Email->send();

                // Report that the user was approved.
                $ActivityModel = new ActivityModel();
                $ActivityModel->save(
                    array(
                    'ActivityUserID' => $UserID,
                    'ActivityType' => 'Registration',
                    'HeadlineFormat' => t('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                    'Story' => t('Welcome Aboard!')
                    ),
                    false,
                    array('GroupBy' => 'ActivityTypeID')
                );

                // Report the approval for moderators.
                $ActivityModel->save(
                    array(
                    'ActivityType' => 'Registration',
                    'ActivityUserID' => Gdn::session()->UserID,
                    'RegardingUserID' => $UserID,
                    'NotifyUserID' => ActivityModel::NOTIFY_MODS,
                    'HeadlineFormat' => t('HeadlineFormat.RegistrationApproval', '{ActivityUserID,user} approved the applications for {RegardingUserID,user}.')),
                    false,
                    array('GroupBy' => array('ActivityTypeID', 'ActivityUserID'))
                );

                Gdn::userModel()->saveAttribute($UserID, 'ApprovedByUserID', Gdn::session()->UserID);
            }


        }
        return true;
    }

    /**
     * Delete a single user.
     *
     * @param int $UserID
     * @param array $Options See DeleteContent(), GetDelete()
     */
    public function delete($UserID, $Options = array()) {
        if ($UserID == $this->GetSystemUserID()) {
            $this->Validation->addValidationResult('', 'You cannot delete the system user.');
            return false;
        }

        $Content = array();

        // Remove shared authentications.
        $this->GetDelete('UserAuthentication', array('UserID' => $UserID), $Content);

        // Remove role associations.
        $this->GetDelete('UserRole', array('UserID' => $UserID), $Content);

        $this->DeleteContent($UserID, $Options, $Content);

        // Remove the user's information
        $this->SQL->update('User')
            ->set(array(
                'Name' => t('[Deleted User]'),
                'Photo' => null,
                'Password' => RandomString('10'),
                'About' => '',
                'Email' => 'user_'.$UserID.'@deleted.email',
                'ShowEmail' => '0',
                'Gender' => 'u',
                'CountVisits' => 0,
                'CountInvitations' => 0,
                'CountNotifications' => 0,
                'InviteUserID' => null,
                'DiscoveryText' => '',
                'Preferences' => null,
                'Permissions' => null,
                'Attributes' => Gdn_Format::Serialize(array('State' => 'Deleted')),
                'DateSetInvitations' => null,
                'DateOfBirth' => null,
                'DateUpdated' => Gdn_Format::toDateTime(),
                'HourOffset' => '0',
                'Score' => null,
                'Admin' => 0,
                'Deleted' => 1
            ))
            ->where('UserID', $UserID)
            ->put();

        // Remove user's cache rows
        $this->ClearCache($UserID);

        return true;
    }

    /**
     *
     *
     * @param $UserID
     * @param array $Options
     * @param array $Content
     * @return bool|int
     * @throws Exception
     */
    public function deleteContent($UserID, $Options = array(), $Content = array()) {
        $Log = val('Log', $Options);
        if ($Log === true) {
            $Log = 'Delete';
        }

        $Result = false;

        // Fire an event so applications can remove their associated user data.
        $this->EventArguments['UserID'] = $UserID;
        $this->EventArguments['Options'] = $Options;
        $this->EventArguments['Content'] =& $Content;
        $this->fireEvent('BeforeDeleteUser');

        $User = $this->getID($UserID, DATASET_TYPE_ARRAY);

        if (!$Log) {
            $Content = null;
        }

        // Remove photos
        /*$PhotoData = $this->SQL->select()->from('Photo')->where('InsertUserID', $UserID)->get();
      foreach ($PhotoData->result() as $Photo) {
         @unlink(PATH_UPLOADS.DS.$Photo->Name);
      }
      $this->SQL->delete('Photo', array('InsertUserID' => $UserID));
      */

        // Remove invitations
        $this->GetDelete('Invitation', array('InsertUserID' => $UserID), $Content);
        $this->GetDelete('Invitation', array('AcceptedUserID' => $UserID), $Content);

        // Remove activities
        $this->GetDelete('Activity', array('InsertUserID' => $UserID), $Content);

        // Remove activity comments.
        $this->GetDelete('ActivityComment', array('InsertUserID' => $UserID), $Content);

        // Remove comments in moderation queue
        $this->GetDelete('Log', array('RecordUserID' => $UserID, 'Operation' => 'Pending'), $Content);

        // Clear out information on the user.
        $this->setField($UserID, array(
            'About' => null,
            'Title' => null,
            'Location' => null));

        if ($Log) {
            $User['_Data'] = $Content;
            unset($Content); // in case data gets copied

            $Result = LogModel::insert($Log, 'User', $User, val('LogOptions', $Options, array()));
        }

        return $Result;
    }

    /**
     *
     *
     * @param $UserID
     * @return bool
     * @throws Exception
     */
    public function decline($UserID) {
        $applicantRoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_APPLICANT);

        // Make sure the user is an applicant
        $RoleData = $this->GetRoles($UserID);
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
     *
     *
     * @param $UserID
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
        $InviteRoles = Gdn::config('Garden.Registration.InviteRoles', array());
        if (!is_array($InviteRoles) || count($InviteRoles) == 0) {
            return 0;
        }

        // Build an array of roles that can send invitations
        $CanInviteRoles = array();
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
                array(
                    'CountInvitations' => $InviteCount,
                    'DateSetInvitations' => Gdn_Format::date('', '%Y-%m-01') // The first day of this month
                ),
                array('UserID' => $UserID)
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
     * @param int The unique id of the user being affected.
     * @param int The number to reduce CountInvitations by.
     */
    public function reduceInviteCount($UserID, $ReduceBy = 1) {
        $CurrentCount = $this->GetInvitationCount($UserID);

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
     * @param int The unique id of the user being affected.
     * @param int The number to increase CountInvitations by.
     */
    public function increaseInviteCount($UserID, $IncreaseBy = 1) {
        $CurrentCount = $this->GetInvitationCount($UserID);

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
     * @param int The UserID to save.
     * @param string The about message being saved.
     */
    public function saveAbout($UserID, $About) {
        $About = substr($About, 0, 1000);
        $this->setField($UserID, 'About', $About);
    }

    /**
     * Saves a name/value to the user's specified $Column.
     *
     * This method throws exceptions when errors are encountered. Use try ...
     * catch blocks to capture these exceptions.
     *
     * @param string The name of the serialized column to save to. At the time of this writing there are three serialized columns on the user table: Permissions, Preferences, and Attributes.
     * @param int The UserID to save.
     * @param mixed The name of the value being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
     * @param mixed The value being saved.
     */
    public function saveToSerializedColumn($Column, $UserID, $Name, $Value = '') {
        // Load the existing values
        $UserData = $this->getID($UserID, DATASET_TYPE_OBJECT);

        if (!$UserData) {
            throw new Exception(sprintf('User %s not found.', $UserID));
        }

        $Values = val($Column, $UserData);

        if (!is_array($Values) && !is_object($Values)) {
            $Values = @unserialize($UserData->$Column);
        }

        // Throw an exception if the field was not empty but is also not an object or array
        if (is_string($Values) && $Values != '') {
            throw new Exception(sprintf(t('Serialized column "%s" failed to be unserialized.'), $Column));
        }

        if (!is_array($Values)) {
            $Values = array();
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
            $Name = array($Name => $Value);
        }


        $RawValues = array_merge($Values, $Name);
        $Values = array();
        foreach ($RawValues as $Key => $RawValue) {
            if (!is_null($RawValue)) {
                $Values[$Key] = $RawValue;
            }
        }

        $Values = Gdn_Format::Serialize($Values);

        // Save the values back to the db
        $SaveResult = $this->SQL->put('User', array($Column => $Values), array('UserID' => $UserID));
        $this->ClearCache($UserID, array('user'));

        return $SaveResult;
    }

    /**
     * Saves a user preference to the database.
     *
     * This is a convenience method that uses $this->SaveToSerializedColumn().
     *
     * @param int The UserID to save.
     * @param mixed The name of the preference being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
     * @param mixed The value being saved.
     */
    public function savePreference($UserID, $Preference, $Value = '') {
        // Make sure that changes to the current user become effective immediately.
        $Session = Gdn::session();
        if ($UserID == $Session->UserID) {
            $Session->setPreference($Preference, $Value, false);
        }

        return $this->SaveToSerializedColumn('Preferences', $UserID, $Preference, $Value);
    }

    /**
     * Saves a user attribute to the database.
     *
     * This is a convenience method that uses $this->SaveToSerializedColumn().
     *
     * @param int The UserID to save.
     * @param mixed The name of the attribute being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
     * @param mixed The value being saved.
     */
    public function saveAttribute($UserID, $Attribute, $Value = '') {
        // Make sure that changes to the current user become effective immediately.
        $Session = Gdn::session();
        if ($UserID == $Session->UserID) {
            $Session->SetAttribute($Attribute, $Value);
        }

        return $this->SaveToSerializedColumn('Attributes', $UserID, $Attribute, $Value);
    }

    /**
     *
     *
     * @param $Data
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
     *
     *
     * @param $User
     * @throws Exception
     */
    public function setCalculatedFields(&$User) {
        if ($v = val('Attributes', $User)) {
            if (is_string($v)) {
                setValue('Attributes', $User, @unserialize($v));
            }
        }
        if ($v = val('Permissions', $User)) {
            if (is_string($v)) {
                setValue('Permissions', $User, @unserialize($v));
            }
        }
        if ($v = val('Preferences', $User)) {
            if (is_string($v)) {
                setValue('Preferences', $User, @unserialize($v));
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
                    $IPAddresses[$i] = ForceIPv4($IPAddress);
                }
                setValue('AllIPAddresses', $User, $IPAddresses);
            }
        }

        setValue('_CssClass', $User, '');
        if ($v = val('Banned', $User)) {
            setValue('_CssClass', $User, 'Banned');
        }

        $this->EventArguments['User'] =& $User;
        $this->fireEvent('SetCalculatedFields');
    }

    /**
     *
     *
     * @param $UserID
     * @param $Meta
     * @param string $Prefix
     */
    public static function setMeta($UserID, $Meta, $Prefix = '') {
        $Deletes = array();
        $Px = Gdn::database()->DatabasePrefix;
        $Sql = "insert {$Px}UserMeta (UserID, Name, Value) values(:UserID, :Name, :Value) on duplicate key update Value = :Value1";

        foreach ($Meta as $Name => $Value) {
            $Name = $Prefix.$Name;
            if ($Value === null || $Value == '') {
                $Deletes[] = $Name;
            } else {
                Gdn::database()->query($Sql, array(':UserID' => $UserID, ':Name' => $Name, ':Value' => $Value, ':Value1' => $Value));
            }
        }
        if (count($Deletes)) {
            Gdn::sql()->whereIn('Name', $Deletes)->where('UserID', $UserID)->delete('UserMeta');
        }
    }

    /**
     *
     *
     * @param $UserID
     * @param string $ExplicitKey
     * @return string
     */
    public function setTransientKey($UserID, $ExplicitKey = '') {
        $Key = $ExplicitKey == '' ? betterRandomString(16, 'Aa0') : $ExplicitKey;
        $this->saveAttribute($UserID, 'TransientKey', $Key);
        return $Key;
    }

    /**
     *
     *
     * @param $UserID
     * @param $Attribute
     * @param bool $DefaultValue
     * @return mixed
     */
    public function getAttribute($UserID, $Attribute, $DefaultValue = false) {
//
//      $Result = $DefaultValue;
//      if ($Data !== FALSE) {
//         $Attributes = Gdn_Format::Unserialize($Data->Attributes);
//         if (is_array($Attributes))
//            $Result = arrayValue($Attribute, $Attributes, $DefaultValue);
//
//      }

        $User = $this->getID($UserID, DATASET_TYPE_ARRAY);
        $Result = val($Attribute, $User['Attributes'], $DefaultValue);

        return $Result;
    }

    /**
     *
     *
     * @param null $User
     * @param bool $Force
     * @throws Exception
     */
    public function sendEmailConfirmationEmail($User = null, $Force = false) {

        if (!$User) {
            $User = Gdn::session()->User;
        } elseif (is_numeric($User))
            $User = $this->getID($User);
        elseif (is_string($User))
            $User = $this->GetByEmail($User);

        if (!$User) {
            throw notFoundException('User');
        }

        $User = (array)$User;

        if (is_string($User['Attributes'])) {
            $User['Attributes'] = @unserialize($User['Attributes']);
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
            $Code = RandomString(8);
            $Attributes = $User['Attributes'];
            if (!is_array($Attributes)) {
                $Attributes = array('EmailKey' => $Code);
            } else {
                $Attributes['EmailKey'] = $Code;
            }

            $this->saveAttribute($User['UserID'], $Attributes);
        }

        $AppTitle = Gdn::config('Garden.Title');
        $Email = new Gdn_Email();
        $Email->subject(sprintf(t('[%s] Confirm Your Email Address'), $AppTitle));
        $Email->to($User['Email']);

        $EmailFormat = t('EmailConfirmEmail', self::DEFAULT_CONFIRM_EMAIL);
        $Data = array();
        $Data['EmailKey'] = $Code;
        $Data['User'] = arrayTranslate((array)$User, array('UserID', 'Name', 'Email'));
        $Data['Title'] = $AppTitle;

        $Message = formatString($EmailFormat, $Data);
        $Message = $this->_AddEmailHeaderFooter($Message, $Data);
        $Email->message($Message);

        $Email->send();
    }

    /**
     * Send welcome email to user.
     *
     * @param $UserID
     * @param $Password
     * @param string $RegisterType
     * @param null $AdditionalData
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

        $Data = array();
        $Data['User'] = arrayTranslate((array)$User, array('UserID', 'Name', 'Email'));
        $Data['Sender'] = arrayTranslate((array)$Sender, array('Name', 'Email'));
        $Data['Title'] = $AppTitle;
        if (is_array($AdditionalData)) {
            $Data = array_merge($Data, $AdditionalData);
        }

        $Data['EmailKey'] = valr('Attributes.EmailKey', $User);

        // Check for the new email format.
        if (($EmailFormat = t("EmailWelcome{$RegisterType}", '#')) != '#') {
            $Message = formatString($EmailFormat, $Data);
        } else {
            $Message = sprintf(
                t('EmailWelcome'),
                $User->Name,
                $Sender->Name,
                $AppTitle,
                ExternalUrl('/'),
                $Password,
                $User->Email
            );
        }

        // Add the email confirmation key.
        if ($Data['EmailKey']) {
            $Message .= "\n\n".FormatString(t('EmailConfirmEmail', self::DEFAULT_CONFIRM_EMAIL), $Data);
        }
        $Message = $this->_AddEmailHeaderFooter($Message, $Data);

        $Email->message($Message);

        $Email->send();
    }

    /**
     * Send password email.
     *
     * @param $UserID
     * @param $Password
     * @throws Exception
     */
    public function sendPasswordEmail($UserID, $Password) {
        $Session = Gdn::session();
        $Sender = $this->getID($Session->UserID);
        $User = $this->getID($UserID);
        $AppTitle = Gdn::config('Garden.Title');
        $Email = new Gdn_Email();
        $Email->subject(sprintf(t('[%s] Password Reset'), $AppTitle));
        $Email->to($User->Email);

        $Data = array();
        $Data['User'] = arrayTranslate((array)$User, array('Name', 'Email'));
        $Data['Sender'] = arrayTranslate((array)$Sender, array('Name', 'Email'));
        $Data['Title'] = $AppTitle;

        $EmailFormat = t('EmailPassword');
        if (strpos($EmailFormat, '{') !== false) {
            $Message = formatString($EmailFormat, $Data);
        } else {
            $Message = sprintf(
                $EmailFormat,
                $User->Name,
                $Sender->Name,
                $AppTitle,
                ExternalUrl('/'),
                $Password,
                $User->Email
            );
        }

        $Message = $this->_AddEmailHeaderFooter($Message, $Data);
        $Email->message($Message);

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

        $Attributes = arrayValue('Attributes', $Data);
        if (is_string($Attributes)) {
            $Attributes = @unserialize($Attributes);
        }

        if (!is_array($Attributes)) {
            $Attributes = array();
        }

        // If the user didnt log in, they won't have a UserID yet. That means they want a new
        // account. So create one for them.
        if (!isset($Data['UserID']) || $Data['UserID'] <= 0) {
            // Prepare the user data.
            $UserData['Name'] = $Data['Name'];
            $UserData['Password'] = RandomString(16);
            $UserData['Email'] = arrayValue('Email', $Data, 'no@email.com');
            $UserData['Gender'] = strtolower(substr(ArrayValue('Gender', $Data, 'u'), 0, 1));
            $UserData['HourOffset'] = arrayValue('HourOffset', $Data, 0);
            $UserData['DateOfBirth'] = arrayValue('DateOfBirth', $Data, '');
            $UserData['CountNotifications'] = 0;
            $UserData['Attributes'] = $Attributes;
            $UserData['InsertIPAddress'] = Gdn::request()->ipAddress();
            if ($UserData['DateOfBirth'] == '') {
                $UserData['DateOfBirth'] = '1975-09-16';
            }

            // Make sure there isn't another user with this username.
            if ($this->ValidateUniqueFields($UserData['Name'], $UserData['Email'])) {
                if (!BanModel::CheckUser($UserData, $this->Validation, true)) {
                    throw permissionException('Banned');
                }

                // Insert the new user.
                $this->AddInsertFields($UserData);
                $UserID = $this->_Insert($UserData);
            }

            if ($UserID) {
                $NewUserRoleIDs = $this->NewUserRoleIDs();

                // Save the roles.
                $Roles = val('Roles', $Data, false);
                if (empty($Roles)) {
                    $Roles = $NewUserRoleIDs;
                }

                $this->SaveRoles($UserID, $Roles, false);
            }
        } else {
            $UserID = $Data['UserID'];
        }

        // Synchronize the transientkey from the external user data source if it is present (eg. WordPress' wpnonce).
        if (array_key_exists('TransientKey', $Attributes) && $Attributes['TransientKey'] != '' && $UserID > 0) {
            $this->SetTransientKey($UserID, $Attributes['TransientKey']);
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
        $RegistrationMethod = c('Garden.Registration.Method', 'Captcha');
        $DefaultRoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);
        switch ($RegistrationMethod) {

            case 'Approval':
                $RoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_APPLICANT);
                break;

            case 'Invitation':
                throw new Gdn_UserException(t('This forum is currently set to invitation only mode.'));
                break;

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
     * @param $Email
     * @return bool
     * @throws Exception
     */
    public function passwordRequest($Email) {
        if (!$Email) {
            return false;
        }

        $Users = $this->getWhere(array('Email' => $Email))->ResultObject();
        if (count($Users) == 0) {
            // Check for the username.
            $Users = $this->getWhere(array('Name' => $Email))->ResultObject();
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
            $PasswordResetKey = BetterRandomString(20, 'Aa0');
            $PasswordResetExpires = strtotime('+1 hour');
            $this->saveAttribute($User->UserID, 'PasswordResetKey', $PasswordResetKey);
            $this->saveAttribute($User->UserID, 'PasswordResetExpires', $PasswordResetExpires);
            $AppTitle = c('Garden.Title');
            $Email->subject(sprintf(t('[%s] Password Reset Request'), $AppTitle));
            $Email->to($User->Email);

            $Email->message(
                sprintf(
                    t('PasswordRequest'),
                    $User->Name,
                    $AppTitle,
                    ExternalUrl('/entry/passwordreset/'.$User->UserID.'/'.$PasswordResetKey)
                )
            );
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
     * @param $UserID
     * @param $Password
     * @return array|bool|null|object|type
     * @throws Exception
     */
    public function passwordReset($UserID, $Password) {
        // Encrypt the password before saving
        $PasswordHash = new Gdn_PasswordHash();
        $Password = $PasswordHash->HashPassword($Password);

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
     * @param boolean $PasswordOK
     */
    public static function rateLimit($User, $PasswordOK) {
        if (Gdn::cache()->activeEnabled()) {
            // Rate limit using Gdn_Cache.
            $UserRateKey = formatString(self::LOGIN_RATE_KEY, array('Source' => $User->UserID));
            $UserRate = (int)Gdn::cache()->get($UserRateKey);
            $UserRate += 1;
            Gdn::cache()->store($UserRateKey, 1, array(
                Gdn_Cache::FEATURE_EXPIRY => self::LOGIN_RATE
            ));

            $SourceRateKey = formatString(self::LOGIN_RATE_KEY, array('Source' => Gdn::request()->ipAddress()));
            $SourceRate = (int)Gdn::cache()->get($SourceRateKey);
            $SourceRate += 1;
            Gdn::cache()->store($SourceRateKey, 1, array(
                Gdn_Cache::FEATURE_EXPIRY => self::LOGIN_RATE
            ));

        } elseif (c('Garden.Apc', false) && function_exists('apc_store')) {
            // Rate limit using the APC data store.
            $UserRateKey = formatString(self::LOGIN_RATE_KEY, array('Source' => $User->UserID));
            $UserRate = (int)apc_fetch($UserRateKey);
            $UserRate += 1;
            apc_store($UserRateKey, 1, self::LOGIN_RATE);

            $SourceRateKey = formatString(self::LOGIN_RATE_KEY, array('Source' => Gdn::request()->ipAddress()));
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

            $UserModel->SaveToSerializedColumn(
                'Attributes',
                $User->UserID,
                array('LastLoginAttempt' => $Now, 'LoginRate' => 1)
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
     * @throws Exception
     */
    public function setField($RowID, $Property, $Value = false) {
        if (!is_array($Property)) {
            $Property = array($Property => $Value);
        }

        $this->defineSchema();
        $Fields = $this->Schema->Fields();

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
        self::SerializeRow($Set);

        $this->SQL
            ->update($this->Name)
            ->set($Set)
            ->where('UserID', $RowID)
            ->put();

        if (in_array($Property, array('Permissions'))) {
            $this->ClearCache($RowID, array('permissions'));
        } else {
            $this->UpdateUserCache($RowID, $Property, $Value);
        }

        if (!is_array($Property)) {
            $Property = array($Property => $Value);
        }

        $this->EventArguments['UserID'] = $RowID;
        $this->EventArguments['Fields'] = $Property;
        $this->fireEvent('AfterSetField');

        return $Value;
    }

    /**
     * Get a user from the cache by name or ID
     *
     * @param type $UserToken either a userid or a username
     * @param string $TokenType either 'userid' or 'name'
     * @return type user array or FALSE
     */
    public function getUserFromCache($UserToken, $TokenType) {
        if ($TokenType == 'name') {
            $UserNameKey = formatString(self::USERNAME_KEY, array('Name' => md5($UserToken)));
            $UserID = Gdn::cache()->get($UserNameKey);

            if ($UserID === Gdn_Cache::CACHEOP_FAILURE) {
                return false;
            }
            $UserToken = $UserID;
            $TokenType = 'userid';
        } else {
            $UserID = $UserToken;
        }

        if ($TokenType != 'userid') {
            return false;
        }

        // Get from memcached
        $UserKey = formatString(self::USERID_KEY, array('UserID' => $UserToken));
        $User = Gdn::cache()->get($UserKey);

        return $User;
    }

    /**
     *
     *
     * @param $UserID
     * @param $Field
     * @param null $Value
     */
    public function updateUserCache($UserID, $Field, $Value = null) {
        // Try and get the user from the cache.
        $User = $this->GetUserFromCache($UserID, 'userid');

        if (!$User) {
            return;
        }

//      $User = $this->getID($UserID, DATASET_TYPE_ARRAY);
        if (!is_array($Field)) {
            $Field = array($Field => $Value);
        }

        foreach ($Field as $f => $v) {
            $User[$f] = $v;
        }
        $this->userCache($User);
    }

    /**
     * Cache user object
     *
     * @param type $User
     * @return type
     */
    public function userCache($User, $UserID = null) {
        if (!$UserID) {
            $UserID = val('UserID', $User, null);
        }
        if (is_null($UserID) || !$UserID) {
            return false;
        }

        $Cached = true;

        $UserKey = formatString(self::USERID_KEY, array('UserID' => $UserID));
        $Cached = $Cached & Gdn::cache()->store($UserKey, $User, array(
                Gdn_Cache::FEATURE_EXPIRY => 3600
            ));

        $UserNameKey = formatString(self::USERNAME_KEY, array('Name' => md5(val('Name', $User))));
        $Cached = $Cached & Gdn::cache()->store($UserNameKey, $UserID, array(
                Gdn_Cache::FEATURE_EXPIRY => 3600
            ));
        return $Cached;
    }

    /**
     * Cache user's roles
     *
     * @param type $UserID
     * @param type $RoleIDs
     * @return type
     */
    public function userCacheRoles($UserID, $RoleIDs) {
        if (is_null($UserID) || !$UserID) {
            return false;
        }

        $Cached = true;

        $UserRolesKey = formatString(self::USERROLES_KEY, array('UserID' => $UserID));
        $Cached = $Cached & Gdn::cache()->store($UserRolesKey, $RoleIDs);
        return $Cached;
    }

    /**
     * Delete cached data for user
     *
     * @param type $UserID
     * @return type
     */
    public function clearCache($UserID, $CacheTypesToClear = null) {
        if (is_null($UserID) || !$UserID) {
            return false;
        }

        if (is_null($CacheTypesToClear)) {
            $CacheTypesToClear = array('user', 'roles', 'permissions');
        }

        if (in_array('user', $CacheTypesToClear)) {
            $UserKey = formatString(self::USERID_KEY, array('UserID' => $UserID));
            Gdn::cache()->Remove($UserKey);
        }

        if (in_array('roles', $CacheTypesToClear)) {
            $UserRolesKey = formatString(self::USERROLES_KEY, array('UserID' => $UserID));
            Gdn::cache()->Remove($UserRolesKey);
        }

        if (in_array('permissions', $CacheTypesToClear)) {
            Gdn::sql()->put('User', array('Permissions' => ''), array('UserID' => $UserID));

            $PermissionsIncrement = $this->GetPermissionsIncrement();
            $UserPermissionsKey = formatString(self::USERPERMISSIONS_KEY, array('UserID' => $UserID, 'PermissionsIncrement' => $PermissionsIncrement));
            Gdn::cache()->Remove($UserPermissionsKey);
        }
        return true;
    }

    /**
     *
     */
    public function clearPermissions() {
        if (!Gdn::cache()->activeEnabled()) {
            $this->SQL->put('User', array('Permissions' => ''), array('Permissions <>' => ''));
        }

        $PermissionsIncrementKey = self::INC_PERMISSIONS_KEY;
        $PermissionsIncrement = $this->GetPermissionsIncrement();
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
        ;
    }
}
