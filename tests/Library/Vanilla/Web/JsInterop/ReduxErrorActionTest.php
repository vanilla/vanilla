<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\JsInterop;

use Garden\Container\Container;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use PHPUnit\Framework\TestCase;
use Vanilla\Exception\PermissionException;
use Vanilla\Web\JsInterpop\ReduxErrorAction;
use VanillaTests\Fixtures\MockLocale;

/**
 * Tests for the redux error action class.
 */
class ReduxErrorActionTest extends TestCase
{
    /**
     * Test the output from various types of errors.
     *
     * @param \Throwable $exception
     * @param array $expected
     *
     * @dataProvider provideErrorOutput
     */
    public function testErrorOutput(\Throwable $exception, array $expected)
    {
        $action = new ReduxErrorAction($exception);
        $this->assertEquals($expected, json_decode(json_encode($action), true));
    }

    /**
     * @return iterable
     */
    public function provideErrorOutput()
    {
        // Some mocking is required because of calls some of these exceptions make.
        $container = new Container();
        $dispatcherMock = $this->createMock(\Gdn_Dispatcher::class);
        $dispatcherMock->method("passData")->willReturnSelf();
        $container->setInstance(\Gdn::AliasDispatcher, $dispatcherMock);
        $container->setInstance(\Gdn::AliasLocale, new MockLocale());
        \Gdn::setContainer($container);

        yield "legacy not found" => [
            notFoundException("Discussion"),
            [
                "type" => "@@serverPage/ERROR",
                "payload" => [
                    "data" => [
                        "message" => "Discussion Not Found",
                        "status" => 404,
                    ],
                ],
            ],
        ];

        yield "legacy permission" => [
            permissionException("my.permission"),
            [
                "type" => "@@serverPage/ERROR",
                "payload" => [
                    "data" => [
                        "message" => "You need the my.permission permission to do that.",
                        "status" => 403,
                    ],
                ],
            ],
        ];

        yield "not found" => [
            new NotFoundException("Discussion"),
            [
                "type" => "@@serverPage/ERROR",
                "payload" => [
                    "data" => [
                        "message" => "Discussion not found.",
                        "status" => 404,
                        "description" => null,
                    ],
                ],
            ],
        ];

        yield "permission" => [
            new PermissionException("some.permission"),
            [
                "type" => "@@serverPage/ERROR",
                "payload" => [
                    "data" => [
                        "message" => "Permission Problem",
                        "status" => 403,
                        "description" => "You need the some.permission permission to do that.",
                        "permissions" => ["some.permission"],
                    ],
                ],
            ],
        ];

        yield "500 error" => [
            new ServerException("Oh my god something awful happened, this is super crazy!", 501, [
                "somecontext" => "foobar",
            ]),
            [
                "type" => "@@serverPage/ERROR",
                "payload" => [
                    "data" => [
                        "message" => "Garden\Web\Exception\ServerException",
                        "status" => 501,
                        "description" => "Oh my god something awful happened, this is super crazy!",
                        "somecontext" => "foobar",
                    ],
                ],
            ],
        ];

        yield "type error" => [
            $this->generateTypeError(),
            [
                "type" => "@@serverPage/ERROR",
                "payload" => [
                    "data" => [
                        "message" => "ArgumentCountError",
                        "status" => 0,
                        "description" =>
                            "Too few arguments to function VanillaTests\Library\Vanilla\Web\JsInterop\ReduxErrorActionTest::VanillaTests\Library\Vanilla\Web\JsInterop\{closure}(), 0 passed in /tests/Library/Vanilla/Web/JsInterop/ReduxErrorActionTest.php on line 148 and exactly 1 expected",
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate a type error.
     *
     * @return \Throwable
     * @psalm-suppress TooFewArguments
     */
    private function generateTypeError(): \Throwable
    {
        try {
            $myFunc = function (\DiscussionModel $model) {};
            $myFunc();
            TestCase::fail("Failed to generate a type error.");
        } catch (\Throwable $throwable) {
            return $throwable;
        }
    }
}
