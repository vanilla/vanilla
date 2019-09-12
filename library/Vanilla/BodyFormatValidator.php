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

    /** @var FormatService */
    private $formatService;

    /**
     * BodyFormatValidator constructor.
     *
     * @param FormatService $formatService
     */
    public function __construct(FormatService $formatService) {
        $this->formatService = $formatService;
    }

    /**
     * Validate richly formatted text.
     *
     * @param string $value The value to validate.
     * @param string $format The format of the field.
     * @return string|Invalid Returns the re-encoded string on success or `Invalid` on failure.
     */
    private function validate($value, string $format) {
        try {
            $result = $this->formatService->filter($value, $format);
        } catch (FormattingException $e) {
            $result = new Invalid($e->getMessage());
        } catch (FormatterNotFoundException $e) {
            // For backwards compatibility unknown formatters are allowed in this validator.
            // The old models handle their own format validation where necessary.
            trigger_error(
                "Could not validate format $format. A formatter was not found."
                . "This is currently a warning, but may be an exception in the future.\n"
                . "To fix this, implement `Vanilla\Contracts\Formatting\FormatInterface` for $format "
                . "and register it with the `Vanilla\Formatting\FormatService`",
                E_USER_WARNING
            );
            return $value;
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
        $format = strtolower($row['Format'] ?? Formats\TextFormat::FORMAT_KEY);
        $valid = $this->validate($value, $format);

        return $valid;
    }
}
