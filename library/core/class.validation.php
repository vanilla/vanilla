<?php
/**
 * Data validation.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Manages data integrity validation rules. Can automatically define a set of
 * validation rules based on a @@Schema with $this->generateBySchema($Schema);
 */
class Gdn_Validation {

    /**
     * @var array The collection of validation rules in the format of $RuleName => $Rule.
     * This list can be added to with $this->addRule($RuleName, $Rule).
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
     * These are rules that have been explicitly called with {@link Gdn_Validation::applyRule()}.
     */
    protected $_FieldRules = [];

    /**
     * @var array An associative array of $FieldName => array($RuleName1, $RuleNameN) rules to be applied to fields.
     * These are rules that come from the current schema that have been applied by {@link Gdn_Validation::applyRulesBySchema()}.
     */
    protected $_SchemaRules = [];

    /** @var array The schema being used to generate validation rules. */
    protected $_Schema = [];

    /** @var bool Whether or not to reset the validation results on validate. */
    protected $_ResetOnValidate = false;

    /** @var array An array of FieldName.RuleName => "Custom Error Message"s. See $this->ApplyRule. */
    private $_CustomErrors = [];

    /**
     * Class constructor. Optionally takes a schema definition to generate validation rules for.
     *
     * @param Gdn_Schema|array $schema A schema object to generate validation rules for.
     * @param bool Whether or not to reset the validation results on {@link validate()}.
     */
    public function __construct($schema = false, $resetOnValidate = false) {
        if (is_object($schema) || is_array($schema)) {
            $this->setSchema($schema);
        }
        $this->setResetOnValidate($resetOnValidate);

        // Define the default validation functions
        $this->_Rules = [];
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
        $this->addRule('Timestamp', 'function:ValidateDate');
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
     * Expand the validation results into a single-dimension array.
     *
     * @param array $results The validation results to expand.
     * @return array Returns an array of error messages.
     */
    public static function resultsAsArray($results) {
        $errors = [];
        foreach ($results as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $code) {
                    $errors[] = trim(sprintf(t($code), t($name)), '.').'.';
                }
            } else {
                $errors[] = trim(sprintf(t($value), t($name)), '.').'.';
            }
        }
        return $errors;
    }

    /**
     * Examine the current schema and fill {@link Gdn_Validation::$_SchemaRules}.
     *
     * The {@link Gdn_Validation::$_SchemaRules} are filled with rules based on the properties of each field in the
     * table schema.
     */
    protected function applyRulesBySchema() {
        $this->_SchemaRules = [];

        foreach ($this->_Schema as $field => $properties) {
            if (is_scalar($properties) || $properties === null) {
                // Some code passes a record as a schema so account for that here.
                $properties = [
                    'AutoIncrement' => false,
                    'AllowNull' => true,
                    'Type' => 'text',
                    'Length' => ''
                ];
                $properties = (object)$properties;
            }

            // Create an array to hold rules for this field
            $ruleNames = [];

            // Force non-null fields without defaults to be required.
            if ($properties->AllowNull === false && $properties->Default == '') {
                $ruleNames[] = 'Required';
            }

            // Force other constraints based on field type.
            switch ($properties->Type) {
                case 'bit':
                case 'bool':
                case 'boolean':
                    $ruleNames[] = 'Boolean';
                    break;

                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                case 'int':
                case 'integer':
                case 'bigint':
                    $ruleNames[] = 'Integer';
                    break;

                case 'double':
                case 'float':
                case 'real':
                case 'decimal':
                case 'dec':
                case 'numeric':
                case 'fixed':
                    $ruleNames[] = 'Decimal';
                    break;

                case 'date':
                case 'datetime':
                    $ruleNames[] = 'Date';
                    break;
                case 'time':
                    $ruleNames[] = 'Time';
                    break;
                case 'year':
                    $ruleNames[] = 'Year';
                    break;
                case 'timestamp':
                    $ruleNames[] = 'Timestamp';
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
                    if (!in_array($field, ['Attributes', 'Data', 'Preferences', 'Permissions'])) {
                        $ruleNames[] = 'String';
                    }
                    if ($properties->Length != '') {
                        $ruleNames[] = 'Length';
                    }
                    break;

                case 'enum':
                case 'set':
                    $ruleNames[] = 'Enum';
                    break;
            }

            if ($field == 'Format') {
                $ruleNames[] = 'Format';
            }

            // Assign the rules to the field.
            // echo '<div>Field: '.$Field.'</div>';
            // print_r($RuleNames);
            $this->applyRuleTo($this->_SchemaRules, $field, $ruleNames);
        }
    }

    /**
     * Applies a $ruleName to a $fieldName. You can apply as many rules to a field as you like.
     *
     * @param string $fieldName The name of the field to apply rules to.
     * @param mixed $ruleName The rule name (or array of rule names) to apply to the field.
     * @param mixed $customError A custom error message you might want to apply to a field
     *  if the rule causes an error to be caught.
     */
    public function applyRule($fieldName, $ruleName, $customError = '') {
        // Make sure that $FieldName is in the validation fields collection
        $this->validationFields();

        if (!array_key_exists($fieldName, $this->_ValidationFields)) { //  && $RuleName == 'Required'
            $this->_ValidationFields[$fieldName] = '';
        }

        $this->applyRuleTo($this->_FieldRules, $fieldName, $ruleName, $customError);
    }

    /**
     * Apply a rule to the given rules array.
     *
     * @param array $array The rules array to apply the rule to.
     * This should be either `$this->_FieldRules` or `$this->_SchemaRules`.
     * @param string $fieldName The name of the field that the rule applies to.
     * @param string $ruleName The name of the rule.
     * @param string $customError A custom error string when the rule is broken.
     */
    protected function applyRuleTo(&$array, $fieldName, $ruleName, $customError = '') {
        $array = (array)$array;

        if (!is_array($ruleName)) {
            if ($customError != '') {
                $this->_CustomErrors[$fieldName.'.'.$ruleName] = $customError;
            }

            $ruleName = [$ruleName];
        }

        $existingRules = val($fieldName, $array, []);

        // Merge the new rules with the existing ones (array_merge) and make
        // sure there is only one of each rule applied (array_unique).
        $array[$fieldName] = array_unique(array_merge($existingRules, $ruleName));
    }

    /**
     * Allows the explicit definition of a schema to use.
     *
     * @param array $schema
     * @deprecated This method has been deprecated in favor of {@link Gdn_Validation::setSchema()}.
     */
    public function applySchema($schema) {
        deprecated('ApplySchema', 'SetSchema');
        $this->setSchema($schema);
    }

    /**
     * Fills $this->_ValidationFields with field names that exist in the $postedFields collection.
     *
     * @param array $postedFields The associative array collection of field names to add.
     * @param boolean $insert A boolean value indicating if the posted fields are to be inserted or
     * updated. If being inserted, the schema's required field rules will be enforced.
     * @return array Returns the subset of {@link $postedFields} that will be validated.
     */
    protected function defineValidationFields($postedFields, $insert = false) {
        $result = [];

        // Start with the fields that have been explicitly defined by `ApplyRule`.
        foreach ($this->_FieldRules as $field => $rules) {
            $result[$field] = val($field, $postedFields, null);
        }

        // Add all of the fields from the schema.
        foreach ($this->getSchemaRules() as $field => $rules) {
            $fieldInfo = $this->_Schema[$field];

            if (!array_key_exists($field, $postedFields)) {
                $required = in_array('Required', $rules);

                // Don't enforce fields that aren't required or required fields during a sparse update.
                if (!$required || !$insert) {
                    continue;
                }
                // Fields with a non-null default can be left out.
                if (val('Default', $fieldInfo, null) !== null || val('AutoIncrement', $fieldInfo)) {
                    continue;
                }
            }
            $result[$field] = val($field, $postedFields, null);
        }

        return $result;
    }

    /**
     * Get all of the validation rules that apply to a given set of data.
     *
     * @param array $postedFields The data that will be validated.
     * @param bool $insert Whether or not this is an insert.
     * @return array Returns an array of `[$field => [$rules, ...]`.
     */
    protected function defineValidationRules($postedFields, $insert = false) {
        $result = (array)$this->_FieldRules;

        // Add all of the fields from the schema.
        foreach ($this->getSchemaRules() as $field => $rules) {
            $fieldInfo = $this->_Schema[$field];

            if (!array_key_exists($field, $postedFields)) {
                $required = in_array('Required', $rules);

                // Don't enforce fields that aren't required or required fields during a sparse update.
                if (!$required || !$insert) {
                    continue;
                }
                // Fields with a non-null default can be left out.
                if (val('Default', $fieldInfo, null) !== null || val('AutoIncrement', $fieldInfo)) {
                    continue;
                }
            }
            if (isset($result[$field])) {
                $result[$field] = array_unique(array_merge($result[$field], $rules));
            } else {
                $result[$field] = $rules;
            }
        }

        return $result;
    }

    /**
     * Set the schema for this validation.
     *
     * @param Gdn_Schema|array $schema The new schema to set.
     * @return Gdn_Validation Returns `$this` for fluent calls.
     * @throws \Exception Throws an exception when {@link $schema} isn't an array or {@link Gdn_Schema} object.
     */
    public function setSchema($schema) {
        if ($schema instanceof Gdn_Schema) {
            $this->_Schema = $schema->fields();
        } elseif (is_array($schema)) {
            $this->_Schema = $schema;
        } else {
            throw new \Exception('Invalid schema of type '.gettype($schema).'.', 500);
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
            $this->_ValidationFields = [];
        }

        return $this->_ValidationFields;
    }

    /**
     * Adds to the rules collection ($this->_Rules).
     *
     * If $ruleName already
     * exists, this method will overwrite the existing rule. There are some
     * special cases:
     *  1. If the $rule begins with "function:", when the rule is evaluated
     * on a field, it will strip the "function:" from the $rule and execute
     * the remaining string name as a function with the field value passed as
     * the first parameter and the related field properties as the second
     * parameter. ie. "function:MySpecialValidation" will evaluate as
     * mySpecialValidation($FieldValue, $FieldProperties). Any function defined
     * in this way is expected to return boolean TRUE or FALSE.
     *  2. If $rule begins with "regex:", when the rule is evaluated on a
     * field, it will strip the "regex:" from $rule and use the remaining
     * string as a regular expression rule. If a match between the regex rule
     * and the field value is made, it will validate as TRUE.
     *  3. Predefined $ruleNames are:
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
     * @param string $ruleName The name of the rule to be added.
     * @param string $rule The rule to be added. These are in the format of "function:FunctionName"
     * or "regex:/regex/". Any function defined here must be included before
     * the rule is enforced or the application will cause a fatal error.
     */
    public function addRule($ruleName, $rule) {
        $this->_Rules[$ruleName] = $rule;
    }

    /**
     * Whether or not the validation results etc should reset whenever {@link validate()} is called.
     *
     * @return boolean Returns true if we reset or false otherwise.
     */
    public function resetOnValidate() {
        return $this->_ResetOnValidate;
    }

    /**
     * Set whether or not the validation results etc should reset whenever {@link validate()} is called.
     *
     * @param boolean $resetOnValidate True to reset or false otherwise.
     * @return Gdn_Validation Returns `$this` for fluent calls.
     */
    public function setResetOnValidate($resetOnValidate) {
        $this->_ResetOnValidate = $resetOnValidate;
        return $this;
    }

    /**
     * Adds a fieldname to the $this->_ValidationFields collection.
     *
     * @param string $fieldName The name of the field to add to the $this->_ValidationFields collection.
     * @param array $postedFields The associative array collection of field names to examine for the value
     *  of $fieldName.
     */
    protected function addValidationField($fieldName, $postedFields) {
        if (!is_array($this->_ValidationFields)) {
            $this->_ValidationFields = [];
        }

        $value = val($fieldName, $postedFields, null);
        $this->_ValidationFields[$fieldName] = $value;
    }

    /**
     * Returns an array of field names that are in both $this->_ValidationFields AND $this->_Schema.
     *
     * @return array Returns an array of fields and values that were validated and in the schema.
     */
    public function schemaValidationFields() {
        $result = array_intersect_key($this->_ValidationFields, $this->_Schema);
        return $result;
    }

    /**
     * Allows you to explicitly set a field property on $this->_Schema. Can be
     * useful when adding rules to fields (ie. a maxlength property on a db's
     * text field).
     *
     * @param string $fieldName The name of the field that we are setting a property for.
     * @param string $propertyName The name of the property being set.
     * @param mixed $value The value of the property to set.
     */
    public function setSchemaProperty($fieldName, $propertyName, $value) {
        if (is_array($this->_Schema) && array_key_exists($fieldName, $this->_Schema)) {
            $field = $this->_Schema[$fieldName];
            if (is_object($field)) {
                $field->$propertyName = $value;
                $this->_Schema[$fieldName] = $field;
            }
        }
    }

    /**
     * Execute a single validation rule and return its result.
     *
     * @param mixed $value The value to validate.
     * @param string $fieldName The name of the field to put into the error result.
     * @param string|array $rule The rule to validate which can be one of the following.
     *  - string: The name of a function used to validate the value.
     *  - 'regex:<regex>': The regular expression used to validate the value.
     *  - array: An array with the following keys:
     *    - Name: The name of the function used to validate.
     *    - Args: An argument to pass to the function after the value.
     * @param string $customError A custom error message.
     * @return bool|string One of the following
     *  - TRUE: The value passed validation.
     *  - string: The error message associated with the error.
     */
    public static function validateRule($value, $fieldName, $rule, $customError = false) {
        // Figure out the type of rule.
        if (is_string($rule)) {
            if (stringBeginsWith($rule, 'regex:', true)) {
                $ruleName = 'validateregex';
                $args = substr($rule, 6);
            } elseif (stringBeginsWith($rule, 'function:', true)) {
                $ruleName = substr($rule, 9);
            } else {
                $ruleName = $rule;
            }
        } elseif (is_array($rule)) {
            $ruleName = val('Name', $rule);
            $args = val('Args', $rule);
        }

        if (!isset($args)) {
            $args = null;
        }

        if (function_exists($ruleName)) {
            $result = $ruleName($value, $args);
            if ($result === true) {
                return true;
            } elseif ($customError)
                return $customError;
            elseif (is_string($result))
                return $result;
            else {
                return sprintf(t($ruleName), t($fieldName));
            }
        } else {
            return sprintf('Validation does not exist: %s.', $ruleName);
        }
    }

    /**
     * Remove a validation rule that was added with {@link Gdn_Validation::applyRule()}.
     *
     * @param $fieldName
     * @param bool $ruleName
     */
    public function unapplyRule($fieldName, $ruleName = false) {
        if ($ruleName) {
            if (isset($this->_FieldRules[$fieldName])) {
                $index = array_search($ruleName, $this->_FieldRules[$fieldName]);

                if ($index !== false) {
                    unset($this->_FieldRules[$fieldName][$index]);
                }
            }
            if (array_key_exists($fieldName, $this->getSchemaRules())) {
                $index = array_search($ruleName, $this->_SchemaRules[$fieldName]);

                if ($index !== false) {
                    unset($this->_SchemaRules[$fieldName][$index]);
                }
            }
        } else {
            $this->getSchemaRules();
            unset(
                $this->_FieldRules[$fieldName],
                $this->_ValidationFields[$fieldName],
                $this->_SchemaRules[$fieldName]
            );
        }

    }

    /**
     * Examines the posted fields, defines $this->_ValidationFields, and enforces the $this->Rules collection on them.
     *
     * @param array $postedFields An associative array of posted fields to be validated.
     * @param boolean $insert A boolean value indicating if the posted fields are to be inserted or
     *  updated. If being inserted, the schema's required field rules will be enforced.
     * @return boolean Whether or not the validation was successful.
     */
    public function validate($postedFields, $insert = false) {
        // Create an array to hold validation result messages
        if (!is_array($this->_ValidationResults) || $this->resetOnValidate()) {
            $this->_ValidationResults = [];
        }

        // Check for a honeypot (anti-spam input)
        $honeypotName = c('Garden.Forms.HoneypotName', '');
        $honeypotContents = getPostValue($honeypotName, '');
        if ($honeypotContents != '') {
            $this->addValidationResult($honeypotName, "You've filled our honeypot! We use honeypots to help prevent spam. If you're not a spammer or a bot, you should contact the application administrator for help.");
        }

        $fieldRules = $this->defineValidationRules($postedFields, $insert);
        $fields = $this->defineValidationFields($postedFields, $insert);

        // Loop through the fields that should be validated
        foreach ($fields as $fieldName => $fieldValue) {
            // If this field has rules to be enforced...
            if (array_key_exists($fieldName, $fieldRules) && is_array($fieldRules[$fieldName])) {
                // Enforce them.
                $rules = $fieldRules[$fieldName];

                // Get the field info for the field.
                $fieldInfo = ['Name' => $fieldName];
                if (is_array($this->_Schema) && array_key_exists($fieldName, $this->_Schema)) {
                    $fieldInfo = array_merge($fieldInfo, (array)$this->_Schema[$fieldName]);
                }
                $fieldInfo = (object)$fieldInfo;

                foreach ($rules as $ruleName) {
                    if (array_key_exists($ruleName, $this->_Rules)) {
                        $rule = $this->_Rules[$ruleName];
                        // echo '<div>FieldName: '.$FieldName.'; Rule: '.$Rule.'</div>';
                        if (substr($rule, 0, 9) == 'function:') {
                            $function = substr($rule, 9);
                            if (!function_exists($function)) {
                                trigger_error(errorMessage('Specified validation function could not be found.', 'Validation', 'Validate', $function), E_USER_ERROR);
                            }

                            $validationResult = $function($fieldValue, $fieldInfo, $postedFields);
                            if ($validationResult !== true) {
                                // If $ValidationResult is not FALSE, assume it is an error message
                                $errorCode = $validationResult === false ? $function : $validationResult;
                                // If there is a custom error, use it above all else
                                $errorCode = val($fieldName.'.'.$ruleName, $this->_CustomErrors, $errorCode);
                                // Add the result
                                $this->addValidationResult($fieldName, $errorCode);
                                // Only add one error per field
                            }
                        } elseif (substr($rule, 0, 6) == 'regex:') {
                            $regex = substr($rule, 6);
                            if (validateRegex($fieldValue, $regex) !== true) {
                                $errorCode = 'Regex';
                                // If there is a custom error, use it above all else
                                $errorCode = val($fieldName.'.'.$ruleName, $this->_CustomErrors, $errorCode);
                                // Add the result
                                $this->addValidationResult($fieldName, $errorCode);
                            }
                        }
                    }
                }
            }
        }
        $this->_ValidationFields = $fields;
        return count($this->_ValidationResults) === 0;
    }

    /**
     * Add a validation result (error) to the validation.
     *
     * @param string $fieldName The name of the form field that has the error.
     * @param string $errorCode The translation code of the error.
     *    Codes that begin with an '@' symbol are treated as literals and not translated.
     */
    public function addValidationResult($fieldName, $errorCode = '') {
        if (!is_array($this->_ValidationResults)) {
            $this->_ValidationResults = [];
        }

        if (is_array($fieldName)) {
            $validationResults = $fieldName;
            $this->_ValidationResults = array_merge($this->_ValidationResults, $validationResults);
        } else {
            if (!array_key_exists($fieldName, $this->_ValidationResults)) {
                $this->_ValidationResults[$fieldName] = [];
            }

            $this->_ValidationResults[$fieldName][] = $errorCode;
        }
    }

    /**
     * Reset the validation results to an empty array.
     */
    public function reset() {
        $this->_ValidationResults = [];
    }

    /**
     * Returns the $this->_ValidationResults array. You must use this method
     * because the array is read-only outside this object.
     *
     * @param bool $reset Whether or not to clear the validation results.
     * @return array Returns an array of validation results (errors).
     */
    public function results($reset = false) {
        if (!is_array($this->_ValidationResults) || $reset) {
            $this->_ValidationResults = [];
        }

        return $this->_ValidationResults;
    }

    /**
     * Get the validation results as an array of error messages.
     *
     * @return array Returns an array of error messages or an empty array if there are no errors.
     */
    public function resultsArray() {
        return static::resultsAsArray($this->results());
    }

    /**
     * Get the validation results as a string of text.
     *
     * @return string Returns the validation results.
     */
    public function resultsText() {
        return static::resultsAsText($this->results());
    }

    /**
     * Format an array of validation results as a string.
     *
     * @param array $results An array of validation results returned from {@link Gdn_Validation::results()}.
     * @return string Returns the validation results as a string.
     */
    public static function resultsAsText($results) {
        $errors = self::resultsAsArray($results);

        $result = implode(' ', $errors);
        return $result;
    }
}
