<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Middleware;

use Garden\Web\Exception\ClientException;
use Vanilla\Web\Middleware\ValidateUTF8Middleware;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Fixtures\Request;

class ValidateUTF8MiddlewareTest extends BootstrapTestCase
{
    /**
     * @param string $method
     * @param array $data
     * @param string|null $expectedExceptionMessage
     * @return void
     * @dataProvider provideRequestValidationData
     */
    public function testRequestValidation(string $method, array $data, ?string $expectedExceptionMessage = null)
    {
        if (isset($expectedExceptionMessage)) {
            $this->expectException(ClientException::class);
            $this->expectExceptionCode(400);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $request = new Request("/foobar", $method, $data);
        $middleware = new ValidateUTF8Middleware();
        $middleware($request, function () {});
        $this->expectNotToPerformAssertions();
    }

    /**
     * Provides test data for valid and invalid utf8 in a request.
     * Partially copied from https://stackoverflow.com/a/3886015.
     *
     * @return \Generator
     */
    public function provideRequestValidationData(): \Generator
    {
        $tests = [
            "Valid ASCII" => ["a", true],
            "Valid 2 Octet Sequence" => ["\xc3\xb1", true],
            "Invalid 2 Octet Sequence" => ["\xc3\x28", false],
            "Invalid Sequence Identifier" => ["\xa0\xa1", false],
            "Valid 3 Octet Sequence" => ["\xe2\x82\xa1", true],
            "Invalid 3 Octet Sequence (in 2nd Octet)" => ["\xe2\x28\xa1", false],
            "Invalid 3 Octet Sequence (in 3rd Octet)" => ["\xe2\x82\x28", false],
            "Valid 4 Octet Sequence" => ["\xf0\x90\x8c\xbc", true],
            "Invalid 4 Octet Sequence (in 2nd Octet)" => ["\xf0\x28\x8c\xbc", false],
            "Invalid 4 Octet Sequence (in 3rd Octet)" => ["\xf0\x90\x28\xbc", false],
            "Invalid 4 Octet Sequence (in 4th Octet)" => ["\xf0\x28\x8c\x28", false],
            "Valid 5 Octet Sequence (but not Unicode!)" => ["\xf8\xa1\xa1\xa1\xa1", false],
            "Valid 6 Octet Sequence (but not Unicode!)" => ["\xfc\xa1\xa1\xa1\xa1\xa1", false],
        ];

        foreach ($tests as $label => $test) {
            [$string, $valid] = $test;
            yield "Test get method and param value with $label" => [
                "GET",
                ["a" => $string],
                $valid ? null : "Request query has invalid utf8 for the value of a.",
            ];
            yield "Test post method and param value with $label" => [
                "POST",
                ["a" => $string],
                $valid ? null : "Request body has invalid utf8 for the value of a.",
            ];
            yield "Test get method and param name with $label" => [
                "GET",
                [$string => "a"],
                $valid ? null : "Request query has invalid utf8 for the name of a parameter.",
            ];
            yield "Test post method and param name with $label" => [
                "POST",
                [$string => "a"],
                $valid ? null : "Request body has invalid utf8 for the name of a parameter.",
            ];
        }

        yield "Test get method and deeply nested parameter value" => [
            "GET",
            ["a" => ["b" => ["c" => "\xc3\x28"]]],
            "Request query has invalid utf8 for the value of a.b.c.",
        ];
        yield "Test post method and deeply nested parameter value" => [
            "POST",
            ["a" => ["b" => ["c" => "\xc3\x28"]]],
            "Request body has invalid utf8 for the value of a.b.c.",
        ];
        yield "Test get method and deeply nested parameter name" => [
            "GET",
            ["a" => ["b" => ["\xc3\x28" => "c"]]],
            "Request query has invalid utf8 for the name of a parameter in a.b.",
        ];
        yield "Test post method and deeply nested parameter name" => [
            "POST",
            ["a" => ["b" => ["\xc3\x28" => "c"]]],
            "Request body has invalid utf8 for the name of a parameter in a.b.",
        ];
    }
}
