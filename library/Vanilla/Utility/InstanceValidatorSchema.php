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
    private $className;

    /**
     * @param string $className
     */
    public function __construct(string $className) {
        parent::__construct();
        $this->className = $className;
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
        if (!($value instanceof $this->className)) {
            $field->addError(get_class($value) . ' is not an instanceof ' . $this->className);

            return false;
        }

        return true;
    }
}
