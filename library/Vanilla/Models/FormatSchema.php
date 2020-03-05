<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Vanilla\FeatureFlagHelper;

/**
 * A utility class for format schemas.
 */
class FormatSchema extends Schema {
    /**
     * FormatSchema constructor.
     *
     * @param bool $deprecated Include deprecated formats.
     */
    public function __construct(bool $deprecated = false) {
        if (FeatureFlagHelper::featureEnabled('legacyFormats')) {
            // We can flip this feature flag if a site's data is really dirty.
            parent::__construct([
                'type' => 'string',
            ]);
        } else {
            $formats = ['rich', 'markdown', 'text', 'textex', 'wysiwyg', 'bbcode'];
            if ($deprecated) {
                $formats = array_merge($formats, [
                    'html',
                    'ipb',
                ]);
            }

            parent::__construct([
                'type' => 'string',
                'enum' => $formats,
            ]);
            $this->addFilter('', function ($value, ValidationField $field) {
                if (is_string($value)) {
                    return strtolower($value);
                }
                return $value;
            });
        }
    }
}
