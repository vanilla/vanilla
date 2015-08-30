<?php
/**
 * Data validation.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Manages data integrity validation rules. Can automatically define a set of
 * validation rules based on a @@Schema with $this->GenerateBySchema($Schema);
 */
class Gdn_Validation {

    /**
     * @var array The collection of validation rules in the format of $RuleName => $Rule.
     * This list can be added to with $this->AddRule($RuleName, $Rule).
     */
    protected $_Rules;

    /**
     * @var array An associative array of fieldname => value pairs that are being validated.
     * In order for a field to become a part of this collection, it must either be present in the defined schema,
     * or have a rule defined in $this->_FieldRules.
     */
    protected $_ValidationFields;

    /**
     * @var array An array of FieldName => Reason arrays that describe which fields failed
     * validation and which functions/regex caused them to fail.
     */
    protected $_ValidationResults;

    /**
     * @var array An associative array of $FieldName => array($RuleName1, $RuleNameN) rules to be applied to fields.
     * These are rules that have been explicitly called with {@link Gdn_Validation::ApplyRule()}.
     */
    protected $_FieldRules = array();

    /**
     * @var array An associative array of $FieldName => array($RuleName1, $RuleNameN) rules to be applied to fields.
     * These are rules that come from the current schema that have been applied by {@link Gdn_Validation::ApplyRulesBySchema()}.
     */
    protected $_SchemaRules = array();

    /** @var array The schema being used to generate validation rules. */
    protected $_Schema = array();

    /** @var bool Whether or not to reset the validation results on validate. */
    protected $_ResetOnValidate = false;

    /** @var array An array of FieldName.RuleName => "Custom Error Message"s. See $this->ApplyRule. */
    private $_CustomErrors = array();

    /**
     * Class constructor. Optionally takes a schema definition to generate validation rules for.
     *
     * @param Gdn_Schema|array $Schema A schema object to generate validation rules for.
     * @param bool Whether or not to reset the validation results on {@link Validate()}.
     */
    public function __construct($Schema = false, $ResetOnValidate = false) {
        if (is_object($Schema) || is_array($Schema)) {
            $this->setSchema($Schema);
        }
        $this->setResetOnValidate($ResetOnValidate);

        // Define the default validation functions
        $this->_Rules = array();
        $this->addRule('Required', 'function:ValidateRequired');
        $this->addRule('RequiredArray', 'function:ValidateRequiredArray');
        $this->addRule('Email', 'function:ValidateEmail');
        $this->addRule('WebAddress', 'function:ValidateWebAddress');
        $this->addRule('Username', 'function:ValidateUsername');
        $this->addRule('UrlString', 'function:ValidateUrlString');
        $this->addRule('UrlStringRelaxed', 'function:ValidateUrlStringRelaxed');
        $this->addRule('Date', 'function:ValidateDate');
        $this->addRule('Integer', 'function:ValidateInteger');
        $this->addRule('Boolean', 'function:ValidateBoolean');
        $this->addRule('Decimal', 'function:ValidateDecimal');
        $this->addRule('String', 'function:ValidateString');
        $this->addRule('Time', 'function:ValidateTime');
        $this->addRule('Timestamp', 'function:ValidateTimestamp');
        $this->addRule('Length', 'function:ValidateLength');
        $this->addRule('Enum', 'function:ValidateEnum');
        $this->addRule('MinimumAge', 'function:ValidateMinimumAge');
        $this->addRule('Captcha', 'function:ValidateCaptcha');
        $this->addRule('Match', 'function:ValidateMatch');
        $this->addRule('Strength', 'function:ValidateStrength');
        $this->addRule('OldPassword', 'function:ValidateOldPassword');
        $this->addRule('Version', 'function:ValidateVersion');
        $this->addRule('PhoneNA', 'function:ValidatePhoneNA');
        $this->addRule('PhoneInt', 'function:ValidatePhoneInt');
        $this->addRule('ZipCode', 'function:ValidateZipCode');
        $this->addRule('Format', 'function:ValidateFormat');
        $this->addRule('Url', 'function:ValidateUrl');
    }

