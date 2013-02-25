<?php if (!defined('APPLICATION')) exit();

/**
 * Validation functions
 * 
 * All of these functions are used by ./class.validation.php to validate form
 * input strings. With the exception of ValidateRegex, each function receives
 * two parameters (the field value and the related database field properties)
 * and is expected to return a boolean TRUE or FALSE indicating if the
 * validation was successful.
 *
 * Note: $Field will be an object of field properties as defined in
 * @@MySQLDriver->_FetchTableSchema (at the bottom of the file). Properties
 * are: (string) Name, (bool) PrimaryKey, (string) Type, (bool) AllowNull,
 * (string) Default, (int) Length, (array) Enum.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

if (!function_exists('ValidateCaptcha')) {
   function ValidateCaptcha($Value) {
      $CaptchaPrivateKey = Gdn::Config('Garden.Registration.CaptchaPrivateKey', '');
      $Response = recaptcha_check_answer($CaptchaPrivateKey, ArrayValue('REMOTE_ADDR', $_SERVER, ''), ArrayValue('recaptcha_challenge_field', $_POST, ''), ArrayValue('recaptcha_response_field', $_POST, ''));
      return $Response->is_valid ?  TRUE : 'The reCAPTCHA value was not entered correctly. Please try again.';
   }
}

if (!function_exists('ValidateRegex')) {
   function ValidateRegex($Value, $Regex) {
      preg_match($Regex, $Value, $Matches);
      return is_array($Matches) && count($Matches) > 0 ? TRUE : FALSE;
   }
}

if (!function_exists('ValidateRequired')) {
   function ValidateRequired($Value, $Field = '') {
      if (is_array($Value) === TRUE)
         return count($Value) > 0 ? TRUE : FALSE;

      if (is_string($Value))
         return trim($Value) == '' ? FALSE : TRUE;

      if (is_numeric($Value))
         return TRUE;

      return FALSE;
   }
}

if (!function_exists('ValidateMeAction')) {
   function ValidateMeAction($Value) {
      $Matched = preg_match('`^/me .*`i', $Value);
      if ($Matched) {
         $HasPermission = Gdn::Session()->CheckPermission('Vanilla.Comments.Me');
         if (!$HasPermission)
            return T('ErrorPermission');
      }
      return TRUE;
   }
}

if (!function_exists('ValidateNoLinks')) {
   /**
    * Check whether or not a
    * 
    * @param string $Value
    * @return bool
    * @since 2.1
    */
   function ValidateNoLinks($Value) {
      $Matched = preg_match('`https?://`i', $Value);
      return !$Matched;
   }
}

if (!function_exists('ValidateRequiredArray')) {
   /**
    * Checkbox lists and DropDown lists that have no values selected return a
    * value of FALSE. Since this could be a valid entry in any other kind of
    * input, these "array" form-data types need their own "required" validation
    * method.
    */
   function ValidateRequiredArray($Value, $Field) {
      if (is_array($Value) === TRUE)
         return count($Value) > 0 ? TRUE : FALSE;

      return FALSE;
   }
}

if (!function_exists('ValidateConnection')) {
   function ValidateConnection($Value, $Field, $FormPostedValues) {
      $DatabaseHost = ArrayValue('Database.Host', $FormPostedValues, '~~Invalid~~');
      $DatabaseName = ArrayValue('Database.Name', $FormPostedValues, '~~Invalid~~');
      $DatabaseUser = ArrayValue('Database.User', $FormPostedValues, '~~Invalid~~');
      $DatabasePassword = ArrayValue('Database.Password', $FormPostedValues, '~~Invalid~~');
      $ConnectionString = GetConnectionString($DatabaseName, $DatabaseHost);
      try {
         $Connection = new PDO(
            $ConnectionString,
            $DatabaseUser,
            $DatabasePassword
         );
      } catch (PDOException $Exception) {
         return sprintf(T('ValidateConnection'), strip_tags($Exception->getMessage()));
      }
      return TRUE;
   }
}

if (!function_exists('ValidateOldPassword')) {
   function ValidateOldPassword($Value, $Field, $FormPostedValues) {
      $OldPassword = ArrayValue('OldPassword', $FormPostedValues, '');
      $Session = Gdn::Session();
      $UserModel = new UserModel();
      $UserID = $Session->UserID;
      return (bool) $UserModel->ValidateCredentials(
         '', $UserID, $OldPassword);
   }
}

if (!function_exists('ValidateEmail')) {
   function ValidateEmail($Value, $Field = '') {
      if (!ValidateRequired($Value))
         return TRUE;
      
      $Result = PHPMailer::ValidateAddress($Value);
      $Result = (bool)$Result;
      return $Result;
   }
}

