<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\API;

use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use Vanilla\LongRunner;
use Vanilla\Permissions;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\SystemCallableInterface;

use AbstractApiController;

/**
 * Endpoints related to system calls in the application.
 */
class CallsApiController extends AbstractApiController {

    private const CALLABLE_ANNOTATION = "@system-callable";

    private const CALL_TIMEOUT = 30;

    /** @var ContainerInterface */
    private $container;

    /** @var LongRunner */
    private $runner;

    /**
     * CallsApiController constructor.
     *
     * @param ContainerInterface $container
     * @param LongRunner $runner
     */
    public function __construct(ContainerInterface $container, LongRunner $runner) {
        $this->container = $container;
        $this->runner = $runner;
    }

    /**
     * Perform a remote procedure call.
     *
     * @param array $body
     * @return Data
     */
    public function post_run(array $body = []): Data {
        $this->permission(Permissions::PERMISSION_SYSTEM);

        $in = $this->schema([
            "method:s",
            "args:a?",
        ], "in");
        $body = $in->validate($body);

        [$class, $method] = explode("::", $body["method"]);

        $object = $this->container->get($class);

        if (!in_array(SystemCallableInterface::class, class_implements($object))) {
            throw new ClientException("Class does not implement " . SystemCallableInterface::class . ": {$class}");
        }

        try {
            $reflection = new ReflectionMethod($object, $method);
        } catch (\ReflectionException $e) {
            throw new ClientException($e->getMessage());
        }

        if (!$reflection->isGenerator()) {
            throw new ClientException("Method is not a generator.");
        }

        $doc = $reflection->getDocComment() ?: "";
        if (!preg_match("/^\s*\*\s" . preg_quote(self::CALLABLE_ANNOTATION) . "\s*$/m", $doc)) {
            throw new ClientException("{$class}::{$method} is not accessible by this method.");
        }

        $args = $body["args"] ?? [];
        $iterator = call_user_func_array([$object, $method], $args);

        $result = ModelUtils::iterateWithTimeout($iterator, self::CALL_TIMEOUT);

        if ($result) {
            $result = new Data(['status' => 200, 'statusType' => 'complete'], 200);
        } else {
            $result = $this->runner->makeJobRunResponse($class, $method, $args);
        }

        return $result;
    }
}
