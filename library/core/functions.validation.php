<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
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
 * @package Garden
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

if (!function_exists('ValidateUsername')) {
   function ValidateUsername($Value, $Field = '') {
      return ValidateRegex(
         $Value,
         '/^([\d\w_]{3,20})?$/si'
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

if (!function_exists('ValidateDate')) {
   function ValidateDate($Value) {
      // Dates should be in YYYY-MM-DD or YYYY-MM-DD HH:MM:SS format
      if (strlen($Value) == 0) {
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
      // Dates should be in YYYY-MM-DD format
      if (preg_match("/^[\d]{4}-{1}[\d]{2}-{1}[\d]{2}$/", $Value) == 1) {
         $Year = intval(substr($Value, 0, 4));
         $Month = intval(substr($Value, 5, 2));
         $Day = intval(substr($Value, 8));
         // The minimum age for joining is 13 years before now.
         $CurrentDay = date('j');
         $CurrentMonth = date('n');
         $CurrentYear = date('Y');
         if ($Year + 13 < $CurrentYear
            || ($Year + 13 == $CurrentYear && $Month < $CurrentMonth)
            || ($Year + 13 == $CurrentYear && $Month == $CurrentMonth && $Day <= $CurrentDay))
            return TRUE;

      }

      return FALSE;
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
      return in_array($Value, $Field->Enum);
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

if (!function_exists('ValidateVersion')) {
   function ValidateVersion($Value) {
      if (empty($Value))
         return TRUE;

      if (preg_match('`(?:\d+\.)*\d+\s*(\w*)\d*`', $Value, $Matches)) {
         // Get the version word out of the matches and validate it.
         $Word = $Matches[1];
         if (!in_array(trim($Word), array('', 'dev', 'alpha', 'a', 'beta', 'b', 'RC', 'rc', '#', 'pl', 'p')))
         	return FALSE;
         return TRUE;
      }
      return FALSE;
   }
}