if (!function_exists('ValidateWebAddress')) {
   function ValidateWebAddress($Value, $Field = '') {
      if ($Value == '')
         return TRUE; // Required picks up this error
      
      return filter_var($Value, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED) !== FALSE;
   }
}

if (!function_exists('ValidateUsernameRegex')) {
   function ValidateUsernameRegex() {
      static $ValidateUsernameRegex;
      
      if (is_null($ValidateUsernameRegex)) {
         $ValidateUsernameRegex = sprintf("[%s]%s",
            C("Garden.User.ValidationRegex","\d\w_"),
            C("Garden.User.ValidationLength","{3,20}"));
      }
      
      return $ValidateUsernameRegex;
   }
}

if (!function_exists('ValidateUsername')) {
   function ValidateUsername($Value, $Field = '') {
      $ValidateUsernameRegex = ValidateUsernameRegex();
      
      return ValidateRegex(
         $Value,
         "/^({$ValidateUsernameRegex})?$/siu"
      );
   }
}

if (!function_exists('ValidateUrlString')) {
   function ValidateUrlString($Value, $Field = '') {
      return ValidateRegex(
         $Value,
         '/^([\d\w_\-]+)?$/si'
      );
   }
}

if (!function_exists('ValidateUrlStringRelaxed')) {
   function ValidateUrlStringRelaxed($Value, $Field = '') {
      if (preg_match('`[/\\\<>\'"]`', $Value))
         return FALSE;
      return TRUE;
   }
}

if (!function_exists('ValidateDate')) {
   function ValidateDate($Value) {
      // Dates should be in YYYY-MM-DD or YYYY-MM-DD HH:MM:SS format
      if (empty($Value)) {
			return TRUE; // blank dates validated through required.
		} else {
			$Matches = array();
			if(preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:\s{1}(\d{2}):(\d{2})(?::(\d{2}))?)?$/', $Value, $Matches)) {
				$Year = $Matches[1];
				$Month = $Matches[2];
				$Day = $Matches[3];
				$Hour = ArrayValue(4, $Matches, 0);
				$Minutes = ArrayValue(5, $Matches, 0);
				$Seconds = ArrayValue(6, $Matches, 0);
			   
            return checkdate($Month, $Day, $Year) && $Hour < 24 && $Minutes < 61 && $Seconds < 61;
         }
      }

      return FALSE;
   }
}

if (!function_exists('ValidateMinimumAge')) {
   function ValidateMinimumAge($Value, $Field, $FormPostedValues) {
      $MinimumAge = C('Garden.Validate.MinimumAge', 13);
      // Dates should be in YYYY-MM-DD format
      if (preg_match("/^[\d]{4}-{1}[\d]{2}-{1}[\d]{2}$/", $Value) == 1) {
         $Year = intval(substr($Value, 0, 4));
         $Month = intval(substr($Value, 5, 2));
         $Day = intval(substr($Value, 8));
         $CurrentDay = date('j');
         $CurrentMonth = date('n');
         $CurrentYear = date('Y');
         // The minimum age for joining is 13 years before now.
         if ($Year + $MinimumAge < $CurrentYear
            || ($Year + $MinimumAge == $CurrentYear && $Month < $CurrentMonth)
            || ($Year + $MinimumAge == $CurrentYear && $Month == $CurrentMonth && $Day <= $CurrentDay))
            return TRUE;
      }
      return T('ValidateMinimumAge', 'You must be at least ' . $MinimumAge . ' years old to proceed.');
   }
}

if (!function_exists('ValidateInteger')) {
   function ValidateInteger($Value, $Field = NULL) {
      if (!$Value || (is_string($Value) && !trim($Value)))
         return TRUE;

      $Integer = intval($Value);
      $String = strval($Integer);
      return $String == $Value ? TRUE : FALSE;
   }
}

if (!function_exists('ValidateBoolean')) {
   function ValidateBoolean($Value, $Field) {
      $String = strval($Value);
      return in_array($String, array('1', '0', 'TRUE', 'FALSE', '')) ? TRUE : FALSE;
   }
}

if (!function_exists('ValidateDecimal')) {
   function ValidateDecimal($Value, $Field) {
       if (is_object($Field) && $Field->AllowNull && $Value === NULL) return TRUE;
       return is_numeric($Value);
   }
}

if (!function_exists('ValidateTime')) {
   function ValidateTime($Value, $Field) {
      // TODO: VALIDATE AS HH:MM:SS OR HH:MM
      return FALSE;
   }
}

if (!function_exists('ValidateTimestamp')) {
   function ValidateTimestamp($Value, $Field) {
      // TODO: VALIDATE A TIMESTAMP
      return FALSE;
   }
}

