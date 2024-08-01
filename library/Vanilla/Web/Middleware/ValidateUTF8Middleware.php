<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Middleware;

use Garden\Web\Exception\ClientException;
use Garden\Web\RequestInterface;
use Vanilla\Utility\ArrayUtils;

class ValidateUTF8Middleware
{
    /**
     * @param RequestInterface $request
     * @param callable $next
     * @return mixed
     * @throws ClientException
     */
    public function __invoke(RequestInterface $request, callable $next)
    {
        $this->validate($request->getQuery(), "Request query");
        $this->validate($request->getBody(), "Request body");

        return $next($request);
    }

    /**
     * Traverses $content and throws a ClientException if invalid utf8 characters are encountered.
     *
     * @param $content
     * @param string $context Where the data comes from for the exception message (i.e. "Request query" or "Request body")
     * @throws ClientException
     */
    private function validate($content, string $context)
    {
        if (empty($content) || !is_array($content)) {
            return;
        }

        ArrayUtils::walkRecursiveArray($content, function ($array, array $path) use ($context) {
            $parentPath = implode(".", $path);
            foreach ($array as $key => $value) {
                if (is_string($key) && !mb_check_encoding($key, "UTF-8")) {
                    $in = !empty($parentPath) ? " in $parentPath." : ".";
                    throw new ClientException("$context has invalid utf8 for the name of a parameter" . $in);
                }
                if (is_string($value) && !mb_check_encoding($value, "UTF-8")) {
                    $fullPath = ltrim("$parentPath.$key", ".");
                    throw new ClientException("$context has invalid utf8 for the value of $fullPath.");
                }
            }
        });
    }
}
