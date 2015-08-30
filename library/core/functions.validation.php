<?php
/**
 * Validation functions
 *
 * All of these functions are used by ./class.validation.php to validate form
 * input strings. With the exception of ValidateRegex, each function receives
 * two parameters (the field value and the related database field properties)
 * and is expected to return a boolean true or false indicating if the
 * validation was successful.
 *
 * Note: $field will be an object of field properties as defined in
 * @@MySQLDriver->_FetchTableSchema (at the bottom of the file). Properties
 * are: (string) Name, (bool) PrimaryKey, (string) Type, (bool) AllowNull,
 * (string) Default, (int) Length, (array) Enum.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

if (!function_exists('ValidateCaptcha')) {
    /**
     * Validate the request captcha.
     *
     * @param null $value Not used.
     * @return bool Returns true if the captcha is valid or an error message otherwise.
     */
    function validateCaptcha($value = null) {
        require_once PATH_LIBRARY.'/vendors/recaptcha/functions.recaptchalib.php';

        $CaptchaPrivateKey = C('Garden.Registration.CaptchaPrivateKey', '');
        $Response = recaptcha_check_answer(
            $CaptchaPrivateKey,
            Gdn::Request()->IpAddress(),
            Gdn::Request()->Post('recaptcha_challenge_field', ''),
            Gdn::Request()->Post('recaptcha_response_field', '')
        );
        return $Response->is_valid ? true : 'The reCAPTCHA value was not entered correctly. Please try again.';
    }
}

if (!function_exists('ValidateRegex')) {
    /**
     * Validate a value against a regular expression.
     *
     * @param string $value The value to validate.
     * @param string $regex The regular expression to validate against.
     * @return bool Returns true if the value validates or false otherwise.
     */
    function validateRegex($value, $regex) {
        return (filter_var($value, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $regex))) !== false);
    }
}

if (!function_exists('ValidateRequired')) {
    /**
     * Validate that a required value isn't empty.
     *
     * @param mixed $value The value to validate.
     * @param object|array|null $field The field object to validate the value against.
     * @return bool Returns true if the value validates or false otherwise.
     */
    function validateRequired($value, $field = null) {
        if (is_array($value) === true) {
            return count($value) > 0;
        }

        if (is_string($value)) {
            // Empty strings should pass if the default value of the field is an empty string.
            if ($value === '' && val('Default', $field, null) === '') {
                return true;
            }

            return trim($value) == '' ? false : true;
        }

        if (is_numeric($value)) {
            return true;
        }

        return false;
    }
}

if (!function_exists('ValidateMeAction')) {
    /**
     * Validate that a string is a valid "me" action.
     *
     * @param mixed $value The value to validate.
     * @return bool|string Returns true if the value is valid or an error message otherwise.
     */
    function validateMeAction($value) {
        $Matched = preg_match('`^/me .*`i', $value);
        if ($Matched) {
            $HasPermission = Gdn::Session()->CheckPermission('Vanilla.Comments.Me');
            if (!$HasPermission) {
                return T('ErrorPermission');
            }
        }
        return true;
    }
}

if (!function_exists('validateNoLinks')) {
    /**
     * Make sure a string has no links.
     *
     * @param mixed $value The value to validate.
     * @return bool Returns true if the value validates or false otherwise.
     * @since 2.1
     */
    function validateNoLinks($value) {
        $Matched = preg_match('`https?://`i', $value);
        return !$Matched;
    }
}

if (!function_exists('validateRequiredArray')) {
    /**
     * Validate that a value is an array and is not empty.
     *
     * Checkbox lists and DropDown lists that have no values selected return a
     * value of false. Since this could be a valid entry in any other kind of
     * input, these "array" form-data types need their own "required" validation
     * method.
     *
     * @param mixed $value The value to validate.
     * @return bool Returns true if the value validates or false otherwise.
     */
    function validateRequiredArray($value) {
        if (is_array($value) === true) {
            return !empty($value);
        }

        return false;
    }
}

