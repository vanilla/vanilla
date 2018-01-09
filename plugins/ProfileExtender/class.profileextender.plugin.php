<?php
/**
 * ProfileExtender Plugin.
 *
 * @author Lincoln Russell <lincoln@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package ProfileExtender
 */

/**
 * Plugin to add additional fields to user profiles.
 *
 * If the field name is an existing column on user table (e.g. Title, About, Location)
 * it will store there. Otherwise, it stores in UserMeta.
 *
 * @todo Option to show in discussions
 * @todo Sort order
 * @todo Lockable for Garden.Moderation.Manage
 * @todo Date fields
 * @todo Gender, birthday adding
 * @todo Dynamic magic field filtering/linking
 * @todo Dynamic validation rule
 */
class ProfileExtenderPlugin extends Gdn_Plugin {

    public function base_render_before($sender) {
        if ($sender->MasterView == 'admin') {
            $sender->addJsFile('profileextender.js', 'plugins/ProfileExtender');
        }
    }

    /** @var array */
    public $MagicLabels = ['Twitter', 'Google', 'Facebook', 'LinkedIn', 'GitHub', 'Website', 'Real Name'];

    /**
     * Available form field types in format Gdn_Type => DisplayName.
     */
    public $FormTypes = [
        'TextBox' => 'TextBox',
        'Dropdown' => 'Dropdown',
        'CheckBox' => 'Checkbox',
        'DateOfBirth' => 'Birthday',
    ];

    /**
     * Whitelist of allowed field properties.
     */
    public $FieldProperties = ['Name', 'Label', 'FormType', 'Required', 'Locked',
        'Options', 'Length', 'Sort', 'OnRegister', 'OnProfile', 'OnDiscussion'];

    /**
     * Blacklist of disallowed field names.
     * Prevents accidental or malicious overwrite of sensitive fields.
     */
    public $ReservedNames = ['Name', 'Email', 'Password', 'HashMethod', 'Admin', 'Banned', 'Points',
        'Deleted', 'Verified', 'Attributes', 'Permissions', 'Preferences'];

    /** @var array */
    public $ProfileFields = [];

    /**
     * Add the Dashboard menu item.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = &$sender->EventArguments['SideMenu'];
        $menu->addLink('Users', t('Profile Fields'), 'settings/profileextender', 'Garden.Settings.Manage');
    }

    /**
     * Add non-checkbox fields to registration forms.
     */
    public function entryController_registerBeforePassword_handler($Sender) {
        $ProfileFields = $this->getProfileFields();
        $Sender->RegistrationFields = [];
        foreach ($ProfileFields as $Name => $Field) {
            if (val('OnRegister', $Field) && val('FormType', $Field) != 'CheckBox') {
                $Sender->RegistrationFields[$Name] = $Field;
            }
        }
        include $Sender->fetchViewLocation('registrationfields', '', 'plugins/ProfileExtender');
    }

    /**
     * Add checkbox fields to registration forms.
     */
    public function entryController_registerFormBeforeTerms_handler($Sender) {
        $ProfileFields = $this->getProfileFields();
        $Sender->RegistrationFields = [];
        foreach ($ProfileFields as $Name => $Field) {
            if (val('OnRegister', $Field) && val('FormType', $Field) == 'CheckBox') {
                $Sender->RegistrationFields[$Name] = $Field;
            }
        }
        include $Sender->fetchViewLocation('registrationfields', '', 'plugins/ProfileExtender');
    }

    /**
     * Required fields on registration forms.
     */
    public function entryController_registerValidation_handler($sender) {
        // Require new fields
        $profileFields = $this->getProfileFields();
        foreach ($profileFields as $name => $field) {
            // Check both so you can't break register form by requiring omitted field
            if (val('Required', $field) && val('OnRegister', $field)) {
                $sender->UserModel->Validation->applyRule($name, 'Required', t('%s is required.', $field['Label']));
            }
        }

        // DateOfBirth zeroes => NULL
        if ('0-00-00' == $sender->Form->getFormValue('DateOfBirth')) {
            $sender->Form->setFormValue('DateOfBirth', null);
        }
    }

