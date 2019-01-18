<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Vanilla\Formatting\Exception\FormatterNotFoundException;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\Formats;

/**
 * Validates body fields to make sure it complies with its format.
 */
class BodyFormatValidator {
    private $validators = [];

    /** @var FormatService */
    private $formatService;

    /**
     * BodyFormatValidator constructor.
     *
     * @param FormatService $formatService
     */
    public function __construct(FormatService $formatService) {
        $this->validators = [
            'rich' => [$this, 'validateRich'],
        ];
        $this->formatService = $formatService;
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
        try {
            $richFormatter = $this->formatService->getFormatter(Formats\RichFormat::FORMAT_KEY);
            $result = $richFormatter->filter($value);
        } catch (FormattingException $e) {
            $result = new Invalid($e->getMessage());
        } catch (FormatterNotFoundException $e) {
            $result = new Invalid($e->getMessage());
        }

        return $result;
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
