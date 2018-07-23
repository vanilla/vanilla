<?php
/**
 * User model.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

use Garden\EventManager;

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

    /** Timeout for SSO */
    const SSO_TIMEOUT = 1200;

    /** @var EventManager */
    private $eventManager;

    /** @var */
    public $SessionColumns;

    /** @var int The number of users when database optimizations kick in. */
    public $UserThreshold = 10000;

    /** @var int The number of users when extreme database optimizations kick in. */
    public $UserMegaThreshold = 1000000;

    /**
     * Class constructor. Defines the related database table name.
     *
     * @param EventManager $eventManager
     */
    public function __construct(EventManager $eventManager = null) {
        parent::__construct('User');

        if ($eventManager === null) {
            $this->eventManager = Gdn::getContainer()->get(EventManager::class);
        } else {
            $this->eventManager = $eventManager;
        }

        $this->addFilterField([
            'Admin', 'Deleted', 'CountVisits', 'CountInvitations', 'CountNotifications', 'Preferences', 'Permissions',
            'LastIPAddress', 'AllIPAddresses', 'DateFirstVisit', 'DateLastActive', 'CountDiscussions', 'CountComments',
            'Score'
        ]);
    }

    /**
     * Generate a random code for use in email confirmation.
     *
     * @return string
     */
    private function confirmationCode() {
        $result = betterRandomString(32, 'Aa0');
        return $result;
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
     * @param string $message Deprecated.
     * @param array $data Deprecated.
     * @return string Deprecated.
     */
    private function addEmailHeaderFooter($message, $data) {
        $header = t('EmailHeader', '');
        if ($header) {
            $message = formatString($header, $data)."\n".$message;
        }

        $footer = t('EmailFooter', '');
        if ($footer) {
            $message .= "\n".formatString($footer, $data);
        }

        return $message;
    }

    /**
     * Set password strength meter on a form.
     *
     * @param Gdn_Controller $controller The controller to add the password strength information to.
     */
    public function addPasswordStrength($controller) {
        $controller->addJsFile('password.js');
        $controller->addDefinition('MinPassLength', c('Garden.Password.MinLength'));
        $controller->addDefinition(
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
     * @param int $userID The ID of the user to ban.
     * @param array $options Additional options for the ban.
     * @throws Exception Throws an exception if something goes wrong during the banning.
     */
    public function ban($userID, $options) {
        $user = $this->getID($userID);
        $banned = val('Banned', $user, 0);

        $this->setField($userID, 'Banned', BanModel::setBanned($banned, true, BanModel::BAN_MANUAL));

        $logID = false;
        if (val('DeleteContent', $options)) {
            $options['Log'] = 'Ban';
            $logID = $this->deleteContent($userID, $options);
        }

        if ($logID) {
            $this->saveAttribute($userID, 'BanLogID', $logID);
        }

        $this->EventArguments['UserID'] = $userID;
        $this->EventArguments['Options'] = $options;
        $this->fireEvent('Ban');

        if (val('AddActivity', $options, true)) {
            switch (val('Reason', $options, '')) {
                case '':
                    $story = null;
                    break;
                case 'Spam':
                    $story = t('Banned for spamming.');
                    break;
                case 'Abuse':
                    $story = t('Banned for being abusive.');
                    break;
                default:
                    $story = $options['Reason'];
                    break;
            }

            $activity = [
                'ActivityType' => 'Ban',
                'NotifyUserID' => ActivityModel::NOTIFY_MODS,
                'ActivityUserID' => $userID,
                'RegardingUserID' => Gdn::session()->UserID,
                'HeadlineFormat' => t('HeadlineFormat.Ban', '{RegardingUserID,You} banned {ActivityUserID,you}.'),
                'Story' => $story,
                'Data' => ['LogID' => $logID]];

            $activityModel = new ActivityModel();
            $activityModel->save($activity);
        }
    }

    /**
     * Checks the specified user's for the given permission. Returns a boolean value indicating if the action is permitted.
     *
     * @param mixed $user The user to check.
     * @param mixed $permission The permission (or array of permissions) to check.
     * @param array $options
     * @return boolean
     */
    public function checkPermission($user, $permission, $options = []) {
        if (is_numeric($user)) {
            $user = $this->getID($user);
        }
        $user = (object)$user;

        if ($user->Banned || $user->Deleted) {
            return false;
        }

        if ($user->Admin) {
            return true;
        }

        // Grab the permissions for the user.
        if ($user->UserID == 0) {
            $permissions = $this->getPermissions(0);
        } elseif (!Gdn::cache()->activeEnabled() && is_array($user->Permissions)) {
            // Only attempt to use the DB field value if permissions aren't being cached elsewhere.
            $permissions = new Vanilla\Permissions($user->Permissions);
        } else {
            $permissions = $this->getPermissions($user->UserID);
        }

        $id = val('ForeignID', $options, null);

        return $permissions->has($permission, $id);
    }

    /**
     * Merge the old user into the new user.
     *
     * @param int $oldUserID The ID of the old user.
     * @param int $newUserID The ID of the new user.
     */
    public function merge($oldUserID, $newUserID) {
        $oldUser = $this->getID($oldUserID, DATASET_TYPE_ARRAY);
        $newUser = $this->getID($newUserID, DATASET_TYPE_ARRAY);

        if (!$oldUser || !$newUser) {
            throw new Gdn_UserException("Could not find one or both users to merge.");
        }

        $map = ['UserID', 'Name', 'Email', 'CountVisits', 'CountDiscussions', 'CountComments'];

        $result = ['MergeID' => null, 'Before' => [
            'OldUser' => arrayTranslate($oldUser, $map),
            'NewUser' => arrayTranslate($newUser, $map)]];

        // Start the merge.
        $mergeID = $this->mergeStart($oldUserID, $newUserID);

        // Copy all discussions from the old user to the new user.
        $this->mergeCopy($mergeID, 'Discussion', 'InsertUserID', $oldUserID, $newUserID);

        // Copy all the comments from the old user to the new user.
        $this->mergeCopy($mergeID, 'Comment', 'InsertUserID', $oldUserID, $newUserID);

        // Update the last comment user ID.
        $this->SQL->put('Discussion', ['LastCommentUserID' => $newUserID], ['LastCommentUserID' => $oldUserID]);

        // Clear the categories cache.
        CategoryModel::clearCache();

        // Copy all of the activities.
        $this->mergeCopy($mergeID, 'Activity', 'NotifyUserID', $oldUserID, $newUserID);
        $this->mergeCopy($mergeID, 'Activity', 'InsertUserID', $oldUserID, $newUserID);
        $this->mergeCopy($mergeID, 'Activity', 'ActivityUserID', $oldUserID, $newUserID);

        // Copy all of the activity comments.
        $this->mergeCopy($mergeID, 'ActivityComment', 'InsertUserID', $oldUserID, $newUserID);

        // Copy all conversations.
        $this->mergeCopy($mergeID, 'Conversation', 'InsertUserID', $oldUserID, $newUserID);
        $this->mergeCopy($mergeID, 'ConversationMessage', 'InsertUserID', $oldUserID, $newUserID, 'MessageID');
        $this->mergeCopy($mergeID, 'UserConversation', 'UserID', $oldUserID, $newUserID, 'ConversationID');

        $this->EventArguments['MergeID'] = $mergeID;
        $this->EventArguments['OldUser'] = $oldUser;
        $this->EventArguments['NewUser'] = $newUser;
        $this->fireEvent('Merge');

        $this->mergeFinish($mergeID);

        $oldUser = $this->getID($oldUserID, DATASET_TYPE_ARRAY);
        $newUser = $this->getID($newUserID, DATASET_TYPE_ARRAY);

        $result['MergeID'] = $mergeID;
        $result['After'] = [
            'OldUser' => arrayTranslate($oldUser, $map),
            'NewUser' => arrayTranslate($newUser, $map)];

        return $result;
    }

    /**
     * Backup user before merging.
     *
     * @param int $mergeID The ID of the merge table entry.
     * @param string $table The name of the table being backed up.
     * @param string $column The name of the column being backed up.
     * @param int $oldUserID The ID of the old user.
     * @param int $newUserID The ID of the new user.
     * @param string $pK The primary key column name of the table.
     */
    private function mergeCopy($mergeID, $table, $column, $oldUserID, $newUserID, $pK = '') {
        if (!$pK) {
            $pK = $table.'ID';
        }

        // Insert the columns to the bak table.
        $sql = "insert ignore GDN_UserMergeItem(`MergeID`, `Table`, `Column`, `RecordID`, `OldUserID`, `NewUserID`)
         select :MergeID, :Table, :Column, `$pK`, :OldUserID, :NewUserID
         from `GDN_$table` t
         where t.`$column` = :OldUserID2";
        Gdn::sql()->Database->query(
            $sql,
            [':MergeID' => $mergeID, ':Table' => $table, ':Column' => $column,
                ':OldUserID' => $oldUserID, ':NewUserID' => $newUserID, ':OldUserID2' => $oldUserID]
        );

        Gdn::sql()->options('Ignore', true)->put(
            $table,
            [$column => $newUserID],
            [$column => $oldUserID]
        );
    }

    /**
     * Start merging user accounts.
     *
     * @param int $oldUserID The ID of the old user.
     * @param int $newUserID The ID of the new user.
     * @return int|null Returns the merge table ID of the merge.
     * @throws Gdn_UserException Throws an exception of there is a data validation error.
     */
    private function mergeStart($oldUserID, $newUserID) {
        $model = new Gdn_Model('UserMerge');

        // Grab the users.
        $oldUser = $this->getID($oldUserID, DATASET_TYPE_ARRAY);
        $newUser = $this->getID($newUserID, DATASET_TYPE_ARRAY);

        // First see if there is a record with the same merge.
        $row = $model->getWhere(['OldUserID' => $oldUserID, 'NewUserID' => $newUserID])->firstRow(DATASET_TYPE_ARRAY);
        if ($row) {
            $mergeID = $row['MergeID'];

            // Save this merge in the log.
            if ($row['Attributes']) {
                $attributes = dbdecode($row['Attributes']);
            } else {
                $attributes = [];
            }

            $attributes['Log'][] = ['UserID' => Gdn::session()->UserID, 'Date' => Gdn_Format::toDateTime()];
            $row = ['MergeID' => $mergeID, 'Attributes' => $attributes];
        } else {
            $row = [
                'OldUserID' => $oldUserID,
                'NewUserID' => $newUserID];
        }

        $userSet = [];
        $oldUserSet = [];
        if (dateCompare($oldUser['DateFirstVisit'], $newUser['DateFirstVisit']) < 0) {
            $userSet['DateFirstVisit'] = $oldUser['DateFirstVisit'];
        }

        if (!isset($row['Attributes']['User']['CountVisits'])) {
            $userSet['CountVisits'] = $oldUser['CountVisits'] + $newUser['CountVisits'];
            $oldUserSet['CountVisits'] = 0;
        }

        if (!empty($userSet)) {
            // Save the user information on the merge record.
            foreach ($userSet as $key => $value) {
                // Only save changed values that aren't already there from a previous merge.
                if ($newUser[$key] != $value && !isset($row['Attributes']['User'][$key])) {
                    $row['Attributes']['User'][$key] = $newUser[$key];
                }
            }
        }

        $mergeID = $model->save($row);
        if (val('MergeID', $row)) {
            $mergeID = $row['MergeID'];
        }

        if (!$mergeID) {
            throw new Gdn_UserException($model->Validation->resultsText());
        }

        // Update the user with the new user-level data.
        $this->setField($newUserID, $userSet);
        if (!empty($oldUserSet)) {
            $this->setField($oldUserID, $oldUserSet);
        }

        return $mergeID;
    }

    /**
     * Finish merging user accounts.
     *
     * @param int $mergeID The merge table ID.
     */
    protected function mergeFinish($mergeID) {
        $row = Gdn::sql()->getWhere('UserMerge', ['MergeID' => $mergeID])->firstRow(DATASET_TYPE_ARRAY);

        if (isset($row['Attributes']) && !empty($row['Attributes'])) {
            trace(dbdecode($row['Attributes']), 'Merge Attributes');
        }

        $userIDs = [
            $row['OldUserID'],
            $row['NewUserID']];

        foreach ($userIDs as $userID) {
            $this->counts('countdiscussions', $userID);
            $this->counts('countcomments', $userID);
        }
    }

    /**
     * User counts.
     *
     * @param string $column The name of the count column. (ex. CountDiscussions, CountComments).
     * @param int|null $userID The user ID to get the counts for or **null** for the current user.
     */
    public function counts($column, $userID = null) {
        if ($userID > 0) {
            $where = ['UserID' => $userID];
        } else {
            $where = null;
        }

        switch (strtolower($column)) {
            case 'countdiscussions':
                Gdn::database()->query(
                    DBAModel::getCountSQL('count', 'User', 'Discussion', 'CountDiscussions', 'DiscussionID', 'UserID', 'InsertUserID', $where)
                );
                break;
            case 'countcomments':
                Gdn::database()->query(
                    DBAModel::getCountSQL('count', 'User', 'Comment', 'CountComments', 'CommentID', 'UserID', 'InsertUserID', $where)
                );
                break;
        }

        if ($userID > 0) {
            $this->clearCache($userID);
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
     * @param int $userID The user to unban.
     * @param array $options Options for the unban.
     * @since 2.1
     */
    public function unBan($userID, $options = []) {
        $user = $this->getID($userID, DATASET_TYPE_ARRAY);
        if (!$user) {
            throw notFoundException();
        }

        $banned = $user['Banned'];
        if (!BanModel::isBanned($banned, BanModel::BAN_AUTOMATIC | BanModel::BAN_MANUAL)) {
            throw new Gdn_UserException(t("The user isn't banned.", "The user isn't banned or is banned by some other function."));
        }

        // Unban the user.
        $newBanned = BanModel::setBanned($banned, false, BanModel::BAN_AUTOMATIC | BanModel::BAN_MANUAL);
        $this->setField($userID, 'Banned', $newBanned);

        // Restore the user's content.
        if (val('RestoreContent', $options)) {
            $banLogID = $this->getAttribute($userID, 'BanLogID');

            if ($banLogID) {
                $logModel = new LogModel();

                try {
                    $logModel->restore($banLogID);
                } catch (Exception $ex) {
                    if ($ex->getCode() != 404) {
                        throw $ex;
                    }
                }
                $this->saveAttribute($userID, 'BanLogID', null);
            }
        }

        // Add an activity for the unbanning.
        if (val('AddActivity', $options, true)) {
            $activityModel = new ActivityModel();

            $story = val('Story', $options, null);

            // Notify the moderators of the unban.
            $activity = [
                'ActivityType' => 'Ban',
                'NotifyUserID' => ActivityModel::NOTIFY_MODS,
                'ActivityUserID' => $userID,
                'RegardingUserID' => Gdn::session()->UserID,
                'HeadlineFormat' => t('HeadlineFormat.Unban', '{RegardingUserID,You} unbanned {ActivityUserID,you}.'),
                'Story' => $story,
                'Data' => [
                    'Unban' => true
                ]
            ];

            $activityModel->queue($activity);

            // Notify the user of the unban.
            $activity['NotifyUserID'] = $userID;
            $activity['Emailed'] = ActivityModel::SENT_PENDING;
            $activity['HeadlineFormat'] = t('HeadlineFormat.Unban.Notification', "You've been unbanned.");
            $activityModel->queue($activity, false, ['Force' => true]);

            $activityModel->saveQueue();
        }
    }

    /**
     * Users respond to confirmation emails by clicking a link that takes them here.
     *
     * @param array|object $user The user confirming their email.
     * @param string $emailKey The token that was emailed to the user.
     * @return bool Returns **true** if the email was confirmed.
     */
    public function confirmEmail($user, $emailKey) {
        $attributes = val('Attributes', $user);
        $storedEmailKey = val('EmailKey', $attributes);
        $userID = val('UserID', $user);

        if (!$storedEmailKey || $emailKey != $storedEmailKey) {
            $this->Validation->addValidationResult('EmailKey', '@'.t(
                'Couldn\'t confirm email.',
                'We couldn\'t confirm your email. Check the link in the email we sent you or try sending another confirmation email.'
            ));
            return false;
        }

        $confirmRoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_UNCONFIRMED);
        $defaultRoles = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);

        // Update the user's roles.
        $userRoles = $this->getRoles($userID);
        $userRoleIDs = [];
        while ($userRole = $userRoles->nextRow(DATASET_TYPE_ARRAY)) {
            $userRoleIDs[] = $userRole['RoleID'];
        }

        // Sanitize result roles
        $roles = array_diff($userRoleIDs, $confirmRoleIDs);
        if (!sizeof($roles)) {
            $roles = $defaultRoles;
        }

        $this->EventArguments['ConfirmUserID'] = $userID;
        $this->EventArguments['ConfirmUserRoles'] = &$roles;
        $this->fireEvent('BeforeConfirmEmail');
        $this->saveRoles($userID, $roles, false);

        // Remove the email confirmation attributes.
        $this->saveAttribute($userID, ['EmailKey' => null]);
        $this->setField($userID, 'Confirmed', 1);
        return true;
    }

    /**
     * Initiate an SSO connection.
     *
     * @param string $string
     * @param bool $throwError
     * @return int|void
     */
    public function sso($string, $throwError = false) {
        if (!$string) {
            return null;
        }

        $parts = explode(' ', $string);

        $string = $parts[0];
        trace($string, "SSO String");
        $data = json_decode(base64_decode($string), true);
        trace($data, 'RAW SSO Data');

        if (!isset($parts[1])) {
            $this->Validation->addValidationResult('sso', 'Missing SSO signature.');
        }
        if (!isset($parts[2])) {
            $this->Validation->addValidationResult('sso', 'Missing SSO timestamp.');
        }
        if (count($this->Validation->results()) > 0) {
            $msg = $this->Validation->resultsText();
            if ($throwError) {
                throw new Gdn_UserException($msg, 400);
            }
            return false;
        }

        $signature = $parts[1];
        $timestamp = $parts[2];
        $hashMethod = val(3, $parts, 'hmacsha1');
        $clientID = val('client_id', $data);
        if (!$clientID) {
            $this->Validation->addValidationResult('sso', 'Missing SSO client_id');
            return false;
        }

        $provider = Gdn_AuthenticationProviderModel::getProviderByKey($clientID);

        if (!filter_var($timestamp, FILTER_VALIDATE_INT) || abs($timestamp - time()) > self::SSO_TIMEOUT) {
            $this->Validation->addValidationResult('sso', 'The timestamp is invalid.');
            return false;
        }

        if (!$provider) {
            $this->Validation->addValidationResult('sso', "Unknown SSO Provider: $clientID");
            return false;
        }

        $secret = $provider['AssociationSecret'];
        if (!trim($secret, '.')) {
            $this->Validation->addValidationResult('sso', 'Missing client secret');
            return false;
        }

        // Check the signature.
        switch ($hashMethod) {
            case 'hmacsha1':
                $calcSignature = hash_hmac('sha1', "$string $timestamp", $secret);
                break;
            default:
                $this->Validation->addValidationResult('sso', "Invalid SSO hash method $hashMethod.");
                return false;
        }
        if ($calcSignature != $signature) {
            $this->Validation->addValidationResult('sso', "Invalid SSO signature: $signature");
            return false;
        }

        $uniqueID = $data['uniqueid'];
        $user = arrayTranslate($data, [
            'name' => 'Name',
            'email' => 'Email',
            'photourl' => 'Photo',
            'roles' => 'Roles',
            'uniqueid' => null,
            'client_id' => null], true);

        // Remove important missing keys.
        if (!array_key_exists('photourl', $data)) {
            unset($user['Photo']);
        }
        if (!array_key_exists('roles', $data)) {
            unset($user['Roles']);
        }

        trace($user, 'SSO User');

        $userID = Gdn::userModel()->connect($uniqueID, $clientID, $user);
        return $userID;
    }

    /**
     * Sync user data.
     *
     * @param array|int $currentUser
     * @param array $newUser Data to overwrite user with.
     * @param bool $force
     * @since 2.1
     * @deprecated since 2.2.
     */
    public function synchUser($currentUser, $newUser, $force = false) {
        deprecated('UserModel::synchUser', 'UserModel::syncUser');
        $this->syncUser($currentUser, $newUser, $force);
    }

    /**
     * Sync user data.
     *
     * @param array|int $currentUser
     * @param array $newUser Data to overwrite user with.
     * @param bool $force
     * @since 2.1
     */
    public function syncUser($currentUser, $newUser, $force = false) {
        // Don't synchronize the user if we are configured not to.
        if (!$force && !c('Garden.Registration.ConnectSynchronize', true)) {
            return;
        }

        if (is_numeric($currentUser)) {
            $currentUser = $this->getID($currentUser, DATASET_TYPE_ARRAY);
        }

        // Don't sync the user photo if they've uploaded one already.
        $photo = val('Photo', $newUser);
        $currentPhoto = val('Photo', $currentUser);
        if (false
            || ($currentPhoto && !stringBeginsWith($currentPhoto, 'http'))
            || !is_string($photo)
            || ($photo && !stringBeginsWith($photo, 'http'))
            || strpos($photo, '.gravatar.') !== false
            || stringBeginsWith($photo, url('/', true))
        ) {
            unset($newUser['Photo']);
            trace('Not setting photo.');
        }

        if (c('Garden.SSO.SyncRoles') && c('Garden.SSO.SyncRolesBehavior') !== 'register') {
            // Translate the role names to IDs.
            $roles = val('Roles', $newUser, '');
            $roleIDs = $this->lookupRoleIDs($roles);
            if (empty($roleIDs)) {
                $roleIDs = $this->newUserRoleIDs();
            }
            $newUser['RoleID'] = $roleIDs;
        } else {
            unset($newUser['Roles']);
            unset($newUser['RoleID']);
        }

        // Save the user information.
        $newUser['UserID'] = $currentUser['UserID'];
        trace($newUser);

        $result = $this->save($newUser, ['NoConfirmEmail' => true, 'FixUnique' => true, 'SaveRoles' => isset($newUser['RoleID'])]);
        if (!$result) {
            trace($this->Validation->resultsText());
        }
    }

    /**
     * Connect a user with a foreign authentication system.
     *
     * @param string $uniqueID The user's unique key in the other authentication system.
     * @param string $providerKey The key of the system providing the authentication.
     * @param array $userData Data to go in the user table.
     * @param array $options Additional connect options.
     * @return int The new/existing user ID.
     */
    public function connect($uniqueID, $providerKey, $userData, $options = []) {
        trace('UserModel->Connect()');
        $provider = Gdn_AuthenticationProviderModel::getProviderByKey($providerKey);

        // Trusted providers can sync roles.
        if (val('Trusted', $provider) && (!empty($userData['Roles']) || !empty($userData['Roles']))) {
            saveToConfig('Garden.SSO.SyncRoles', true, false);
        }

        $userID = false;
        if (!isset($userData['UserID'])) {
            // Check to see if the user already exists.
            $auth = $this->getAuthentication($uniqueID, $providerKey);
            $userID = val('UserID', $auth);

            if ($userID) {
                $userData['UserID'] = $userID;
            }
        }

        if ($userID) {
            // Save the user.
            $this->syncUser($userID, $userData);
            return $userID;
        } else {
            // The user hasn't already been connected. We want to see if we can't find the user based on some critera.

            // Check to auto-connect based on email address.
            if (c('Garden.SSO.AutoConnect', c('Garden.Registration.AutoConnect')) && isset($userData['Email'])) {
                $user = $this->getByEmail($userData['Email']);
                trace($user, "Autoconnect User");
                if ($user) {
                    $user = (array)$user;
                    // Save the user.
                    $this->syncUser($user, $userData);
                    $userID = $user['UserID'];
                }
            }

            if (!$userID) {
                // Create a new user.
                $userData['Password'] = md5(microtime());
                $userData['HashMethod'] = 'Random';

                touchValue('CheckCaptcha', $options, false);
                touchValue('NoConfirmEmail', $options, true);
                touchValue('NoActivity', $options, true);

                // Translate SSO style roles to an array of role IDs suitable for registration.
                if (!empty($userData['Roles']) && !isset($userData['RoleID'])) {
                    $userData['RoleID'] = $this->lookupRoleIDs($userData['Roles']);
                }
                touchValue('SaveRoles', $options, !empty($userData['RoleID']) && c('Garden.SSO.SyncRoles', false));

                trace($userData, 'Registering User');
                $userID = $this->register($userData, $options);
            }

            if ($userID) {
                // Save the authentication.
                $this->saveAuthentication([
                    'UniqueID' => $uniqueID,
                    'Provider' => $providerKey,
                    'UserID' => $userID
                ]);
            } else {
                trace($this->Validation->resultsText(), TRACE_ERROR);
            }
        }

        return $userID;
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
     * @param string $value The gender string.
     * @return string
     */
    public static function fixGender($value) {
        if (!$value || !is_string($value)) {
            return 'u';
        }

        if ($value) {
            $value = strtolower(substr(trim($value), 0, 1));
        }

        if (!in_array($value, ['u', 'm', 'f'])) {
            $value = 'u';
        }

        return $value;
    }

    /**
     * A convenience method to be called when inserting users.
     *
     * Users are inserted in various methods depending on registration setups.
     *
     * @param array $fields The user to insert.
     * @param array $options Insert options.
     * @return int|false Returns the new ID of the user or **false** if there was an error.
     */
    private function insertInternal($fields, $options = []) {
        $this->EventArguments['InsertFields'] =& $fields;
        $this->fireEvent('BeforeInsertUser');

        if (!val('Setup', $options)) {
            unset($fields['Admin']);
        }

        $roles = val('Roles', $fields);
        unset($fields['Roles']);

        // Massage the roles for email confirmation.
        if (self::requireConfirmEmail() && !val('NoConfirmEmail', $options)) {
            $confirmRoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_UNCONFIRMED);

            if (!empty($confirmRoleIDs)) {
                touchValue('Attributes', $fields, []);
                $confirmationCode = $this->confirmationCode();
                $fields['Attributes']['EmailKey'] = $confirmationCode;
                $fields['Confirmed'] = 0;
                $roles = array_merge($roles, $confirmRoleIDs);
            }
        }

        // Make sure to encrypt the password for saving...
        if (array_key_exists('Password', $fields) && !val('HashMethod', $fields)) {
            $passwordHash = new Gdn_PasswordHash();
            $fields['Password'] = $passwordHash->hashPassword($fields['Password']);
            $fields['HashMethod'] = 'Vanilla';
        }

        // Certain configurations can allow blank email addresses.
        if (val('Email', $fields, null) === null) {
            $fields['Email'] = '';
        }

        if (array_key_exists('Attributes', $fields) && !is_string($fields['Attributes'])) {
            $fields['Attributes'] = dbencode($fields['Attributes']);
        }

        $userID = $this->SQL->insert($this->Name, $fields);
        if (is_array($roles)) {
            $this->saveRoles($userID, $roles, false);
        }

        // Approval registration requires an email confirmation.
        if ($userID && isset($confirmationCode) && strtolower(c('Garden.Registration.Method')) == 'approval') {
            // Send the confirmation email.
            $this->sendEmailConfirmationEmail($userID);
        }

        // Fire an event for user inserts
        $this->EventArguments['InsertUserID'] = $userID;
        $this->EventArguments['InsertFields'] = $fields;
        $this->fireEvent('AfterInsertUser');

        return $userID;
    }

    /**
     * Add user data to a result set.
     *
     * @param array|Gdn_DataSet $data Results we need to associate user data with.
     * @param array $columns Database columns containing UserIDs to get data for.
     * @param array $options Optionally pass list of user data to collect with key 'Join'.
     */
    public function joinUsers(&$data, $columns, $options = []) {
        if ($data instanceof Gdn_DataSet) {
            $data2 = $data->result();
        } else {
            $data2 = &$data;
        }

        // Grab all of the user fields that need to be joined.
        $userIDs = [];
        foreach ($data as $row) {
            foreach ($columns as $columnName) {
                $iD = val($columnName, $row);
                if (is_numeric($iD)) {
                    $userIDs[$iD] = 1;
                }
            }
        }

        // Get the users.
        $users = $this->getIDs(array_keys($userIDs));

        // Get column name prefix (ex: 'Insert' from 'InsertUserID')
        $prefixes = [];
        foreach ($columns as $columnName) {
            $prefixes[] = stringEndsWith($columnName, 'UserID', true, true);
        }

        // Join the user data using prefixes (ex: 'Name' for 'InsertUserID' becomes 'InsertName')
        $join = val('Join', $options, ['Name', 'Email', 'Photo']);

        foreach ($data2 as &$row) {
            foreach ($prefixes as $px) {
                $iD = val($px.'UserID', $row);
                if (is_numeric($iD)) {
                    $user = val($iD, $users, false);
                    foreach ($join as $column) {
                        $value = $user[$column];
                        if ($column == 'Photo') {
                            if ($value && !isUrl($value)) {
                                $value = Gdn_Upload::url(changeBasename($value, 'n%s'));
                            } elseif (!$value) {
                                $value = UserModel::getDefaultAvatarUrl($user);
                            }
                        }
                        setValue($px.$column, $row, $value);
                    }
                } else {
                    foreach ($join as $column) {
                        setValue($px.$column, $row, null);
                    }
                }


            }
        }
    }

    /**
     * Add multi-dimensional user data to an array.
     *
     * @param array $rows Results we need to associate user data with.
     * @param array $columns Database columns containing UserIDs to get data for.
     * @param array $options Additional options. Passed to filter event.
     */
    public function expandUsers(array &$rows, array $columns, array $options = []) {
        // How are we supposed to lookup users by column if we don't have any columns?
        if (count($rows) === 0 || count($columns) === 0) {
            return;
        }

        reset($rows);
        $single = is_string(key($rows));

        $userIDs = [];

        $extractUserIDs = function(array $row) use ($columns, &$userIDs) {
            foreach ($columns as $key) {
                if (array_key_exists($key, $row)) {
                    $id = $row[$key];
                    $userIDs[$id] = true;
                }
            }
        };

        // Fetch the users we'll be injecting into the rows.
        if ($single) {
            $extractUserIDs($rows);
        } else {
            foreach ($rows as $row) {
                $extractUserIDs($row);
            }
        }
        $users = !empty($userIDs) ? $this->getIDs(array_keys($userIDs)) : [];

        $populate = function(array &$row) use ($users, $columns) {
            foreach ($columns as $key) {
                $destination = stringEndsWith($key, 'ID', true, true);
                $id = val($key, $row);
                $user = null;
                if (is_numeric($id)) {
                    // Massage the data, before injecting it into the results.
                    $user = array_key_exists($id, $users) ? $users[$id] : false;
                    if ($user) {
                        // Make sure all user records have a valid photo.
                        $photo = val('Photo', $user);
                        if ($photo && !isUrl($photo)) {
                            $photoBase = changeBasename($photo, 'n%s');
                            $photo = Gdn_Upload::url($photoBase);
                        }
                        if (empty($photo)) {
                            $photo = UserModel::getDefaultAvatarUrl($user);
                        }
                        setValue('Photo', $user, $photo);
                        // Add an alias to Photo. Currently only used in API calls.
                        setValue('PhotoUrl', $user, $photo);
                    } else {
                        $user = [
                            'userID' => 0,
                            'name' => 'unknown',
                            'email' => 'unknown@example.com'
                        ];
                        $user['photoUrl'] = self::getDefaultAvatarUrl($user);
                    }
                }

                setValue($destination, $row, $user);
            }
        };

        // Inject those user records.
        if ($single) {
            $populate($rows);
        } else {
            foreach ($rows as &$row) {
                $populate($row);
            }
        }

        // Don't bother addons with whether or not this is a single row. Pack and unpack it here, as necessary.
        if ($single) {
            $rows = [$rows];
        }
        $rows = $this->eventManager->fireFilter('userModel_expandUsers', $rows, $options);
        if ($single) {
            $rows = reset($rows);
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
     * @param bool $safeData Makes sure that the query does not return any sensitive information about the user.
     * (password, attributes, preferences, etc).
     */
    public function userQuery($safeData = false) {
        if ($safeData) {
            $this->SQL->select('u.UserID, u.Name, u.Photo, u.CountVisits, u.DateFirstVisit, u.DateLastActive, u.DateInserted, u.DateUpdated, u.Score, u.Deleted, u.CountDiscussions, u.CountComments');
        } else {
            $this->SQL->select('u.*');
        }
        $this->SQL->from('User u');
    }

    /**
     * Load and compile user permissions
     *
     * @deprecated Use UserModel::getPermissions instead.
     * @param integer $userID
     * @param boolean $serialize
     * @return array
     */
    public function definePermissions($userID, $serialize = false) {
        if ($serialize) {
            deprecated("UserModel->definePermissions(id, true)", "UserModel->definePermissions(id)");
        }

        $permissions = $this->getPermissions($userID);

        return $serialize ? dbencode($permissions->getPermissions()) : $permissions->getPermissions();
    }

    /**
     * Take raw permission definitions and create.
     *
     * @param array $rawPermissions Database rows from the permissions table.
     * @return array Compiled permissions
     */
    public static function compilePermissions($rawPermissions) {
        $permissions = new Vanilla\Permissions();
        $permissions->compileAndLoad($rawPermissions);
        return $permissions->getPermissions();
    }

    /**
     * Default Gdn_Model::get() behavior.
     *
     * Prior to 2.0.18 it incorrectly behaved like GetID.
     * This method can be deleted entirely once it's been deprecated long enough.
     *
     * @return object DataSet
     */
    public function get($orderFields = '', $orderDirection = 'asc', $limit = false, $offset = false) {
        if (is_numeric($orderFields)) {
            // They're using the old version that was a misnamed getID()
            deprecated('UserModel->get()', 'UserModel->getID()');
            $result = $this->getID($orderFields);
        } else {
            $result = parent::get($orderFields, $orderDirection, $limit, $offset);
        }
        return $result;
    }

    /**
     * Get a user by their username.
     *
     * @param string $username The username of the user.
     * @return bool|object Returns the user or **false** if they don't exist.
     */
    public function getByUsername($username) {
        if ($username == '') {
            return false;
        }

        // Check page cache, then memcached
        $user = $this->getUserFromCache($username, 'name');

        if ($user === Gdn_Cache::CACHEOP_FAILURE) {
            $this->userQuery();
            $user = $this->SQL->where('u.Name', $username)->get()->firstRow(DATASET_TYPE_ARRAY);
            if ($user) {
                // If success, cache user
                $this->userCache($user);
            }
        }

        // Apply calculated fields
        $this->setCalculatedFields($user);

        // By default, firstRow() gives stdClass
        if ($user !== false) {
            $user = (object)$user;
        }

        return $user;
    }

    /**
     * Get user by email address.
     *
     * @param string $email The email address of the user.
     * @return array|bool|stdClass Returns the user or **false** if they don't exist.
     */
    public function getByEmail($email) {
        $this->userQuery();
        $user = $this->SQL->where('u.Email', $email)->get()->firstRow();
        $this->setCalculatedFields($user);
        return $user;
    }

    /**
     * Get users by role.
     *
     * @param int|string $role The ID or name of the role.
     * @return Gdn_DataSet Returns the users with the given role.
     */
    public function getByRole($role) {
        $roleID = $role; // Optimistic
        if (is_string($role)) {
            $roleModel = new RoleModel();
            $roles = $roleModel->getArray();
            $rolesByName = array_flip($roles);

            $roleID = val($role, $rolesByName, null);

            // No such role
            if (is_null($roleID)) {
                return new Gdn_DataSet();
            }
        }

        return $this->SQL->select('u.*')
            ->from('User u')
            ->join('UserRole ur', 'u.UserID = ur.UserID')
            ->where('ur.RoleID', $roleID, true, false)
            ->orderBy('DateInserted', 'desc')
            ->get();
    }

    /**
     * Get the most recently active users.
     *
     * @param int $limit The number of users to return.
     * @return Gdn_DataSet Returns a list of users.
     */
    public function getActiveUsers($limit = 5) {
        $userIDs = $this->SQL
            ->select('UserID')
            ->from('User')
            ->orderBy('DateLastActive', 'desc')
            ->limit($limit, 0)
            ->get()->resultArray();
        $userIDs = array_column($userIDs, 'UserID');

        $data = $this->SQL->getWhere('User', ['UserID' => $userIDs], 'DateLastActive', 'desc');
        return $data;
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
     * @param int|bool $limit
     * @param int|bool $offset
     * @return Gdn_DataSet Returns a data set of the users who are applicants.
     */
    public function getApplicants($limit = false, $offset = false) {
        $applicantRoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_APPLICANT);

        if (empty($applicantRoleIDs)) {
            return new Gdn_DataSet();
        }

        $this->SQL->select('u.*')
            ->from('User u')
            ->join('UserRole ur', 'u.UserID = ur.UserID')
            ->where('ur.RoleID', $applicantRoleIDs)
            ->orderBy('DateInserted', 'desc');

        if ($limit) {
            $this->SQL->limit($limit, $offset);
        }

        $result = $this->SQL->get();
        return $result;
    }

    /**
     * Get the a user authentication row.
     *
     * @param string $uniqueID The unique ID of the user in the foreign authentication scheme.
     * @param string $provider The key of the provider.
     * @return array|false
     */
    public function getAuthentication($uniqueID, $provider) {
        return $this->SQL->getWhere(
            'UserAuthentication',
            ['ForeignUserKey' => $uniqueID, 'ProviderKey' => $provider]
        )->firstRow(DATASET_TYPE_ARRAY);
    }

    /**
     * Get the user authentication row by user ID.
     *
     * @param int $userID The ID of the user to get the authentication for.
     * @param string $provider The key of the provider.
     * @return array|false Returns the authentication row or **false** if there isn't one.
     */
    public function getAuthenticationByUser($userID, $provider) {
        return $this->SQL->getWhere(
            'UserAuthentication',
            ['UserID' => $userID, 'ProviderKey' => $provider]
        )->firstRow(DATASET_TYPE_ARRAY);
    }

    /**
     *
     *
     * @param array|bool $like
     * @return int
     */
    public function getCountLike($like = false) {
        $this->SQL
            ->select('u.UserID', 'count', 'UserCount')
            ->from('User u');

        if (is_array($like)) {
            $this->SQL
                ->beginWhereGroup()
                ->orLike($like, '', 'right')
                ->endWhereGroup();
        }
        $this->SQL
            ->where('u.Deleted', 0);

        $data = $this->SQL->get()->firstRow();

        return $data === false ? 0 : $data->UserCount;
    }

    /**
     *
     *
     * @param array|false $where
     * @return int
     */
    public function getCountWhere($where = false) {
        $this->SQL
            ->select('u.UserID', 'count', 'UserCount')
            ->from('User u');

        if (is_array($where)) {
            $this->SQL->where($where);
        }

        $data = $this->SQL
            ->where('u.Deleted', 0)
            ->get()
            ->firstRow();

        return $data === false ? 0 : $data->UserCount;
    }

    /**
     * Get a user by ID.
     *
     * @param int $iD The ID of the user.
     * @param string|false $datasetType Whether to return an array or object.
     * @param array $options Additional options to affect fetching. Currently unused.
     * @return array|object|false Returns the user or **false** if the user wasn't found.
     */
    public function getID($iD, $datasetType = false, $options = []) {
        if (!$iD) {
            return false;
        }
        $datasetType = $datasetType ?: DATASET_TYPE_OBJECT;

        // Check page cache, then memcached
        $user = $this->getUserFromCache($iD, 'userid');

        // If not, query DB
        if ($user === Gdn_Cache::CACHEOP_FAILURE) {
            $user = parent::getID($iD, DATASET_TYPE_ARRAY);

            // We want to cache a non-existent user no-matter what.
            if (!$user) {
                $user = null;
            }

            $this->userCache($user, $iD);
        } elseif (!$user) {
            return false;
        }

        // Apply calculated fields
        $this->setCalculatedFields($user);

        // Allow FALSE returns
        if ($user === false || is_null($user)) {
            return false;
        }

        if (is_array($user) && $datasetType == DATASET_TYPE_OBJECT) {
            $user = (object)$user;
        }

        if (is_object($user) && $datasetType == DATASET_TYPE_ARRAY) {
            $user = (array)$user;
        }

        $this->EventArguments['LoadedUser'] = &$user;
        $this->fireEvent('AfterGetID');

        return $user;
    }

    /**
     *
     *
     * @param array $iDs
     * @param bool $skipCacheQuery
     * @return array
     * @throws Exception
     */
    public function getIDs($iDs, $skipCacheQuery = false) {
        $databaseIDs = $iDs;
        $data = [];

        if (!$skipCacheQuery) {
            $keys = [];
            // Make keys for cache query
            foreach ($iDs as $userID) {
                if (!$userID) {
                    continue;
                }
                $keys[] = formatString(self::USERID_KEY, ['UserID' => $userID]);
            }

            // Query cache layer
            $cacheData = Gdn::cache()->get($keys);
            if (!is_array($cacheData)) {
                $cacheData = [];
            }

            foreach ($cacheData as $realKey => $user) {
                if ($user === null) {
                    $resultUserID = trim(strrchr($realKey, '.'), '.');
                } else {
                    $resultUserID = val('UserID', $user);
                }
                $this->setCalculatedFields($user);
                $data[$resultUserID] = $user;
            }

            //echo "from cache:\n";
            //print_r($Data);

            $databaseIDs = array_diff($databaseIDs, array_keys($data));
            unset($cacheData);
        }

        // Clean out bogus blank entries
        $databaseIDs = array_diff($databaseIDs, [null, '']);

        // If we are missing any users from cache query, fill em up here
        if (sizeof($databaseIDs)) {
            $databaseData = $this->SQL->whereIn('UserID', $databaseIDs)->getWhere('User')->result(DATASET_TYPE_ARRAY);
            $databaseData = Gdn_DataSet::index($databaseData, 'UserID');

            //echo "from DB:\n";
            //print_r($DatabaseData);

            foreach ($databaseIDs as $iD) {
                if (isset($databaseData[$iD])) {
                    $user = $databaseData[$iD];
                    $this->userCache($user, $iD);
                    // Apply calculated fields
                    $this->setCalculatedFields($user);
                    $data[$iD] = $user;
                } else {
                    $user = null;
                    $this->userCache($user, $iD);
                }
            }
        }

        $this->EventArguments['RequestedIDs'] = $iDs;
        $this->EventArguments['LoadedUsers'] = &$data;
        $this->fireEvent('AfterGetIDs');

        return $data;
    }

    /**
     * Retrieve IP addresses associated with a user.
     *
     * @param int $userID Unique ID for a user.
     * @return array IP addresses for the user.
     */
    public function getIPs($userID) {
        $iPs = [];

        try {
            $packedIPs = Gdn::sql()->getWhere('UserIP', ['UserID' => $userID])->resultArray();
        } catch (\Exception $e) {
            return $iPs;
        }

        foreach ($packedIPs as $userIP) {
            if ($unpackedIP = ipDecode($userIP['IPAddress'])) {
                $iPs[] = $unpackedIP;
            }
        }

        return $iPs;
    }

    /**
     *
     *
     * @param bool $like
     * @param string $orderFields
     * @param string $orderDirection
     * @param bool $limit
     * @param bool $offset
     * @return Gdn_DataSet
     * @throws Exception
     */
    public function getLike($like = false, $orderFields = '', $orderDirection = 'asc', $limit = false, $offset = false) {
        $this->userQuery();
        $this->SQL
            ->join('UserRole ur', "u.UserID = ur.UserID", 'left');

        if (is_array($like)) {
            $this->SQL
                ->beginWhereGroup()
                ->orLike($like, '', 'right')
                ->endWhereGroup();
        }

        return $this->SQL
            ->where('u.Deleted', 0)
            ->orderBy($orderFields, $orderDirection)
            ->limit($limit, $offset)
            ->get();
    }

    /**
     * Retries UserMeta information for a UserID / Key pair.
     *
     * This method takes a $userID or array of $userIDs, and a $key. It converts the
     * $key to fully qualified format and then queries for the associated value(s). $key
     * can contain SQL wildcards, in which case multiple results can be returned.
     *
     * If $userID is an array, the return value will be a multi dimensional array with the first
     * axis containing UserIDs and the second containing fully qualified UserMetaKeys, associated with
     * their values.
     *
     * If $userID is a scalar, the return value will be a single dimensional array of $UserMetaKey => $Value
     * pairs.
     *
     * @param int $userID UserID or array of UserIDs.
     * @param string $key Relative user meta key.
     * @param string $prefix
     * @param string $default
     * @return array results or $default
     */
    public static function getMeta($userID, $key, $prefix = '', $default = '') {
        $sql = Gdn::sql()
            ->select('*')
            ->from('UserMeta u');

        if (is_array($userID)) {
            $sql->whereIn('u.UserID', $userID);
        } else {
            $sql->where('u.UserID', $userID);
        }

        if (strpos($key, '%') !== false) {
            $sql->like('u.Name', $key, 'none');
        } else {
            $sql->where('u.Name', $key);
        }

        $data = $sql->get()->resultArray();

        if (is_array($userID)) {
            $result = array_fill_keys($userID, []);
        } else {
            if (strpos($key, '%') === false) {
                $result = [stringBeginsWith($key, $prefix, false, true) => $default];
            } else {
                $result = [];
            }
        }

        foreach ($data as $row) {
            $name = stringBeginsWith($row['Name'], $prefix, false, true);

            if (is_array($userID)) {
                $result[$row['UserID']][$name] = $row['Value'];
            } else {
                $result[$name] = $row['Value'];
            }
        }

        return $result;
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
     * @param int $userID
     * @param bool $refresh
     * @return array|object|false
     */
    public function getSession($userID, $refresh = false) {
        // Ask for the user. This will check cache first.
        $user = $this->getID($userID, DATASET_TYPE_OBJECT);

        if (!$user) {
            return false;
        }

        // If we require confirmation and user is not confirmed
        $confirmEmail = self::requireConfirmEmail();
        $confirmed = val('Confirmed', $user);
        if ($confirmEmail && !$confirmed) {
            // Replace permissions with those of the ConfirmEmailRole
            $confirmEmailRoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_UNCONFIRMED);

            if (!is_array($confirmEmailRoleID) || count($confirmEmailRoleID) == 0) {
                throw new Exception(sprintf(t('No role configured with a type of "%s".'), RoleModel::TYPE_UNCONFIRMED), 400);
            }

            $roleModel = new RoleModel();
            $permissionsModel = new Vanilla\Permissions();
            $rolePermissions = $roleModel->getPermissions($confirmEmailRoleID);
            $permissionsModel->compileAndLoad($rolePermissions);

            // Ensure Confirm Email role can always sign in
            if (!$permissionsModel->has('Garden.SignIn.Allow')) {
                $permissionsModel->set('Garden.SignIn.Allow', true);
            }

            $user->Permissions = $permissionsModel->getPermissions();

            // Otherwise normal loadings!
        } else {
            if ($user && ($user->Permissions == '' || Gdn::cache()->activeEnabled())) {
                $userPermissions = $this->getPermissions($userID);
                $user->Permissions = $userPermissions->getPermissions();
            }
        }

        // Remove secret info from session
        unset($user->Password, $user->HashMethod);

        return $user;
    }

    /**
     * Retrieve a summary of "safe" user information for external API calls.
     *
     * @param string $orderFields
     * @param string $orderDirection
     * @param bool $limit
     * @param bool $offset
     * @return array|null
     * @throws Exception
     */
    public function getSummary($orderFields = '', $orderDirection = 'asc', $limit = false, $offset = false) {
        $this->userQuery(true);
        $data = $this->SQL
            ->where('u.Deleted', 0)
            ->orderBy($orderFields, $orderDirection)
            ->limit($limit, $offset)
            ->get();

        // Set corrected PhotoUrls.
        $result = &$data->result();
        foreach ($result as &$row) {
            if ($row->Photo && !isUrl($row->Photo)) {
                $row->Photo = Gdn_Upload::url(changeBasename($row->Photo, 'p%s'));
            }
        }

        return $result;
    }

    /**
     * Retrieves a "system user" id that can be used to perform non-real-person tasks.
     *
     * @return int Returns a user ID.
     */
    public function getSystemUserID() {
        $systemUserID = c('Garden.SystemUserID');
        if (!$systemUserID) {
            $systemUser = $this->SQL
                ->select('UserID')
                ->from('User u')
                ->where('u.Name', 'System')
                ->get()->firstRow(DATASET_TYPE_ARRAY);
            if($systemUser) {
                $systemUserID = $systemUser['UserID'];
            } else {
                $systemUser = [
                    'Name' => t('System'),
                    'Photo' => asset('/applications/dashboard/design/images/usericon.png', true),
                    'Password' => randomString('20'),
                    'HashMethod' => 'Random',
                    'Email' => 'system@example.com',
                    'DateInserted' => Gdn_Format::toDateTime(),
                    'Admin' => '2'
                ];

                $this->EventArguments['SystemUser'] = &$systemUser;
                $this->fireEvent('BeforeSystemUser');

                $systemUserID = $this->SQL->insert($this->Name, $systemUser);
            }
            saveToConfig('Garden.SystemUserID', $systemUserID);
        }
        return $systemUserID;
    }

    /**
     * Add points to a user's total.
     *
     * @param int $userID
     * @param int $points
     * @param string $source
     * @param int|false $timestamp
     * @since 2.1.0
     */
    public static function givePoints($userID, $points, $source = 'Other', $timestamp = false) {
        if (!$timestamp === false) {
            $timestamp = time();
        }

        if (is_array($source)) {
            $categoryID = val('CategoryID', $source, 0);
            $source = $source[0];
        } else {
            $categoryID = 0;
        }

        if ($categoryID > 0) {
            $categoryIDs = [$categoryID, 0];
        } else {
            $categoryIDs = [$categoryID];
        }

        foreach ($categoryIDs as $iD) {
            // Increment source points for the user.
            self::givePointsInternal($userID, $points, 'a', $source, $iD);

            // Increment total points for the user.
            self::givePointsInternal($userID, $points, 'w', 'Total', $iD, $timestamp);
            self::givePointsInternal($userID, $points, 'm', 'Total', $iD, $timestamp);
            self::givePointsInternal($userID, $points, 'a', 'Total', $iD, $timestamp);

            // Increment global daily points.
            self::givePointsInternal(0, $points, 'd', 'Total', $iD, $timestamp);
        }

        // Grab the user's total points.
        $totalPoints = Gdn::sql()->getWhere('UserPoints', ['UserID' => $userID, 'SlotType' => 'a', 'Source' => 'Total', 'CategoryID' => 0])->value('Points');

        Gdn::userModel()->setField($userID, 'Points', $totalPoints);

        // Fire a give points event.
        Gdn::userModel()->EventArguments['UserID'] = $userID;
        Gdn::userModel()->EventArguments['CategoryID'] = $categoryID;
        Gdn::userModel()->EventArguments['TotalPoints'] = $totalPoints;
        Gdn::userModel()->EventArguments['GivenPoints'] = $points;
        Gdn::userModel()->EventArguments['Source'] = $source;
        Gdn::userModel()->EventArguments['Timestamp'] = $timestamp;
        Gdn::userModel()->EventArguments['Points'] = $totalPoints; // Deprecated in favor of TotalPoints
        Gdn::userModel()->fireEvent('GivePoints');
    }

    /**
     * Add points to a user's total in a specific time slot.
     *
     * @param int $userID
     * @param int $points
     * @param string $slotType
     * @param string $source
     * @param int $categoryID
     * @param int|false $timestamp
     * @since 2.1.0
     * @see UserModel::givePoints()
     */
    private static function givePointsInternal($userID, $points, $slotType, $source = 'Total', $categoryID = 0, $timestamp = false) {
        $timeSlot = gmdate('Y-m-d', Gdn_Statistics::timeSlotStamp($slotType, $timestamp));

        $px = Gdn::database()->DatabasePrefix;
        $sql = "insert {$px}UserPoints (UserID, SlotType, TimeSlot, Source, CategoryID, Points)
         values (:UserID, :SlotType, :TimeSlot, :Source, :CategoryID, :Points)
         on duplicate key update Points = Points + :Points1";

        Gdn::database()->query($sql, [
            ':UserID' => $userID,
            ':Points' => $points,
            ':SlotType' => $slotType,
            ':Source' => $source,
            ':CategoryID' => $categoryID,
            ':TimeSlot' => $timeSlot,
            ':Points1' => $points]);
    }

    /**
     * Register a new user.
     *
     * @param array $formPostValues
     * @param array $options
     * @return bool|int|string
     */
    public function register($formPostValues, $options = []) {
        $formPostValues['LastIPAddress'] = ipEncode(Gdn::request()->ipAddress());

        // Check for banning first.
        $valid = BanModel::checkUser($formPostValues, null, true);
        if (!$valid) {
            $this->Validation->addValidationResult('UserID', 'Sorry, permission denied.');
        }

        // Throw an event to allow plugins to block the registration.
        unset($this->EventArguments['User']);
        $this->EventArguments['RegisteringUser'] =& $formPostValues;
        $this->EventArguments['Valid'] =& $valid;
        $this->fireEvent('BeforeRegister');

        if (!$valid) {
            return false; // plugin blocked registration
        }
        if (array_key_exists('Gender', $formPostValues)) {
            $formPostValues['Gender'] = self::fixGender($formPostValues['Gender']);
        }

        $method = strtolower(val('Method', $options, c('Garden.Registration.Method')));

        switch ($method) {
            case 'basic':
            case 'captcha': // deprecated
                $userID = $this->insertForBasic($formPostValues, val('CheckCaptcha', $options, true), $options);
                break;
            case 'approval':
                $userID = $this->insertForApproval($formPostValues, $options);
                break;
            case 'invitation':
                $userID = $this->insertForInvite($formPostValues, $options);
                break;
            case 'closed':
                $userID = false;
                $this->Validation->addValidationResult('Registration', 'Registration is closed.');
                break;
            default:
                $userID = $this->insertForBasic($formPostValues, val('CheckCaptcha', $options, false), $options);
                break;
        }

        if ($userID) {
            $this->EventArguments['UserID'] = $userID;
            $this->fireEvent('AfterRegister');
        }
        return $userID;
    }

    /**
     * Remove the photo from a user.
     *
     * @param int $userID
     */
    public function removePicture($userID) {
        // Grab the current photo.
        $user = $this->getID($userID, DATASET_TYPE_ARRAY);
        $photo = $user['Photo'];

        // Only attempt to delete a physical file, not a URL.
        if (!isUrl($photo)) {
            $profilePhoto = changeBasename($photo, 'p%s');
            $upload = new Gdn_Upload();
            $upload->delete($profilePhoto);
        }

        // Wipe the Photo field.
        $this->setField($userID, 'Photo', null);
    }

    /**
     * Get a user's counter.
     *
     * @param int|string|object $user
     * @param string $column
     * @return int|false
     */
    public function profileCount($user, $column) {
        if (is_numeric($user)) {
            $user = $this->SQL->getWhere('User', ['UserID' => $user])->firstRow(DATASET_TYPE_ARRAY);
        } elseif (is_string($user)) {
            $user = $this->SQL->getWhere('User', ['Name' => $user])->firstRow(DATASET_TYPE_ARRAY);
        } elseif (is_object($user)) {
            $user = (array)$user;
        }

        if (!$user) {
            return false;
        }

        if (array_key_exists($column, $user) && $user[$column] === null) {
            $userID = $user['UserID'];
            switch ($column) {
                case 'CountComments':
                    $count = $this->SQL->getCount('Comment', ['InsertUserID' => $userID]);
                    $this->setField($userID, 'CountComments', $count);
                    break;
                case 'CountDiscussions':
                    $count = $this->SQL->getCount('Discussion', ['InsertUserID' => $userID]);
                    $this->setField($userID, 'CountDiscussions', $count);
                    break;
                case 'CountBookmarks':
                    $count = $this->SQL->getCount('UserDiscussion', ['UserID' => $userID, 'Bookmarked' => '1']);
                    $this->setField($userID, 'CountBookmarks', $count);
                    break;
                default:
                    $count = false;
                    break;
            }
            return $count;
        } elseif ($user[$column]) {
            return $user[$column];
        } else {
            return false;
        }
    }

    /**
     * Generic save procedure.
     *
     * @param array $formPostValues The user to save.
     * @param array $settings Controls certain save functionality.
     *
     * - SaveRoles - Save 'RoleID' field as user's roles. Default false.
     * - HashPassword - Hash the provided password on update. Default true.
     * - FixUnique - Try to resolve conflicts with unique constraints on Name and Email. Default false.
     * - ValidateEmail - Make sure the provided email addresses is formatted properly. Default true.
     * - NoConfirmEmail - Disable email confirmation. Default false.
     *
     */
    public function save($formPostValues, $settings = []) {
        // See if the user's related roles should be saved or not.
        $saveRoles = val('SaveRoles', $settings);

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Custom Rule: This will make sure that at least one role was selected if saving roles for this user.
        if ($saveRoles) {
            $this->Validation->addRule('OneOrMoreArrayItemRequired', 'function:ValidateOneOrMoreArrayItemRequired');
            $this->Validation->applyRule('RoleID', 'OneOrMoreArrayItemRequired');
        } else {
            $this->Validation->unapplyRule('RoleID', 'OneOrMoreArrayItemRequired');
        }

        $this->Validation->addRule('UsernameBlacklist', 'function:validateAgainstUsernameBlacklist');
        $this->Validation->applyRule('Name', 'UsernameBlacklist');

        // Make sure that checkbox values are saved as the appropriate value.
        if (array_key_exists('ShowEmail', $formPostValues)) {
            $formPostValues['ShowEmail'] = forceBool($formPostValues['ShowEmail'], '0', '1', '0');
        }

        if (array_key_exists('Banned', $formPostValues)) {
            $formPostValues['Banned'] = intval($formPostValues['Banned']);
        }

        if (array_key_exists('Confirmed', $formPostValues)) {
            $formPostValues['Confirmed'] = forceBool($formPostValues['Confirmed'], '0', '1', '0');
        }

        if (array_key_exists('Verified', $formPostValues)) {
            $formPostValues['Verified'] = forceBool($formPostValues['Verified'], '0', '1', '0');
        }

        // Do not allowing setting this via general save.
        unset($formPostValues['Admin']);

        // This field is deprecated but included on user objects for backwards compatibility.
        // It will absolutely break if you try to save it back to the database.
        unset($formPostValues['AllIPAddresses']);

        if (array_key_exists('Gender', $formPostValues)) {
            $formPostValues['Gender'] = self::fixGender($formPostValues['Gender']);
        }

        if (array_key_exists('DateOfBirth', $formPostValues) && $formPostValues['DateOfBirth'] == '0-00-00') {
            $formPostValues['DateOfBirth'] = null;
        }

        $userID = val('UserID', $formPostValues);
        $user = [];
        $insert = $userID > 0 ? false : true;
        if ($insert) {
            $this->addInsertFields($formPostValues);
        } else {
            $this->addUpdateFields($formPostValues);
            $user = $this->getID($userID, DATASET_TYPE_ARRAY);
            if (!$user) {
                $user = [];
            }

            // Block banning the superadmin or System accounts
            if (val('Admin', $user) == 2 && val('Banned', $formPostValues)) {
                $this->Validation->addValidationResult('Banned', 'You may not ban a System user.');
            } elseif (val('Admin', $user) && val('Banned', $formPostValues)) {
                $this->Validation->addValidationResult('Banned', 'You may not ban a user with the Admin flag set.');
            }
        }

        $this->EventArguments['FormPostValues'] = $formPostValues;
        $this->fireEvent('BeforeSaveValidation');

        $recordRoleChange = true;

        if ($userID && val('FixUnique', $settings)) {
            $uniqueValid = $this->validateUniqueFields(val('Name', $formPostValues), val('Email', $formPostValues), $userID, true);
            if (!$uniqueValid['Name']) {
                unset($formPostValues['Name']);
            }
            if (!$uniqueValid['Email']) {
                unset($formPostValues['Email']);
            }
            $uniqueValid = true;
        } else {
            $uniqueValid = $this->validateUniqueFields(val('Name', $formPostValues), val('Email', $formPostValues), $userID);
        }

        // Add & apply any extra validation rules:
        if (array_key_exists('Email', $formPostValues) && val('ValidateEmail', $settings, true)) {
            $this->Validation->applyRule('Email', 'Email');
        }

        if ($this->validate($formPostValues, $insert) && $uniqueValid) {
            // All fields on the form that need to be validated (including non-schema field rules defined above)
            $fields = $this->Validation->validationFields();
            $roleIDs = val('RoleID', $fields, 0);
            $username = val('Name', $fields);
            $email = val('Email', $fields, '');

            // Only fields that are present in the schema
            $fields = $this->Validation->schemaValidationFields();

            // Remove the primary key from the fields collection before saving.
            unset($fields[$this->PrimaryKey]);

            if (!$insert && array_key_exists('Password', $fields) && val('HashPassword', $settings, true)) {
                // Encrypt the password for saving only if it won't be hashed in _Insert()
                $passwordHash = new Gdn_PasswordHash();
                $fields['Password'] = $passwordHash->hashPassword($fields['Password']);
                $fields['HashMethod'] = 'Vanilla';
            }

            // Check for email confirmation.
            if (self::requireConfirmEmail() && !val('NoConfirmEmail', $settings)) {
                // Email address has changed
                if (isset($fields['Email']) && (
                        array_key_exists('Confirmed', $fields) &&
                        $fields['Confirmed'] == 0 ||
                        (
                            $userID == Gdn::session()->UserID &&
                            $fields['Email'] != Gdn::session()->User->Email &&
                            !Gdn::session()->checkPermission('Garden.Users.Edit')
                        )
                    )
                ) {
                    $attributes = val('Attributes', Gdn::session()->User);
                    if (is_string($attributes)) {
                        $attributes = dbdecode($attributes);
                    }

                    $confirmEmailRoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_UNCONFIRMED);
                    if (!empty($confirmEmailRoleID)) {
                        // The confirm email role is set and it exists so go ahead with the email confirmation.
                        $emailKey = $this->confirmationCode();
                        setValue('EmailKey', $attributes, $emailKey);
                        $fields['Attributes'] = dbencode($attributes);
                        $fields['Confirmed'] = 0;
                    }
                }
            }

            $this->EventArguments['SaveRoles'] = &$saveRoles;
            $this->EventArguments['RoleIDs'] = &$roleIDs;
            $this->EventArguments['Fields'] = &$fields;
            $this->fireEvent('BeforeSave');
            $user = array_merge($user, $fields);

            // Check the validation results again in case something was added during the BeforeSave event.
            if (count($this->Validation->results()) == 0) {
                // Encode any IP fields that aren't already encoded.
                $ipCols = ['InsertIPAddress', 'LastIPAddress', 'UpdateIPAddress'];
                foreach ($ipCols as $col) {
                    if (isset($fields[$col]) && filter_var($fields[$col], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6)) {
                        $fields[$col] = ipEncode($fields[$col]);
                    }
                }
                unset($col);

                // If the primary key exists in the validated fields and it is a
                // numeric value greater than zero, update the related database row.
                if ($userID > 0) {
                    // If they are changing the username & email, make sure they aren't
                    // already being used (by someone other than this user)
                    if (val('Name', $fields, '') != '' || val('Email', $fields, '') != '') {
                        if (!$this->validateUniqueFields($username, $email, $userID)) {
                            return false;
                        }
                    }

                    // Determine if the password reset information needs to be cleared.
                    $clearPasswordReset = false;
                    if (array_key_exists('Password', $fields)) {
                        // New password? Clear the password reset info.
                        $clearPasswordReset = true;
                    } elseif (array_key_exists('Email', $fields)) {
                        $row = $this->getID($userID, DATASET_TYPE_ARRAY);
                        if ($fields['Email'] != val('Email', $row)) {
                            // New email? Clear the password reset info.
                            $clearPasswordReset = true;
                        }
                    }

                    if ($clearPasswordReset) {
                        $this->clearPasswordReset($userID);
                        // The save routine could've tweaked existing attributes. Make sure fields are purged here too.
                        if (array_key_exists('Attributes', $fields)) {
                            // Attributes might be a string at this point. They'll be converted into a string before saving.
                            if (is_string($fields['Attributes'])) {
                                $fields['Attributes'] = dbdecode($fields['Attributes']);
                            }
                            if (!empty($fields['Attributes']) && is_array($fields['Attributes'])) {
                                unset($fields['Attributes']['PasswordResetKey']);
                                unset($fields['Attributes']['PasswordResetExpires']);
                            }
                        }
                    }

                    if (array_key_exists('Preferences', $fields) && !is_string($fields['Preferences'])) {
                        $fields['Preferences'] = dbencode($fields['Preferences']);
                    }

                    if (array_key_exists('Attributes', $fields) && !is_string($fields['Attributes'])) {
                        $fields['Attributes'] = dbencode($fields['Attributes']);
                    }

                    // Perform save DB operation
                    $this->SQL->put($this->Name, $fields, [$this->PrimaryKey => $userID]);

                    // Record activity if the person changed his/her photo.
                    $photo = val('Photo', $formPostValues);
                    if ($photo !== false) {
                        if (val('CheckExisting', $settings)) {
                            $user = $this->getID($userID);
                            $oldPhoto = val('Photo', $user);
                        }

                        if (isset($oldPhoto) && $oldPhoto != $photo) {
                            if (isUrl($photo)) {
                                $photoUrl = $photo;
                            } else {
                                $photoUrl = Gdn_Upload::url(changeBasename($photo, 'n%s'));
                            }

                            $activityModel = new ActivityModel();
                            if ($userID == Gdn::session()->UserID) {
                                $headlineFormat = t('HeadlineFormat.PictureChange', '{RegardingUserID,You} changed {ActivityUserID,your} profile picture.');
                            } else {
                                $headlineFormat = t('HeadlineFormat.PictureChange.ForUser', '{RegardingUserID,You} changed the profile picture for {ActivityUserID,user}.');
                            }

                            $activityModel->save([
                                'ActivityUserID' => $userID,
                                'RegardingUserID' => Gdn::session()->UserID,
                                'ActivityType' => 'PictureChange',
                                'HeadlineFormat' => $headlineFormat,
                                'Story' => img($photoUrl, ['alt' => t('Thumbnail')])
                            ]);
                        }
                    }

                } else {
                    $recordRoleChange = false;
                    if (!$this->validateUniqueFields($username, $email)) {
                        return false;
                    }

                    // Define the other required fields:
                    $fields['Email'] = $email;

                    $fields['Roles'] = $roleIDs;
                    // Make sure that the user is assigned to one or more roles:
                    $saveRoles = false;

                    // And insert the new user.
                    $userID = $this->insertInternal($fields, $settings);

                    if ($userID > 0) {
                        // Report that the user was created.
                        $activityModel = new ActivityModel();
                        $activityModel->save(
                            [
                            'ActivityType' => 'Registration',
                            'ActivityUserID' => $userID,
                            'HeadlineFormat' => t('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                                'Story' => t('Welcome Aboard!')],
                            false,
                            ['GroupBy' => 'ActivityTypeID']
                        );

                        // Report the creation for mods.
                        $activityModel->save([
                            'ActivityType' => 'Registration',
                            'ActivityUserID' => Gdn::session()->UserID,
                            'RegardingUserID' => $userID,
                            'NotifyUserID' => ActivityModel::NOTIFY_MODS,
                            'HeadlineFormat' => t('HeadlineFormat.AddUser', '{ActivityUserID,user} added an account for {RegardingUserID,user}.')]);
                    }
                }
                // Now update the role settings if necessary.
                if ($saveRoles) {
                    // If no RoleIDs were provided, use the system defaults
                    if (!is_array($roleIDs)) {
                        $roleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);
                    }

                    $this->saveRoles($userID, $roleIDs, $recordRoleChange);
                }

                // Send the confirmation email.
                if (isset($emailKey)) {
                    if (!is_array($user)) {
                        $user = $this->getID($userID, DATASET_TYPE_ARRAY);
                    }
                    $this->sendEmailConfirmationEmail($user, true);
                }

                $this->clearCache($userID, ['user']);
                $this->EventArguments['UserID'] = $userID;
                $this->fireEvent('AfterSave');
            } else {
                $userID = false;
            }
        } else {
            $userID = false;
        }

        return $userID;
    }

    /**
     * Create an admin user account.
     *
     * @param array $formPostValues
     */
    public function saveAdminUser($formPostValues) {
        $userID = 0;

        // Add & apply any extra validation rules:
        $name = val('Name', $formPostValues, '');
        $formPostValues['Email'] = val('Email', $formPostValues, strtolower($name.'@'.Gdn_Url::host()));
        $formPostValues['ShowEmail'] = '0';
        $formPostValues['TermsOfService'] = '1';
        $formPostValues['DateOfBirth'] = '1975-09-16';
        $formPostValues['DateLastActive'] = Gdn_Format::toDateTime();
        $formPostValues['DateUpdated'] = Gdn_Format::toDateTime();
        $formPostValues['Gender'] = 'u';
        $formPostValues['Admin'] = '1';

        $this->addInsertFields($formPostValues);

        if ($this->validate($formPostValues, true) === true) {
            $fields = $this->Validation->schemaValidationFields(); // Only fields that are present in the schema

            // Insert the new user
            $userID = $this->insertInternal($fields, ['NoConfirmEmail' => true, 'Setup' => true]);

            if ($userID > 0) {
                $activityModel = new ActivityModel();
                $activityModel->save(
                    [
                    'ActivityUserID' => $userID,
                    'ActivityType' => 'Registration',
                    'HeadlineFormat' => t('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                    'Story' => t('Welcome Aboard!')
                    ],
                    false,
                    ['GroupBy' => 'ActivityTypeID']
                );
            }

            $this->saveRoles($userID, [16], false);
        }
        return $userID;
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
     * @param array|string $filter
     * @param string $orderFields
     * @param string $orderDirection
     * @param bool $limit
     * @param bool $offset
     * @return Gdn_DataSet
     */
    public function search($filter, $orderFields = '', $orderDirection = 'asc', $limit = false, $offset = false) {
        $optimize = false;

        if (is_array($filter)) {
            $where = $filter;
            $keywords = val('Keywords', $filter, '');
            $optimize = val('Optimize', $filter);
            unset($where['Keywords'], $where['Optimize']);
        } else {
            $keywords = $filter;
        }
        $keywords = trim($keywords);

        // Check for an IP address.
        if (preg_match('`\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}`', $keywords)) {
            $ipAddress = $keywords;
            $this->addIpFilters($ipAddress, ['LastIPAddress']);
        } elseif (strtolower($keywords) == 'banned') {
            $this->SQL->where('u.Banned >', 0);
            $keywords = '';
        } elseif (preg_match('/^\d+$/', $keywords)) {
            $numericQuery = $keywords;
            $keywords = '';
        } elseif (!empty($keywords)) {
            // Check to see if the search exactly matches a role name.
            $roleID = $this->SQL->getWhere('Role', ['Name' => $keywords])->value('RoleID');
        }

        $this->EventArguments['Keywords'] =& $keywords;
        $this->EventArguments['RankID'] =& $rankID;
        $this->EventArguments['Optimize'] =& $optimize;
        $this->fireEvent('BeforeUserQuery');

        $this->userQuery();

        $this->fireEvent('AfterUserQuery');

        if (isset($where)) {
            $this->SQL->where($where);
        }

        if (!empty($roleID)) {
            $this->SQL->join('UserRole ur2', "u.UserID = ur2.UserID and ur2.RoleID = $roleID");
        } elseif (isset($numericQuery)) {
            // We've searched for a number. Return UserID AND any exact numeric name match.
            $this->SQL->beginWhereGroup()
                ->where('u.UserID', $numericQuery)
                ->orWhere('u.Name', $numericQuery)
                ->endWhereGroup();
        } elseif ($keywords) {
            if ($optimize) {
                // An optimized search should only be done against name OR email.
                if (strpos($keywords, '@') !== false) {
                    $this->SQL->like('u.Email', $keywords, 'right');
                } else {
                    $this->SQL->like('u.Name', $keywords, 'right');
                }
            } else {
                // Search on the user table.
                $like = ['u.Name' => $keywords, 'u.Email' => $keywords];

                $this->SQL
                    ->orOp()
                    ->beginWhereGroup()
                    ->orLike($like, '', 'right')
                    ->endWhereGroup();
            }
        }

        // Optimized searches need at least some criteria before performing a query.
        if ($optimize && $this->SQL->whereCount() == 0 && empty($roleID)) {
            $this->SQL->reset();
            return new Gdn_DataSet([]);
        }

        $data = $this->SQL
            ->where('u.Deleted', 0)
            ->orderBy($orderFields, $orderDirection)
            ->limit($limit, $offset)
            ->get();

        $result = &$data->result();

        foreach ($result as &$row) {
            if ($row->Photo && !isUrl($row->Photo)) {
                $row->Photo = Gdn_Upload::url(changeBasename($row->Photo, 'n%s'));
            }

            $row->Attributes = dbdecode($row->Attributes);
            $row->Preferences = dbdecode($row->Preferences);
        }

        return $data;
    }


    /**
     * Appends filters to the current SQL object. Filters users with a given IP Address in the UserIP table. Extends
     * filtering to IPs in the GDN_User table for any fields passed in the $fields param.
     *
     * @param string $ip The IP Address to search for.
     * @param array $fields The additional fields to check in the UserTable
     */
    private function addIpFilters($ip, $fields = []) {
        // Get a clean SQL object.
        $sql = clone $this->SQL;
        $sql->reset();

        // Get all users that matches the IP address.
        $sql
            ->select('UserID')
            ->from('UserIP')
            ->where('IPAddress', inet_pton($ip));

        $matchingUserIDs = $sql->get()->resultArray();
        $userIDs = array_column($matchingUserIDs, 'UserID');

        // Add these users to search query.
        $this->SQL
            ->orWhereIn('u.UserID', $userIDs);

        // Check the user table ip fields.
        $allowedFields = ['LastIPAddress', 'InsertIPAddress', 'UpdateIPAddress'];

        foreach ($fields as $field) {
            if (in_array($field, $allowedFields)) {
                $this->SQL->orWhereIn('u.'.$field, [$ip, inet_pton($ip)]);
            }
        }
    }

    /**
     * Count search results.
     *
     * @param array|string $filter
     * @return int
     */
    public function searchCount($filter = '') {
        if (is_array($filter)) {
            $where = $filter;
            $keywords = $where['Keywords'];
            unset($where['Keywords'], $where['Optimize']);
        } else {
            $keywords = $filter;
        }
        $keywords = trim($keywords);

        // Check to see if the search exactly matches a role name.
        $roleID = false;
        if (strtolower($keywords) == 'banned') {
            $this->SQL->where('u.Banned >', 0);
        } else {
            $roleID = $this->SQL->getWhere('Role', ['Name' => $keywords])->value('RoleID');
        }

        if (isset($where)) {
            $this->SQL->where($where);
        }

        $this->SQL
            ->select('u.UserID', 'count', 'UserCount')
            ->from('User u');

        // Check for an IP address.
        if (preg_match('`\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}`', $keywords)) {
            $fields = ['LastIPAddress'];
            $this->addIpFilters($keywords, $fields);
        } else if ($roleID) {
            $this->SQL->join('UserRole ur2', "u.UserID = ur2.UserID and ur2.RoleID = $roleID");
        } else {
            // Search on the user table.
            $like = trim($keywords) == '' ? false : ['u.Name' => $keywords, 'u.Email' => $keywords];

            if (is_array($like)) {
                $this->SQL
                    ->orOp()
                    ->beginWhereGroup()
                    ->orLike($like, '', 'right')
                    ->endWhereGroup();
            }
        }

        $this->SQL
            ->where('u.Deleted', 0);

        $data = $this->SQL->get()->firstRow();

        return $data === false ? 0 : $data->UserCount;
    }

    /**
     * Search all users by username.
     *
     * @param string $name The username to search. Supports wildcards (e.g. user*).
     * @param string $sortField Column to sort resutls by.
     * @param string $sortDirection Direction used for column sort.
     * @param int|bool $limit Maximum results to return.
     * @param int|bool $offset Offset for result rows.
     * @return Gdn_DataSet
     */
    public function searchByName($name, $sortField = 'name', $sortDirection = 'asc', $limit = false, $offset = false) {
        $wildcardSearch = (substr($name, -1, 1) === '*');

        // Preserve existing % by escaping.
        $name = trim($name);
        $name = str_replace('%', '\%', $name);
        if ($wildcardSearch) {
            $name = rtrim($name, '*');
        }

        // Avoid potential pollution by resetting.
        $this->SQL->reset();
        $this->SQL->from('User');
        if ($wildcardSearch) {
            $this->SQL->like('Name', $name, 'right');
        } else {
            $this->SQL->where('Name', $name);
        }
        $result = $this->SQL
            ->where('Deleted', 0)
            ->orderBy($sortField, $sortDirection)
            ->limit($limit, $offset)
            ->get();
        return $result;
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
     * @param string $search
     * @param int $limit
     * @since 2.2
     */
    public function tagSearch($search, $limit = 10) {
        $search = trim(str_replace(['%', '_'], ['\%', '\_'], $search));

        list($order, $direction) = $this->getMentionsSort();

        return $this->SQL
            ->select('UserID', '', 'id')
            ->select('Name', '', 'name')
            ->from('User')
            ->like('Name', $search, 'right')
            ->where('Deleted', 0)
            ->orderBy($order, $direction)
            ->limit($limit)
            ->get()
            ->resultArray();
    }

    /**
     * To be used for invitation registration.
     *
     * @param array $formPostValues
     * @param array $options
     * @return int UserID.
     */
    public function insertForInvite($formPostValues, $options = []) {
        $roleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);
        if (!is_array($roleIDs) || count($roleIDs) == 0) {
            throw new Exception(t('The default role has not been configured.'), 400);
        }

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules:
        $this->Validation->applyRule('Email', 'Email');

        // Make sure that the checkbox val for email is saved as the appropriate enum
        if (array_key_exists('ShowEmail', $formPostValues)) {
            $formPostValues['ShowEmail'] = forceBool($formPostValues['ShowEmail'], '0', '1', '0');
        }

        if (array_key_exists('Banned', $formPostValues)) {
            $formPostValues['Banned'] = forceBool($formPostValues['Banned'], '0', '1', '0');
        }

        $this->addInsertFields($formPostValues);

        // Make sure that the user has a valid invitation code, and also grab
        // the user's email from the invitation:
        $invitationCode = val('InvitationCode', $formPostValues, '');

        $invitation = $this->SQL->getWhere('Invitation', ['Code' => $invitationCode])->firstRow();

        // If there is no invitation then bail out.
        if (!$invitation) {
            $this->Validation->addValidationResult('InvitationCode', 'Invitation not found.');
            return false;
        }

        // Get expiration date in timestamp. If nothing set, grab config default.
        $inviteExpiration = $invitation->DateExpires;
        if ($inviteExpiration != null) {
            $inviteExpiration = Gdn_Format::toTimestamp($inviteExpiration);
        } else {
            $defaultExpire = '1 week';
            $inviteExpiration = strtotime(c('Garden.Registration.InviteExpiration', '1 week'), Gdn_Format::toTimestamp($invitation->DateInserted));
            if ($inviteExpiration === false) {
                $inviteExpiration = strtotime($defaultExpire);
            }
        }

        if ($inviteExpiration <= time()) {
            $this->Validation->addValidationResult('DateExpires', 'The invitation has expired.');
        }

        $inviteUserID = $invitation->InsertUserID;
        $formPostValues['Email'] = $invitation->Email;

        if ($this->validate($formPostValues, true)) {
            // Check for spam.
            $spam = SpamModel::isSpam('Registration', $formPostValues);
            if ($spam) {
                $this->Validation->addValidationResult('Spam', 'You are not allowed to register at this time.');
                return;
            }

            $fields = $this->Validation->validationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
            $username = val('Name', $fields);
            $email = val('Email', $fields);
            $fields = $this->Validation->schemaValidationFields(); // Only fields that are present in the schema
            unset($fields[$this->PrimaryKey]);

            // Make sure the username & email aren't already being used
            if (!$this->validateUniqueFields($username, $email)) {
                return false;
            }

            // Define the other required fields:
            if ($inviteUserID > 0) {
                $fields['InviteUserID'] = $inviteUserID;
            }

            // And insert the new user.
            if (!isset($options['NoConfirmEmail'])) {
                $options['NoConfirmEmail'] = true;
            }

            // Use RoleIDs from Invitation table, if any. They are stored as a
            // serialized array of the Role IDs.
            $invitationRoleIDs = $invitation->RoleIDs;
            if (strlen($invitationRoleIDs)) {
                $invitationRoleIDs = dbdecode($invitationRoleIDs);

                if (is_array($invitationRoleIDs)
                    && count(array_filter($invitationRoleIDs))
                ) {
                    // Overwrite default RoleIDs set at top of method.
                    $roleIDs = $invitationRoleIDs;
                }
            }

            $fields['Roles'] = $roleIDs;
            $userID = $this->insertInternal($fields, $options);

            // Associate the new user id with the invitation (so it cannot be used again)
            $this->SQL
                ->update('Invitation')
                ->set('AcceptedUserID', $userID)
                ->set('DateAccepted', Gdn_Format::toDateTime())
                ->where('InvitationID', $invitation->InvitationID)
                ->put();

            // Report that the user was created.
            $activityModel = new ActivityModel();
            $activityModel->save(
                [
                'ActivityUserID' => $userID,
                'ActivityType' => 'Registration',
                'HeadlineFormat' => t('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                'Story' => t('Welcome Aboard!')
                ],
                false,
                ['GroupBy' => 'ActivityTypeID']
            );
        } else {
            $userID = false;
        }
        return $userID;
    }

    /**
     * To be used for approval registration.
     *
     * @param array $formPostValues
     * @param array $options
     *  - ValidateSpam
     *  - CheckCaptcha
     * @return int UserID.
     */
    public function insertForApproval($formPostValues, $options = []) {
        $roleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_APPLICANT);
        if (empty($roleIDs)) {
            throw new Exception(t('The default role has not been configured.'), 400);
        }

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules:
        $this->Validation->applyRule('Email', 'Email');

        // Make sure that the checkbox val for email is saved as the appropriate enum
        if (array_key_exists('ShowEmail', $formPostValues)) {
            $formPostValues['ShowEmail'] = forceBool($formPostValues['ShowEmail'], '0', '1', '0');
        }

        if (array_key_exists('Banned', $formPostValues)) {
            $formPostValues['Banned'] = forceBool($formPostValues['Banned'], '0', '1', '0');
        }

        $this->addInsertFields($formPostValues);

        if ($this->validate($formPostValues, true)) {

            if (val('ValidateSpam', $options, true)) {
                // Check for spam.
                $spam = SpamModel::isSpam('Registration', $formPostValues);
                if ($spam) {
                    $this->Validation->addValidationResult('Spam', 'You are not allowed to register at this time.');
                    return;
                }
            }

            $fields = $this->Validation->validationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
            $username = val('Name', $fields);
            $email = val('Email', $fields);
            $fields = $this->Validation->schemaValidationFields(); // Only fields that are present in the schema
            unset($fields[$this->PrimaryKey]);

            if (!$this->validateUniqueFields($username, $email)) {
                return false;
            }

            // If in Captcha registration mode, check the captcha value.
            if (val('CheckCaptcha', $options, true) && Captcha::enabled()) {
                $captchaIsValid = Captcha::validate();
                if ($captchaIsValid !== true) {
                    $this->Validation->addValidationResult('Garden.Registration.CaptchaPublicKey', t('The captcha was not completed correctly. Please try again.'));
                    return false;
                }
            }

            // Define the other required fields:
            $fields['Email'] = $email;
            $fields['Roles'] = (array)$roleIDs;

            // And insert the new user
            $userID = $this->insertInternal($fields, $options);
        } else {
            $userID = false;
        }
        return $userID;
    }

    /**
     * To be used for basic registration, and captcha registration.
     *
     * @param array $formPostValues
     * @param bool $checkCaptcha
     * @param array $options
     * @return bool|int|string
     * @throws Exception
     */
    public function insertForBasic($formPostValues, $checkCaptcha = true, $options = []) {
        $roleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);
        if (!is_array($roleIDs) || count($roleIDs) == 0) {
            throw new Exception(t('The default role has not been configured.'), 400);
        }

        if (val('SaveRoles', $options)) {
            $roleIDs = val('RoleID', $formPostValues);
        }

        $userID = false;

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules.
        if (val('ValidateEmail', $options, true)) {
            $this->Validation->applyRule('Email', 'Email');
        }

        // TODO: DO I NEED THIS?!
        // Make sure that the checkbox val for email is saved as the appropriate enum
        if (array_key_exists('ShowEmail', $formPostValues)) {
            $formPostValues['ShowEmail'] = forceBool($formPostValues['ShowEmail'], '0', '1', '0');
        }

        if (array_key_exists('Banned', $formPostValues)) {
            $formPostValues['Banned'] = forceBool($formPostValues['Banned'], '0', '1', '0');
        }

        $this->addInsertFields($formPostValues);

        if ($this->validate($formPostValues, true) === true) {
            $fields = $this->Validation->validationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
            $username = val('Name', $fields);
            $email = val('Email', $fields);
            $fields = $this->Validation->schemaValidationFields(); // Only fields that are present in the schema
            $fields['Roles'] = $roleIDs;
            unset($fields[$this->PrimaryKey]);

            // If in Captcha registration mode, check the captcha value.
            if ($checkCaptcha && Captcha::enabled()) {
                $captchaIsValid = Captcha::validate();
                if ($captchaIsValid !== true) {
                    $this->Validation->addValidationResult('Garden.Registration.CaptchaPublicKey', t('The captcha was not completed correctly. Please try again.'));
                    return false;
                }
            }

            if (!$this->validateUniqueFields($username, $email)) {
                return false;
            }

            // Check for spam.
            if (val('ValidateSpam', $options, true)) {
                $validateSpam = $this->validateSpamRegistration($formPostValues);
                if ($validateSpam !== true) {
                    return $validateSpam;
                }
            }

            // Define the other required fields:
            $fields['Email'] = $email;

            // And insert the new user
            $userID = $this->insertInternal($fields, $options);
            if ($userID > 0 && !val('NoActivity', $options)) {
                $activityModel = new ActivityModel();
                $activityModel->save(
                    [
                    'ActivityUserID' => $userID,
                    'ActivityType' => 'Registration',
                    'HeadlineFormat' => t('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                    'Story' => t('Welcome Aboard!')
                    ],
                    false,
                    ['GroupBy' => 'ActivityTypeID']
                );
            }
        }
        return $userID;
    }

    /**
     * Parent override.
     *
     * @param array &$fields
     */
    public function addInsertFields(&$fields) {
        $this->defineSchema();

        // Set the hour offset based on the client's clock.
        $clientHour = val('ClientHour', $fields, '');
        if (is_numeric($clientHour) && $clientHour >= 0 && $clientHour < 24) {
            $hourOffset = $clientHour - date('G', time());
            $fields['HourOffset'] = $hourOffset;
        }

        // Set some required dates.
        $now = Gdn_Format::toDateTime();
        $fields[$this->DateInserted] = $now;
        touchValue('DateFirstVisit', $fields, $now);
        $fields['DateLastActive'] = $now;
        $fields['InsertIPAddress'] = ipEncode(Gdn::request()->ipAddress());
        $fields['LastIPAddress'] = ipEncode(Gdn::request()->ipAddress());
    }

    /**
     * Record an IP address for a user.
     *
     * @param int $userID Unique ID of the user.
     * @param string $iP Human-readable IP address.
     * @param string $dateUpdated Force an update timesetamp.
     * @return bool Was the operation successful?
     */
    public function saveIP($userID, $iP, $dateUpdated = false) {
        if (!filter_var($iP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6)) {
            return false;
        }

        $packedIP = ipEncode($iP);
        $px = Gdn::database()->DatabasePrefix;

        if (!$dateUpdated) {
            $dateUpdated = Gdn_Format::toDateTime();
        }

        $query = "insert into {$px}UserIP (UserID, IPAddress, DateInserted, DateUpdated)
            values (:UserID, :IPAddress, :DateInserted, :DateUpdated)
            on duplicate key update DateUpdated = :DateUpdated2";
        $values = [
            ':UserID' => $userID,
            ':IPAddress' => $packedIP,
            ':DateInserted' => Gdn_Format::toDateTime(),
            ':DateUpdated' => $dateUpdated,
            ':DateUpdated2' => $dateUpdated
        ];

        try {
            Gdn::database()->query($query, $values);
            $result = true;
        } catch (\Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Updates visit level information such as date last active and the user's ip address.
     * @param int $userID
     * @param null|int|float $clientHour
     * @throws Exception If the user ID is not valid.
     * @return bool True on success, false if the user is banned or deleted.
     */
    public function updateVisit($userID, $clientHour = null) {
        $userID = (int)$userID;
        if (!$userID) {
            throw new Exception('A valid User ID is required.');
        }

        $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);

        // Do not update visit information if the user is banned or deleted.
        if (val('Banned', $user) || val('Deleted', $user)) {
            return false;
        }

        $fields = [];

        if (Gdn_Format::toTimestamp($user['DateLastActive']) < strtotime('5 minutes ago')) {
            // We only update the last active date once every 5 minutes to cut down on DB activity.
            $fields['DateLastActive'] = Gdn_Format::toDateTime();
        }

        // Update session level information if necessary.
        if ($userID == Gdn::session()->UserID) {
            $iP = Gdn::request()->ipAddress();
            $fields['LastIPAddress'] = ipEncode($iP);
            $this->saveIP($userID, $iP);

            if (Gdn::session()->newVisit()) {
                $fields['CountVisits'] = val('CountVisits', $user, 0) + 1;
                $this->fireEvent('Visit');
            }
        }

        // Set the hour offset based on the client's clock.
        if (is_numeric($clientHour) && $clientHour >= 0 && $clientHour < 24) {
            $hourOffset = $clientHour - date('G', time());
            $fields['HourOffset'] = $hourOffset;
        }

        // See if the fields have changed.
        $set = [];
        foreach ($fields as $name => $value) {
            if (val($name, $user) != $value) {
                $set[$name] = $value;
            }
        }

        if (!empty($set)) {
            $this->EventArguments['Fields'] = &$set;
            $this->fireEvent('UpdateVisit');

            $this->setField($userID, $set);
        }

        if ($user['LastIPAddress'] != $fields['LastIPAddress']) {
            $user = $this->getID($userID, DATASET_TYPE_ARRAY);
            if (!BanModel::checkUser($user, null, true, $bans)) {
                $banModel = new BanModel();
                $ban = array_pop($bans);
                $banModel->saveUser($user, true, $ban);
                $banModel->setCounts($ban);
            }
        }

        return true;
    }


    /**
     * Returns a list of lowercase, blacklisted usernames. Currently profileController endpoints,
     * in core or in plugins, are blacklisted.
     */
    public static function getUsernameBlacklist() {
        $pluginEndpoints = [
            'addons',
            'applyrank',
            'avatar',
            'card',
            'comments',
            'deletenote',
            'discussions',
            'facebookconnect',
            'following',
            'githubconnect',
            'hubsso',
            'ignore',
            'jsconnect',
            'linkedinconnect',
            'note',
            'notes',
            'online',
            'pegaconnect',
            'picture',
            'quotes',
            'reactions',
            'removepicture',
            'removewarning',
            'reversewarning',
            'salesforceconnect',
            'setlocale',
            'signature',
            'thumbnail',
            'twitterconnect',
            'usercard',
            'username',
            'viewnote',
            'warn',
            'warnings',
            'whosonline',
            'zendeskconnect'
        ];

        $profileControllerEndpoints = [];

        // Get public methods on ProfileController
        $reflection = new ReflectionClass('ProfileController');
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class == $reflection->getName()) {
                $profileControllerEndpoints[] = $method->name;
            }
        }

        $profileControllerEndpoints = array_map(function($str) { return strtolower($str); }, $profileControllerEndpoints);
        $endpoints = array_merge($profileControllerEndpoints, $pluginEndpoints);
        return $endpoints;
    }

    /**
     * Validate submitted user data.
     *
     * @param array $formPostValues
     * @param bool $insert
     * @return bool|array
     */
    public function validate($formPostValues, $insert = false) {
        $this->defineSchema();

        if (self::noEmail()) {
            // Remove the email requirement.
            $this->Validation->unapplyRule('Email', 'Required');
        }

        if (!$insert && !isset($formPostValues['Name'])) {
            $this->Validation->unapplyRule('Name');
        }

        return $this->Validation->validate($formPostValues, $insert);
    }

    /**
     * Validate User Credential.
     *
     * Fetches a user row by email (or name) and compare the password.
     * If the password was not stored as a blowfish hash, the password will be saved again.
     * Return the user's id, admin status and attributes.
     *
     * @param string $email
     * @param string $password
     * @return object|false Returns the user matching the credentials or **false** if the user doesn't validate.
     */
    public function validateCredentials($email = '', $iD = 0, $password) {
        $this->EventArguments['Credentials'] = ['Email' => $email, 'ID' => $iD, 'Password' => $password];
        $this->fireEvent('BeforeValidateCredentials');

        if (!$email && !$iD) {
            throw new Exception('The email or id is required');
        }

        try {
            $this->SQL->select('UserID, Name, Attributes, Admin, Password, HashMethod, Deleted, Banned')
                ->from('User');

            if ($iD) {
                $this->SQL->where('UserID', $iD);
            } else {
                if (strpos($email, '@') > 0) {
                    $this->SQL->where('Email', $email);
                } else {
                    $this->SQL->where('Name', $email);
                }
            }

            $dataSet = $this->SQL->get();
        } catch (Exception $ex) {
            $this->SQL->reset();

            // Try getting the user information without the new fields.
            $this->SQL->select('UserID, Name, Attributes, Admin, Password')
                ->from('User');

            if ($iD) {
                $this->SQL->where('UserID', $iD);
            } else {
                if (strpos($email, '@') > 0) {
                    $this->SQL->where('Email', $email);
                } else {
                    $this->SQL->where('Name', $email);
                }
            }

            $dataSet = $this->SQL->get();
        }

        if ($dataSet->numRows() < 1) {
            return false;
        }

        $userData = $dataSet->firstRow();
        // Check for a deleted user.
        if (val('Deleted', $userData)) {
            return false;
        }

        $passwordHash = new Gdn_PasswordHash();
        $hashMethod = val('HashMethod', $userData);
        if (!$passwordHash->checkPassword($password, $userData->Password, $hashMethod, $userData->Name)) {
            return false;
        }

        if ($passwordHash->Weak || ($hashMethod && strcasecmp($hashMethod, 'Vanilla') != 0)) {
            $pw = $passwordHash->hashPassword($password);
            $this->SQL->update('User')
                ->set('Password', $pw)
                ->set('HashMethod', 'Vanilla')
                ->where('UserID', $userData->UserID)
                ->put();
        }

        $userData->Attributes = dbdecode($userData->Attributes);
        return $userData;
    }

    /**
     *
     *
     * @param array $user
     * @return bool|string
     * @since 2.1
     */
    public function validateSpamRegistration($user) {
        $discoveryText = val('DiscoveryText', $user);
        $log = validateRequired($discoveryText);
        $spam = SpamModel::isSpam('Registration', $user, ['Log' => $log]);

        if ($spam) {
            if ($log) {
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
     * Checks to see if $username and $email are already in use by another member.
     *
     * @param string $username
     * @param string $email
     * @param string $userID
     * @param bool $return
     * @return array|bool
     */
    public function validateUniqueFields($username, $email, $userID = '', $return = false) {
        $valid = true;
        $where = [];
        if (is_numeric($userID)) {
            $where['UserID <> '] = $userID;
        }

        $result = ['Name' => true, 'Email' => true];

        // Make sure the username & email aren't already being used
        if (c('Garden.Registration.NameUnique', true) && $username) {
            $where['Name'] = $username;
            $testData = $this->getWhere($where);
            if ($testData->numRows() > 0) {
                $result['Name'] = false;
                $valid = false;
            }
            unset($where['Name']);
        }

        if (c('Garden.Registration.EmailUnique', true) && $email) {
            $where['Email'] = $email;
            $testData = $this->getWhere($where);
            if ($testData->numRows() > 0) {
                $result['Email'] = false;
                $valid = false;
            }
        }

        if ($return) {
            return $result;
        } else {
            if (!$result['Name']) {
                $this->Validation->addValidationResult('Name', 'The name you entered is already in use by another member.');
            }
            if (!$result['Email']) {
                $this->Validation->addValidationResult('Email', 'The email you entered is in use by another member.');
            }
            return $valid;
        }
    }

    /**
     * Approve a membership applicant.
     *
     * @param int $userID
     * @param string|null $email Deprecated.
     * @return bool
     * @throws Exception
     */
    public function approve($userID, $email = null) {
        if ($email !== null) {
            deprecated('Using the $email parameter of UserModel::approve.');
        }

        $applicantFound = $this->isApplicant($userID);

        if ($applicantFound) {
            // Retrieve the default role(s) for new users
            $roleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);

            // Wipe out old & insert new roles for this user
            $this->saveRoles($userID, $roleIDs, false);

            // Send out a notification to the user
            $user = $this->getID($userID);
            if ($user) {
                $email = new Gdn_Email();
                $email->subject(sprintf(t('[%1$s] Membership Approved'), c('Garden.Title')));
                $email->to($user->Email);

                $message = sprintf(t('Hello %s!'), val('Name', $user)).' '.t('You have been approved for membership.');
                $emailTemplate = $email->getEmailTemplate()
                    ->setMessage($message)
                    ->setButton(externalUrl(signInUrl()), t('Sign In Now'))
                    ->setTitle(t('Membership Approved'));

                $email->setEmailTemplate($emailTemplate);

                try {
                    $email->send();
                } catch (Exception $e) {
                    if (debug()) {
                        throw $e;
                    }
                }

                // Report that the user was approved.
                $activityModel = new ActivityModel();
                $activityModel->save(
                    [
                    'ActivityUserID' => $userID,
                    'ActivityType' => 'Registration',
                    'HeadlineFormat' => t('HeadlineFormat.Registration', '{ActivityUserID,You} joined.'),
                    'Story' => t('Welcome Aboard!')
                    ],
                    false,
                    ['GroupBy' => 'ActivityTypeID']
                );

                // Report the approval for moderators.
                $activityModel->save(
                    [
                    'ActivityType' => 'Registration',
                    'ActivityUserID' => Gdn::session()->UserID,
                    'RegardingUserID' => $userID,
                    'NotifyUserID' => ActivityModel::NOTIFY_MODS,
                        'HeadlineFormat' => t('HeadlineFormat.RegistrationApproval', '{ActivityUserID,user} approved the applications for {RegardingUserID,user}.')],
                    false,
                    ['GroupBy' => ['ActivityTypeID', 'ActivityUserID']]
                );

                Gdn::userModel()->saveAttribute($userID, 'ApprovedByUserID', Gdn::session()->UserID);
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

        $content = [];

        // Remove shared authentications.
        $this->getDelete('UserAuthentication', ['UserID' => $userID], $content);

        // Remove role associations.
        $this->getDelete('UserRole', ['UserID' => $userID], $content);

        $this->deleteContent($userID, $options, $content);

        $userData = $this->getID($userID, DATASET_TYPE_ARRAY);

        // Remove the user's information
        $this->SQL->update('User')
            ->set([
                'Name' => t('[Deleted User]'),
                'Photo' => null,
                'Password' => randomString('10'),
                'HashMethod' => 'Random',
                'About' => '',
                'Email' => 'user_'.$userID.'@deleted.invalid',
                'ShowEmail' => '0',
                'Gender' => 'u',
                'CountVisits' => 0,
                'CountInvitations' => 0,
                'CountNotifications' => 0,
                'InviteUserID' => null,
                'DiscoveryText' => '',
                'Preferences' => null,
                'Permissions' => null,
                'Attributes' => dbencode([
                    'State' => 'Deleted',
                    // We cannot keep emails until we have a method to purge deleted users.
                    // See https://github.com/vanilla/vanilla/pull/5808 for more details.
                    'OriginalName' => $userData['Name'],
                    'DeletedBy' => Gdn::session()->UserID,
                ]),
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
     * @param int $userID
     * @param array $options
     * @param array $content
     * @return bool|int
     */
    public function deleteContent($userID, $options = [], $content = []) {
        $log = val('Log', $options);
        if ($log === true) {
            $log = 'Delete';
        }

        $result = false;

        // Fire an event so applications can remove their associated user data.
        $this->EventArguments['UserID'] = $userID;
        $this->EventArguments['Options'] = $options;
        $this->EventArguments['Content'] = &$content;
        $this->fireEvent('BeforeDeleteUser');

        $user = $this->getID($userID, DATASET_TYPE_ARRAY);

        if (!$log) {
            $content = null;
        }

        // Remove invitations
        $this->getDelete('Invitation', ['InsertUserID' => $userID], $content);
        $this->getDelete('Invitation', ['AcceptedUserID' => $userID], $content);

        // Remove activities
        $this->getDelete('Activity', ['InsertUserID' => $userID], $content);

        // Remove activity comments.
        $this->getDelete('ActivityComment', ['InsertUserID' => $userID], $content);

        // Remove comments in moderation queue
        $this->getDelete('Log', ['RecordUserID' => $userID, 'Operation' => 'Pending'], $content);

        // Clear out information on the user.
        $this->setField($userID, [
            'About' => null,
            'Title' => null,
            'Location' => null]);

        if ($log) {
            $user['_Data'] = $content;
            unset($content); // in case data gets copied

            $result = LogModel::insert($log, 'User', $user, val('LogOptions', $options, []));
        }

        return $result;
    }

    /**
     * Decline a user's application to join the forum.
     *
     * @param int $userID
     * @return bool
     * @throws Exception
     */
    public function decline($userID) {
        $applicantRoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_APPLICANT);

        // Make sure the user is an applicant
        $roleData = $this->getRoles($userID);
        if ($roleData->numRows() == 0) {
            throw new Exception(t('ErrorRecordNotFound'));
        } else {
            $appRoles = $roleData->result(DATASET_TYPE_ARRAY);
            $applicantFound = false;
            foreach ($appRoles as $appRole) {
                if (in_array(val('RoleID', $appRole), $applicantRoleIDs)) {
                    $applicantFound = true;
                }
            }
        }

        if ($applicantFound) {
            $this->deleteID($userID);
        }
        return true;
    }

    /**
     * Get number of available invites a user has.
     *
     * @param int $userID
     * @return int
     */
    public function getInvitationCount($userID) {
        // If this user is master admin, they should have unlimited invites.
        if ($this->SQL
                ->select('UserID')
                ->from('User')
                ->where('UserID', $userID)
                ->where('Admin', '1')
                ->get()
                ->numRows() > 0
        ) {
            return -1;
        }

        // Get the Registration.InviteRoles settings:
        $inviteRoles = Gdn::config('Garden.Registration.InviteRoles', []);
        if (!is_array($inviteRoles) || count($inviteRoles) == 0) {
            return 0;
        }

        // Build an array of roles that can send invitations
        $canInviteRoles = [];
        foreach ($inviteRoles as $roleID => $invites) {
            if ($invites > 0 || $invites == -1) {
                $canInviteRoles[] = $roleID;
            }
        }

        if (count($canInviteRoles) == 0) {
            return 0;
        }

        // See which matching roles the user has
        $userRoleData = $this->SQL->select('RoleID')
            ->from('UserRole')
            ->where('UserID', $userID)
            ->whereIn('RoleID', $canInviteRoles)
            ->get();

        if ($userRoleData->numRows() == 0) {
            return 0;
        }

        // Define the maximum number of invites the user is allowed to send
        $inviteCount = 0;
        foreach ($userRoleData->result() as $userRole) {
            $count = $inviteRoles[$userRole->RoleID];
            if ($count == -1) {
                $inviteCount = -1;
            } elseif ($inviteCount != -1 && $count > $inviteCount) {
                $inviteCount = $count;
            }
        }

        // If the user has unlimited invitations, return that value
        if ($inviteCount == -1) {
            return -1;
        }

        // Get the user's current invitation settings from their profile
        $user = $this->SQL->select('CountInvitations, DateSetInvitations')
            ->from('User')
            ->where('UserID', $userID)
            ->get()
            ->firstRow();

        // If CountInvitations is null (ie. never been set before) or it is a new month since the DateSetInvitations
        if ($user->CountInvitations == '' || is_null($user->DateSetInvitations) || Gdn_Format::date($user->DateSetInvitations, '%m %Y') != Gdn_Format::date('', '%m %Y')) {
            // Reset CountInvitations and DateSetInvitations
            $this->SQL->put(
                $this->Name,
                [
                    'CountInvitations' => $inviteCount,
                    'DateSetInvitations' => Gdn_Format::date('', '%Y-%m-01') // The first day of this month
                ],
                ['UserID' => $userID]
            );
            return $inviteCount;
        } else {
            // Otherwise return CountInvitations
            return $user->CountInvitations;
        }
    }

    /**
     * Get rows from a table then delete them.
     *
     * @param string $table The name of the table.
     * @param array $where The where condition for the delete.
     * @param array $data The data to put the result.
     * @since 2.1
     */
    public function getDelete($table, $where, &$data) {
        if (is_array($data)) {
            // Grab the records.
            $result = $this->SQL->getWhere($table, $where)->resultArray();

            if (empty($result)) {
                return;
            }

            // Put the records in the result array.
            if (isset($data[$table])) {
                $data[$table] = array_merge($data[$table], $result);
            } else {
                $data[$table] = $result;
            }
        }

        $this->SQL->delete($table, $where);
    }

    /**
     * Reduces the user's CountInvitations value by the specified amount.
     *
     * @param int $userID The unique id of the user being affected.
     * @param int $reduceBy The number to reduce CountInvitations by.
     */
    public function reduceInviteCount($userID, $reduceBy = 1) {
        $currentCount = $this->getInvitationCount($userID);

        // Do not reduce if the user has unlimited invitations
        if ($currentCount == -1) {
            return true;
        }

        // Do not reduce the count below zero.
        if ($reduceBy > $currentCount) {
            $reduceBy = $currentCount;
        }

        $this->SQL->update($this->Name)
            ->set('CountInvitations', 'CountInvitations - '.$reduceBy, false)
            ->where('UserID', $userID)
            ->put();
    }

    /**
     * Increases the user's CountInvitations value by the specified amount.
     *
     * @param int $userID The unique id of the user being affected.
     * @param int $increaseBy The number to increase CountInvitations by.
     */
    public function increaseInviteCount($userID, $increaseBy = 1) {
        $currentCount = $this->getInvitationCount($userID);

        // Do not alter if the user has unlimited invitations
        if ($currentCount == -1) {
            return true;
        }

        $this->SQL->update($this->Name)
            ->set('CountInvitations', 'CountInvitations + '.$increaseBy, false)
            ->where('UserID', $userID)
            ->put();
    }

    /**
     * Saves the user's About field.
     *
     * @param int $userID The UserID to save.
     * @param string $about The about message being saved.
     */
    public function saveAbout($userID, $about) {
        $about = substr($about, 0, 1000);
        $this->setField($userID, 'About', $about);
    }

    /**
     * Saves a name/value to the user's specified $column.
     *
     * This method throws exceptions when errors are encountered. Use try catch blocks to capture these exceptions.
     *
     * @param string $column The name of the serialized column to save to. At the time of this writing there are three serialized columns on the user table: Permissions, Preferences, and Attributes.
     * @param int $userID The UserID to save.
     * @param mixed $name The name of the value being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $value argument will be ignored.
     * @param mixed $value The value being saved.
     */
    public function saveToSerializedColumn($column, $userID, $name, $value = '') {
        // Load the existing values
        $userData = $this->getID($userID, DATASET_TYPE_OBJECT);

        if (!$userData) {
            throw new Exception(sprintf('User %s not found.', $userID));
        }

        $values = val($column, $userData);

        if (!is_array($values) && !is_object($values)) {
            $values = dbdecode($userData->$column);
        }

        // Throw an exception if the field was not empty but is also not an object or array
        if (is_string($values) && $values != '') {
            throw new Exception(sprintf(t('Serialized column "%s" failed to be unserialized.'), $column));
        }

        if (!is_array($values)) {
            $values = [];
        }

        // Hook for plugins
        $this->EventArguments['CurrentValues'] = &$values;
        $this->EventArguments['Column'] = &$column;
        $this->EventArguments['UserID'] = &$userID;
        $this->EventArguments['Name'] = &$name;
        $this->EventArguments['Value'] = &$value;
        $this->fireEvent('BeforeSaveSerialized');

        // Assign the new value(s)
        if (!is_array($name)) {
            $name = [$name => $value];
        }


        $rawValues = array_merge($values, $name);
        $values = [];
        foreach ($rawValues as $key => $rawValue) {
            if (!is_null($rawValue)) {
                $values[$key] = $rawValue;
            }
        }

        $values = dbencode($values);

        // Save the values back to the db
        $saveResult = $this->SQL->put('User', [$column => $values], ['UserID' => $userID]);
        $this->clearCache($userID, ['user']);

        return $saveResult;
    }

    /**
     * Saves a user preference to the database.
     *
     * This is a convenience method that uses $this->saveToSerializedColumn().
     *
     * @param int $userID The UserID to save.
     * @param mixed $preference The name of the preference being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $value argument will be ignored.
     * @param mixed $value The value being saved.
     */
    public function savePreference($userID, $preference, $value = '') {
        // Make sure that changes to the current user become effective immediately.
        $session = Gdn::session();
        if ($userID == $session->UserID) {
            $session->setPreference($preference, $value, false);
        }

        return $this->saveToSerializedColumn('Preferences', $userID, $preference, $value);
    }

    /**
     * Saves a user attribute to the database.
     *
     * This is a convenience method that uses $this->saveToSerializedColumn().
     *
     * @param int $userID The UserID to save.
     * @param mixed $attribute The name of the attribute being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $value argument will be ignored.
     * @param mixed $value The value being saved.
     */
    public function saveAttribute($userID, $attribute, $value = '') {
        // Make sure that changes to the current user become effective immediately.
        $session = Gdn::session();
        if ($userID == $session->UserID) {
            $session->setAttribute($attribute, $value);
        }

        return $this->saveToSerializedColumn('Attributes', $userID, $attribute, $value);
    }

    /**
     *
     *
     * @param array $data
     * @return Gdn_DataSet|string
     */
    public function saveAuthentication($data) {
        $cn = $this->Database->connection();
        $px = $this->Database->DatabasePrefix;

        $uID = $cn->quote($data['UniqueID']);
        $provider = $cn->quote($data['Provider']);
        $userID = $cn->quote($data['UserID']);

        $sql = "insert {$px}UserAuthentication (ForeignUserKey, ProviderKey, UserID) values ($uID, $provider, $userID) on duplicate key update UserID = $userID";
        $result = $this->Database->query($sql);
        return $result;
    }

    /**
     * Set fields that need additional manipulation after retrieval.
     *
     * @param array|object &$user
     * @throws Exception
     */
    public function setCalculatedFields(&$user) {
        if ($v = val('Attributes', $user)) {
            if (is_string($v)) {
                setValue('Attributes', $user, dbdecode($v));
            }
        }
        if ($v = val('Permissions', $user)) {
            if (is_string($v)) {
                setValue('Permissions', $user, dbdecode($v));
            }
        }
        if ($v = val('Preferences', $user)) {
            if (is_string($v)) {
                setValue('Preferences', $user, dbdecode($v));
            }
        }
        if ($v = val('Photo', $user)) {
            if (!isUrl($v)) {
                $photoUrl = Gdn_Upload::url(changeBasename($v, 'n%s'));
            } else {
                $photoUrl = $v;
            }

            setValue('PhotoUrl', $user, $photoUrl);
        }

        $confirmed = val('Confirmed', $user, null);
        if ($confirmed !== null) {
            setValue('EmailConfirmed', $user, $confirmed);
        }
        $verified = val('Verified', $user, null);
        if ($verified !== null) {
            setValue('BypassSpam', $user, $verified);
        }

        // We store IPs in the UserIP table. To avoid unnecessary queries, the full list is not built here. Shim for BC.
        setValue('AllIPAddresses', $user, [
            val('InsertIPAddress', $user),
            val('LastIPAddress', $user)
        ]);

        setValue('_CssClass', $user, '');
        if (val('Banned', $user)) {
            setValue('_CssClass', $user, 'Banned');
        }

        $this->EventArguments['User'] = &$user;
        $this->fireEvent('SetCalculatedFields');
    }

    /**
     *
     *
     * @param int $userID
     * @param array $meta
     * @param string $prefix
     */
    public static function setMeta($userID, $meta, $prefix = '') {
        $deletes = [];
        $px = Gdn::database()->DatabasePrefix;
        $sql = "insert {$px}UserMeta (UserID, Name, Value) values(:UserID, :Name, :Value) on duplicate key update Value = :Value1";

        foreach ($meta as $name => $value) {
            $name = $prefix.$name;
            if ($value === null || $value == '') {
                $deletes[] = $name;
            } else {
                Gdn::database()->query($sql, [':UserID' => $userID, ':Name' => $name, ':Value' => $value, ':Value1' => $value]);
            }
        }
        if (count($deletes)) {
            Gdn::sql()->whereIn('Name', $deletes)->where('UserID', $userID)->delete('UserMeta');
        }
    }

    /**
     * Set the TransientKey attribute on a user.
     *
     * @param int $userID
     * @param string $explicitKey
     * @return string
     */
    public function setTransientKey($userID, $explicitKey = '') {
        $key = $explicitKey == '' ? betterRandomString(16, 'Aa0') : $explicitKey;
        $this->saveAttribute($userID, 'TransientKey', $key);
        return $key;
    }

    /**
     * Get an Attribute from a single user.
     *
     * @param int $userID
     * @param string $attribute
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getAttribute($userID, $attribute, $defaultValue = false) {
        $user = $this->getID($userID, DATASET_TYPE_ARRAY);
        $result = val($attribute, $user['Attributes'], $defaultValue);

        return $result;
    }

    /**
     * Send the confirmation email.
     *
     * @param int|string|null $user
     * @param bool $force
     * @throws Exception
     */
    public function sendEmailConfirmationEmail($user = null, $force = false) {

        if (!$user) {
            $user = Gdn::session()->User;
        } elseif (is_numeric($user)) {
            $user = $this->getID($user);
        } elseif (is_string($user)) {
            $user = $this->getByEmail($user);
        }

        if (!$user) {
            throw notFoundException('User');
        }

        $user = (array)$user;

        if (is_string($user['Attributes'])) {
            $user['Attributes'] = dbdecode($user['Attributes']);
        }

        // Make sure the user needs email confirmation.
        if ($user['Confirmed'] && !$force) {
            $this->Validation->addValidationResult('Role', 'Your email doesn\'t need confirmation.');

            // Remove the email key.
            if (isset($user['Attributes']['EmailKey'])) {
                unset($user['Attributes']['EmailKey']);
                $this->saveAttribute($user['UserID'], $user['Attributes']);
            }

            return;
        }

        // Make sure there is a confirmation code.
        $code = valr('Attributes.EmailKey', $user);
        if (!$code) {
            $code = $this->confirmationCode();
            $attributes = $user['Attributes'];
            if (!is_array($attributes)) {
                $attributes = ['EmailKey' => $code];
            } else {
                $attributes['EmailKey'] = $code;
            }

            $this->saveAttribute($user['UserID'], $attributes);
        }

        $appTitle = Gdn::config('Garden.Title');
        $email = new Gdn_Email();
        $email->subject(sprintf(t('[%s] Confirm Your Email Address'), $appTitle));
        $email->to($user['Email']);

        $emailUrlFormat = '{/entry/emailconfirm,exurl,domain}/{User.UserID,rawurlencode}/{EmailKey,rawurlencode}';
        $data = [];
        $data['EmailKey'] = $code;
        $data['User'] = arrayTranslate((array)$user, ['UserID', 'Name', 'Email']);

        $url = formatString($emailUrlFormat, $data);
        $message = formatString(t('Hello {User.Name}!'), $data).' '.t('You need to confirm your email address before you can continue.');

        $emailTemplate = $email->getEmailTemplate()
            ->setTitle(t('Confirm Your Email Address'))
            ->setMessage($message)
            ->setButton($url, t('Confirm My Email Address'));

        $email->setEmailTemplate($emailTemplate);

        try {
            $email->send();
        } catch (Exception $e) {
            if (debug()) {
                throw $e;
            }
        }
    }

    /**
     * Send welcome email to user.
     *
     * @param int $userID
     * @param string $password
     * @param string $registerType
     * @param array|null $additionalData
     * @throws Exception
     */
    public function sendWelcomeEmail($userID, $password, $registerType = 'Add', $additionalData = null) {
        $session = Gdn::session();
        $sender = $this->getID($session->UserID);
        $user = $this->getID($userID);

        if (!validateEmail($user->Email)) {
            return;
        }

        $appTitle = Gdn::config('Garden.Title');
        $email = new Gdn_Email();
        $email->subject(sprintf(t('[%s] Welcome Aboard!'), $appTitle));
        $email->to($user->Email);
        $emailTemplate = $email->getEmailTemplate();

        $data = [];
        $data['User'] = arrayTranslate((array)$user, ['UserID', 'Name', 'Email']);
        $data['Sender'] = arrayTranslate((array)$sender, ['Name', 'Email']);
        $data['Title'] = $appTitle;
        if (is_array($additionalData)) {
            $data = array_merge($data, $additionalData);
        }

        $data['EmailKey'] = valr('Attributes.EmailKey', $user);

        $message = '<p>'.formatString(t('Hello {User.Name}!'), $data).' ';

        $message .= $this->getEmailWelcome($registerType, $user, $data, $password);

        // Add the email confirmation key.
        if ($data['EmailKey']) {
            $emailUrlFormat = '{/entry/emailconfirm,exurl,domain}/{User.UserID,rawurlencode}/{EmailKey,rawurlencode}';
            $url = formatString($emailUrlFormat, $data);
            $message .= '<p>'.t('You need to confirm your email address before you can continue.').'</p>';
            $emailTemplate->setButton($url, t('Confirm My Email Address'));
        } else {
            $emailTemplate->setButton(externalUrl('/'), t('Access the Site'));
        }

        $emailTemplate->setMessage($message);
        $emailTemplate->setTitle(t('Welcome Aboard!'));

        $email->setEmailTemplate($emailTemplate);

        try {
            $email->send();
        } catch (Exception $e) {
            if (debug()) {
                throw $e;
            }
        }
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
        $appTitle = c('Garden.Title', c('Garden.HomepageTitle'));

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
                        formatString(t('Connected With: {ProviderName}'), $data).'<br></p>';
                    break;
                case 'Register' :
                    $welcome = formatString(t('You have successfully registered for an account at {Title}.'), $data).' '.
                        t('Find your account information below.').'<br></p>'.
                        '<p>'.sprintf(t('%s: %s'), t('Username'), val('Name', $user)).'<br>'.
                        sprintf(t('%s: %s'), t('Email'), val('Email', $user)).'<br></p>';
                    break;
                default :
                    $welcome = sprintf(t('%s has created an account for you at %s.'), val('Name', val('Sender', $data)), $appTitle).' '.
                        t('Find your account information below.').'<br></p>'.
                        '<p>'.sprintf(t('%s: %s'), t('Email'), val('Email', $user)).'<br>'.
                        sprintf(t('%s: %s'), t('Password'), $password).'<br></p>';
            }
        }
        return $welcome;
    }

    /**
     * Send password email.
     *
     * @param int $userID
     * @param string $password
     */
    public function sendPasswordEmail($userID, $password) {
        $session = Gdn::session();
        $sender = $this->getID($session->UserID);
        $user = $this->getID($userID);
        $appTitle = Gdn::config('Garden.Title');
        $email = new Gdn_Email();
        $email->subject('['.$appTitle.'] '.t('Reset Password'));
        $email->to($user->Email);
        $greeting = formatString(t('Hello %s!'), val('Name', $user));
        $message = '<p>'.$greeting.' '.sprintf(t('%s has reset your password at %s.'), val('Name', $sender), $appTitle).' '.
            t('Find your account information below.').'<br></p>'.
            '<p>'.sprintf(t('%s: %s'), t('Email'), val('Email', $user)).'<br>'.
            sprintf(t('%s: %s'), t('Password'), $password).'</p>';

        $emailTemplate = $email->getEmailTemplate()
            ->setTitle(t('Reset Password'))
            ->setMessage($message)
            ->setButton(externalUrl('/'), t('Access the Site'));

        $email->setEmailTemplate($emailTemplate);

        try {
            $email->send();
        } catch (Exception $e) {
            if (debug()) {
                throw $e;
            }
        }
    }

    /**
     * Synchronizes the user based on a given UserKey.
     *
     * @param string $userKey A string that uniquely identifies this user.
     * @param array $data Information to put in the user table.
     * @return int The ID of the user.
     */
    public function synchronize($userKey, $data) {
        $userID = 0;

        $attributes = val('Attributes', $data);
        if (is_string($attributes)) {
            $attributes = dbdecode($attributes);
        }

        if (!is_array($attributes)) {
            $attributes = [];
        }

        // If the user didnt log in, they won't have a UserID yet. That means they want a new
        // account. So create one for them.
        if (!isset($data['UserID']) || $data['UserID'] <= 0) {
            // Prepare the user data.
            $userData = [];
            $userData['Name'] = $data['Name'];
            $userData['Password'] = randomString(16);
            $userData['Email'] = val('Email', $data, 'no@email.com');
            $userData['Gender'] = strtolower(substr(val('Gender', $data, 'u'), 0, 1));
            $userData['HourOffset'] = val('HourOffset', $data, 0);
            $userData['DateOfBirth'] = val('DateOfBirth', $data, '');
            $userData['CountNotifications'] = 0;
            $userData['Attributes'] = $attributes;
            $userData['InsertIPAddress'] = ipEncode(Gdn::request()->ipAddress());
            if ($userData['DateOfBirth'] == '') {
                $userData['DateOfBirth'] = '1975-09-16';
            }

            // Make sure there isn't another user with this username.
            if ($this->validateUniqueFields($userData['Name'], $userData['Email'])) {
                if (!BanModel::checkUser($userData, $this->Validation, true)) {
                    throw permissionException('Banned');
                }

                // Insert the new user.
                $this->addInsertFields($userData);
                $userID = $this->insertInternal($userData);
            }

            if ($userID > 0) {
                $newUserRoleIDs = $this->newUserRoleIDs();

                // Save the roles.
                $roles = val('Roles', $data, false);
                if (empty($roles)) {
                    $roles = $newUserRoleIDs;
                }

                $this->saveRoles($userID, $roles, false);
            }
        } else {
            $userID = $data['UserID'];
        }

        // Synchronize the transientkey from the external user data source if it is present (eg. WordPress' wpnonce).
        if (array_key_exists('TransientKey', $attributes) && $attributes['TransientKey'] != '' && $userID > 0) {
            $this->setTransientKey($userID, $attributes['TransientKey']);
        }

        return $userID;
    }

    /**
     *
     *
     * @return array
     * @throws Gdn_UserException
     */
    public function newUserRoleIDs() {
        // Registration method
        $registrationMethod = c('Garden.Registration.Method', 'Basic');
        $defaultRoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);
        switch ($registrationMethod) {

            case 'Approval':
                $roleID = RoleModel::getDefaultRoles(RoleModel::TYPE_APPLICANT);
                break;

            case 'Invitation':
                throw new Gdn_UserException(t('This forum is currently set to invitation only mode.'));
            case 'Basic':
            case 'Captcha':
            default:
                $roleID = $defaultRoleID;
                break;
        }

        if (empty($roleID)) {
            trace("You don't have any default roles defined.", TRACE_WARNING);
        }
        return $roleID;
    }

    /**
     * Send forgot password email.
     *
     * @param string $email
     * @return bool
     */
    public function passwordRequest($email) {
        if (!$email) {
            return false;
        }

        $users = $this->getWhere(['Email' => $email])->resultObject();
        if (count($users) == 0) {
            // Check for the username.
            $users = $this->getWhere(['Name' => $email])->resultObject();
        }

        $this->EventArguments['Users'] =& $users;
        $this->EventArguments['Email'] = $email;
        $this->fireEvent('BeforePasswordRequest');

        if (count($users) == 0) {
            $this->Validation->addValidationResult('Name', "Couldn't find an account associated with that email/username.");
            return false;
        }

        $noEmail = true;

        foreach ($users as $user) {
            if (!$user->Email) {
                continue;
            }
            $email = new Gdn_Email(); // Instantiate in loop to clear previous settings
            $passwordResetKey = betterRandomString(20, 'Aa0');
            $passwordResetExpires = strtotime('+1 hour');
            $this->saveAttribute($user->UserID, 'PasswordResetKey', $passwordResetKey);
            $this->saveAttribute($user->UserID, 'PasswordResetExpires', $passwordResetExpires);
            $appTitle = c('Garden.Title');
            $email->subject('['.$appTitle.'] '.t('Reset Your Password'));
            $email->to($user->Email);

            $emailTemplate = $email->getEmailTemplate()
                ->setTitle(t('Reset Your Password'))
                ->setMessage(sprintf(t('We\'ve received a request to change your password.'), $appTitle))
                ->setButton(externalUrl('/entry/passwordreset/'.$user->UserID.'/'.$passwordResetKey), t('Change My Password'));
            $email->setEmailTemplate($emailTemplate);

            try {
                $email->send();
            } catch (Exception $e) {
                if (debug()) {
                    throw $e;
                }
            }

            $noEmail = false;
        }

        if ($noEmail) {
            $this->Validation->addValidationResult('Name', 'There is no email address associated with that account.');
            return false;
        }
        return true;
    }

    /**
     * Do a password reset.
     *
     * @param int $userID
     * @param string $password
     * @return array|false Returns the user or **false** if the user doesn't exist.
     */
    public function passwordReset($userID, $password) {
        // Encrypt the password before saving
        $passwordHash = new Gdn_PasswordHash();
        $password = $passwordHash->hashPassword($password);

        // Set the new password on the user row.
        $this->SQL
            ->update('User')
            ->set('Password', $password)
            ->set('HashMethod', 'Vanilla')
            ->where('UserID', $userID)
            ->put();

        // Clear any password reset information.
        $this->clearPasswordReset($userID);

        $this->EventArguments['UserID'] = $userID;
        $this->fireEvent('AfterPasswordReset');

        return $this->getID($userID);
    }

    /**
     * Check and apply login rate limiting
     *
     * @param array $user
     * @param bool $passwordOK
     */
    public static function rateLimit($user, $passwordOK) {
        if (Gdn::cache()->activeEnabled()) {
            // Rate limit using Gdn_Cache.
            $userRateKey = formatString(self::LOGIN_RATE_KEY, ['Source' => $user->UserID]);
            $userRate = (int)Gdn::cache()->get($userRateKey);
            $userRate += 1;
            Gdn::cache()->store($userRateKey, 1, [
                Gdn_Cache::FEATURE_EXPIRY => self::LOGIN_RATE
            ]);

            $sourceRateKey = formatString(self::LOGIN_RATE_KEY, ['Source' => Gdn::request()->ipAddress()]);
            $sourceRate = (int)Gdn::cache()->get($sourceRateKey);
            $sourceRate += 1;
            Gdn::cache()->store($sourceRateKey, 1, [
                Gdn_Cache::FEATURE_EXPIRY => self::LOGIN_RATE
            ]);

        } elseif (c('Garden.Apc', false) && function_exists('apc_store')) {
            // Rate limit using the APC data store.
            $userRateKey = formatString(self::LOGIN_RATE_KEY, ['Source' => $user->UserID]);
            $userRate = (int)apc_fetch($userRateKey);
            $userRate += 1;
            apc_store($userRateKey, 1, self::LOGIN_RATE);

            $sourceRateKey = formatString(self::LOGIN_RATE_KEY, ['Source' => Gdn::request()->ipAddress()]);
            $sourceRate = (int)apc_fetch($sourceRateKey);
            $sourceRate += 1;
            apc_store($sourceRateKey, 1, self::LOGIN_RATE);

        } else {
            // Rate limit using user attributes.
            $now = time();
            $userModel = Gdn::userModel();
            $lastLoginAttempt = $userModel->getAttribute($user->UserID, 'LastLoginAttempt', 0);
            $userRate = $userModel->getAttribute($user->UserID, 'LoginRate', 0);
            $userRate += 1;

            if ($lastLoginAttempt + self::LOGIN_RATE < $now) {
                $userRate = 0;
            }

            $userModel->saveToSerializedColumn(
                'Attributes',
                $user->UserID,
                ['LastLoginAttempt' => $now, 'LoginRate' => 1]
            );

            // IP rate limiting is not available without an active cache.
            $sourceRate = 0;

        }

        // Put user into cooldown mode.
        if ($userRate > 1) {
            throw new Gdn_UserException(t('LoginUserCooldown', 'You are trying to log in too often. Slow down!.'));
        }
        if ($sourceRate > 1) {
            throw new Gdn_UserException(t('LoginSourceCooldown', 'Your IP is trying to log in too often. Slow down!'));
        }

        return true;
    }

    /**
     * Clear out the password reset values for a user.
     *
     * @param int $userID
     */
    private function clearPasswordReset($userID) {
        $this->saveAttribute($userID, [
            'PasswordResetKey' => null,
            'PasswordResetExpires' => null
        ]);
    }

    /**
     * Set a single user property.
     *
     * @param int $rowID
     * @param array|string $property
     * @param bool $value
     * @return bool
     */
    public function setField($rowID, $property, $value = false) {
        if (!is_array($property)) {
            $property = [$property => $value];
        }

        $this->defineSchema();
        $fields = $this->Schema->fields();

        $set = array_intersect_key($property, $fields);
        self::serializeRow($set);

        $this->SQL
            ->update($this->Name)
            ->set($set)
            ->where('UserID', $rowID)
            ->put();

        if (in_array($property, ['Permissions'])) {
            $this->clearCache($rowID, ['permissions']);
        } else {
            $this->updateUserCache($rowID, $property, $value);
        }

        if (!is_array($property)) {
            $property = [$property => $value];
        }

        $this->EventArguments['UserID'] = $rowID;
        $this->EventArguments['Fields'] = $property;
        $this->fireEvent('AfterSetField');

        return $value;
    }

    /**
     * Get a user from the cache by name or ID
     *
     * @param string|int $userToken either a userid or a username
     * @param string $tokenType either 'userid' or 'name'
     * @return array|false Returns a user array or **false** if the user isn't in the cache.
     */
    public function getUserFromCache($userToken, $tokenType) {
        if ($tokenType == 'name') {
            $userNameKey = formatString(self::USERNAME_KEY, ['Name' => md5($userToken)]);
            $userID = Gdn::cache()->get($userNameKey);

            if ($userID === Gdn_Cache::CACHEOP_FAILURE) {
                return false;
            }
            $userToken = $userID;
            $tokenType = 'userid';
        }

        if ($tokenType != 'userid') {
            return false;
        }

        // Get from memcached
        $userKey = formatString(self::USERID_KEY, ['UserID' => $userToken]);
        $user = Gdn::cache()->get($userKey);

        return $user;
    }

    /**
     *
     *
     * @param int $userID
     * @param string|array $field
     * @param mixed|null $value
     */
    public function updateUserCache($userID, $field, $value = null) {
        // Try and get the user from the cache.
        $user = $this->getUserFromCache($userID, 'userid');

        if (!$user) {
            return;
        }

        if (!is_array($field)) {
            $field = [$field => $value];
        }

        foreach ($field as $f => $v) {
            $user[$f] = $v;
        }
        $this->userCache($user);
    }

    /**
     * Cache a user.
     *
     * @param array $user The user to cache.
     * @return bool Returns **true** if the user was cached or **false** otherwise.
     */
    public function userCache($user, $userID = null) {
        if (!$userID) {
            $userID = val('UserID', $user, null);
        }
        if (is_null($userID) || !$userID) {
            return false;
        }

        $cached = true;

        $userKey = formatString(self::USERID_KEY, ['UserID' => $userID]);
        $cached = $cached & Gdn::cache()->store($userKey, $user, [
                Gdn_Cache::FEATURE_EXPIRY => 3600
            ]);

        $userNameKey = formatString(self::USERNAME_KEY, ['Name' => md5(val('Name', $user))]);
        $cached = $cached & Gdn::cache()->store($userNameKey, $userID, [
                Gdn_Cache::FEATURE_EXPIRY => 3600
            ]);
        return $cached;
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
     * @param int|null $userID The user to clear the cache for.
     * @return bool Returns **true** if the cache was cleared or **false** otherwise.
     */
    public function clearCache($userID, $cacheTypesToClear = null) {
        if (is_null($userID) || !$userID) {
            return false;
        }

        if (is_null($cacheTypesToClear)) {
            $cacheTypesToClear = ['user', 'roles', 'permissions'];
        }

        if (in_array('user', $cacheTypesToClear)) {
            $userKey = formatString(self::USERID_KEY, ['UserID' => $userID]);
            Gdn::cache()->remove($userKey);
        }

        if (in_array('roles', $cacheTypesToClear)) {
            $userRolesKey = formatString(self::USERROLES_KEY, ['UserID' => $userID]);
            Gdn::cache()->remove($userRolesKey);
        }

        if (in_array('permissions', $cacheTypesToClear)) {
            Gdn::sql()->put('User', ['Permissions' => ''], ['UserID' => $userID]);

            $permissionsIncrement = $this->getPermissionsIncrement();
            $userPermissionsKey = formatString(self::USERPERMISSIONS_KEY, ['UserID' => $userID, 'PermissionsIncrement' => $permissionsIncrement]);
            Gdn::cache()->remove($userPermissionsKey);
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

        $permissionsIncrementKey = self::INC_PERMISSIONS_KEY;
        $permissionsIncrement = $this->getPermissionsIncrement();
        if ($permissionsIncrement == 0) {
            Gdn::cache()->store($permissionsIncrementKey, 1);
        } else {
            Gdn::cache()->increment($permissionsIncrementKey);
        }
    }

    /**
     * Get a user's permissions.
     *
     * @param int $userID Unique ID of the user.
     * @return Vanilla\Permissions
     */
    public function getPermissions($userID) {
        $permissions = new Vanilla\Permissions();
        $permissionsKey = '';

        if (Gdn::cache()->activeEnabled()) {
            $permissionsIncrement = $this->getPermissionsIncrement();
            $permissionsKey = formatString(self::USERPERMISSIONS_KEY, [
                'UserID' => $userID,
                'PermissionsIncrement' => $permissionsIncrement
            ]);

            $cachedPermissions = Gdn::cache()->get($permissionsKey);
            if ($cachedPermissions !== Gdn_Cache::CACHEOP_FAILURE) {
                $permissions->setPermissions($cachedPermissions);
                return $permissions;
            }
        }

        $data = Gdn::permissionModel()->cachePermissions($userID);
        $permissions->compileAndLoad($data);

        $this->EventArguments['UserID'] = $userID;
        $this->EventArguments['Permissions'] = $permissions;
        $this->fireEvent('loadPermissions');

        if (Gdn::cache()->activeEnabled()) {
            Gdn::cache()->store($permissionsKey, $permissions->getPermissions());
        } else {
            // Save the permissions to the user table
            if ($userID > 0) {
                $this->SQL->put(
                    'User',
                    ['Permissions' => dbencode($permissions->getPermissions())],
                    ['UserID' => $userID]
                );
            }
        }

        return $permissions;
    }

    /**
     *
     *
     * @return bool|int|mixed
     */
    public function getPermissionsIncrement() {
        $permissionsIncrementKey = self::INC_PERMISSIONS_KEY;
        $permissionsKeyValue = Gdn::cache()->get($permissionsIncrementKey);

        if (!$permissionsKeyValue) {
            $stored = Gdn::cache()->store($permissionsIncrementKey, 1);
            return $stored ? 1 : false;
        }

        return $permissionsKeyValue;
    }

    /**
     *
     *
     * @param array $roles
     * @return array
     */
    protected function lookupRoleIDs($roles) {
        if (is_string($roles)) {
            $roles = explode(',', $roles);
        } elseif (!is_array($roles)) {
            $roles = [];
        }
        $roles = array_map('trim', $roles);
        $roles = array_map('strtolower', $roles);

        $allRoles = RoleModel::roles();
        $roleIDs = [];
        foreach ($allRoles as $roleID => $role) {
            $name = strtolower($role['Name']);
            if (in_array($name, $roles) || in_array($roleID, $roles)) {
                $roleIDs[] = $roleID;
            }
        }
        return $roleIDs;
    }

    /**
     * Clears navigation preferences for a user.
     *
     * @param string $userID Optional - defaults to sessioned user
     */
    public function clearNavigationPreferences($userID = '') {
        if (!$userID) {
            $userID = Gdn::session()->UserID;
        }

        $this->savePreference($userID, 'DashboardNav.Collapsed', []);
        $this->savePreference($userID, 'DashboardNav.SectionLandingPages', []);
        $this->savePreference($userID, 'DashboardNav.DashboardLandingPage', '');
    }

    /**
     * Checks if a url is saved as a navigation preference and if so, deletes it.
     * Also optionally resets the section dashboard landing page, which may be desirable if a user no longer has
     * permission to access pages in that section.
     *
     * @param string $url The url to search the user navigation preferences for, defaults to the request
     * @param string $userID The ID of the user to clear the preferences for, defaults to the sessioned user
     * @param bool $resetSectionPreference Whether to reset the dashboard section landing page
     */
    public function clearSectionNavigationPreference($url = '', $userID = '', $resetSectionPreference = true) {
        if (!$userID) {
            $userID = Gdn::session()->UserID;
        }

        if ($url == '') {
            $url = Gdn::request()->url();
        }

        $user = $this->getID($userID);
        $preferences = val('Preferences', $user, []);
        $landingPages = val('DashboardNav.SectionLandingPages', $preferences, []);

        // Run through the user's saved landing page per section and if the url matches the passed url,
        // remove that preference.
        foreach ($landingPages as $section => $landingPage) {
            $url = strtolower(trim($url, '/'));
            $landingPage = strtolower(trim($landingPage, '/'));
            if ($url == $landingPage || stringEndsWith($url, $landingPage)) {
                unset($landingPages[$section]);
            }
        }

        $this->savePreference($userID, 'DashboardNav.SectionLandingPages', $landingPages);

        if ($resetSectionPreference) {
            $this->savePreference($userID, 'DashboardNav.DashboardLandingPage', '');
        }
    }

    /**
     * @param int $userID
     * @throws Exception
     * @return bool
     */
    public function isApplicant($userID) {
        $result = false;

        $applicantRoleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_APPLICANT);

        // Make sure the user is an applicant.
        $roleData = $this->getRoles($userID);
        if (count($roleData) == 0) {
            throw new Exception(t('ErrorRecordNotFound'));
        } else {
            foreach ($roleData as $appRole) {
                if (in_array(val('RoleID', $appRole), $applicantRoleIDs)) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Do the registration values indicate SPAM?
     *
     * @param array $formPostValues
     * @throws Gdn_UserException if the values trigger a positive SPAM match.
     * @return bool
     */
    public function isRegistrationSpam(array $formPostValues) {
        $result = (bool)SpamModel::isSpam('Registration', $formPostValues, ['Log' => false]);
        return $result;
    }

    /**
     * Validate the strength of a user's password.
     *
     * @param string $password A password to test.
     * @param string $username The name of the user. Used to verify the password doesn't contain this value.
     * @throws Gdn_UserException if the password is too weak.
     * @return bool
     */
    public function validatePasswordStrength($password, $username) {
        $strength = passwordStrength($password, $username);
        $result = (bool)$strength['Pass'];

        if ($result === false) {
            throw new Gdn_UserException('The password is too weak.');
        }
        return $result;
    }

    /**
     * Get the proper sort column and direction for a user query, based on the Garden.MentionsOrder config.
     *
     * @return array An array of two elements representing a sort: column and direction.
     */
    public function getMentionsSort() {
        $mentionsOrder = c('Garden.MentionsOrder');
        switch ($mentionsOrder) {
            case 'Name':
                $column = 'Name';
                $direction = 'asc';
                break;
            case 'DateLastActive':
                $column = 'DateLastActive';
                $direction = 'desc';
                break;
            case 'CountComments':
            default:
                $column = 'CountComments';
                $direction = 'desc';
                break;
        }

        $result = [$column, $direction];
        return $result;
    }
}
