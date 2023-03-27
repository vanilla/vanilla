<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Vanilla\Utility\StringUtils;

/**
 * Custom validation for role request schemas.
 */
class RoleRequestValidation extends Validation
{
    /** @var Schema */
    private $schema;

    /**
     * DI.
     *
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
        $this->setTranslateFieldNames(true);
    }

    /**
     * Translate an error message string.
     *
     * @param string $str
     * @return string
     */
    public function translate($str)
    {
        $field = $this->schema->getField(["properties", $str]);
        if (is_array($field)) {
            $str = $field["x-label"] ?? StringUtils::labelize($str);
        }
        $r = t($str);
        return $r;
    }
}
