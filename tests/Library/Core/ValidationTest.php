<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Core;

use Gdn_Validation;
use PHPUnit\Framework\TestCase;
use Vanilla\Invalid;

/**
 * Tests for the **Gdn_Validation** object.
 */
class ValidationTest extends TestCase {
    /**
     * Test some basic valid types.
     *
     * @param string $type The dbtype to validate.
     * @param mixed $value A valid example of that type.
     * @dataProvider provideValidTypes
     */
    public function testValidType($type, $value) {
        if (in_array($type, ['time'], true)) {
            $this->markTestIncomplete("The $type type has not been implemented.");
        }

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

    public function testCallbackValidator() {


    }

    protected function testBasicCallback($value, $valid) {
        $val = new Gdn_Validation(null, true);
        $val->addRule('test', function ($value) {
            $filtered = filter_var($value, FILTER_VALIDATE_INT);

            if ($filtered === false) {
                return Invalid::emptyMessage();
            } else {
                return $filtered;
            }
        }, true);
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

            ['time', '13:14'],
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