    /**
     * Examines the current schema and fills {@link Gdn_Validation::$_SchemaRules} with rules based
     * on the properties of each field in the table schema.
     */
    protected function applyRulesBySchema() {
        $this->_SchemaRules = array();

        foreach ($this->_Schema as $Field => $Properties) {
            if (is_scalar($Properties)) {
                // Some code passes a record as a schema so account for that here.
                $Properties = array(
                    'AutoIncrement' => false,
                    'AllowNull' => true,
                    'Type' => 'text',
                    'Length' => ''
                );
                $Properties = (object)$Properties;
            }

            // Create an array to hold rules for this field
            $RuleNames = array();

            // Force non-null fields without defaults to be required.
            if ($Properties->AllowNull === false && $Properties->Default == '') {
                $RuleNames[] = 'Required';
            }

            // Force other constraints based on field type.
            switch ($Properties->Type) {
                case 'bit':
                case 'bool':
                case 'boolean':
                    $RuleNames[] = 'Boolean';
                    break;

                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                case 'int':
                case 'integer':
                case 'bigint':
                    $RuleNames[] = 'Integer';
                    break;

                case 'double':
                case 'float':
                case 'real':
                case 'decimal':
                case 'dec':
                case 'numeric':
                case 'fixed':
                    $RuleNames[] = 'Decimal';
                    break;

                case 'date':
                case 'datetime':
                    $RuleNames[] = 'Date';
                    break;
                case 'time':
                    $RuleNames[] = 'Time';
                    break;
                case 'year':
                    $RuleNames[] = 'Year';
                    break;
                case 'timestamp':
                    $RuleNames[] = 'Timestamp';
                    break;

                case 'char':
                case 'varchar':
                case 'tinyblob':
                case 'blob':
                case 'mediumblob':
                case 'longblob':
                case 'tinytext':
                case 'mediumtext':
                case 'text':
                case 'longtext':
                case 'binary':
                case 'varbinary':
                    if (!in_array($Field, array('Attributes', 'Data', 'Preferences', 'Permissions'))) {
                        $RuleNames[] = 'String';
                    }
                    if ($Properties->Length != '') {
                        $RuleNames[] = 'Length';
                    }
                    break;

                case 'enum':
                case 'set':
                    $RuleNames[] = 'Enum';
                    break;
            }

            if ($Field == 'Format') {
                $RuleNames[] = 'Format';
            }

            // Assign the rules to the field.
            // echo '<div>Field: '.$Field.'</div>';
            // print_r($RuleNames);
            $this->applyRuleTo($this->_SchemaRules, $Field, $RuleNames);
        }
    }

    /**
     * Applies a $RuleName to a $FieldName. You can apply as many rules to a field as you like.
     *
     * @param string $FieldName The name of the field to apply rules to.
     * @param mixed $RuleName The rule name (or array of rule names) to apply to the field.
     * @param mixed $CustomError A custom error message you might want to apply to a field
     *  if the rule causes an error to be caught.
     */
    public function applyRule($FieldName, $RuleName, $CustomError = '') {
        // Make sure that $FieldName is in the validation fields collection
        $this->validationFields();

        if (!array_key_exists($FieldName, $this->_ValidationFields)) { //  && $RuleName == 'Required'
            $this->_ValidationFields[$FieldName] = '';
        }

        $this->applyRuleTo($this->_FieldRules, $FieldName, $RuleName, $CustomError);
    }

    /**
     * Apply a rule to the given rules array.
     *
     * @param array $Array The rules array to apply the rule to.
     * This should be either `$this->_FieldRules` or `$this->_SchemaRules`.
     * @param string $FieldName The name of the field that the rule applies to.
     * @param string $RuleName The name of the rule.
     * @param string $CustomError A custom error string when the rule is broken.
     */
    protected function applyRuleTo(&$Array, $FieldName, $RuleName, $CustomError = '') {
        $Array = (array)$Array;

        if (!is_array($RuleName)) {
            if ($CustomError != '') {
                $this->_CustomErrors[$FieldName.'.'.$RuleName] = $CustomError;
            }

            $RuleName = array($RuleName);
        }

        $ExistingRules = val($FieldName, $Array, array());

        // Merge the new rules with the existing ones (array_merge) and make
        // sure there is only one of each rule applied (array_unique).
        $Array[$FieldName] = array_unique(array_merge($ExistingRules, $RuleName));
    }

