<?php
/**
 * ProfileExtender Plugin.
 *
 * @author Lincoln Russell <lincoln@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package ProfileExtender
 */

$PluginInfo['ProfileExtender'] = array(
    'Name' => 'Profile Extender',
    'Description' => 'Add fields (like status, location, or gamer tags) to profiles and registration.',
    'Version' => '3.0.2',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'MobileFriendly' => true,
    //'RegisterPermissions' => array('Plugins.ProfileExtender.Add'),
    'SettingsUrl' => '/dashboard/settings/profileextender',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => "Lincoln Russell",
    'AuthorEmail' => 'lincoln@vanillaforums.com',
    'AuthorUrl' => 'http://lincolnwebs.com'
);

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

    /** @var array */
    public $MagicLabels = array('Twitter', 'Google', 'Facebook', 'LinkedIn', 'Website', 'Real Name');

    /**
     * Available form field types in format Gdn_Type => DisplayName.
     */
    public $FormTypes = array(
        'TextBox' => 'TextBox',
        'Dropdown' => 'Dropdown',
        'CheckBox' => 'Checkbox',
        'DateOfBirth' => 'Birthday',
    );

    /**
     * Whitelist of allowed field properties.
     */
    public $FieldProperties = array('Name', 'Label', 'FormType', 'Required', 'Locked',
        'Options', 'Length', 'Sort', 'OnRegister', 'OnProfile', 'OnDiscussion');

    /**
     * Blacklist of disallowed field names.
     * Prevents accidental or malicious overwrite of sensitive fields.
     */
    public $ReservedNames = array('Name', 'Email', 'Password', 'HashMethod', 'Admin', 'Banned', 'Points',
        'Deleted', 'Verified', 'Attributes', 'Permissions', 'Preferences');

    /** @var array */
    public $ProfileFields = array();

    /**
     * Add the Dashboard menu item.
     */
    public function base_GetAppSettingsMenuItems_handler($Sender) {
        $Menu = &$Sender->EventArguments['SideMenu'];
        $Menu->addLink('Users', t('Profile Fields'), 'settings/profileextender', 'Garden.Settings.Manage');
    }

    /**
     * Add non-checkbox fields to registration forms.
     */
    public function entryController_RegisterBeforePassword_handler($Sender) {
        $ProfileFields = $this->GetProfileFields();
        $Sender->RegistrationFields = array();
        foreach ($ProfileFields as $Name => $Field) {
            if (val('OnRegister', $Field) && val('FormType', $Field) != 'CheckBox') {
                $Sender->RegistrationFields[$Name] = $Field;
            }
        }
        include($this->getView('registrationfields.php'));
    }

    /**
     * Add checkbox fields to registration forms.
     */
    public function entryController_RegisterFormBeforeTerms_handler($Sender) {
        $ProfileFields = $this->getProfileFields();
        $Sender->RegistrationFields = array();
        foreach ($ProfileFields as $Name => $Field) {
            if (val('OnRegister', $Field) && val('FormType', $Field) == 'CheckBox') {
                $Sender->RegistrationFields[$Name] = $Field;
            }
        }
        include($this->getView('registrationfields.php'));
    }

    /**
     * Required fields on registration forms.
     */
    public function entryController_registerValidation_handler($Sender) {
        // Require new fields
        $ProfileFields = $this->getProfileFields();
        foreach ($ProfileFields as $Name => $Field) {
            // Check both so you can't break register form by requiring omitted field
            if (val('Required', $Field) && val('OnRegister', $Field)) {
                $Sender->UserModel->Validation->applyRule($Name, 'Required', $Field['Label']." is required.");
            }
        }

        // DateOfBirth zeroes => NULL
        if ('0-00-00' == $Sender->Form->getFormValue('DateOfBirth')) {
            $Sender->Form->setFormValue('DateOfBirth', null);
        }
    }

    /**
     * Special manipulations.
     */
    public function parseSpecialFields($Fields = array()) {
        if (!is_array($Fields)) {
            return $Fields;
        }

        foreach ($Fields as $Label => $Value) {
            if ($Value == '') {
                continue;
            }

            // Use plaintext for building these
            $Value = Gdn_Format::text($Value);

            switch ($Label) {
                case 'Twitter':
                    $Fields['Twitter'] = '@'.anchor($Value, 'http://twitter.com/'.$Value);
                    break;
                case 'Facebook':
                    $Fields['Facebook'] = anchor($Value, 'http://facebook.com/'.$Value);
                    break;
                case 'LinkedIn':
                    $Fields['LinkedIn'] = anchor($Value, 'http://www.linkedin.com/in/'.$Value);
                    break;
                case 'Google':
                    $Fields['Google'] = anchor('Google+', $Value, '', array('rel' => 'me'));
                    break;
                case 'Website':
                    $LinkValue = (isUrl($Value)) ? $Value : 'http://'.$Value;
                    $Fields['Website'] = anchor($Value, $LinkValue);
                    break;
                case 'Real Name':
                    $Fields['Real Name'] = wrap(htmlspecialchars($Value), 'span', array('itemprop' => 'name'));
                    break;
            }
        }

        return $Fields;
    }

    /**
     * Add fields to edit profile form.
     */
    public function profileController_editMyAccountAfter_handler($Sender) {
        $this->profileFields($Sender);
    }

    /**
     * Add custom fields to discussions.
     */
    public function base_AuthorInfo_handler($Sender, $Args) {
        //echo ' '.WrapIf(htmlspecialchars(val('Department', $Args['Author'])), 'span', array('class' => 'MItem AuthorDepartment'));
        //echo ' '.WrapIf(htmlspecialchars(val('Organization', $Args['Author'])), 'span', array('class' => 'MItem AuthorOrganization'));
    }

    /**
     * Get custom profile fields.
     *
     * @return array
     */
    private function getProfileFields() {
        $Fields = c('ProfileExtender.Fields', array());

        if (!is_array($Fields)) {
            $Fields = array();
        }

        // Data checks
        foreach ($Fields as $Name => $Field) {
            // Require an array for each field
            if (!is_array($Field) || strlen($Name) < 1) {
                unset($Fields[$Name]);
                //RemoveFromConfig('ProfileExtender.Fields.'.$Name);
            }

            // Verify field form type
            if (!isset($Field['FormType'])) {
                $Fields[$Name]['FormType'] = 'TextBox';
            } elseif (!array_key_exists($Field['FormType'], $this->FormTypes))
                unset($this->ProfileFields[$Name]);
        }

        // Special case for birthday field
        if (isset($Fields['DateOfBirth'])) {
            $Fields['DateOfBirth']['FormType'] = 'Date';
            $Fields['DateOfBirth']['Label'] = t('Birthday');
        }

        return $Fields;
    }

    /**
     * Get data for a single profile field.
     *
     * @param $Name
     * @return array
     */
    private function getProfileField($Name) {
        $Field = c('ProfileExtender.Fields.'.$Name, array());
        if (!isset($Field['FormType'])) {
            $Field['FormType'] = 'TextBox';
        }
        return $Field;
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
        $this->UserFields = Gdn::userModel()->GetMeta($Sender->data("User.UserID"), 'Profile.%', 'Profile.');

        // Fill in user data on form
        foreach ($this->UserFields as $Field => $Value) {
            $Sender->Form->setValue($Field, $Value);
        }

        include($this->getView('profilefields.php'));
    }

    /**
     * Settings page.
     */
    public function settingsController_profileExtender_create($Sender) {
        $Sender->permission('Garden.Settings.Manage');
        // Detect if we need to upgrade settings
        if (!c('ProfileExtender.Fields')) {
            $this->setup();
        }

        // Set data
        $Data = $this->getProfileFields();
        $Sender->setData('ExtendedFields', $Data);

        $Sender->addSideMenu('settings/profileextender');
        $Sender->setData('Title', t('Profile Fields'));
        $Sender->render('settings', '', 'plugins/ProfileExtender');
    }

    /**
     * Add/edit a field.
     */
    public function settingsController_profileFieldAddEdit_create($Sender, $Args) {
        $Sender->permission('Garden.Settings.Manage');
        $Sender->setData('Title', t('Add Profile Field'));

        if ($Sender->Form->authenticatedPostBack()) {
            // Get whitelisted properties
            $FormPostValues = $Sender->Form->formValues();
            foreach ($FormPostValues as $Key => $Value) {
                if (!in_array($Key, $this->FieldProperties)) {
                    unset ($FormPostValues[$Key]);
                }
            }

            // Make Options an array
            if ($Options = val('Options', $FormPostValues)) {
                $Options = explode("\n", preg_replace('/[^\w\s()-]/u', '', $Options));
                if (count($Options) < 2) {
                    $Sender->Form->addError('Must have at least 2 options.', 'Options');
                }
                setValue('Options', $FormPostValues, $Options);
            }

            // Check label
            if (val('FormType', $FormPostValues) == 'DateOfBirth') {
                setValue('Label', $FormPostValues, 'DateOfBirth');
            }
            if (!val('Label', $FormPostValues)) {
                $Sender->Form->addError('Label is required.', 'Label');
            }

            // Check form type
            if (!array_key_exists(val('FormType', $FormPostValues), $this->FormTypes)) {
                $Sender->Form->addError('Invalid form type.', 'FormType');
            }

            // Force CheckBox options
            if (val('FormType', $FormPostValues) == 'CheckBox') {
                setValue('Required', $FormPostValues, true);
                setValue('OnRegister', $FormPostValues, true);
            }

            // Merge updated data into config
            $Fields = $this->GetProfileFields();
            if (!$Name = val('Name', $FormPostValues)) {
                // Make unique name from label for new fields
                $Name = $TestSlug = preg_replace('`[^0-9a-zA-Z]`', '', val('Label', $FormPostValues));
                $i = 1;
                while (array_key_exists($Name, $Fields) || in_array($Name, $this->ReservedNames)) {
                    $Name = $TestSlug.$i++;
                }
            }

            // Save if no errors
            if (!$Sender->Form->errorCount()) {
                $Data = c('ProfileExtender.Fields.'.$Name, array());
                $Data = array_merge((array)$Data, (array)$FormPostValues);
                saveToConfig('ProfileExtender.Fields.'.$Name, $Data);
                $Sender->RedirectUrl = url('/settings/profileextender');
            }
        } elseif (isset($Args[0])) {
            // Editing
            $Data = $this->GetProfileField($Args[0]);
            if (isset($Data['Options']) && is_array($Data['Options'])) {
                $Data['Options'] = implode("\n", $Data['Options']);
            }
            $Sender->Form->setData($Data);
            $Sender->Form->addHidden('Name', $Args[0]);
            $Sender->setData('Title', t('Edit Profile Field'));
        }

        $CurrentFields = $this->getProfileFields();
        $FormTypes = $this->FormTypes;

        /**
         * We only allow one DateOfBirth field, since it is a special case.  Remove it as an option if we already
         * have one, unless we're editing the one instance we're allowing.
         */
        if (array_key_exists('DateOfBirth', $CurrentFields) && $Sender->Form->getValue('FormType') != 'DateOfBirth') {
            unset($FormTypes['DateOfBirth']);
        }

        $Sender->setData('FormTypes', $FormTypes);
        $Sender->setData('CurrentFields', $CurrentFields);

        $Sender->render('addedit', '', 'plugins/ProfileExtender');
    }

    /**
     * Delete a field.
     */
    public function settingsController_profileFieldDelete_create($Sender, $Args) {
        $Sender->permission('Garden.Settings.Manage');
        $Sender->setData('Title', 'Delete Field');
        if (isset($Args[0])) {
            if ($Sender->Form->authenticatedPostBack()) {
                removeFromConfig('ProfileExtender.Fields.'.$Args[0]);
                $Sender->RedirectUrl = url('/settings/profileextender');
            } else {
                $Sender->setData('Field', $this->getProfileField($Args[0]));
            }
        }
        $Sender->render('delete', '', 'plugins/ProfileExtender');
    }

    /**
     * Display custom fields on Edit User form.
     */
    public function userController_afterFormInputs_handler($Sender) {
        echo '<ul>';
        $this->profileFields($Sender);
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
                    $AllFields['DateOfBirth'] = array('Label' => t('Birthday'), 'OnProfile' => true);
                }
            }

            // Display all non-hidden fields
            $ProfileFields = array_reverse($ProfileFields);
            foreach ($ProfileFields as $Name => $Value) {
                // Skip empty and hidden fields.
                if (!$Value || !val('OnProfile', $AllFields[$Name])) {
                    continue;
                }

                // Non-magic fields must be plain text, but we'll auto-link
                if (!in_array($Name, $this->MagicLabels)) {
                    $Value = Gdn_Format::links(Gdn_Format::text($Value));
                }

                echo ' <dt class="ProfileExtend Profile'.Gdn_Format::alphaNumeric($Name).'">'.Gdn_Format::text($AllFields[$Name]['Label']).'</dt> ';
                echo ' <dd class="ProfileExtend Profile'.Gdn_Format::alphaNumeric($Name).'">'.Gdn_Format::htmlFilter($Value).'</dd> ';
            }
        } catch (Exception $ex) {
            // No errors
        }
    }

    /**
     * Save custom profile fields when saving the user.
     *
     * @param $Sender object
     * @param $Args array
     */
    public function userModel_afterSave_handler($Sender, $Args) {
        $this->updateUserFields($Args['UserID'], $Args['FormPostValues']);
    }

    /**
     * Save custom profile fields on registration.
     *
     * @param $Sender object
     * @param $Args array
     */
    public function userModel_afterInsertUser_handler($Sender, $Args) {
        $this->updateUserFields($Args['InsertUserID'], $Args['User']);
    }

    /**
     * Update user with new profile fields.
     *
     * @param $UserID int
     * @param $Fields array
     */
    protected function updateUserFields($UserID, $Fields) {
        // Confirm we have submitted form values
        if (is_array($Fields)) {
            // Retrieve whitelist & user column list
            $AllowedFields = $this->getProfileFields();
            $Columns = Gdn::sql()->fetchColumns('User');

            foreach ($Fields as $Name => $Field) {
                // Whitelist
                if (!array_key_exists($Name, $AllowedFields)) {
                    unset($Fields[$Name]);
                    continue;
                }
                // Don't allow duplicates on User table
                if (in_array($Name, $Columns)) {
                    unset($Fields[$Name]);
                }
            }

            // Update UserMeta if any made it thru
            if (count($Fields)) {
                Gdn::userModel()->setMeta($UserID, $Fields, 'Profile.');
            }
        }
    }

    /**
     * Import from CustomProfileFields or upgrade from ProfileExtender 2.0.
     */
    public function setup() {
        if ($Fields = c('Plugins.ProfileExtender.ProfileFields', c('Plugins.CustomProfileFields.SuggestedFields'))) {
            // Get defaults
            $Hidden = c('Plugins.ProfileExtender.HideFields', c('Plugins.CustomProfileFields.HideFields'));
            $OnRegister = c('Plugins.ProfileExtender.RegistrationFields');
            $Length = c('Plugins.ProfileExtender.TextMaxLength', c('Plugins.CustomProfileFields.ValueLength'));

            // Convert to arrays
            $Fields = array_filter((array)explode(',', $Fields));
            $Hidden = array_filter((array)explode(',', $Hidden));
            $OnRegister = array_filter((array)explode(',', $OnRegister));

            // Assign new data structure
            $NewData = array();
            foreach ($Fields as $Field) {
                // Make unique slug
                $Name = $TestSlug = preg_replace('`[^0-9a-zA-Z]`', '', $Field);
                $i = 1;
                while (array_key_exists($Name, $NewData) || in_array($Name, $this->ReservedNames)) {
                    $Name = $TestSlug.$i++;
                }

                // Convert
                $NewData[$Name] = array(
                    'Label' => $Field,
                    'Length' => $Length,
                    'FormType' => 'TextBox',
                    'OnProfile' => (in_array($Field, $Hidden)) ? 0 : 1,
                    'OnRegister' => (in_array($Field, $OnRegister)) ? 1 : 0,
                    'OnDiscussion' => 0,
                    'Required' => 0,
                    'Locked' => 0,
                    'Sort' => 0
                );
            }
            saveToConfig('ProfileExtender.Fields', $NewData);
        }
    }
}

// 2.0 used these config settings; the first 3 were a comma-separated list of field names.
//'Plugins.ProfileExtender.ProfileFields' => array('Control' => 'TextBox', 'Options' => array('MultiLine' => TRUE)),
//'Plugins.ProfileExtender.RegistrationFields' => array('Control' => 'TextBox', 'Options' => array('MultiLine' => TRUE)),
//'Plugins.ProfileExtender.HideFields' => array('Control' => 'TextBox', 'Options' => array('MultiLine' => TRUE)),
//'Plugins.ProfileExtender.TextMaxLength' => array('Control' => 'TextBox'),
