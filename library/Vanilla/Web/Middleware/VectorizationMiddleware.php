<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Web\Middleware;

use Exception;
use Garden\BasePathTrait;
use Garden\Container\ContainerException;
use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Vanilla\Dashboard\VectorizationModel;
use Vanilla\Utility\ModelUtils;

/**
 * A middleware that prepare the document to be ingested for vectorized search.
 */
class VectorizationMiddleware
{
    use BasePathTrait;

    /**
     * Setup the middleware.
     *
     * @param VectorizationModel $vectorizationModel
     */
    public function __construct(private VectorizationModel $vectorizationModel)
    {
    }

    /**
     * Invoke the middleware to generate the document fragments.
     *
     * @param RequestInterface $request The current request.
     * @param callable $next The next middleware
     * @return mixed
     * @throws ContainerException
     * @throws Exception
     */
    public function __invoke(RequestInterface $request, callable $next): mixed
    {
        $response = Data::box($next($request));

        if (!$this->vectorizationModel->isEnabled()) {
            return $response;
        }

        $vectorize = false;
        $rawExpands = $request->getQuery()["expand"] ?? null;
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
                $data = $this->vectorizationModel->applyExpand($resource, $data);
                $response->setData($data);
            }
        }

        return $response;
    }
}
