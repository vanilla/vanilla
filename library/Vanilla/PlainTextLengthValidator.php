<?php
/**
 * @author Patrick Kelly <patrick.k@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Vanilla\Invalid;
use Vanilla\Formatting\FormatService;
use Vanilla\Contracts\LocaleInterface;

/*
 * Validates the Body field length by stripping any formatting code.
 */
class PlainTextLengthValidator {

    /** @var LocaleInterface */
    private $locale;

    /** @var FormatService */
    private $formatService;

    /**
     * PlainTextLengthValidator constructor.
     *
     * @param FormatService $formatService Service to apply a formatter.
     * @param LocaleInterface $locale For translating error messages.
     */
    public function __construct(FormatService $formatService, LocaleInterface $locale) {
        $this->locale = $locale;
        $this->formatService = $formatService;
    }

    /**
     * Validate content length by stripping most meta-data and formatting.
     *
     * @param string $value User input content.
     * @param object $field Properties of field where content is found.
     * @param array $post POST array.
     * @return mixed Either an Invalid Object or the value.
     */
    private function validate($value, $field, $post) {
        $format = $post['Format'] ?? '';
        if (!$format) {
            $noFormatError = $this->locale->translate('%s Not Found');
            return new Invalid(sprintf($noFormatError, 'Format'));
        }

        $maxPlainTextLength = $field->maxPlainTextLength ?? null;
        if (!is_numeric($maxPlainTextLength)) {
            throw new \InvalidArgumentException("Invalid max plain-text length specified in field schema.");
        }

        $plainTextLength = $this->formatService->getPlainTextLength($value, $format);
        if ($plainTextLength <= $maxPlainTextLength) {
            return $value;
        } else {
            $validationMessage = $this->locale->translate('ValidateLength' ?? '');
            $fieldName = $this->locale->translate($field->Name ?? '');
            $diff = $plainTextLength - $maxPlainTextLength;
            return new Invalid(sprintf($validationMessage, $fieldName, abs($diff)));
        }
    }

    /**
     * Execute validate function for visible text validator.
     *
     * @param string $value User input content.
     * @param object $field Properties of field where content is found.
     * @param array $row POST array.
     * @return mixed Either an Invalid Object or the value.
     */
    public function __invoke($value, $field, $row = []) {
        return $this->validate($value, $field, $row);
    }
}
