<?php if (!defined('APPLICATION')) exit();

/**
 * Data validation
 * 
 * Manages data integrity validation rules. Can automatically define a set of
 * validation rules based on a @@Schema with $this->GenerateBySchema($Schema);
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_Validation {


   /**
    * The collection of validation rules in the format of $RuleName =>
    * $Rule. This list can be added to with $this->AddRule($RuleName, $Rule).
    *
    * @var array
    */
   protected $_Rules;


   /**
    * An associative array of fieldname => value pairs that are being
    * validated. In order for a field to become a part of this collection, it
    * must either be present in the defined schema, or have a rule defined in
    * $this->_FieldRules.
    *
    * @var array
    */
   protected $_ValidationFields;


   /**
    * An associative array of fieldname => value pairs that are in
    * $this->_ValidationFields AND $this->_Schema. This array is populated by
    * $this->AddValidationField(); You can access it from outside this class
    * with $this->SchemaValidationFields();
    *
    * @var array
    */
   protected $_SchemaValidationFields = array();


   /**
    * An array of FieldName => Reason arrays that describe which fields failed
    * validation and which functions/regex caused them to fail.
    *
    * @var array
    */
   protected $_ValidationResults;


   /**
    * An associative array of $FieldName => array($RuleName1, $RuleNameN)
    * rules to be applied to fields.
    *
    * @var array
    */
   protected $_FieldRules = array();


   /**
    * The schema being used to generate validation rules.
    *
    * @var array
    */
   protected $_Schema = NULL;


   /**
    * An array of fields from $this->_Schema that are required for validation.
    *
    * @var array
    */
   private $_RequiredSchemaFields = array();


   /**
    * An array of FieldName.RuleName => "Custom Error Message"s. See $this->ApplyRule.
    *
    * @var array
    */
   private $_CustomErrors = array();


   /**
    * Class constructor. Optionally takes a schema definition to generate
    * validation rules for.
    *
    * @param object $Schema A schema object to generate validation rules for.
    */
   public function __construct($Schema = FALSE) {
      if ($Schema !== FALSE)
         $this->ApplyRulesBySchema($Schema);

      // Define the default validation functions
      $this->_Rules = array();
      $this->AddRule('Required', 'function:ValidateRequired');
      $this->AddRule('RequiredArray', 'function:ValidateRequiredArray');
      $this->AddRule('Email', 'function:ValidateEmail');
      $this->AddRule('WebAddress', 'function:ValidateWebAddress');
      $this->AddRule('Username', 'function:ValidateUsername');
      $this->AddRule('UrlString', 'function:ValidateUrlString');
      $this->AddRule('UrlStringRelaxed', 'function:ValidateUrlStringRelaxed');
      $this->AddRule('Date', 'function:ValidateDate');
      $this->AddRule('Integer', 'function:ValidateInteger');
      $this->AddRule('Boolean', 'function:ValidateBoolean');
      $this->AddRule('Decimal', 'function:ValidateDecimal');
      $this->AddRule('Time', 'function:ValidateTime');
      $this->AddRule('Timestamp', 'function:ValidateTimestamp');
      $this->AddRule('Length', 'function:ValidateLength');
      $this->AddRule('Enum', 'function:ValidateEnum');
      $this->AddRule('MinimumAge', 'function:ValidateMinimumAge');
      $this->AddRule('Captcha', 'function:ValidateCaptcha');
      $this->AddRule('Match', 'function:ValidateMatch');
      $this->AddRule('Strength', 'function:ValidateStrength');
      $this->AddRule('OldPassword', 'function:ValidateOldPassword');
      $this->AddRule('Version', 'function:ValidateVersion');
      $this->AddRule('PhoneNA', 'function:ValidatePhoneNA');
      $this->AddRule('PhoneInt', 'function:ValidatePhoneInt');
      $this->AddRule('ZipCode', 'function:ValidateZipCode');
      $this->AddRule('Format', 'function:ValidateFormat');
   }


   /**
    * Examines the provided schema and fills $this->_Rules with rules based
    * on the properties of each field in the table schema.
    *
    * @param object $Schema A schema object to generate validation rules for.
    */
   public function ApplyRulesBySchema($Schema) {
      $this->_Schema = $Schema->Fetch();
      foreach($this->_Schema as $Field => $Properties) {
         // Create an array to hold rules for this field
         $RuleNames = array();

         if ($Properties->AutoIncrement === TRUE) {
            // Skip all rules for auto-incrementing integer columns - they will
         // not be inserted or updated.
         } else {
            // Force non-null fields without defaults to be required.
            if ($Properties->AllowNull === FALSE && $Properties->Default == '') {
               $RuleNames[] = 'Required';
               $this->_RequiredSchemaFields[] = $Field;
            }

            // Force other constraints based on field type.
            switch($Properties->Type) {
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
                  if ($Properties->Length != '')
                     $RuleNames[] = 'Length';
                  break;

               case 'enum':
               case 'set':
                  $RuleNames[] = 'Enum';
                  break;
            }
            
            if ($Field == 'Format') {
               $RuleNames[] = 'Format';
            }
         }
         // Assign the rules to the field.
         // echo '<div>Field: '.$Field.'</div>';
         // print_r($RuleNames);
         $this->_ApplyRule($Field, $RuleNames);
      }
   }


   /**
    * Applies a $RuleName to a $FieldName. You can apply as many rules to a
    * field as you like.
    *
    * @param string $FieldName The name of the field to apply rules to.
    * @param mixed $RuleName The rule name (or array of rule names) to apply to the field.
    * @param mixed $CustomError A custom error message you might want to apply to a field
    *  if the rule causes an error to be caught.
    */
   public function ApplyRule($FieldName, $RuleName, $CustomError = '') {
      // Make sure that $FieldName is in the validation fields collection
      $this->ValidationFields();
      
      if (!array_key_exists($FieldName, $this->_ValidationFields)) //  && $RuleName == 'Required'
         $this->_ValidationFields[$FieldName] = '';
         
      $this->_ApplyRule($FieldName, $RuleName, $CustomError);
   }
   
   /**
    * Apply an array of validation rules all at once.
    * @param array $Fields 
    */
   public function ApplyRules($Fields) {
      foreach ($Fields as $Index => $Row) {
         $Validation = GetValue('Validation', $Row);
         if (!$Validation)
            continue;
         
         $FieldName = GetValue('Name', $Row, $Index);
         if (is_string($Validation)) {
            $this->ApplyRule($FieldName, $Validation);
         } elseif (is_array($Validation)) {
            foreach ($Validation as $Rule) {
               if (is_array($Rule)) {
                  $this->ApplyRule($FieldName, $Rule[0], $Rule[1]);
               } else {
                  $this->ApplyRule($FieldName, $Rule);
               }
            }
         }
      }
   }
      
   protected function _ApplyRule($FieldName, $RuleName, $CustomError = '') {
      if (!is_array($this->_FieldRules))
         $this->_FieldRules = array();

      if (!is_array($RuleName)) {
         if ($CustomError != '')
            $this->_CustomErrors[$FieldName . '.' . $RuleName] = $CustomError;

         $RuleName = array($RuleName);
      }

      if (count($RuleName) > 0) {
         $ExistingRules = ArrayValue($FieldName, $this->_FieldRules, array());

         // Merge the new rules with the existing ones (array_merge) and make
         // sure there is only one of each rule applied (array_unique).
         $this->_FieldRules[$FieldName] = array_unique(array_merge($ExistingRules, $RuleName));
      }
   }
   

   /**
    * Allows the explicit definition of a schema to use
    *
    * @param array $Schema
    */
   public function ApplySchema($Schema) {
      $this->_Schema = $Schema;
   }


   /**
    * Fills $this->_ValidationFields with field names that exist in the
    * $PostedFields collection.
    *
    * @param array $PostedFields The associative array collection of field names to add.
    * @param array $Schema A schema to examine for field names. If not provided, it will look for
    *  fields that are in $this->_FieldRules and $PostedFields.
    * @param boolean $Insert A boolean value indicating if the posted fields are to be inserted or
    * updated. If being inserted, the schema's required field rules will be
    * enforced.
    */
   public function DefineValidationFields($PostedFields, $Schema = NULL, $Insert = FALSE) {
      $this->ValidationFields();

      if ($Schema != NULL)
         $this->_Schema = $Schema;

      // What fields should be validated?

      // 1. Any field that was already explicitly added to the validationfields collection
      foreach($this->_ValidationFields as $Field => $Val) {
         $this->AddValidationField($Field, $PostedFields);
      }
      
      if ($Schema != NULL) {
         // 2. Any field that is required by the schema
         foreach($Schema as $Field => $Properties) {
            if (array_key_exists($Field, $PostedFields) || ($Insert && in_array($Field, $this->_RequiredSchemaFields)))
               $this->AddValidationField($Field, $PostedFields);
         }
      } else {
         // 3. Any of the form-posted field
         foreach($this->_FieldRules as $Field => $Rules) {
            if (array_key_exists($Field, $PostedFields))
               $this->AddValidationField($Field, $PostedFields);
         }
      }
   }


   /**
    * Returns the an array of fieldnames that are being validated.
    *
    * @return array
    */
   public function ValidationFields() {
      if (!is_array($this->_ValidationFields))
         $this->_ValidationFields = array();
         
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
   public function AddRule($RuleName, $Rule) {
      $this->_Rules[$RuleName] = $Rule;
   }


   /**
    * Adds a fieldname to the $this->_ValidationFields collection.
    *
    * @param string $FieldName The name of the field to add to the $this->_ValidationFields collection.
    * @param array $PostedFields The associative array collection of field names to examine for the value
    *  of $FieldName.
    */
   public function AddValidationField($FieldName, $PostedFields) {
      $this->ValidationFields();

//      if (in_array($FieldName, $this->_ValidationFields) === FALSE) {
         $Value = ArrayValue($FieldName, $PostedFields, NULL);
         $this->_ValidationFields[$FieldName] = $Value;
         // Also add to the array of field names that are being validated *and* are present in the schema
         if (is_array($this->_Schema) && array_key_exists($FieldName, $this->_Schema))
            $this->_SchemaValidationFields[$FieldName] = $Value;
//      }
   }


   /**
    * Returns an array of field names that are in both $this->_ValidationFields
    * AND $this->_Schema.
    *
    * @return array
    */
   public function SchemaValidationFields() {
      return $this->_SchemaValidationFields;
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
   public function SetSchemaProperty($FieldName, $PropertyName, $Value) {
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
   public static function ValidateRule($Value, $FieldName, $Rule, $CustomError = FALSE) {
      // Figure out the type of rule.
      if (is_string($Rule)) {
         if (StringBeginsWith($Rule, 'regex:', TRUE)) {
            $RuleName = 'validateregex';
            $Args = substr($Rule, 6);
         } elseif (StringBeginsWith($Rule, 'function:', TRUE)) {
            $RuleName = substr($Rule, 9);
         } else {
            $RuleName = $Rule;
         }
      } elseif (is_array($Rule)) {
         $RuleName = GetValue('Name', $Rule);
         $Args = GetValue('Args', $Rule);
      }

      if (!isset($Args))
         $Args = NULL;

      if (function_exists($RuleName)) {
         $Result = $RuleName($Value, $Args);
         if ($Result === TRUE)
            return TRUE;
         elseif ($CustomError)
            return $CustomError;
         elseif (is_string($Result))
            return $Result;
         else
            return sprintf(T($RuleName), T($FieldName));
      } else {
         return sprintf('Validation does not exist: %s.', $RuleName);
      }
   }
   
   public function UnapplyRule($FieldName, $RuleName = FALSE) {
      if ($RuleName) {
         if (isset($this->_FieldRules[$FieldName])) {
            $Index = array_search($RuleName, $this->_FieldRules[$FieldName]);
            
            if ($Index !== FALSE)
               unset($this->_FieldRules[$FieldName][$Index]);
         }
      } else {
         unset($this->_FieldRules[$FieldName]);
         unset($this->_ValidationFields[$FieldName]);
      }
      
   }

   /**
    * Examines the posted fields, defines $this->_ValidationFields, and
    * enforces the $this->Rules collection on them.
    *
    * @param array $PostedFields An associative array of posted fields to be validated.
    * @param boolean $Insert A boolean value indicating if the posted fields are to be inserted or
    *  updated. If being inserted, the schema's required field rules will be
    *  enforced.
    * @return boolean Whether or not the validation was successful.
    */
   public function Validate($PostedFields, $Insert = FALSE) {
      $this->DefineValidationFields($PostedFields, $this->_Schema, $Insert);

      // Create an array to hold validation result messages
      if (!is_array($this->_ValidationResults))
         $this->_ValidationResults = array();

      // Check for a honeypot (anti-spam input)
      $HoneypotName = Gdn::Config('Garden.Forms.HoneypotName', '');
      $HoneypotContents = GetPostValue($HoneypotName, '');
      if ($HoneypotContents != '')
         $this->AddValidationResult($HoneypotName, "You've filled our honeypot! We use honeypots to help prevent spam. If you're  not a spammer or a bot, you should contact the application administrator for help.");

      
      // Loop through the fields that should be validated
      foreach($this->_ValidationFields as $FieldName => $FieldValue) {
         // If this field has rules to be enforced...
         if (array_key_exists($FieldName, $this->_FieldRules) && is_array($this->_FieldRules[$FieldName])) {
            // Enforce them...
            $this->_FieldRules[$FieldName] = array_values($this->_FieldRules[$FieldName]);
            $RuleCount = count($this->_FieldRules[$FieldName]);
            for($i = 0; $i < $RuleCount; ++$i) {
               $RuleName = $this->_FieldRules[$FieldName][$i];
               if (array_key_exists($RuleName, $this->_Rules)) {
                  $Rule = $this->_Rules[$RuleName];
                  // echo '<div>FieldName: '.$FieldName.'; Rule: '.$Rule.'</div>';
                  if (substr($Rule, 0, 9) == 'function:') {
                     $Function = substr($Rule, 9);
                     if (!function_exists($Function))
                        trigger_error(ErrorMessage('Specified validation function could not be found.', 'Validation', 'Validate', $Function), E_USER_ERROR);

                     // Call the function. Core-defined validation functions can
                     // be found in ./functions.validation.php
                     $FieldInfo = array('Name' => $FieldName);
                     if (is_array($this->_Schema) && array_key_exists($FieldName, $this->_Schema))
                        $FieldInfo = array_merge($FieldInfo, (array)$this->_Schema[$FieldName]);
                     $FieldInfo = (object)$FieldInfo;

                     $ValidationResult = $Function($FieldValue, $FieldInfo, $PostedFields);
                     if ($ValidationResult !== TRUE) {
                        // If $ValidationResult is not FALSE, assume it is an error message
                        $ErrorCode = $ValidationResult === FALSE ? $Function : $ValidationResult;
                        // If there is a custom error, use it above all else
                        $ErrorCode = ArrayValue($FieldName . '.' . $RuleName, $this->_CustomErrors, $ErrorCode);
                        // Add the result
                        $this->AddValidationResult($FieldName, $ErrorCode);
                        // Only add one error per field
                        $i = $RuleCount;
                     }
                  } else if (substr($Rule, 0, 6) == 'regex:') {
                     $Regex = substr($Rule, 6);
                     if (ValidateRegex($FieldValue, $Regex) !== TRUE) {
                        $ErrorCode = 'Regex';
                        // If there is a custom error, use it above all else
                        $ErrorCode = ArrayValue($FieldName . '.' . $RuleName, $this->_CustomErrors, $ErrorCode);
                        // Add the result
                        $this->AddValidationResult($FieldName, $ErrorCode);
                     }
                  }
               }
            }
         }
      }
      return count($this->_ValidationResults) == 0 ? TRUE : FALSE;
   }

   /**
    * Add a validation result (error) to the validation.
    *
    * @param string $FieldName The name of the form field that has the error.
    * @param string $ErrorCode The translation code of the error.
    *    Codes that begin with an '@' symbol are treated as literals and not translated.
    */
   public function AddValidationResult($FieldName, $ErrorCode = '') {
      if (!is_array($this->_ValidationResults))
         $this->_ValidationResults = array();

      if(is_array($FieldName)) {
         $ValidationResults = $FieldName;
         $this->_ValidationResults = array_merge($this->_ValidationResults, $ValidationResults);
      } else {
         if (!array_key_exists($FieldName, $this->_ValidationResults))
            $this->_ValidationResults[$FieldName] = array();

         $this->_ValidationResults[$FieldName][] = $ErrorCode;
      }
   }

   /**
    * Returns the $this->_ValidationResults array. You must use this method
    * because the array is read-only outside this object.
    *
    * @return array
    */
   public function Results($Reset = FALSE) {
      if (!is_array($this->_ValidationResults) || $Reset)
         $this->_ValidationResults = array();
      
      return $this->_ValidationResults;
   }
   
   public function ResultsText() {
      return self::ResultsAsText($this->Results());
   }
   
   public static function ResultsAsText($Results) {
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
      
      $Result = implode('. ', $Errors).'.';
      return $Result;
   }
}