    /**
     * Allows the explicit definition of a schema to use.
     *
     * @param array $Schema
     * @deprecated This method has been deprecated in favor of {@link Gdn_Validation::SetSchema()}.
     */
    public function applySchema($Schema) {
        deprecated('ApplySchema', 'SetSchema');
        $this->setSchema($Schema);
    }

    /**
     * Fills $this->_ValidationFields with field names that exist in the $PostedFields collection.
     *
     * @param array $PostedFields The associative array collection of field names to add.
     * @param boolean $Insert A boolean value indicating if the posted fields are to be inserted or
     * updated. If being inserted, the schema's required field rules will be enforced.
     * @return array Returns the subset of {@link $PostedFields} that will be validated.
     */
    protected function defineValidationFields($PostedFields, $Insert = false) {
        $Result = array();

        // Start with the fields that have been explicitly defined by `ApplyRule`.
        foreach ($this->_FieldRules as $Field => $Rules) {
            $Result[$Field] = val($Field, $PostedFields, null);
        }

        // Add all of the fields from the schema.
        foreach ($this->getSchemaRules() as $Field => $Rules) {
            $FieldInfo = $this->_Schema[$Field];

            if (!array_key_exists($Field, $PostedFields)) {
                $Required = in_array('Required', $Rules);

                // Don't enforce fields that aren't required or required fields during a sparse update.
                if (!$Required || !$Insert) {
                    continue;
                }
                // Fields with a non-null default can be left out.
                if (val('Default', $FieldInfo, null) !== null || val('AutoIncrement', $FieldInfo)) {
                    continue;
                }
            }
            $Result[$Field] = val($Field, $PostedFields, null);
        }

        return $Result;
    }

    /**
     * Get all of the validation rules that apply to a given set of data.
     *
     * @param array $PostedFields The data that will be validated.
     * @param bool $Insert Whether or not this is an insert.
     * @return array Returns an array of `[$Field => [$Rules, ...]`.
     */
    protected function defineValidationRules($PostedFields, $Insert = false) {
        $Result = (array)$this->_FieldRules;

        // Add all of the fields from the schema.
        foreach ($this->getSchemaRules() as $Field => $Rules) {
            $FieldInfo = $this->_Schema[$Field];

            if (!array_key_exists($Field, $PostedFields)) {
                $Required = in_array('Required', $Rules);

                // Don't enforce fields that aren't required or required fields during a sparse update.
                if (!$Required || !$Insert) {
                    continue;
                }
                // Fields with a non-null default can be left out.
                if (val('Default', $FieldInfo, null) !== null || val('AutoIncrement', $FieldInfo)) {
                    continue;
                }
            }
            if (isset($Result[$Field])) {
                $Result[$Field] = array_unique(array_merge($Result[$Field], $Rules));
            } else {
                $Result[$Field] = $Rules;
            }
        }

        return $Result;
    }

    /**
     * Set the schema for this validation.
     *
     * @param Gdn_Schema|array $Schema The new schema to set.
     * @return Gdn_Validation Returns `$this` for fluent calls.
     * @throws \Exception Throws an exception when {@link $Schema} isn't an array or {@link Gdn_Schema} object.
     */
    public function setSchema($Schema) {
        if ($Schema instanceof Gdn_Schema) {
            $this->_Schema = $Schema->fields();
        } elseif (is_array($Schema)) {
            $this->_Schema = $Schema;
        } else {
            throw new \Exception('Invalid schema of type '.gettype($Schema).'.', 500);
        }
        $this->_SchemaRules = null;

        return $this;
    }

    /**
     * Get all of the rules as defined by the schema.
     *
     * @return array Returns an array in the form `[$FieldName => [$Rules, ...]`.
     */
    public function getSchemaRules() {
        if (!$this->_SchemaRules) {
            $this->applyRulesBySchema($this->_Schema);
        }
        return $this->_SchemaRules;
    }

    /**
     * Returns the an array of fieldnames that are being validated.
     *
     * @return array
     */
    public function validationFields() {
        if (!is_array($this->_ValidationFields)) {
            $this->_ValidationFields = array();
        }

        return $this->_ValidationFields;
    }