if (!function_exists('validateConnection')) {
    /**
     * Validate an that an array contains valid database information.
     *
     * @param mixed $value The value to validate.
     * @param mixed $field Not used.
     * @param array $data The data to validate against.
     * @return bool|string Returns true if the value is valid or an error message otherwise.
     * @deprecated
     */
    function validateConnection($value, $field, $data) {
        $DatabaseHost = ArrayValue('Database.Host', $data, '~~Invalid~~');
        $DatabaseName = ArrayValue('Database.Name', $data, '~~Invalid~~');
        $DatabaseUser = ArrayValue('Database.User', $data, '~~Invalid~~');
        $DatabasePassword = ArrayValue('Database.Password', $data, '~~Invalid~~');
        $ConnectionString = getConnectionString($DatabaseName, $DatabaseHost);
        try {
            $Connection = new PDO(
                $ConnectionString,
                $DatabaseUser,
                $DatabasePassword
            );
        } catch (PDOException $Exception) {
            return sprintf(T('ValidateConnection'), strip_tags($Exception->getMessage()));
        }
        return true;
    }
}

if (!function_exists('validateOldPassword')) {
    /**
     * Validate that a password authenticates against a user.
     *
     * @param mixed $value Not used.
     * @param mixed $field Not used.
     * @param array $data The data to validate.
     * @return bool Returns true if the value validates or false otherwise.
     */
    function validateOldPassword($value, $field, $data) {
        $OldPassword = ArrayValue('OldPassword', $data, '');
        $Session = Gdn::Session();
        $UserModel = new UserModel();
        $UserID = $Session->UserID;
        return (bool)$UserModel->validateCredentials('', $UserID, $OldPassword);
    }
}

if (!function_exists('validateEmail')) {
    /**
     * Validate that a value is a valid email address.
     *
     * @param mixed $value The value to validate.
     * @param object|array|null $field The field meta information for {@link $value}.
     * @return bool Returns true if the value validates or false otherwise.
     */
    function validateEmail($value, $field = null) {
        if (!validateRequired($value, $field)) {
            return true;
        }

        return (filter_var($value, FILTER_VALIDATE_EMAIL) !== false);
    }
}

if (!function_exists('validateWebAddress')) {
    /**
     * Validate that a value is a valid URL.
     *
     * @param mixed $value The value to validate.
     * @return bool Returns true if the value validates or false otherwise.
     */
    function validateWebAddress($value) {
        if ($value == '') {
            return true; // Required picks up this error
        }

        return filter_var($value, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED) !== false;
    }
}

if (!function_exists('validateUsernameRegex')) {
    /**
     * Get the regular expression used to validate usernames.
     *
     * @return string Returns a regular expression without enclosing delimiters.
     */
    function validateUsernameRegex() {
        static $ValidateUsernameRegex;

        if (is_null($ValidateUsernameRegex)) {
            // Set our default ValidationRegex based on Unicode support.
            // Unicode includes Numbers, Letters, Marks, & Connector punctuation.
            $DefaultPattern = (unicodeRegexSupport()) ? '\pN\pL\pM\pPc' : '\w';

            $ValidateUsernameRegex = sprintf(
                "[%s]%s",
                C("Garden.User.ValidationRegex", $DefaultPattern),
                C("Garden.User.ValidationLength", "{3,20}")
            );
        }

        return $ValidateUsernameRegex;
    }
}

if (!function_exists('validateUsername')) {
    /**
     * Validate the a string is valid for use as a username.
     *
     * @param mixed $value The value to validate.
     * @return bool Returns true if the value validates or false otherwise.
     */
    function validateUsername($value) {
        $ValidateUsernameRegex = ValidateUsernameRegex();

        return ValidateRegex(
            $value,
            "/^({$ValidateUsernameRegex})?$/siu"
        );
    }
}

if (!function_exists('validateUrlString')) {
    /**
     * Validate that a string can be used in a URL without any encoding required.
     *
     * @param mixed $value The value to validate.
     * @return bool Returns true if the value validates or false otherwise.
     */
    function validateUrlString($value) {
        return validateRegex(
            $value,
            '/^([\d\w_\-]+)?$/si'
        );
    }
}

if (!function_exists('validateUrlStringRelaxed')) {
    /**
     * A relaxed version of {@link validateUrlString()} that only requires no path separators or tag delimiters.
     *
     * @param mixed $value The value to validate.
     * @return bool Returns true if the value validates or false otherwise.
     */
    function validateUrlStringRelaxed($value) {
        if (preg_match('`[/\\\<>\'"]`', $value)) {
            return false;
        }
        return true;
    }
}

