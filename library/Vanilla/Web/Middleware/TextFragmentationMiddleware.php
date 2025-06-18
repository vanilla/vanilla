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
use Vanilla\Dashboard\DocumentModel;
use Vanilla\Utility\ModelUtils;
use vectorization\ElasticVectorizationService;

/**
 * A middleware that generate the document fragments.
 */
class TextFragmentationMiddleware
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

        if (!$this->documentModel->isEnabled()) {
            return $response;
        }

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