if (!function_exists('ValidateLength')) {
   function ValidateLength($Value, $Field) {
      if (function_exists('mb_strlen'))
         $Diff = mb_strlen($Value, 'UTF-8') - $Field->Length;
      else
         $Diff = strlen($Value) - $Field->Length;
         
      if ($Diff <= 0) {
         return TRUE;
      } else {
         return sprintf(T('ValidateLength'), T($Field->Name), $Diff);
      }
   }
}

if (!function_exists('ValidateEnum')) {
   function ValidateEnum($Value, $Field) {
      return (in_array($Value, $Field->Enum) || ($Field->AllowNull && is_null($Value)));
   }
}

if (!function_exists('ValidateFormat')) {
   function ValidateFormat($Value) {
      return strcasecmp($Value, 'Raw') != 0 || Gdn::Session()->CheckPermission('Garden.Settings.Manage');
   }
}

if (!function_exists('ValidateOneOrMoreArrayItemRequired')) {
   function ValidateOneOrMoreArrayItemRequired($Value, $Field) {
      return is_array($Value) === TRUE && count($Value) > 0 ? TRUE : FALSE;
   }
}

if (!function_exists('ValidatePermissionFormat')) {
   function ValidatePermissionFormat($Permission) {
      // Make sure there are at least three "parts" to each permission.
      if (is_array($Permission) === FALSE)
         $Permission = explode(',', $Permission);

      $PermissionCount = count($Permission);
      for ($i = 0; $i < $PermissionCount; ++$i) {
         if (count(explode('.', $Permission[$i])) < 3)
            return sprintf(T('The following permission did not meet the permission naming requirements and could not be added: %s'), $Permission[$i]);

      }
      return TRUE;
   }
}

if (!function_exists('ValidateMatch')) {
   /**
    * Takes the FieldName being validated, appends "Match" to it, and searches
    * $PostedFields for the Match fieldname, compares their values, and returns
    * true if they match.
    */
   function ValidateMatch($Value, $Field, $PostedFields) {
      $MatchValue = ArrayValue($Field->Name.'Match', $PostedFields);
      return $Value == $MatchValue ? TRUE : FALSE;
   }
}

if (!function_exists('ValidateStrength')) {
   /**
    * Validate a password's strength
    * 
    * @param string $Value
    * @param string $Field
    * @param array $FormValues
    */
   function ValidateStrength($Value, $Field, $PostedFields) {
      $UsernameValue = GetValue('Name', $PostedFields);
      $PScore = PasswordStrength($Value, $UsernameValue);
      return $PScore['Pass'] ? TRUE : FALSE;
   }
}

if (!function_exists('ValidateVersion')) {
   function ValidateVersion($Value) {
      if (empty($Value))
         return TRUE;

      if (preg_match('`(?:\d+\.)*\d+\s*([a-z]*)\d*`i', $Value, $Matches)) {
         // Get the version word out of the matches and validate it.
         $Word = $Matches[1];
         if (!in_array(trim($Word), array('', 'dev', 'alpha', 'a', 'beta', 'b', 'RC', 'rc', '#', 'pl', 'p')))
         	return FALSE;
         return TRUE;
      }
      return FALSE;
   }
}

/**
 * Validate phone number against North American Numbering Plan.
 * @link http://blog.stevenlevithan.com/archives/validate-phone-number
 */
if (!function_exists('ValidatePhoneNA')) {
   function ValidatePhoneNA($Value, $Field = '') {
      if ($Value == '')
         return true; // Do not require by default.
      $Valid = ValidateRegex($Value, '/^(?:\+?1[-. ]?)?\(?([0-9]{3})\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/');
      return ($Valid) ? $Valid : T('ValidatePhone', 'Phone number is invalid.');
   }
}

/**
 * Loose validation for international phone number (but must start with a plus sign).
 */
if (!function_exists('ValidatePhoneInt')) {
   function ValidatePhoneInt($Value, $Field = '') {
      if ($Value == '')
         return true; // Do not require by default.
      $Valid = ValidateRegex($Value, '/^\+(?:[0-9] ?){6,14}[0-9]$/');
      return ($Valid) ? $Valid : T('ValidatePhone', 'Phone number is invalid.');
   }
}

/**
 * Validate US zip code (5-digit or 9-digit with hyphen).
 */
if (!function_exists('ValidateZipCode')) {
   function ValidateZipCode($Value, $Field = '') {
      if ($Value == '')
         return true; // Do not require by default.
      $Valid = ValidateRegex($Value, '/^([0-9]{5})(-[0-9]{4})?$/');
      return ($Valid) ? $Valid : T('ValidateZipCode', 'Zip code is invalid.');
   }
}
