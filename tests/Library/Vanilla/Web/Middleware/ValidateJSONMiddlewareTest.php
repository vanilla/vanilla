<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Middleware;

use Garden\Web\Exception\ClientException;
use Vanilla\Web\Middleware\ValidateJSONMiddleware;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Fixtures\Request;

/**
 * Tests for validating JSON with the middleware
 */
class ValidateJSONMiddlewareTest extends BootstrapTestCase
{
    /**
     * Test validating different JSON strings
     *
     * @param string $json
     * @param bool $expectsException
     * @return void
     * @throws ClientException
     * @dataProvider provideJSONData
     */
    public function testValidateJSON(string $json, bool $expectsException)
    {
        if ($expectsException) {
            $this->expectException(ClientException::class);
            $this->expectExceptionCode(400);
        }
        $request = new Request("/foobar", "POST");
        $request->setHeader("Content-Type", "application/json");
        $request->setBody($json);
        $middleware = new ValidateJSONMiddleware();
        $middleware($request, function () {});
        $this->expectNotToPerformAssertions();
    }

    /**
     * Provides JSON strings for testing
     *
     * @return array[]
     */
    public function provideJSONData(): array
    {
        return [
            "Good JSON" => ['{"foo":"bar"}', false],
            "Trailing slash" => ['{"foo":"foo",}', true],
        ];
    }
}