    /**
     * Adds to the rules collection ($this->_Rules).
     *
     * If $RuleName already
     * exists, this method will overwrite the existing rule. There are some
     * special cases:
     *  1. If the $Rule begins with "function:", when the rule is evaluated
     * on a field, it will strip the "function:" from the $Rule and execute
     * the remaining string name as a function with the field value passed as
     * the first parameter and the related field properties as the second
     * parameter. ie. "function:MySpecialValidation" will evaluate as
     * MySpecialValidation($FieldValue, $FieldProperties). Any function defined
     * in this way is expected to return boolean TRUE or FALSE.
     *  2. If $Rule begins with "regex:", when the rule is evaluated on a
     * field, it will strip the "regex:" from $Rule and use the remaining
     * string as a regular expression rule. If a match between the regex rule
     * and the field value is made, it will validate as TRUE.
     *  3. Predefined $RuleNames are:
     *  RuleName   Rule
     *  ========================================================================
     *  Required   Will not accept a null or empty value.
     *  Email      Will validate against an email regex.
     *  Date       Will only accept valid date values in a variety of formats.
     *  Integer    Will only accept an integer.
     *  Boolean    Will only accept 1 or 0.
     *  Decimal    Will only accept a decimal.
     *  Time       Will only accept a time in HH:MM:SS or HH:MM format.
     *  Timestamp  Will only accept a valid timestamp.
     *  Length     Will not accept a value longer than $Schema[$Field]->Length.
     *  Enum       Will only accept one of the values in the $Schema[$Field]->Enum array.
     *
     * @param string $RuleName The name of the rule to be added.
     * @param string $Rule The rule to be added. These are in the format of "function:FunctionName"
     * or "regex:/regex/". Any function defined here must be included before
     * the rule is enforced or the application will cause a fatal error.
     */
    public function addRule($RuleName, $Rule) {
        $this->_Rules[$RuleName] = $Rule;
    }

    /**
     * Whether or not the validation results etc should reset whenever {@link Validate()} is called.
     *
     * @return boolean Returns true if we reset or false otherwise.
     */
    public function resetOnValidate() {
        return $this->_ResetOnValidate;
    }

    /**
     * Set whether or not the validation results etc should reset whenever {@link Validate()} is called.
     *
     * @param boolean $ResetOnValidate True to reset or false otherwise.
     * @return Gdn_Validation Returns `$this` for fluent calls.
     */
    public function setResetOnValidate($ResetOnValidate) {
        $this->_ResetOnValidate = $ResetOnValidate;
        return $this;
    }

    /**
     * Adds a fieldname to the $this->_ValidationFields collection.
     *
     * @param string $FieldName The name of the field to add to the $this->_ValidationFields collection.
     * @param array $PostedFields The associative array collection of field names to examine for the value
     *  of $FieldName.
     */
    protected function addValidationField($FieldName, $PostedFields) {
        if (!is_array($this->_ValidationFields)) {
            $this->_ValidationFields = array();
        }

        $Value = arrayValue($FieldName, $PostedFields, null);
        $this->_ValidationFields[$FieldName] = $Value;
    }

    /**
     * Returns an array of field names that are in both $this->_ValidationFields AND $this->_Schema.
     *
     * @return array Returns an array of fields and values that were validated and in the schema.
     */
    public function schemaValidationFields() {
        $Result = array_intersect_key($this->_ValidationFields, $this->_Schema);
        return $Result;
    }

    /**
     * Allows you to explicitly set a field property on $this->_Schema. Can be
     * useful when adding rules to fields (ie. a maxlength property on a db's
     * text field).
     *
     * @param string $FieldName The name of the field that we are setting a property for.
     * @param string $PropertyName The name of the property being set.
     * @param mixed $Value The value of the property to set.
     */
    public function setSchemaProperty($FieldName, $PropertyName, $Value) {
        if (is_array($this->_Schema) && array_key_exists($FieldName, $this->_Schema)) {
            $Field = $this->_Schema[$FieldName];
            if (is_object($Field)) {
                $Field->$PropertyName = $Value;
                $this->_Schema[$FieldName] = $Field;
            }
        }
    }

