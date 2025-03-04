<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Web\Middleware;

use Garden\BasePathTrait;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Vanilla\Dashboard\DocumentModel;
use Vanilla\Utility\ModelUtils;

/**
 * A middleware that sets a locale based on slug
 */
class ChunkingMiddleware
{
    use BasePathTrait;

    /**
     * Setup the middleware.
     *
     * @param DocumentModel $documentModel
     */
    public function __construct(private DocumentModel $documentModel)
    {
    }

    /**
     * Invoke the middleware that sets the ban.
     *
     * @param RequestInterface $request The current request.
     * @param callable $next The next middleware
     * @return mixed
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function __invoke(RequestInterface $request, callable $next): mixed
    {
        $response = Data::box($next($request));

        $rawExpands = $request->getQuery()["expand"] ?? null;
        $vectorize = false;
        if ($rawExpands !== null) {
            if (is_array($rawExpands)) {
                $rawExpands = implode(",", $rawExpands);
            }
            $vectorize = ModelUtils::isExpandOption("vectorize", explode(",", $rawExpands), true);
        }
        if ($vectorize) {
            $data = $response->getData();
            $resource = $response->getMeta("resource");
            if (is_array($data) && !empty($resource)) {
                $data = $this->documentModel->processDocument($resource, $data);
                $response->setData($data);
            }
        }
        return $response;
    }
}