if (!function_exists('validateDate')) {
    /**
     * Validate that a value is a valid date string.
     *
     * @param mixed $value The value to validate.
     * @return bool Returns true if the value validates or false otherwise.
     */
    function validateDate($value) {
        // Dates should be in YYYY-MM-DD or YYYY-MM-DD HH:MM:SS format
        if (empty($value)) {
            return true; // blank dates validated through required.
        } else {
            $Matches = array();
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:\s{1}(\d{2}):(\d{2})(?::(\d{2}))?)?$/', $value, $Matches)) {
                $Year = $Matches[1];
                $Month = $Matches[2];
                $Day = $Matches[3];
                $Hour = ArrayValue(4, $Matches, 0);
                $Minutes = ArrayValue(5, $Matches, 0);
                $Seconds = ArrayValue(6, $Matches, 0);

                return checkdate($Month, $Day, $Year) && $Hour < 24 && $Minutes < 61 && $Seconds < 61;
            }
        }

        return false;
    }
}

if (!function_exists('validateMinimumAge')) {
    /**
     * Validate that a value passes minimum age requirements.
     *
     * The minimum age is stored in the `Garden.Validate.MinimumAge` config setting.
     *
     * @param mixed $value The value to validate.
     * @return bool|string Returns true if the value is valid or an error message otherwise.
     */
    function validateMinimumAge($value) {
        $MinimumAge = C('Garden.Validate.MinimumAge', 13);
        // Dates should be in YYYY-MM-DD format
        if (preg_match("/^[\d]{4}-{1}[\d]{2}-{1}[\d]{2}$/", $value) == 1) {
            $Year = intval(substr($value, 0, 4));
            $Month = intval(substr($value, 5, 2));
            $Day = intval(substr($value, 8));
            $CurrentDay = date('j');
            $CurrentMonth = date('n');
            $CurrentYear = date('Y');
            // The minimum age for joining is 13 years before now.
            if ($Year + $MinimumAge < $CurrentYear
                || ($Year + $MinimumAge == $CurrentYear && $Month < $CurrentMonth)
                || ($Year + $MinimumAge == $CurrentYear && $Month == $CurrentMonth && $Day <= $CurrentDay)
            ) {
                return true;
            }
        }
        return T('ValidateMinimumAge', 'You must be at least '.$MinimumAge.' years old to proceed.');
    }
}

if (!function_exists('validateInteger')) {
    /**
     * Validate that a value can be converted into an integer.
     *
     * @param mixed $value The value to validate.
     * @return bool Returns true if the value validates or false otherwise.
     */
    function validateInteger($value) {
        if (!$value || (is_string($value) && !trim($value))) {
            return true;
        }
        $Integer = intval($value);
        $String = strval($Integer);
        return $String == $value;
    }
}

if (!function_exists('validateBoolean')) {
    /**
     * Validate that a value can be converted into a boolean (true or false).
     *
     * @param mixed $value The value to validate.
     * @return bool Returns true if the value validates or false otherwise.
     */
    function validateBoolean($value) {
        $String = strval($value);
        return in_array($String, array('1', '0', 'true', 'false', '')) ? true : false;
    }
}

if (!function_exists('validateDecimal')) {
    /**
     * Validate that a value can be converted into a decimal number.
     *
     * @param mixed $value The value to validate.
     * @param object $field The field information for the value.
     * @return bool Returns true if the value validates or false otherwise.
     */
    function validateDecimal($value, $field) {
        if (is_object($field) && $field->AllowNull && $value === null) {
            return true;
        }
        return is_numeric($value);
    }
}

if (!function_exists('validateString')) {
    /**
     * Validate that a value can be converted into a string.
     *
     * This function will pass on numbers or booleans because those values can be converted to a string.
     *
     * @param mixed $value The value to validate.
     * @param object $field The database field object to validate against.
     * @return bool Returns true if {@link $value} is a valid string.
     */
    function validateString($value, $field) {
        if (!$value || (is_string($value) && !trim($value))) {
            return true;
        }

        return is_scalar($value);
    }
}

