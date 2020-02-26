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
use Vanilla\Contracts\ConfigurationInterface;

/*
 * Validates the Body field length by stripping any formatting code.
 */
class VisibleTextLengthValidator {

    /** @var LocaleInterface */
    private $locale;

    /** @var FormatService */
    private $formatService;

    /** @var int */
    private $maxTextLength;

    /**
     * VisibleTextLengthValidator constructor.
     *
     * @param int $maxTextLength Maximum length of text.
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
        $stringLength = $this->formatService->getVisibleTextLength($value, $format);
        $diff = $stringLength - ($field->maxTextLength ?? $this->getMaxTextLength());
        if ($diff <= 0) {
            return $value;
        } else {
            $validationMessage = $this->locale->translate('ValidateLength' ?? '');
            $fieldName = $this->locale->translate($field->Name ?? '');
            return new Invalid(sprintf($validationMessage, $fieldName, abs($diff)));
        }
    }

    /**
     * Get the Maximum Text Length.
     *
     * @return int
     */
    public function getMaxTextLength(): int {
        if (!$this->maxTextLength) {
            $config = \Gdn::getContainer()->get(ConfigurationInterface::class);
            $this->maxTextLength = $config->get('Vanilla.Comment.MaxLength');
        }
        return $this->maxTextLength;
    }

    /**
     * Set the Maximum Text Length.
     *
     * @param int $maxTextLength
     * @return void
     */
    public function setMaxTextLength(int $maxTextLength): void {
        $this->maxTextLength = $maxTextLength;
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
