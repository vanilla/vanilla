<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden;


use Garden\Exception\ValidationException;

/**
 * An class for collecting validation errors.
 */
class Validation {
    /// Properties ///

    protected $errors = [];

    protected $mainMessage;

    protected $status;

    /// Methods ///

    /**
     * Initialize an instance of the {@link Validation} class.
     *
     * @param array $errors An array of errors.
     * @param string $mainMessage The main message of the error.
     * @param int $status The http status code of the error or 0 to build the status code from the indivdual errors.
     */
    public function __construct(array $errors = [], $mainMessage = '', $status = 0) {
        $this->errors = $errors;
        $this->mainMessage = $mainMessage;
        $this->status = $status;
    }

    /**
     * Gets the error message from an error.
     *
     * Errors are stored with either a message or a translation code.
     * This method will look at both to determine the full message.
     *
     * @param array $error The error array.
     * @return string Returns the message from the error.
     */
    public static function errorMessage(array $error) {
        if (isset($error['message'])) {
            return $error['message'];
        } else {
            $field = val('field', $error, '*');
            if (is_array($field)) {
                $field = implode(', ', $field);
            }
            return sprintft($error['code'].': %s.', $field);
        }
    }

    /**
     * Add an error.
     *
     * @param string $messageCode The message translation code.
     * If you add a message that starts with "@" then no translation will take place.
     * @param string|array $field The name of the field to add or an array of fields if the error applies to
     * more than one field.
     * @param int|array $options An array of additional information to add to the error entry or a numeric error code.
     * @return Validation Returns $this for fluent calls.
     */
    public function addError($messageCode, $field = '*', $options = []) {
        $error = [];
        if (substr($messageCode, 0, 1) === '@') {
            $error['message'] = substr($messageCode, 1);
        } else {
            $error['code'] = $messageCode;
        }
        if (is_array($field)) {
            $fieldname = array_select(['path', 'name'], $field);

            if ($fieldname) {
                // This is a full field object.
                $fieldKey = $fieldname;
                $error['field'] = $fieldKey;
            } else {
                $fieldKey = '*';
                $error['field'] = $field;
            }
        } else {
            $fieldKey = $field;
            if ($field !== '*') {
                $error['field'] = $field;
            }
        }

        if (is_array($options)) {
            $error += $options;
        } else if (is_int($options)) {
            $error['status'] = $options;
        }

        $this->errors[$fieldKey][] = $error;

        return $this;
    }

    /**
     * Gets the main error message for the validation.
     *
     * @param string|null $value Pass a new main message or null to get the current main message.
     * @return Validation|string Returns the main message or $this for fluent sets.
     */
    public function mainMessage($value = null) {
        if ($value !== null) {
            $this->mainMessage = $value;
            return $this;
        }

        return $this->mainMessage;
    }

    /**
     * Get or set the error status code.
     *
     * The status code is an http resonse code and should be of the 4xx variety.
     *
     * @param int|null $value Pass a new status code or null to get the current code.
     * @return Validation|int Returns the current status code or $this for fluent sets.
     */
    public function status($value = null) {
        if ($value !== null) {
            $this->status = $value;
            return $this;
        }
        if ($this->status) {
            return $this->status;
        }

        // There was no status so loop through the errors and look for the highest one.
        $maxStatus = 400;
        foreach ($this->errors as $field => $errors) {
            foreach ($errors as $error) {
                if (isset($error['status']) && $error['status'] > $maxStatus) {
                    $maxStatus = $error['status'];
                }
            }
        }
        return $maxStatus;
    }

    /**
     * Get the message for this exception.
     *
     * @return string Returns the exception message.
     */
    public function getMessage() {
        if ($this->mainMessage) {
            return $this->mainMessage;
        }

        // Generate the message by concatenating all of the errors together.
        $messages = [];
        foreach ($this->errors as $errors) {
            foreach ($errors as $error) {
                $field = val('field', $error, '*');
                if (is_array($field)) {
                    $field = implode(', ', $field);
                }

                if (isset($error['message'])) {
                    $message = $error['message'];
                } elseif (strpos($error['code'], '%s') === false) {
                    $message = sprintft($error['code'].': %s.', $field);
                } else {
                    $message = sprintft($error['code'], $field);
                }

                $messages[] = $message;
            }
        }
        return implode(' ', $messages);
    }

    /**
     * Gets all of the errors as a flat array.
     *
     * The errors are internally stored indexed by field. This method flattens them for final error returns.
     *
     * @return array Returns all of the errors.
     */
    public function getErrorsFlat() {
        $result = [];
        foreach ($this->errors as $errors) {
            foreach ($errors as $error) {
                $result[] = $error;
            }
        }
        return $result;
    }

    /**
     * Check whether or not the validation is free of errors.
     *
     * @return bool Returns true if there are no errors, false otherwise.
     */
    public function isValid() {
        return count($this->errors) === 0;
    }

    /**
     * Check whether or not a particular field is has errors.
     *
     * @param string $field The name of the field to check for validity.
     * @return bool Returns true if the field has no errors, false otherwise.
     */
    public function fieldValid($field) {
        $result = !isset($this->errors[$field]) || count($this->errors[$field]) === 0;
        return $result;
    }
}
