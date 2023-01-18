<?php
/**
 * ProfileExtender Plugin.
 *
 * @author Lincoln Russell <lincoln@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package ProfileExtender
 */

use Garden\Container\Container;
use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Attributes;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\Exception\PermissionException;
use Vanilla\OpenAPIBuilder;
use Vanilla\Utility\StringUtils;

/**
 * Plugin to add additional fields to user profiles.
 *
 * If the field name is an existing column on user table (e.g. Title, Location, Gender)
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
class ProfileExtenderPlugin extends Gdn_Plugin
{
    const EXPORT_PROFILE_CHUNK = 50000;

    /** @var array */
    public $MagicLabels = ["Twitter", "Google", "Facebook", "LinkedIn", "GitHub", "Instagram", "Website", "Real Name"];

    /**
     * Available form field types in format Gdn_Type => DisplayName.
     */
    public $FormTypes = [
        "TextBox" => "TextBox",
        "Dropdown" => "Dropdown",
        "CheckBox" => "Checkbox",
        "DateOfBirth" => "Birthday",
    ];

    /**
     * Whitelist of allowed field properties.
     */
    public $FieldProperties = [
        "Name",
        "Label",
        "FormType",
        "Required",
        "Locked",
        "Options",
        "Length",
        "Sort",
        "OnRegister",
        "OnProfile",
        "OnDiscussion",
        "SalesForceID",
    ];

    /**
     * Blacklist of disallowed field names.
     * Prevents accidental or malicious overwrite of sensitive fields.
     */
    public $ReservedNames = [
        "Name",
        "Email",
        "Password",
        "HashMethod",
        "Admin",
        "Banned",
        "Points",
        "Deleted",
        "Verified",
        "Attributes",
        "Permissions",
        "Preferences",
    ];

    // As those are moved from fields in the `User` table to the `UserMeta` table, they will become irrelevant.
    private const BUILTIN_FIELDS = ["Title", "Location", "Gender"];

    /** @var array */
    public $ProfileFields = [];

    /** @var ProfileFieldModel */
    private $profileFieldModel;

    /**
     * DI.
     *
     * @param \ProfileExtenderPlugin $profileExtenderPlugin
     */
    public function __construct(ProfileFieldModel $profileFieldModel)
    {
        parent::__construct();
        $this->profileFieldModel = $profileFieldModel;
    }

    /**
     * Hook in before content is rendered.
     *
     * @param mixed $sender
     */
    public function base_render_before($sender)
    {
        if ($sender->MasterView == "admin") {
            $sender->addJsFile("profileextender.js", "plugins/ProfileExtender");
        }
    }

    /**
     * Modify container rules.
     *
     * @param Container $dic
     */
    public function container_init(Container $dic): void
    {
        // Add the OpenAPI filter to set the field schema.
        $dic->rule(OpenAPIBuilder::class)->addCall("addFilter", ["filter" => [$this, "filterOpenApi"]]);
    }

    /**
     * Change config settings based on whether Profile Extender fields duplicate built-in fields.
     */
    public function gdn_dispatcher_appStartup_handler()
    {
        // If profile extender fields replace built-in fields, they should be editable.
        $profileFields = $this->getProfileFields();
        $keys = $profileFields;
        array_walk($keys, function (&$item) {
            $item = $item["Name"];
        });
        if (in_array("Title", $keys, true)) {
            Gdn::config()->set("Garden.Profile.Titles", true, true, false);
        }
        if (in_array("Location", $keys, true)) {
            Gdn::config()->set("Garden.Profile.Locations", true, true, false);
        }
    }

    /**
     * Add the Dashboard menu item.
     *
     * @param Object $sender
     */
    public function base_getAppSettingsMenuItems_handler($sender)
    {
        $menu = &$sender->EventArguments["SideMenu"];
        $menu->addLink("Users", t("Profile Fields"), "settings/profileextender", "Garden.Settings.Manage");
    }

    /**
     * Add non-checkbox fields to registration forms.
     *
     * @param EntryController $sender
     */

    public function entryController_registerBeforePassword_handler($sender)
    {
        /* @var $form Gdn_Form */
        $form = $sender->Form;
        $isExistingUser = $form->getFormValue("ConnectingExistingUser", false);
        // Never show the Profile Extender fields when someone is reconnecting.
        if ($isExistingUser) {
            return;
        }
        $ProfileFields = $this->getProfileFields();
        $sender->RegistrationFields = [];
        $isOnConnect = $sender->Request->getPath() === "/entry/connect";
        foreach ($ProfileFields as $Name => $Field) {
            $isCheckBox = $Field["FormType"] === "CheckBox";
            // CheckBox can be displayed through "registerBeforePassword" event only on connect.
            $isCheckBoxOnConnect = $isOnConnect && $isCheckBox;
            if (val("OnRegister", $Field) && (!$isCheckBox || $isCheckBoxOnConnect)) {
                $sender->RegistrationFields[$Name] = $Field;
            }
        }
        include $sender->fetchViewLocation("registrationfields", "", "plugins/ProfileExtender");
    }

    /**
     * Add checkbox fields to registration forms.
     *
     * @param EntryController $sender
     * @throws Exception If there is an error in the form.
     */
    public function entryController_registerFormBeforeTerms_handler($sender)
    {
        /* @var $form Gdn_Form */

        $form = $sender->Form;
        $isExistingUser = $form->getFormValue("ConnectingExistingUser", false);
        // Never show the Profile Extender fields when someone is reconnecting.
        if ($isExistingUser) {
            return;
        }
        $ProfileFields = $this->getProfileFields();
        $sender->RegistrationFields = [];
        foreach ($ProfileFields as $Name => $Field) {
            if (val("OnRegister", $Field) && val("FormType", $Field) == "CheckBox") {
                $sender->RegistrationFields[$Name] = $Field;
            }
        }
        include $sender->fetchViewLocation("registrationfields", "", "plugins/ProfileExtender");
    }

    /**
     * Required fields on registration forms.
     *
     * @param EntryController $sender
     */
    public function entryController_registerValidation_handler($sender)
    {
        /* @var $form Gdn_Form */
        $form = $sender->Form;
        $isExistingUser = $form->getFormValue("ConnectingExistingUser", false);
        // Never show the Profile Extender fields when someone is reconnecting.
        if ($isExistingUser) {
            return;
        }
        // Require new fields
        $profileFields = $this->getProfileFields();
        foreach ($profileFields as $key => $field) {
            // Unicode whitespace check
            if (trim(StringUtils::stripUnicodeWhitespace($sender->Form->getFormValue($field["Name"]))) === "") {
                $sender->Form->setFormValue($field["Name"], "");
            }

            // Check both so you can't break register form by requiring omitted field
            $name = isset($field["Name"]) ? $field["Name"] : (string) $key;
            if (val("Required", $field) && val("OnRegister", $field)) {
                $sender->UserModel->Validation->applyRule($name, "Required", t("%s is required.", $field["Label"]));
            }
        }

        // DateOfBirth zeroes => NULL
        if ("0-00-00" == $sender->Form->getFormValue("DateOfBirth")) {
            $sender->Form->setFormValue("DateOfBirth", null);
        }
    }

    /**
     * Special manipulations.
     */
    public function parseSpecialFields($fields = [])
    {
        if (!is_array($fields)) {
            return $fields;
        }

        foreach ($fields as $label => $value) {
            if ($value == "") {
                continue;
            }

            // Use plaintext for building these
            $value = Gdn_Format::text($value);

            switch ($label) {
                case "Twitter":
                    $fields["Twitter"] = "@" . anchor($value, "http://twitter.com/" . $value);
                    break;
                case "Facebook":
                    $fields["Facebook"] = anchor($value, "http://facebook.com/" . $value);
                    break;
                case "LinkedIn":
                    $fields["LinkedIn"] = anchor($value, "http://www.linkedin.com/in/" . $value);
                    break;
                case "GitHub":
                    $fields["GitHub"] = anchor($value, "https://github.com/" . $value);
                    break;
                case "Google":
                    $fields["Google"] = anchor("Google+", $value, "", ["rel" => "me"]);
                    break;
                case "Instagram":
                    $fields["Instagram"] = "@" . anchor($value, "http://instagram.com/" . $value);
                    break;
                case "Website":
                    $linkValue = isUrl($value) ? $value : "http://" . $value;
                    $fields["Website"] = anchor($value, $linkValue);
                    break;
                case "Real Name":
                    $fields["Real Name"] = wrap(htmlspecialchars($value), "span", ["itemprop" => "name"]);
                    break;
            }
        }

        return $fields;
    }

    /**
     * Add fields to edit profile form.
     *
     * @param ProfileController $sender
     */
    public function profileController_editMyAccountAfter_handler($sender)
    {
        $this->profileFields($sender);
    }

    /**
     * Set the label on the Title and Location fields if there is a ProfileExtender field for them.
     *
     * @param ProfileController $sender
     */
    public function profileController_beforeEdit_handler($sender)
    {
        if (c("ProfileExtender.Fields.Title.Label")) {
            $sender->setData("_TitleLabel", c("ProfileExtender.Fields.Title.Label"));
            // Allow Title field to be a dropdown
            if (c("ProfileExtender.Fields.Title.FormType") === "Dropdown") {
                $sender->setData("_TitleFormType", "Dropdown");
                $titleArray = c("ProfileExtender.Fields.Title.Options");
                $titleArray = array_combine($titleArray, $titleArray);
                $sender->setData("_TitleOptions", $titleArray);
            }
        }
        if (c("ProfileExtender.Fields.Location.Label")) {
            $sender->setData("_LocationLabel", c("ProfileExtender.Fields.Location.Label"));
        }
    }

    /**
     * Get custom profile fields.
     *
     * @param bool $stripBuiltinFields Whether to strip out built-in fields replicated in the profileExtender.
     * @return array
     */
    public function getProfileFields($stripBuiltinFields = false)
    {
        $fields = c("ProfileExtender.Fields", []);
        if (!is_array($fields)) {
            $fields = [];
        }

        // Data checks
        foreach ($fields as $k => $field) {
            $name = isset($field["Name"]) ? $field["Name"] : $k;

            // If the field is one of the `built-in` fields(ie. originally saved within the `User` table) & we want to exclude them.
            if (in_array($name, self::BUILTIN_FIELDS, true) && $stripBuiltinFields) {
                unset($fields[$k]);
            }

            // Require an array for each field
            if (!is_array($field) || strlen($name) < 1) {
                unset($fields[$k]);
            }

            // Verify field form type
            if (!isset($field["FormType"])) {
                $fields[$k]["FormType"] = "TextBox";
            } elseif (!array_key_exists($field["FormType"], $this->FormTypes)) {
                unset($this->ProfileFields[$name]);
            } elseif ($fields[$k]["FormType"] == "DateOfBirth") {
                // Special case for birthday field
                $fields[$k]["FormType"] = "Date";
                $fields[$k]["Label"] = t("Birthday");
            }
        }

        return $fields;
    }

    /**
     * Get data for a single profile field.
     *
     * @param $name
     * @return array|null
     */
    private function getProfileField($name)
    {
        $fields = $this->getProfileFields();
        foreach ($fields as $field) {
            if (isset($field["Name"]) && $name === $field["Name"]) {
                if (!isset($field["FormType"])) {
                    $field["FormType"] = "TextBox";
                }
                return $field;
            }
        }
        return null;
    }

    /**
     * Display custom profile fields on form.
     *
     * @param Object $Sender
     * @param bool $stripBuiltinFields Whether to strip out built-in fields replicated in the profileExtender.
     * @param bool $allFields Whether to get all fields or only fields that have OnProfile=true
     * @access private
     */
    public function profileFields($Sender, bool $stripBuiltinFields = false, bool $allFields = false)
    {
        $userID = $Sender->Form->getValue("UserID");

        /** @var EventManager $eventManager */
        $eventManager = Gdn::getContainer()->get(EventManager::class);
        // Retrieve user's existing profile fields

        $this->ProfileFields = [];
        $profileFields = $this->getProfileFields($stripBuiltinFields);
        if ($allFields) {
            $this->ProfileFields = $profileFields;
        } else {
            foreach ($profileFields as $name => $field) {
                if ($field["OnProfile"] ?? false) {
                    $this->ProfileFields[$name] = $field;
                }
            }
        }

        $this->ProfileFields = $eventManager->fireFilter("modifyProfileFields", $this->ProfileFields);
        // Get user-specific data
        $userProfileValues = $this->getUserProfileValues([$userID]);
        $this->UserFields = $userProfileValues[$userID] ?? [];

        $this->fireEvent("beforeGetProfileFields");
        // Fill in user data on form
        foreach ($this->UserFields as $Field => $Value) {
            $Sender->Form->setValue($Field, $Value);
        }

        include $Sender->fetchViewLocation("profilefields", "", "plugins/ProfileExtender");
    }

    /**
     * Settings page.
     */
    public function settingsController_profileExtender_create($sender)
    {
        $sender->permission("Garden.Settings.Manage");
        // Detect if we need to upgrade settings
        if (!c("ProfileExtender.Fields")) {
            $this->setup();
        }

        // Set data
        $data = $this->getProfileFields();
        $sender->setData("ExtendedFields", $data);

        $sender->setHighlightRoute("settings/profileextender");
        $sender->setData("Title", t("Profile Fields"));
        $sender->render("settings", "", "plugins/ProfileExtender");
    }

    /**
     * Add/edit a field.
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_profileFieldAddEdit_create($sender, $args)
    {
        $sender->permission("Garden.Settings.Manage");
        $sender->setData("Title", t("Add Profile Field"));

        if ($sender->Form->authenticatedPostBack()) {
            // Get whitelisted properties
            $formPostValues = $sender->Form->formValues();
            foreach ($formPostValues as $key => $value) {
                if (!in_array($key, $this->FieldProperties)) {
                    unset($formPostValues[$key]);
                }
            }

            // Make Options an array
            if ($options = val("Options", $formPostValues)) {
                $options = explode("\n", preg_replace("/[^\w\s()-]/u", "", $options));
                if (count($options) < 2) {
                    $sender->Form->addError("Must have at least 2 options.", "Options");
                }
                setValue("Options", $formPostValues, $options);
            }

            // Check label
            if (val("FormType", $formPostValues) == "DateOfBirth") {
                setValue("Label", $formPostValues, "DateOfBirth");
            }
            if (!val("Label", $formPostValues)) {
                $sender->Form->addError("Label is required.", "Label");
            }

            // Check form type
            if (!array_key_exists(val("FormType", $formPostValues), $this->FormTypes)) {
                $sender->Form->addError("Invalid form type.", "FormType");
            }

            // Merge updated data into config
            $fields = $this->getProfileFields();
            if (!($name = val("Name", $formPostValues))) {
                // Make unique name from label for new fields
                if (unicodeRegexSupport()) {
                    $regex = "/[^\pL\pN]/u";
                } else {
                    $regex = "/[^a-z\d]/i";
                }
                // Make unique slug
                $name = $testSlug = substr(preg_replace($regex, "", val("Label", $formPostValues)), 0, 50);
                $i = 1;

                // Fallback in case the name is empty
                if (empty($name)) {
                    $name = $testSlug = md5(val("Label", $formPostValues));
                }
                $keys = $fields;
                array_walk($keys, function (&$item) {
                    $item = $item["Name"];
                });
                while (in_array($name, $keys) || in_array($name, $this->ReservedNames)) {
                    $name = $testSlug . $i++;
                }
            }

            // Save if no errors
            if (!$sender->Form->errorCount()) {
                $data = (array) Gdn::config("ProfileExtender.Fields");
                $formPostValues = (array) $formPostValues;
                $key = null;
                foreach ($data as $k => &$field) {
                    if (isset($field["Name"]) && $name === $field["Name"]) {
                        $formPostValues = array_merge((array) $field, $formPostValues);
                        $key = $k;
                    }
                }

                if (!isset($formPostValues["Name"])) {
                    $formPostValues["Name"] = $name;
                }

                if (is_null($key)) {
                    $data = array_filter($data);
                    $key = count($data);
                }

                Gdn::config()->saveToConfig("ProfileExtender.Fields." . $key, $formPostValues);
                $sender->setRedirectTo("/settings/profileextender");
            }
        } elseif (isset($args[0])) {
            // Editing
            $data = $this->getProfileField($args[0]);
            if (isset($data["Options"]) && is_array($data["Options"])) {
                $data["Options"] = implode("\n", $data["Options"]);
            }
            $sender->Form->setData($data);
            $sender->Form->addHidden("Name", $args[0]);
            $sender->setData("Title", t("Edit Profile Field"));
        }

        $currentFields = $this->getProfileFields();
        $formTypes = $this->FormTypes;

        /**
         * We only allow one DateOfBirth field, since it is a special case.  Remove it as an option if we already
         * have one, unless we're editing the one instance we're allowing.
         */
        if (array_key_exists("DateOfBirth", $currentFields) && $sender->Form->getValue("FormType") != "DateOfBirth") {
            unset($formTypes["DateOfBirth"]);
        }

        $sender->setData("FormTypes", $formTypes);
        $sender->setData("CurrentFields", $currentFields);
        $sender->fireEvent("beforeProfileExtenderAddEditRender");

        $sender->render("addedit", "", "plugins/ProfileExtender");
    }

    /**
     * Delete a field.
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_profileFieldDelete_create($sender, $args)
    {
        $sender->permission("Garden.Settings.Manage");
        $sender->setData("Title", "Delete Field");
        if (isset($args[0])) {
            if ($sender->Form->authenticatedPostBack()) {
                $fields = $this->getProfileFields();
                foreach ($fields as $key => $field) {
                    if (isset($field["Name"]) && $field["Name"] === $args[0]) {
                        unset($fields[$key]);
                    }
                }
                $fields = array_values($fields);
                Gdn::config()->set("ProfileExtender.Fields", $fields);
                $sender->setRedirectTo("/settings/profileextender");
            } else {
                $sender->setData("Field", $this->getProfileField($args[0]));
            }
        }
        $sender->render("delete", "", "plugins/ProfileExtender");
    }

    /**
     * Display custom fields on Edit User form.
     */
    public function userController_afterFormInputs_handler($sender)
    {
        echo "<ul>";
        $this->profileFields($sender, false, true);
        echo "</ul>";
    }

    /**
     * Reorder ProfileFields according to the sequence in config.
     *
     * @param array $profileFields
     * @return array
     */
    public function reorderProfileFields(array $profileFields): array
    {
        $orderedFields = $this->getProfileFields();
        $orderedFieldsNamesAsKey = array_column($orderedFields, "Name");

        //the new array will have the right order
        $reordered = [];
        foreach ($orderedFieldsNamesAsKey as $name) {
            if (array_key_exists($name, $profileFields)) {
                $reordered[$name] = $profileFields[$name];
            }
        }

        //if the user has fields and they are not in config, we still need to include them
        $leftovers = array_diff_key($profileFields, $reordered);

        return array_merge($reordered, $leftovers);
    }

    /**
     * Display custom fields on Profile.
     *
     * @param UserInfoModule $Sender
     */
    public function userInfoModule_onBasicInfo_handler($Sender)
    {
        if ($Sender->User->Banned) {
            return;
        }

        try {
            // Get the custom fields
            $ProfileFields = Gdn::userModel()->getMeta($Sender->User->UserID, "Profile.%", "Profile.");

            $ProfileFields = $this->reorderProfileFields($ProfileFields);

            Gdn::controller()->setData("ExtendedFields", $ProfileFields);

            // Get allowed GDN_User fields.
            $Blacklist = array_combine($this->ReservedNames, $this->ReservedNames);
            $NativeFields = array_diff_key((array) $Sender->User, $Blacklist);

            // Combine custom fields (GDN_UserMeta) with GDN_User fields.
            // This is OK because we're blacklisting our $ReservedNames AND whitelisting $AllFields below.
            $ProfileFields = array_merge($ProfileFields, $NativeFields);

            // Import from CustomProfileFields if available
            if (
                !count($ProfileFields) &&
                is_object($Sender->User) &&
                c("Plugins.CustomProfileFields.SuggestedFields", false)
            ) {
                $ProfileFields = Gdn::userModel()->getAttribute($Sender->User->UserID, "CustomProfileFields", false);
                if ($ProfileFields) {
                    // Migrate to UserMeta & delete original
                    Gdn::userModel()->setMeta($Sender->User->UserID, $ProfileFields, "Profile.");
                    Gdn::userModel()->saveAttribute($Sender->User->UserID, "CustomProfileFields", false);
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
            if (c("ProfileExtender.Fields.DateOfBirth.OnProfile")) {
                // Do not use Gdn_Format::Date because it shifts to local timezone
                $BirthdayStamp = Gdn_Format::toTimestamp($Sender->User->DateOfBirth);
                if ($BirthdayStamp) {
                    $ProfileFields["DateOfBirth"] = date(t("Birthday Format", "F j, Y"), $BirthdayStamp);
                    $AllFields["DateOfBirth"] = ["Label" => t("Birthday"), "OnProfile" => true];
                }
            }

            // CheckBox fields should display as "Yes" or "No"
            foreach ($AllFields as $name => $data) {
                if ($data["FormType"] === "CheckBox") {
                    $ProfileFields[$name] =
                        $ProfileFields[$name] == "1" ? t("Profile.Yes", "Yes") : t("Profile.No", "No");
                }
            }

            // Display all non-hidden fields
            require_once Gdn::controller()->fetchViewLocation(
                "helper_functions",
                "",
                "plugins/ProfileExtender",
                true,
                false
            );
            extendedProfileFields($ProfileFields, $AllFields, $this->MagicLabels);
        } catch (Exception $ex) {
            // No errors
        }
    }

    /**
     * Validate profile extender fields before saving the user.
     *
     * @param \UserModel $sender
     * @param array $args
     */
    public function userModel_beforeSaveValidation_handler(\UserModel $sender, array $args)
    {
        $allowedFields = $this->getProfileFields();
        foreach ($allowedFields as $key => $value) {
            $checkField = array_key_exists($value["Name"], $args["FormPostValues"]) && isset($value["Required"]);
            $invalid = $checkField && $value["Required"] == 1 && trim($args["FormPostValues"][$value["Name"]]) === "";
            if ($invalid) {
                $sender->Validation->addValidationResult($key, sprintf(t("%s is required."), $value["Label"]));
            }
        }
    }

    /**
     * Save custom profile fields when saving the user.
     *
     * @param \UserModel $sender
     * @param array $args
     */
    public function userModel_afterSave_handler(\UserModel $sender, array $args)
    {
        $this->updateUserFields($args["UserID"], $args["FormPostValues"]);
    }

    /**
     * Save custom profile fields on registration.
     *
     * @param \UserModel $sender
     * @param array $args
     */
    public function userModel_afterInsertUser_handler(\UserModel $sender, array $args)
    {
        if (!empty($args["RegisteringUser"])) {
            $this->updateUserFields($args["InsertUserID"], $args["RegisteringUser"]);
        }
    }

    /**
     * Update user with new profile fields.
     *
     * @param int $userID The user ID to update.
     * @param array $fields Key/value pairs of fields to update.
     */
    public function updateUserFields($userID, $fields)
    {
        // Confirm we have submitted form values
        if (is_array($fields)) {
            // Retrieve whitelist & user column list
            $allowedFields = array_column($this->getProfileFields(), null, "Name");
            $columns = Gdn::sql()->fetchColumns("User");

            foreach ($fields as $name => $field) {
                // Whitelist.
                if (!isset($allowedFields[$name])) {
                    unset($fields[$name]);
                    continue;
                }

                // Allowed checkboxes should be 1 or 0.
                if ($allowedFields[$name]["FormType"] === "CheckBox") {
                    $fields[$name] = $field == true ? 1 : 0;
                }
            }

            // Update UserMeta if any made it through, including user column fields which are currently migrated over to UserMeta.
            if (count($fields)) {
                Gdn::userModel()->setMeta($userID, $fields, "Profile.");
            }
        }
    }

    /**
     * Get the profile extender fields for a single user.
     *
     * @param int $userID
     * @return array
     */
    public function getUserFields(int $userID): array
    {
        $values = $this->getUserProfileValues([$userID]);
        return $values[$userID] ?? [];
    }

    /**
     * Create the query to export profiles.
     *
     * @param array $columnNames
     * @param array $fields
     * @return Gdn_SQLDriver
     */
    private function exportProfilesQuery(array $columnNames, array $fields)
    {
        // Set up our basic query.
        $exportProfilesSQL = Gdn::sql()
            ->select([
                "u.Name",
                "u.Email",
                "u.DateInserted",
                "u.DateLastActive",
                "inet6_ntoa(u.LastIPAddress)",
                "u.CountDiscussions",
                "u.CountComments",
                "u.CountVisits",
                "u.Points",
                "u.InviteUserID",
                "u2.Name as InvitedByName",
                "u.Location",
                "group_concat(r.Name) as Roles",
            ])
            ->from("User u")
            ->leftJoin("User u2", "u.InviteUserID = u2.UserID and u.InviteUserID is not null")
            ->join("UserRole ur", "u.UserID = ur.UserID")
            ->join("Role r", "r.RoleID = ur.RoleID")
            ->where("u.Deleted", 0)
            ->where("u.Admin <", 2)
            ->groupBy("u.UserID");

        if (val("DateOfBirth", $fields)) {
            $columnNames[] = "Birthday";
            $exportProfilesSQL->select("u.DateOfBirth");
            unset($fields["DateOfBirth"]);
        }

        if (Gdn::addonManager()->isEnabled("Ranks", \Vanilla\Addon::TYPE_ADDON)) {
            $columnNames[] = "Rank";
            $exportProfilesSQL->select("ra.Name as Rank")->leftJoin("Rank ra", "ra.RankID = u.RankID");
        }

        $lowerCaseColumnNames = array_map("strtolower", $columnNames);
        $i = 0;
        foreach ($fields as $fieldData) {
            $slugName = $fieldData["Name"];
            // Don't overwrite data if there's already a column with the same name.
            if (in_array(strtolower($slugName), $lowerCaseColumnNames)) {
                continue;
            }
            // Add this field to the output
            $columnNames[] = val("Label", $fieldData, $slugName);

            // Subquery for left join to get minimum UserMetaID
            $subquery1 = Gdn::database()->createSql();
            $subquery1
                ->select("m.UserMetaID", "MIN")
                ->select("m.UserID")
                ->from("UserMeta m")
                ->where("m.Name", "Profile.$slugName")
                ->groupBy("m.UserID");
            $exportProfilesSQL->leftJoin("(" . $subquery1->getSelect(true) . ") s1_$i", "s1_$i.UserID = u.UserID");

            // Subquery for left join to get corresponding UserMeta value
            $subquery2 = Gdn::database()->createSql();
            $subquery2->select("m.UserMetaID, m.Value")->from("UserMeta m");
            $exportProfilesSQL->leftJoin(
                "(" . $subquery2->getSelect(true) . ") s2_$i",
                "s2_$i.UserMetaID = s1_$i.UserMetaID"
            );

            // Add field value to the query
            $exportProfilesSQL->select("s2_$i.Value", "", $slugName);
            $i++;
        }

        return $exportProfilesSQL;
    }

    /**
     * Endpoint to export basic user data along with all custom fields into CSV.
     *
     * @param UtilityController $sender
     */
    public function utilityController_exportProfiles_create($sender)
    {
        // Clear our ability to do this.
        $sender->permission("Garden.Settings.Manage");
        if (Gdn::userModel()->pastUserMegaThreshold()) {
            throw new Gdn_UserException("You have too many users to export automatically.");
        }

        // Determine profile fields we need to add.
        $fields = $this->getProfileFields();
        $columnNames = [
            "Name",
            "Email",
            "Joined",
            "Last Seen",
            "LastIPAddress",
            "Discussions",
            "Comments",
            "Visits",
            "Points",
            "InviteUserID",
            "InvitedByName",
            "Location",
            "Roles",
        ];
        $output = fopen("php://output", "w");
        safeHeader("Content-Type:application/csv");
        safeHeader("Content-Disposition:attachment;filename=profiles_export.csv");
        fputcsv($output, $columnNames);

        // Get our user data.
        $offset = 0;
        do {
            $exportProfilesQuery = $this->exportProfilesQuery($columnNames, $fields);
            $userDataset = $exportProfilesQuery->limit(self::EXPORT_PROFILE_CHUNK, $offset)->get();
            while ($user = $userDataset->nextRow(DATASET_TYPE_ARRAY)) {
                if ($user) {
                    fputcsv($output, $user);
                }
            }
            // Go to the next chunk.
            $offset += self::EXPORT_PROFILE_CHUNK;
        } while ($userDataset->count());
        fclose($output);

        if (!\Vanilla\Utility\DebugUtils::isTestMode()) {
            die();
        }
    }

    /**
     * Import from CustomProfileFields or upgrade from ProfileExtender 2.0.
     */
    public function setup()
    {
        if ($fields = c("Plugins.ProfileExtender.ProfileFields", c("Plugins.CustomProfileFields.SuggestedFields"))) {
            // Get defaults
            $hidden = c("Plugins.ProfileExtender.HideFields", c("Plugins.CustomProfileFields.HideFields"));
            $onRegister = c("Plugins.ProfileExtender.RegistrationFields");
            $length = c("Plugins.ProfileExtender.TextMaxLength", c("Plugins.CustomProfileFields.ValueLength"));

            // Convert to arrays
            $fields = array_filter((array) explode(",", $fields));
            $hidden = array_filter((array) explode(",", $hidden));
            $onRegister = array_filter((array) explode(",", $onRegister));

            // Assign new data structure
            $newData = [];
            foreach ($fields as $field) {
                if (unicodeRegexSupport()) {
                    $regex = "/[^\pL\pN]/u";
                } else {
                    $regex = "/[^a-z\d]/i";
                }
                // Make unique slug
                $name = $testSlug = preg_replace($regex, "", $field);
                $i = 1;

                // Fallback in case the name is empty
                if (empty($name)) {
                    $name = $testSlug = md5($field);
                }

                while (array_key_exists($name, $newData) || in_array($name, $this->ReservedNames)) {
                    $name = $testSlug . $i++;
                }

                // Convert
                $newData[] = [
                    "Label" => $field,
                    "Name" => $name,
                    "Length" => $length,
                    "FormType" => "TextBox",
                    "OnProfile" => in_array($field, $hidden) ? 0 : 1,
                    "OnRegister" => in_array($field, $onRegister) ? 1 : 0,
                    "OnDiscussion" => 0,
                    "Required" => 0,
                    "Locked" => 0,
                    "Sort" => 0,
                ];
            }
            Gdn::config()->saveToConfig("ProfileExtender.Fields", $newData);
        }
    }

    /**
     * Setup structure on update
     */
    public function structure()
    {
        $profileFields = $this->getProfileFields();
        $updateRequired = false;
        foreach ($profileFields as $k => &$field) {
            if (is_string($k)) {
                $field["Name"] = $k;
                $updateRequired = true;
            }
        }
        if ($updateRequired) {
            Gdn::config()->saveToConfig("ProfileExtender.Fields", array_values($profileFields));
        }
    }

    /**
     * Get the extended values associated with a user.
     *
     * @param int[] $userIDs
     */
    public function getUserProfileValues(array $userIDs): array
    {
        $result = Gdn::userModel()->getMeta($userIDs, "Profile.%", "Profile.");
        $eventManager = Gdn::getContainer()->get(EventManager::class);
        $result = $eventManager->fireFilter("modifyUserFields", $result);
        return $result;
    }

    /**
     * Get the extended values, but make sure they are defined and cast them to their correct types.
     *
     * @param array $userIDs
     * @return array
     */
    public function getUserProfileValuesChecked(array $userIDs): array
    {
        $values = $this->getUserProfileValues($userIDs);
        $fields = array_column($this->getProfileFields(), null, "Name");
        $utc = new DateTimeZone("UTC");
        foreach ($values as $id => &$row) {
            $row = new Attributes(array_intersect_key($row, $fields));
            foreach ($row as $key => &$value) {
                switch ($fields[$key]["FormType"] ?? "TextBox") {
                    case "CheckBox":
                        $value = (bool) $value;
                        break;
                    case "DateOfBirth":
                        try {
                            $value = new DateTimeImmutable($value, $utc);
                        } catch (\Exception $ex) {
                            $value = null;
                        }
                }
            }
        }
        return $values;
    }

    /**
     * Return the schema object that represents the API fields.
     *
     * This schema is used as the input schema for the `PATCH /users/:id/extended` endpoint and for field expansion schema.
     *
     * @param string|null $schemaType
     * @returns Schema
     */
    private function getProfileSchema(?string $schemaType = ""): Schema
    {
        $fields = array_column($this->getProfileFields(true), null, "Name");

        // Dynamically build the schema based on the fields and data types.
        $schemaArray = [];

        foreach ($fields as $field) {
            $name = $field["Name"];
            $types = ["CheckBox" => "b", "Date" => "dt"];
            $dataType = $types[$field["FormType"]] ?? "s";

            if ($field["FormType"] === "Dropdown") {
                $schemaArray["{$name}:{$dataType}?"] = ["enum" => $field["Options"]];
            } else {
                $schemaArray[] = "{$name}:{$dataType}?";
            }
        }

        $schema = Schema::parse($schemaArray);

        return $schema;
    }

    /**
     * The `PATCH /users/:id/extended` endpoint.
     *
     * @param UsersApiController $usersApi
     * @param int $id
     * @param array $body
     * @return \Garden\Web\Data
     * @throws ValidationException|\Garden\Web\Exception\HttpException|NotFoundException|PermissionException
     */
    public function usersApiController_patch_extended(
        UsersApiController $usersApi,
        int $id,
        array $body
    ): \Garden\Web\Data {
        if (\Vanilla\FeatureFlagHelper::featureEnabled(ProfileFieldModel::FEATURE_FLAG)) {
            return $usersApi->patch_profileFields($id, $body);
        }
        $userID = $usersApi->getSession()->UserID;
        $userModel = new UserModel();
        if ($id !== $userID) {
            $usersApi->permission("Garden.Users.Edit");
        }
        $in = $this->getProfileSchema("in");
        $out = $this->getProfileSchema("out");
        $body = $in->validate($body, true);
        $schemaArray = $in->getSchemaArray();
        // Special handling of the DateOfBirth field, which lives in the User table.
        $dateOfBirth = isset($schemaArray["properties"]["DateOfBirth"]);
        if ($dateOfBirth && isset($body["DateOfBirth"])) {
            $dob = $body["DateOfBirth"]->format("Y-m-d");
            $userModel->save(["UserID" => $id, "DateOfBirth" => $dob]);
        }
        $this->updateUserFields($id, $body);
        $row = $this->getUserProfileValuesChecked([$id]);
        // More special handling of DateOfBirth.
        if ($dateOfBirth) {
            $user = $userModel->getId($id);
            $row[$id]->DateOfBirth = $user->DateOfBirth;
        }
        $result = $out->validate($row[$id]);
        $result = new Data($result);
        return $result;
    }

    /**
     * Augment the generated OpenAPI schema with the profile extender fields.
     *
     * Since the profile extender fields are defined at runtime we have to add them to the OpenAPI schema dynamically.
     *
     * @param array $openApi
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function filterOpenApi(array &$openApi): void
    {
        if (\Vanilla\FeatureFlagHelper::featureEnabled(ProfileFieldModel::FEATURE_FLAG)) {
            /** @var ProfileFieldModel $profileFieldModel */
            $profileFieldModel = Gdn::getContainer()->get(ProfileFieldModel::class);
            $schema = $profileFieldModel->getUserProfileFieldSchema();
        } else {
            $schema = $this->getProfileSchema("out");
        }

        // Add the extended fields schema to openapi.
        \Vanilla\Utility\ArrayUtils::setByPath(
            "components.schemas.ExtendedUserFields.properties",
            $openApi,
            $schema->jsonSerialize()["properties"] ?? []
        );

        // Add the "extended' option to the user expand options enum.
        $userExpandEnum = \Vanilla\Utility\ArrayUtils::getByPath(
            "components.parameters.UserExpand.schema.items.enum",
            $openApi,
            []
        );
        $userExpandEnum[] = "extended";
        \Vanilla\Utility\ArrayUtils::setByPath(
            "components.parameters.UserExpand.schema.items.enum",
            $openApi,
            $userExpandEnum
        );
    }
}

// 2.0 used these config settings; the first 3 were a comma-separated list of field names.
//'Plugins.ProfileExtender.ProfileFields' => array('Control' => 'TextBox', 'Options' => array('MultiLine' => TRUE)),
//'Plugins.ProfileExtender.RegistrationFields' => array('Control' => 'TextBox', 'Options' => array('MultiLine' => TRUE)),
//'Plugins.ProfileExtender.HideFields' => array('Control' => 'TextBox', 'Options' => array('MultiLine' => TRUE)),
//'Plugins.ProfileExtender.TextMaxLength' => array('Control' => 'TextBox'),