if (!function_exists('validateTime')) {
    /**
     * Validate that a value can be converted into a time string.
     *
     * @param mixed $value The value to validate.
     * @return bool Returns true if the value validates or false otherwise.
     */
    function validateTime($value) {
        // TODO: VALIDATE AS HH:MM:SS OR HH:MM
        return false;
    }
}

if (!function_exists('validateTimestamp')) {
    /**
     * Validate that a value is a timestamp.
     *
     * @param mixed $value The value to validate.
     * @return bool Returns true if the value validates or false otherwise.
     */
    function validateTimestamp($value) {
        // TODO: VALIDATE A TIMESTAMP
        return false;
    }
}

if (!function_exists('validateLength')) {
    /**
     * Validate that a string is not too long.
     *
     * @param mixed $value The value to validate.
     * @param object $field The field information that contains the maximum length for the {@link $value}.
     * @return bool|string
     */
    function validateLength($value, $field) {
        if (function_exists('mb_strlen')) {
            $Diff = mb_strlen($value, 'UTF-8') - $field->Length;
        } else {
            $Diff = strlen($value) - $field->Length;
        }

        if ($Diff <= 0) {
            return true;
        } else {
            return sprintf(T('ValidateLength'), T($field->Name), $Diff);
        }
    }
}

if (!function_exists('validateEnum')) {
    /**
     * Validate that a value is one of the values allowed in an enum.
     *
     * @param mixed $value The value to validate.
     * @param object $field The object must contain an `Enum` property which is an array of valid choices for
     * {@link $value}.
     * @return bool Returns true of the value is valid or false otherwise.
     */
    function validateEnum($value, $field) {
        return (in_array($value, $field->Enum) || ($field->AllowNull && !validateRequired($value)));
    }
}

if (!function_exists('validateFormat')) {
    /**
     * Check that a value is a correct format for {@link Gdn_Format::To()}.
     *
     * @param mixed $value The value to validate.
     * @return bool Returns true of the value is valid or false otherwise.
     */
    function validateFormat($value) {
        return strcasecmp($value, 'Raw') != 0 || Gdn::Session()->CheckPermission('Garden.Settings.Manage');
    }
}

if (!function_exists('validateOneOrMoreArrayItemRequired')) {
    /**
     * Check that a value is an array and isn't empty.
     *
     * @param mixed $value The value to validate.
     * @return bool Returns true of the value is valid or false otherwise.
     */
    function validateOneOrMoreArrayItemRequired($value) {
        return is_array($value) === true && !empty($value);
    }
}

if (!function_exists('validatePermissionFormat')) {
    /**
     * Validate that a string is in the correct format for a Vanilla permission.
     *
     * @param mixed $value The value to validate.
     * @return bool|string Returns true if the value is a valid permission name or a string with an error message
     * otherwise.
     */
    function validatePermissionFormat($value) {
        // Make sure there are at least three "parts" to each permission.
        if (is_array($value) === false) {
            $value = explode(',', $value);
        }

        $PermissionCount = count($value);
        for ($i = 0; $i < $PermissionCount; ++$i) {
            if (count(explode('.', $value[$i])) < 3) {
                return sprintf(
                    T('The following permission did not meet the permission naming requirements and could not be added: %s'),
                    $value[$i]
                );
            }

        }
        return true;
    }
}

if (!function_exists('validateMatch')) {
    /**
     * Validate a value in an odd and soon to be removed way.
     *
     * Takes the FieldName being validated, appends "Match" to it, and searches
     * $PostedFields for the Match fieldname, compares their values, and returns
     * true if they match.
     *
     * @param mixed $value The value to validate.
     * @param object $field The field meta information.
     * @param array $data The full posted data.
     * @return bool Returns true if the value is valid or false otherwise.
     * @deprecated
     */
    function validateMatch($value, $field, $data) {
        $MatchValue = ArrayValue($field->Name.'Match', $data);
        return $value == $MatchValue ? true : false;
    }
}

