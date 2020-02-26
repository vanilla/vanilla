<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use Gdn_Validation;
use Vanilla\Invalid;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for the **Gdn_Validation** object.
 */
class ValidationTest extends MinimalContainerTestCase {

    /**
     * Test the ability to validate a post body's formatting.
     *
     * @param array $row Post row.
     * @param bool $isValid Does this post row have a valid body?
     * @dataProvider provideBodyFormatRows
     */
    public function testBodyFormat(array $row, bool $isValid) {
        $validation = new Gdn_Validation([
            'Body' => (object)['AllowNull' => false, 'Default' => '', 'Type' => 'string'],
            'Format' => (object)['AllowNull' => false, 'Default' => '', 'Type' => 'string'],
        ], true);
        $validation->addRule('BodyFormat', \Gdn::getContainer()->get(\Vanilla\BodyFormatValidator::class));

        $result = $validation->validate($row);
        $this->assertSame($isValid, $result);
    }

    /**
     * Provide post rows for validating formatting.
     *
     * @return array
     */
    public function provideBodyFormatRows() {
        return [
            [
                ['Body' => '[{"insert":"This is a valid rich post."}]', 'Format' => 'Rich'],
                true
            ],
            [
                ['Body' => 'This is not a valid rich post.', 'Format' => 'Rich'],
                false
            ],
        ];
    }

    /**
     * Test some basic valid types.
     *
     * @param string $type The dbtype to validate.
     * @param mixed $value A valid example of that type.
     * @dataProvider provideValidTypes
     */
    public function testValidType($type, $value) {
        $val = new Gdn_Validation(['v' => (object)['Type' => $type, 'AllowNull' => true, 'Enum' => ['foo', 'bar']]], true);
        $r = $val->validate(['v' => $value]);
        $this->assertTrue($r);
    }

    /**
     * A failed required validation should be the only error.
     *
     * @param string $type The type to test.
     * @param mixed $_ Not used.
     * @dataProvider provideValidTypes
     */
    public function testRequiredMissing($type, $_) {
        $val = new Gdn_Validation(['v' => (object)['Type' => $type, 'AllowNull' => false, 'Default' => null]], true);

        $r = $val->validate([], true);
        $this->assertFalse($r);
        $results = $val->results()['v'];
        $this->assertContains('ValidateRequired', $results);
        $this->assertCount(1, $results);
    }

    /**
     * Test a basic callback validator.
     *
     * @param mixed $value The value to test.
     * @param int|Invalid $valid The expected result of the callback.
     * @dataProvider provideBasicCallbackTests
     */
    public function testBasicCallback($value, $valid) {
        $val = new Gdn_Validation(null, true);
        $val->addRule('test', function ($value) {
            $filtered = filter_var($value, FILTER_VALIDATE_INT);

            if ($filtered === false) {
                return Invalid::emptyMessage();
            } else {
                return $filtered;
            }
        });
        $val->applyRule('v', 'test');

        $validated = $val->validate(['v' => $value]);

        if ($valid instanceof Invalid) {
            $this->assertFalse($validated);
        } else {
            $this->assertTrue($validated);
            $this->assertSame($valid, $val->validationFields()['v']);
        }
    }

    /**
     * Provide test data for **testBasicCallback()**.
     *
     * @return array Returns a data provider.
     */
    public function provideBasicCallbackTests() {
        $r = [
            [123, 123],
            ['456', 456],
            ['foo', Invalid::emptyMessage()],
        ];

        return array_column($r, null, 0);
    }

    /**
     * Provide types and valid values for them.
     *
     * @return array Returns a data provider array.
     */
    public function provideValidTypes() {
        $r = [
            ['bool', true],
            ['boolean', false],

            ['tinyint', 123],
            ['smallint', -123],
            ['mediumint', 123],
            ['int', '123'],
            ['integer', 123],
            ['bigint', 0],

            ['double', 1.2],
            ['float', 33],
            ['real', 44.4],
            ['decimal', '12.3'],
            ['dec', 44.4],
            ['numeric', 44.4],
            ['fixed', 44.4],

            ['date', '2018-01-01'],
            ['datetime', '2018-02-01 13:44'],

            ['time', '9:00'],
            ['time', '13:14'],
            ['time', '23:59:59'],
            ['timestamp', '2018-10-01'],

            ['year', 2018],

            ['char', 'foo'],
            ['varchar', 'foo'],
            ['tinyblob', 'foo'],
            ['blob', 'foo'],
            ['mediumblob', 'foo'],
            ['longblob', 'foo'],
            ['tinytext', 'foo'],
            ['mediumtext', 'foo'],
            ['text', 'foo'],
            ['longtext', 'foo'],
            ['binary', 'foo'],
            ['varbinary', 'foo'],

            ['enum', 'foo'],
            ['set', 'bar'],
        ];

        return array_column($r, null, 0);
    }
}