    /**
     * Execute a single validation rule and return its result.
     *
     * @param mixed $Value The value to validate.
     * @param string $FieldName The name of the field to put into the error result.
     * @param string|array $Rule The rule to validate which can be one of the following.
     *  - string: The name of a function used to validate the value.
     *  - 'regex:<regex>': The regular expression used to validate the value.
     *  - array: An array with the following keys:
     *    - Name: The name of the function used to validate.
     *    - Args: An argument to pass to the function after the value.
     * @param string $CustomError A custom error message.
     * @return bool|string One of the following
     *  - TRUE: The value passed validation.
     *  - string: The error message associated with the error.
     */
    public static function validateRule($Value, $FieldName, $Rule, $CustomError = false) {
        // Figure out the type of rule.
        if (is_string($Rule)) {
            if (stringBeginsWith($Rule, 'regex:', true)) {
                $RuleName = 'validateregex';
                $Args = substr($Rule, 6);
            } elseif (stringBeginsWith($Rule, 'function:', true)) {
                $RuleName = substr($Rule, 9);
            } else {
                $RuleName = $Rule;
            }
        } elseif (is_array($Rule)) {
            $RuleName = val('Name', $Rule);
            $Args = val('Args', $Rule);
        }

        if (!isset($Args)) {
            $Args = null;
        }

        if (function_exists($RuleName)) {
            $Result = $RuleName($Value, $Args);
            if ($Result === true) {
                return true;
            } elseif ($CustomError)
                return $CustomError;
            elseif (is_string($Result))
                return $Result;
            else {
                return sprintf(T($RuleName), T($FieldName));
            }
        } else {
            return sprintf('Validation does not exist: %s.', $RuleName);
        }
    }

    /**
     * Remove a validation rule that was added with {@link Gdn_Validation::ApplyRule()}.
     *
     * @param $FieldName
     * @param bool $RuleName
     */
    public function unapplyRule($FieldName, $RuleName = false) {
        if ($RuleName) {
            if (isset($this->_FieldRules[$FieldName])) {
                $Index = array_search($RuleName, $this->_FieldRules[$FieldName]);

                if ($Index !== false) {
                    unset($this->_FieldRules[$FieldName][$Index]);
                }
            }
            if (array_key_exists($FieldName, $this->getSchemaRules())) {
                $Index = array_search($RuleName, $this->_SchemaRules[$FieldName]);

                if ($Index !== false) {
                    unset($this->_SchemaRules[$FieldName][$Index]);
                }
            }
        } else {
            $this->getSchemaRules();
            unset(
                $this->_FieldRules[$FieldName],
                $this->_ValidationFields[$FieldName],
                $this->_SchemaRules[$FieldName]
            );
        }

    }

