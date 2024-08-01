<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Permissions;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\SystemCallableInterface;

use AbstractApiController;

/**
 * Endpoints related to system calls in the application.
 */
class CallsApiController extends AbstractApiController
{
    /** @var LongRunner */
    private $runner;

    /**
     * CallsApiController constructor.
     *
     * @param LongRunner $runner
     */
    public function __construct(LongRunner $runner)
    {
        $this->runner = $runner;
    }

    /**
     * Perform a remote procedure call.
     *
     * @param array $body
     * @return Data
     */
    public function post_run(array $body = []): Data
    {
        // This is a special permission that can only be applied through `SystemTokenMiddleware`
        // if the request has a signed system token as the body.
        $this->permission(Permissions::PERMISSION_SYSTEM);

        $in = $this->schema(["class:s", "method:s", "args:a?", "options:o?"], "in");
        $body = $in->validate($body);

        $class = $body["class"];
        $method = $body["method"];
        $args = $body["args"] ?? [];
        $options = $body["options"] ?? [];
        $data = $this->runner->runApi(new LongRunnerAction($class, $method, $args, $options));
        return $data;
    }
}
