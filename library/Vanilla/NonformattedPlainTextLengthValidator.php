<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla;

use Vanilla\Formatting\Formats\TextFormat;

class NonformattedPlainTextLengthValidator extends PlainTextLengthValidator
{
    /**
     * Overrides base method to force format to text.
     *
     * {@inheritdoc}
     */
    protected function validate($value, $field, $post)
    {
        return parent::validate($value, $field, ["Format" => TextFormat::FORMAT_KEY] + $post);
    }
}
