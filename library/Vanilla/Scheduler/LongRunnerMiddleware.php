<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\RequestInterface;

/**
 * Middleware for configuring long runners.
 *
 * Add 2 global API parameters for configuring the long runner.
 * /api/v2/runs/long?longRunnerMode=async&longRunneTimeout=10
 */
final class LongRunnerMiddleware
{
    public const PARAM_MODE = "longRunnerMode";
    public const PARAM_TIMEOUT = "longRunnerTimeout";

    /** @var LongRunner */
    private $longRunner;

    /**
     * DI.
     *
     * @param LongRunner $longRunner
     */
    public function __construct(LongRunner $longRunner)
    {
        $this->longRunner = $longRunner;
    }

    /**
     * Invoke the middleware on a request.
     *
     * @param RequestInterface $request
     * @param callable $next
     * @return mixed
     */
    public function __invoke(RequestInterface $request, callable $next)
    {
        $schema = Schema::parse([
            "longRunnerMode:s?" => [
                "enum" => [LongRunner::MODE_ASYNC, LongRunner::MODE_SYNC],
            ],
            "longRunnerTimeout:i?" => [
                "max" => LongRunner::TIMEOUT_MAX,
                "min" => 0,
            ],
        ]);
        $query = $request->getQuery();
        $schema->validate($query);
        if (isset($query[self::PARAM_MODE])) {
            $this->longRunner->setMode($query[self::PARAM_MODE]);
            unset($query[self::PARAM_MODE]);
        }

        if (isset($query[self::PARAM_TIMEOUT])) {
            $this->longRunner->setTimeout($query[self::PARAM_TIMEOUT]);
            unset($query[self::PARAM_TIMEOUT]);
        }
        $request->setQuery($query);

        return $next($request);
    }
}
