<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;

/**
 * Utility class for generating validators that item(s) are an instance of something.
 */
class InstanceValidatorSchema extends Schema {

    /** @var string */
    private $classNames;

    /**
     * @param string|array $className
     */
    public function __construct($className) {
        parent::__construct();
        $this->classNames = is_array($className) ? $className : [$className];
        $this->addValidator("", [$this, 'validator']);
    }

    /**
     * A validator function for Garden schema that verifies a class is a particular instance.
     *
     * @param mixed $value The value to validate.
     * @param ValidationField $field The validation field to report errors on.
     *
     * @return bool
     */
    public function validator($value, ValidationField $field) {
        $matches = false;
        foreach ($this->classNames as $className) {
            if ($value instanceof $className) {
                $matches = true;
                break;
            }
        }

        if (!$matches) {
            $field->addError(get_class($value) . ' is not an instanceof oneof ' . implode(", ", $this->classNames));

            return false;
        }

        return true;
    }
}