    /**
     * Examines the posted fields, defines $this->_ValidationFields, and enforces the $this->Rules collection on them.
     *
     * @param array $PostedFields An associative array of posted fields to be validated.
     * @param boolean $Insert A boolean value indicating if the posted fields are to be inserted or
     *  updated. If being inserted, the schema's required field rules will be enforced.
     * @return boolean Whether or not the validation was successful.
     */
    public function validate($PostedFields, $Insert = false) {
        // Create an array to hold validation result messages
        if (!is_array($this->_ValidationResults) || $this->resetOnValidate()) {
            $this->_ValidationResults = array();
        }

        // Check for a honeypot (anti-spam input)
        $HoneypotName = C('Garden.Forms.HoneypotName', '');
        $HoneypotContents = getPostValue($HoneypotName, '');
        if ($HoneypotContents != '') {
            $this->addValidationResult($HoneypotName, "You've filled our honeypot! We use honeypots to help prevent spam. If you're not a spammer or a bot, you should contact the application administrator for help.");
        }

        $FieldRules = $this->defineValidationRules($PostedFields, $Insert);
        $Fields = $this->defineValidationFields($PostedFields, $Insert);

        // Loop through the fields that should be validated
        foreach ($Fields as $FieldName => $FieldValue) {
            // If this field has rules to be enforced...
            if (array_key_exists($FieldName, $FieldRules) && is_array($FieldRules[$FieldName])) {
                // Enforce them.
                $Rules = $FieldRules[$FieldName];

                // Get the field info for the field.
                $FieldInfo = array('Name' => $FieldName);
                if (is_array($this->_Schema) && array_key_exists($FieldName, $this->_Schema)) {
                    $FieldInfo = array_merge($FieldInfo, (array)$this->_Schema[$FieldName]);
                }
                $FieldInfo = (object)$FieldInfo;

                foreach ($Rules as $RuleName) {
                    if (array_key_exists($RuleName, $this->_Rules)) {
                        $Rule = $this->_Rules[$RuleName];
                        // echo '<div>FieldName: '.$FieldName.'; Rule: '.$Rule.'</div>';
                        if (substr($Rule, 0, 9) == 'function:') {
                            $Function = substr($Rule, 9);
                            if (!function_exists($Function)) {
                                trigger_error(errorMessage('Specified validation function could not be found.', 'Validation', 'Validate', $Function), E_USER_ERROR);
                            }

                            $ValidationResult = $Function($FieldValue, $FieldInfo, $PostedFields);
                            if ($ValidationResult !== true) {
                                // If $ValidationResult is not FALSE, assume it is an error message
                                $ErrorCode = $ValidationResult === false ? $Function : $ValidationResult;
                                // If there is a custom error, use it above all else
                                $ErrorCode = arrayValue($FieldName.'.'.$RuleName, $this->_CustomErrors, $ErrorCode);
                                // Add the result
                                $this->addValidationResult($FieldName, $ErrorCode);
                                // Only add one error per field
                            }
                        } elseif (substr($Rule, 0, 6) == 'regex:') {
                            $Regex = substr($Rule, 6);
                            if (ValidateRegex($FieldValue, $Regex) !== true) {
                                $ErrorCode = 'Regex';
                                // If there is a custom error, use it above all else
                                $ErrorCode = arrayValue($FieldName.'.'.$RuleName, $this->_CustomErrors, $ErrorCode);
                                // Add the result
                                $this->addValidationResult($FieldName, $ErrorCode);
                            }
                        }
                    }
                }
            }
        }
        $this->_ValidationFields = $Fields;
        return count($this->_ValidationResults) === 0;
    }

    /**
     * Add a validation result (error) to the validation.
     *
     * @param string $FieldName The name of the form field that has the error.
     * @param string $ErrorCode The translation code of the error.
     *    Codes that begin with an '@' symbol are treated as literals and not translated.
     */
    public function addValidationResult($FieldName, $ErrorCode = '') {
        if (!is_array($this->_ValidationResults)) {
            $this->_ValidationResults = array();
        }

        if (is_array($FieldName)) {
            $ValidationResults = $FieldName;
            $this->_ValidationResults = array_merge($this->_ValidationResults, $ValidationResults);
        } else {
            if (!array_key_exists($FieldName, $this->_ValidationResults)) {
                $this->_ValidationResults[$FieldName] = array();
            }

            $this->_ValidationResults[$FieldName][] = $ErrorCode;
        }
    }

    /**
     * Returns the $this->_ValidationResults array. You must use this method
     * because the array is read-only outside this object.
     *
     * @param bool $Reset Whether or not to clear the validation results.
     * @return array Returns an array of validation results (errors).
     */
    public function results($Reset = false) {
        if (!is_array($this->_ValidationResults) || $Reset) {
            $this->_ValidationResults = array();
        }

        return $this->_ValidationResults;
    }

    /**
     * Get the validation results as a string of text.
     *
     * @return string Returns the validation results.
     */
    public function resultsText() {
        return self::resultsAsText($this->results());
    }

    /**
     * Format an array of validation results as a string.
     *
     * @param array $Results An array of validation results returned from {@link Gdn_Validation::Results()}.
     * @return string Returns the validation results as a string.
     */
    public static function resultsAsText($Results) {
        $Errors = array();
        foreach ($Results as $Name => $Value) {
            if (is_array($Value)) {
                foreach ($Value as $Code) {
                    $Errors[] = trim(sprintf(T($Code), T($Name)), '.');
                }
            } else {
                $Errors[] = trim(sprintf(T($Value), T($Name)), '.');
            }
        }

        $Result = implode('. ', $Errors);
        if ($Result) {
            $Result .= '.';
        }
        return $Result;
    }
}
