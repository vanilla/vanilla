<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

/**
 * Validates body fields to make sure it complies with its format.
 */
class BodyFormatValidator {
    private $validators = [];

    /**
     * BodyFormatValidator constructor.
     */
    public function __construct() {
        $this->validators = [
            'rich' => [$this, 'validateRich'],
        ];
    }

    /**
     * Add a validator for a specific format.
     *
     * The validator must be a callable with the following signature:
     *
     * ```
     * function validate($value, $field, $row = []): mixed|Invalid
     * ```
     *
     * The validator will return value, optionally filtered on success or an instance of `Vanilla\Invalid` on failure.
     *
     * Adding a validator to a format that already exists will replace the existing validator.
     *
     * @param string $format The format to validate.
     * @param callable|null $validator The validation function.
     * @return $this
     */
    public function addFormatValidator(string $format, callable $validator = null) {
        $this->validators[strtolower($format)] = $validator;
        return $this;
    }

    /**
     * Validate richly formatted text.
     *
     * @param string $value The value to validate.
     * @param object $field The field meta data of the value.
     * @param array $row The entire row where the field is.
     * @return string|Invalid Returns the re-encoded string on success or `Invalid` on failure.
     */
    private function validateRich($value, $field, $row = []) {
        $value = json_decode($value, true);
        if ($value === null) {
            $value = new Invalid("%s is not valid rich text.");
        } else {
            // Re-encode the value to escape unicode values.
            $this->stripUselessEmbedData($value);
            $value = json_encode($value);
        }

        return $value;
    }

    /**
     * There is certain embed data from the rich editor that we want to strip out. This includes
     *
     * - Malformed partially formed operations (dataPromise).
     * - Nested embed data.
     *
     * @param array[] $operations The quill operations to loop through.
     */
    private function stripUselessEmbedData(array &$operations) {
        foreach($operations as $key => $op) {
            // If a dataPromise is still stored on the embed, that means it never loaded properly on the client.
            $dataPromise = $op['insert']['embed-external']['dataPromise'] ?? null;
            if ($dataPromise !== null) {
                unset($operations[$key]);
            }

            // Remove nested external embed data. We don't want it rendered and this will prevent it from being
            // searched.
            $format = $op['insert']['embed-external']['data']['format'] ?? null;
            if ($format === 'Rich') {
                $bodyRaw = $op['insert']['embed-external']['data']['bodyRaw'] ?? null;
                if (is_array($bodyRaw)) {
                    foreach ($bodyRaw as $subInsertIndex => &$subInsertOp) {
                        $externalEmbed = $operations[$key]['insert']['embed-external']['data']['bodyRaw'][$subInsertIndex]['insert']['embed-external'] ?? null;
                        if ($externalEmbed !== null)  {
                            unset($operations[$key]['insert']['embed-external']['data']['bodyRaw'][$subInsertIndex]['insert']['embed-external']['data']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Validate a body field against its format.
     *
     * @param string $value The value to validate.
     * @param object $field The field meta data of the value.
     * @param array $row The entire row where the field is.
     * @return string|Invalid Returns the valid string on success or `Invalid` on failure.
     */
    public function __invoke($value, $field, $row = []) {
        $format = strtolower($row['Format'] ?? 'raw');

        if (isset($this->validators[$format])) {
            $valid = call_user_func($this->validators[$format], $value, $field, $row);
        } else {
            $valid = $value;
        }

        return $valid;
    }
}