if (!function_exists('validateMinTextLength')) {
    /**
     * Validate that a value is at least a certain length after being converted to plain text.
     *
     * @param mixed $value The value to validate.
     * @param object $field The field meta information.
     * @param array $Post The full posted data.
     * @return bool|string Returns true if teh value is valid or an error string otherwise.
     */
    function validateMinTextLength($value, $field, $Post) {
        if (isset($Post['Format'])) {
            $value = Gdn_Format::To($value, $Post['Format']);
        }

        $value = html_entity_decode(trim(strip_tags($value)));
        $MinLength = GetValue('MinLength', $field, 0);

        if (function_exists('mb_strlen')) {
            $Diff = $MinLength - mb_strlen($value, 'UTF-8');
        } else {
            $Diff = $MinLength - strlen($value);
        }

        if ($Diff <= 0) {
            return true;
        } else {
            return sprintf(T('ValidateMinLength'), T($field->Name), $Diff);
        }
    }
}

if (!function_exists('validateStrength')) {
    /**
     * Validate a password's strength.
     *
     * @param string $value The value to validate.
     * @param object $field Not used.
     * @param array $data The full post data.
     * @return bool Returns true if the value represents a strong enough password or false otherwise.
     */
    function validateStrength($value, $field, $data) {
        $UsernameValue = GetValue('Name', $data);
        $PScore = PasswordStrength($value, $UsernameValue);
        return $PScore['Pass'] ? true : false;
    }
}

if (!function_exists('validateVersion')) {
    /**
     * Validates that a value is a correctly formatted version string.
     *
     * @param mixed $value The value to validate.
     * @return bool Returns true if the value represents a version string or false otherwise.
     */
    function validateVersion($value) {
        if (empty($value)) {
            return true;
        }

        if (preg_match('`(?:\d+\.)*\d+\s*([a-z]*)\d*`i', $value, $Matches)) {
            // Get the version word out of the matches and validate it.
            $Word = $Matches[1];
            if (!in_array(trim($Word), array('', 'dev', 'alpha', 'a', 'beta', 'b', 'RC', 'rc', '#', 'pl', 'p'))) {
                return false;
            }
            return true;
        }
        return false;
    }
}

if (!function_exists('validatePhoneNA')) {
    /**
     * Validate phone number against North American Numbering Plan.
     *
     * @param mixed $value The value to validate.
     * @return bool|string Returns true if the value is a valid phone number or an error string otherwise.
     * @link http://blog.stevenlevithan.com/archives/validate-phone-number
     */
    function validatePhoneNA($value) {
        if ($value == '') {
            return true; // Do not require by default.
        }
        $Valid = validateRegex($value, '/^(?:\+?1[-. ]?)?\(?([0-9]{3})\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/');
        return ($Valid) ? $Valid : T('ValidatePhone', 'Phone number is invalid.');
    }
}

if (!function_exists('validatePhoneInt')) {
    /**
     * Loose validation for international phone number (but must start with a plus sign).
     *
     * @param mixed $value The value to validate.
     * @return bool|string Returns true if the value is a valid phone number or an error string otherwise.
     */
    function validatePhoneInt($value) {
        if ($value == '') {
            return true; // Do not require by default.
        }
        $Valid = validateRegex($value, '/^\+(?:[0-9] ?){6,14}[0-9]$/');
        return ($Valid) ? $Valid : T('ValidatePhone', 'Phone number is invalid.');
    }
}

if (!function_exists('validateUrl')) {
    /**
     * Check to see if a value represents a valid url.
     *
     * @param string $value The value to validate.
     * @return bool Returns true if the value is a value url or false otherwise.
     */
    function validateUrl($value) {
        if (empty($value)) {
            return true;
        }
        $Valid = (bool)filter_var($value, FILTER_VALIDATE_URL);
        return $Valid;
    }
}

if (!function_exists('validateZipCode')) {
    /**
     * Validate US zip code (5-digit or 9-digit with hyphen).
     *
     * @param mixed $value The value to validate.
     * @return bool|string
     */
    function validateZipCode($value) {
        if ($value == '') {
            return true; // Do not require by default.
        }
        $Valid = validateRegex($value, '/^([0-9]{5})(-[0-9]{4})?$/');
        return ($Valid) ? $Valid : T('ValidateZipCode', 'Zip code is invalid.');
    }
}
