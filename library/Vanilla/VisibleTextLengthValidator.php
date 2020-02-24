<?php

namespace Vanilla;

use Vanilla\Invalid;
use Vanilla\Formatting\FormatService;
use Vanilla\Contracts\LocaleInterface;

class VisibleTextLengthValidator {

    /**
     * Validate content length by stripping most meta-data and formatting.
     *
     * @param $value
     * @param $field
     * @param $post
     * @return Invalid
     */
    private function validate($value, $field, $post){
        $format = $post['Format'] ?? '';
        $formatServices = \Gdn::formatService();
        $stringLength = $formatServices->getVisibleTextLength($value, $format);
        $diff = $stringLength - $field->maxTextLength;
        if ($diff <= 0) {
            return $value;
        } else {
            $locale = \Gdn::locale();
            $validationMessage = $locale->translate('ValidateLength' ?? '');
            $fieldName = $locale->translate($field->Name ?? '');
            return new Invalid(sprintf($validationMessage, $fieldName, abs($diff)));
        }
    }

    public function __invoke($value, $field, $row = []) {
        return $this->validate($value, $field, $row);
    }
}
