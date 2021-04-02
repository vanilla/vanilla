<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Iterator;
use Vanilla\CurrentTimeStamp;
use Vanilla\Permissions;
use Vanilla\Web\SystemCallableInterface;

/**
 * Verify behavior of the /api/v2/calls resource.
 */
class CallsTest extends AbstractAPIv2Test {

    private $baseUrl = "calls";

    /**
     * Verify admins cannot access the /calls/run endpoint by default.
     */
    public function testRunAdminNoPermission(): void {
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");
        $this->getSession()->getPermissions()->setAdmin(1);
        $this->api()->post("{$this->baseUrl}/run");
    }

    /**
     * Verify behavior upon successful completion of an iterator.
     */
    public function testRunComplete(): void {
        $rule = "@@" . __FUNCTION__;
        $argsSpy = [];
        $this->container()->setInstance($rule, new class($argsSpy) implements SystemCallableInterface {

            /** @var array */
            private $argsSpy;

            /**
             * Setup the class.
             *
             * @param array $argsSpy
             */
            public function __construct(array &$argsSpy) {
                $this->argsSpy = &$argsSpy;
            }

            /**
             * Big ole dummy.
             *
             * @param mixed $args
             * @return Iterator
             * @system-callable
             */
            public function run(...$args): Iterator {
                $this->argsSpy = $args;
                yield true;
            }
        });

        $this->getSession()->getPermissions()->set(Permissions::PERMISSION_SYSTEM, true);

        $body = [
            "method" => "{$rule}::run",
            "args" => ["foo", "bar"],
        ];
        $response = $this->api()->post("{$this->baseUrl}/run", $body);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            "status" => 200,
            "statusType" => "complete",
        ], $response->getBody());
        $this->assertSame($body["args"], $argsSpy);
    }

    /**
     * Verify behavior upon incomplete execution of an iterator.
     */
    public function testRunIncomplete(): void {
        $rule = "@@" . __FUNCTION__;
        $this->container()->setInstance($rule, new class() implements SystemCallableInterface {

            /**
             * Big ole dummy.
             *
             * @return Iterator
             * @system-callable
             */
            public function run(): Iterator {
                CurrentTimeStamp::mockTime(CurrentTimeStamp::get() + 360);
                yield true;
            }
        });

        $this->getSession()->getPermissions()->set(Permissions::PERMISSION_SYSTEM, true);

        $response = $this->api()->post("{$this->baseUrl}/run", [
            "method" => "{$rule}::run",
            "args" => [],
        ]);

        $this->assertSame(202, $response->getStatusCode());
        $this->assertArraySubsetRecursive([
            "status" => 202,
            "statusType" => "incomplete",
        ], $response->getBody());
    }

    /**
     * Verify behavior upon referencing a class that does not implement the callable interface.
     */
    public function testRunInvalidClass(): void {
        $rule = "@@" . __FUNCTION__;
        $this->container()->setInstance($rule, new class() {
        });

        $this->getSession()->getPermissions()->set(Permissions::PERMISSION_SYSTEM, true);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Class does not implement " . SystemCallableInterface::class);
        $this->api()->post("{$this->baseUrl}/run", [
            "method" => "{$rule}::run",
            "args" => [],
        ]);
    }

    /**
     * Verify behavior upon attempting to invoke a method that does not have the proper annotation.
     */
    public function testRunInvalidMethodAnnotation(): void {
        $rule = "@@" . __FUNCTION__;
        $this->container()->setInstance($rule, new class() implements SystemCallableInterface {

            /**
             * Big ole dummy.
             *
             * @return bool
             */
            public function run(): Iterator {
                yield true;
            }
        });

        $this->getSession()->getPermissions()->set(Permissions::PERMISSION_SYSTEM, true);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage($rule . "::run is not accessible by this method.");
        $this->api()->post("{$this->baseUrl}/run", [
            "method" => "{$rule}::run",
            "args" => [],
        ]);
    }

    /**
     * Verify behavior upon attempting to invoke a method that is not a generator.
     */
    public function testRunInvalidGenerator(): void {
        $rule = "@@" . __FUNCTION__;
        $this->container()->setInstance($rule, new class() implements SystemCallableInterface {

            /**
             * Big ole dummy.
             *
             * @return bool
             */
            public function run(): bool {
                return true;
            }
        });

        $this->getSession()->getPermissions()->set(Permissions::PERMISSION_SYSTEM, true);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Method is not a generator.");
        $this->api()->post("{$this->baseUrl}/run", [
            "method" => "{$rule}::run",
            "args" => [],
        ]);
    }
}