    /**
     * Special manipulations.
     */
    public function parseSpecialFields($fields = []) {
        if (!is_array($fields)) {
            return $fields;
        }

        foreach ($fields as $label => $value) {
            if ($value == '') {
                continue;
            }

            // Use plaintext for building these
            $value = Gdn_Format::text($value);

            switch ($label) {
                case 'Twitter':
                    $fields['Twitter'] = '@'.anchor($value, 'http://twitter.com/'.$value);
                    break;
                case 'Facebook':
                    $fields['Facebook'] = anchor($value, 'http://facebook.com/'.$value);
                    break;
                case 'LinkedIn':
                    $fields['LinkedIn'] = anchor($value, 'http://www.linkedin.com/in/'.$value);
                    break;
                case 'GitHub':
                    $fields['GitHub'] = anchor($value, 'https://github.com/'.$value);
                    break;
                case 'Google':
                    $fields['Google'] = anchor('Google+', $value, '', ['rel' => 'me']);
                    break;
                case 'Website':
                    $linkValue = (isUrl($value)) ? $value : 'http://'.$value;
                    $fields['Website'] = anchor($value, $linkValue);
                    break;
                case 'Real Name':
                    $fields['Real Name'] = wrap(htmlspecialchars($value), 'span', ['itemprop' => 'name']);
                    break;
            }
        }

        return $fields;
    }

    /**
     * Add fields to edit profile form.
     */
    public function profileController_editMyAccountAfter_handler($sender) {
        $this->profileFields($sender);
    }

    /**
     * Add custom fields to discussions.
     */
    public function base_authorInfo_handler($sender, $args) {
        //echo ' '.wrapIf(htmlspecialchars(val('Department', $Args['Author'])), 'span', array('class' => 'MItem AuthorDepartment'));
        //echo ' '.wrapIf(htmlspecialchars(val('Organization', $Args['Author'])), 'span', array('class' => 'MItem AuthorOrganization'));
    }

    /**
     * Get custom profile fields.
     *
     * @return array
     */
    private function getProfileFields() {
        $fields = c('ProfileExtender.Fields', []);
        if (!is_array($fields)) {
            $fields = [];
        }

        // Data checks
        foreach ($fields as $name => $field) {
            // Require an array for each field
            if (!is_array($field) || strlen($name) < 1) {
                unset($fields[$name]);
                //RemoveFromConfig('ProfileExtender.Fields.'.$Name);
            }

            // Verify field form type
            if (!isset($field['FormType'])) {
                $fields[$name]['FormType'] = 'TextBox';
            } elseif (!array_key_exists($field['FormType'], $this->FormTypes)) {
                unset($this->ProfileFields[$name]);
            } elseif ($fields[$name]['FormType'] == 'DateOfBirth') {
                // Special case for birthday field
                $fields[$name]['FormType'] = 'Date';
                $fields[$name]['Label'] = t('Birthday');
            }
        }

        return $fields;
    }

    /**
     * Get data for a single profile field.
     *
     * @param $name
     * @return array
     */
    private function getProfileField($name) {
        $field = c('ProfileExtender.Fields.'.$name, []);
        if (!isset($field['FormType'])) {
            $field['FormType'] = 'TextBox';
        }
        return $field;
    }

    /**
     * Display custom profile fields on form.
     *
     * @access private
     */
    private function profileFields($Sender) {
        // Retrieve user's existing profile fields
        $this->ProfileFields = $this->getProfileFields();

        // Get user-specific data
        $this->UserFields = Gdn::userModel()->getMeta($Sender->Form->getValue('UserID'), 'Profile.%', 'Profile.');
        // Fill in user data on form
        foreach ($this->UserFields as $Field => $Value) {
            $Sender->Form->setValue($Field, $Value);
        }

        include_once $Sender->fetchViewLocation('profilefields', '', 'plugins/ProfileExtender');
    }

    /**
     * Settings page.
     */
    public function settingsController_profileExtender_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        // Detect if we need to upgrade settings
        if (!c('ProfileExtender.Fields')) {
            $this->setup();
        }

        // Set data
        $data = $this->getProfileFields();
        $sender->setData('ExtendedFields', $data);

