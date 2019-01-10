<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use Gdn_Validation;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use PHPUnit\Framework\TestCase;
use Vanilla\Utility\ModelUtils;

/**
 * Tests for Vanilla\Utility\ModelUtilsTest class.
 */
class ModelUtilsTest extends TestCase {

    /**
     * Test converting a Garden Schema exception into its Gdn_Validation equivalent.
     */
    public function testValidationExceptionToValidationResult() {
        $validation = new Validation();
        $validation->addError("name", "name is required.");
        $validation->addError("email", "email is required.");
        $exception = new ValidationException($validation);

        $expected = new Gdn_Validation();
        $expected->addValidationResult("name", "%s is required.");
        $expected->addValidationResult("email", "%s is required.");

        $actual = ModelUtils::validationExceptionToValidationResult($exception);
        $this->assertEquals($expected, $actual);
    }
}