        $sender->setHighlightRoute('settings/profileextender');
        $sender->setData('Title', t('Profile Fields'));
        $sender->render('settings', '', 'plugins/ProfileExtender');
    }

    /**
     * Add/edit a field.
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_profileFieldAddEdit_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('Title', t('Add Profile Field'));

        if ($sender->Form->authenticatedPostBack()) {
            // Get whitelisted properties
            $formPostValues = $sender->Form->formValues();
            foreach ($formPostValues as $key => $value) {
                if (!in_array($key, $this->FieldProperties)) {
                    unset ($formPostValues[$key]);
                }
            }

            // Make Options an array
            if ($options = val('Options', $formPostValues)) {
                $options = explode("\n", preg_replace('/[^\w\s()-]/u', '', $options));
                if (count($options) < 2) {
                    $sender->Form->addError('Must have at least 2 options.', 'Options');
                }
                setValue('Options', $formPostValues, $options);
            }

            // Check label
            if (val('FormType', $formPostValues) == 'DateOfBirth') {
                setValue('Label', $formPostValues, 'DateOfBirth');
            }
            if (!val('Label', $formPostValues)) {
                $sender->Form->addError('Label is required.', 'Label');
            }

            // Check form type
            if (!array_key_exists(val('FormType', $formPostValues), $this->FormTypes)) {
                $sender->Form->addError('Invalid form type.', 'FormType');
            }

            // Force CheckBox options
            if (val('FormType', $formPostValues) == 'CheckBox') {
                setValue('Required', $formPostValues, true);
                setValue('OnRegister', $formPostValues, true);
            }

            // Merge updated data into config
            $fields = $this->getProfileFields();
            if (!$name = val('Name', $formPostValues)) {
                // Make unique name from label for new fields
                if (unicodeRegexSupport()) {
                    $regex = '/[^\pL\pN]/u';
                } else {
                    $regex = '/[^a-z\d]/i';
                }
                // Make unique slug
                $name = $testSlug = substr(preg_replace($regex, '', val('Label', $formPostValues)), 0, 50);
                $i = 1;

                // Fallback in case the name is empty
                if (empty($name)) {
                    $name = $testSlug = md5($field);
                }
                while (array_key_exists($name, $fields) || in_array($name, $this->ReservedNames)) {
                    $name = $testSlug.$i++;
                }
            }

            // Save if no errors
            if (!$sender->Form->errorCount()) {
                $data = c('ProfileExtender.Fields.'.$name, []);
                $data = array_merge((array)$data, (array)$formPostValues);
                saveToConfig('ProfileExtender.Fields.'.$name, $data);
                $sender->setRedirectTo('/settings/profileextender');
            }
        } elseif (isset($args[0])) {
            // Editing
            $data = $this->getProfileField($args[0]);
            if (isset($data['Options']) && is_array($data['Options'])) {
                $data['Options'] = implode("\n", $data['Options']);
            }
            $sender->Form->setData($data);
            $sender->Form->addHidden('Name', $args[0]);
            $sender->setData('Title', t('Edit Profile Field'));
        }

        $currentFields = $this->getProfileFields();
        $formTypes = $this->FormTypes;

        /**
         * We only allow one DateOfBirth field, since it is a special case.  Remove it as an option if we already
         * have one, unless we're editing the one instance we're allowing.
         */
        if (array_key_exists('DateOfBirth', $currentFields) && $sender->Form->getValue('FormType') != 'DateOfBirth') {
            unset($formTypes['DateOfBirth']);
        }

        $sender->setData('FormTypes', $formTypes);
        $sender->setData('CurrentFields', $currentFields);

        $sender->render('addedit', '', 'plugins/ProfileExtender');
    }

    /**
     * Delete a field.
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_profileFieldDelete_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('Title', 'Delete Field');
        if (isset($args[0])) {
            if ($sender->Form->authenticatedPostBack()) {
                removeFromConfig('ProfileExtender.Fields.'.$args[0]);
                $sender->setRedirectTo('/settings/profileextender');
            } else {
                $sender->setData('Field', $this->getProfileField($args[0]));
            }
        }
        $sender->render('delete', '', 'plugins/ProfileExtender');
    }

    /**
     * Display custom fields on Edit User form.
     */
    public function userController_afterFormInputs_handler($sender) {
        echo '<ul>';
        $this->profileFields($sender);
        echo '</ul>';
    }

    /**
     * Display custom fields on Profile.
     */
    public function userInfoModule_onBasicInfo_handler($Sender) {
        if ($Sender->User->Banned) {
            return;
        }

        try {
            // Get the custom fields
            $ProfileFields = Gdn::userModel()->getMeta($Sender->User->UserID, 'Profile.%', 'Profile.');

            Gdn::controller()->setData('ExtendedFields', $ProfileFields);

            // Get allowed GDN_User fields.
            $Blacklist = array_combine($this->ReservedNames, $this->ReservedNames);
            $NativeFields = array_diff_key((array)$Sender->User, $Blacklist);

            // Combine custom fields (GDN_UserMeta) with GDN_User fields.
            // This is OK because we're blacklisting our $ReservedNames AND whitelisting $AllFields below.
            $ProfileFields = array_merge($ProfileFields, $NativeFields);

            // Import from CustomProfileFields if available
            if (!count($ProfileFields) && is_object($Sender->User) && c('Plugins.CustomProfileFields.SuggestedFields', false)) {
                $ProfileFields = Gdn::userModel()->getAttribute($Sender->User->UserID, 'CustomProfileFields', false);
                if ($ProfileFields) {
                    // Migrate to UserMeta & delete original
                    Gdn::userModel()->setMeta($Sender->User->UserID, $ProfileFields, 'Profile.');
                    Gdn::userModel()->saveAttribute($Sender->User->UserID, 'CustomProfileFields', false);
                }
            }

            // Send them off for magic formatting
            $ProfileFields = $this->parseSpecialFields($ProfileFields);

            // Get all field data, error check
            $AllFields = $this->getProfileFields();
            if (!is_array($AllFields) || !is_array($ProfileFields)) {
                return;
            }

            // DateOfBirth is special case that core won't handle
            // Hack it in here instead
            if (c('ProfileExtender.Fields.DateOfBirth.OnProfile')) {
                // Do not use Gdn_Format::Date because it shifts to local timezone
                $BirthdayStamp = Gdn_Format::toTimestamp($Sender->User->DateOfBirth);
                if ($BirthdayStamp) {
                    $ProfileFields['DateOfBirth'] = date(t('Birthday Format', 'F j, Y'), $BirthdayStamp);
                    $AllFields['DateOfBirth'] = ['Label' => t('Birthday'), 'OnProfile' => true];
                }
            }

            // Display all non-hidden fields
            require_once Gdn::controller()->fetchViewLocation('helper_functions', '', 'plugins/ProfileExtender', true, false);
            $ProfileFields = array_reverse($ProfileFields, true);
            extendedProfileFields($ProfileFields, $AllFields, $this->MagicLabels);
        } catch (Exception $ex) {
            // No errors
        }
    }

    /**
     * Save custom profile fields when saving the user.
     *
     * @param $sender object
     * @param $args array
     */
    public function userModel_afterSave_handler($sender, $args) {
        $this->updateUserFields($args['UserID'], $args['FormPostValues']);
    }

    /**
     * Save custom profile fields on registration.
     *
     * @param $sender object
     * @param $args array
     */
    public function userModel_afterInsertUser_handler($sender, $args) {
        $this->updateUserFields($args['InsertUserID'], $args['RegisteringUser']);
    }

    /**
     * Update user with new profile fields.
     *
     * @param $userID int
     * @param $fields array
     */
    protected function updateUserFields($userID, $fields) {
        // Confirm we have submitted form values
        if (is_array($fields)) {
            // Retrieve whitelist & user column list
            $allowedFields = $this->getProfileFields();
            $columns = Gdn::sql()->fetchColumns('User');

            foreach ($fields as $name => $field) {
                // Whitelist
                if (!array_key_exists($name, $allowedFields)) {
                    unset($fields[$name]);
                    continue;
                }
                // Don't allow duplicates on User table
                if (in_array($name, $columns)) {
                    unset($fields[$name]);
                }
            }

            // Update UserMeta if any made it thru
            if (count($fields)) {
                Gdn::userModel()->setMeta($userID, $fields, 'Profile.');
            }
        }
    }


    /**
     * Endpoint to export basic user data along with all custom fields into CSV.
     */
    public function utilityController_exportProfiles_create($sender) {
        // Clear our ability to do this.
        $sender->permission('Garden.Settings.Manage');
        if (Gdn::userModel()->pastUserMegaThreshold()) {
            throw new Gdn_UserException('You have too many users to export automatically.');
        }

        // Determine profile fields we need to add.
        $fields = $this->getProfileFields();
        $columnNames = ['Name', 'Email', 'Joined', 'Last Seen', 'Discussions', 'Comments', 'Points', 'InviteUserID', 'InvitedByName'];

        // Set up our basic query.
        Gdn::sql()
            ->select('u.Name')
            ->select('u.Email')
            ->select('u.DateInserted')
            ->select('u.DateLastActive')
            ->select('u.CountDiscussions')
            ->select('u.CountComments')
            ->select('u.Points')
            ->select('u.InviteUserID')
            ->select('u2.Name', '', 'InvitedByName')
            ->from('User u')
            ->leftJoin('User u2', 'u.InviteUserID = u2.UserID and u.InviteUserID is not null')
            ->where('u.Deleted', 0)
            ->where('u.Admin <', 2);

        if (val('DateOfBirth', $fields)) {
            $columnNames[] = 'Birthday';
            Gdn::sql()->select('u.DateOfBirth');
            unset($fields['DateOfBirth']);
        }

        $i = 0;
        foreach ($fields as $slug => $fieldData) {
            // Add this field to the output
            $columnNames[] = val('Label', $fieldData, $slug);

            // Add this field to the query.
            Gdn::sql()
                ->join('UserMeta a'.$i, "u.UserID = a$i.UserID and a$i.Name = 'Profile.$slug'", 'left')
                ->select('a'.$i.'.Value', '', $slug);
            $i++;
        }

        // Get our user data.
        $users = Gdn::sql()->get()->resultArray();

        // Serve a CSV of the results.
        exportCSV($columnNames, $users);
        die();

        // Useful for query debug.
        //$sender->render('blank');
    }

    /**
     * Import from CustomProfileFields or upgrade from ProfileExtender 2.0.
     */
    public function setup() {
        if ($fields = c('Plugins.ProfileExtender.ProfileFields', c('Plugins.CustomProfileFields.SuggestedFields'))) {
            // Get defaults
            $hidden = c('Plugins.ProfileExtender.HideFields', c('Plugins.CustomProfileFields.HideFields'));
            $onRegister = c('Plugins.ProfileExtender.RegistrationFields');
            $length = c('Plugins.ProfileExtender.TextMaxLength', c('Plugins.CustomProfileFields.ValueLength'));

            // Convert to arrays
            $fields = array_filter((array)explode(',', $fields));
            $hidden = array_filter((array)explode(',', $hidden));
            $onRegister = array_filter((array)explode(',', $onRegister));

            // Assign new data structure
            $newData = [];
            foreach ($fields as $field) {
                if (unicodeRegexSupport()) {
                    $regex = '/[^\pL\pN]/u';
                } else {
                    $regex = '/[^a-z\d]/i';
                }
                // Make unique slug
                $name = $testSlug = preg_replace($regex, '', $field);
                $i = 1;

                // Fallback in case the name is empty
                if (empty($name)) {
                    $name = $testSlug = md5($field);
                }
                while (array_key_exists($name, $newData) || in_array($name, $this->ReservedNames)) {
                    $name = $testSlug.$i++;
                }

                // Convert
                $newData[$name] = [
                    'Label' => $field,
                    'Length' => $length,
                    'FormType' => 'TextBox',
                    'OnProfile' => (in_array($field, $hidden)) ? 0 : 1,
                    'OnRegister' => (in_array($field, $onRegister)) ? 1 : 0,
                    'OnDiscussion' => 0,
                    'Required' => 0,
                    'Locked' => 0,
                    'Sort' => 0
                ];
            }
            saveToConfig('ProfileExtender.Fields', $newData);
        }
    }
}

// 2.0 used these config settings; the first 3 were a comma-separated list of field names.
//'Plugins.ProfileExtender.ProfileFields' => array('Control' => 'TextBox', 'Options' => array('MultiLine' => TRUE)),
//'Plugins.ProfileExtender.RegistrationFields' => array('Control' => 'TextBox', 'Options' => array('MultiLine' => TRUE)),
//'Plugins.ProfileExtender.HideFields' => array('Control' => 'TextBox', 'Options' => array('MultiLine' => TRUE)),
//'Plugins.ProfileExtender.TextMaxLength' => array('Control' => 'TextBox'),
