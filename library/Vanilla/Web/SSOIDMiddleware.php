<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;


use Garden\Web\RequestInterface;
use Garden\BasePathTrait;

/**
 * Middleware to lookup foreign user IDs and add them to API responses.
 */
class SSOIDMiddleware {

    use BasePathTrait;

    private const EXPAND_FIELD = "expand";

    private const ID_FIELD = "ssoID";

    private $userFields = ["insertUser", "updateUser"];

    /**
     * Setup the middleware.
     *
     * @param string $basePath
     */
    public function __construct(string $basePath) {
        $this->setBasePath($basePath);
    }

    /**
     * Invoke the middleware on a request.
     *
     * @param RequestInterface $request
     * @param callable $next
     * @return mixed
     */
    public function __invoke(RequestInterface $request, callable $next) {
        $fields = $this->fieldsFromRequest($request);
        $this->scrubExpand($request, $fields);

        $response = $next($request);
        return $response;
    }

    /**
     * Return a list of fields to expand on from the request.
     *
     * @param RequestInterface $request
     * @return array
     */
    private function fieldsFromRequest(RequestInterface $request): array {
        $result = [];

        $expand = $this->readExpand($request);
        foreach ($this->userFields as $field) {
            if (in_array($field . "." . self::ID_FIELD, $expand)) {
                $result[] = $field;
            }
        }

        return $result;
    }

    /**
     * Extract the API expand array from a request.
     *
     * @param RequestInterface $request
     * @return array
     */
    private function readExpand(RequestInterface $request): array {
        $query = $request->getQuery();
        $expand = $query[self::EXPAND_FIELD] ?? "";
        $fields = explode(",", $expand);
        array_walk($fields, "trim");
        return $fields;
    }

    private function scrubExpand(RequestInterface $request, array $fields): void {
        $query = $request->getQuery();
        $expand = $this->readExpand($request);
        if (empty($expand)) {
            return;
        }

        $scrubbedExpand = [];
        foreach ($expand as $field) {
            if (!in_array($field . "." . self::ID_FIELD, $expand)) {
                $scrubbedExpand[] = $field;
            }
        }

        $scrubbedExpand = [];
        if (empty($scrubbedExpand)) {
            unset($query[self::EXPAND_FIELD]);
        } else {
            $query[self::EXPAND_FIELD] = implode(",", $scrubbedExpand);
        }
        $request->setQuery($query);
    }

    /**
     * Add the extra SSO ID expand parameters to the
     *
     * @param array $openAPI
     */
    public function filterOpenAPI(array &$openAPI) {
        foreach ($openAPI as $key => &$value) {
            if (is_array($value)) {
                if (isset($value['parameters']) && is_array($value['parameters'])) {
                    foreach ($value['parameters'] as &$parameter) {
                        if ('expand' === ($parameter['name'] ?? '') && is_array($parameter['schema']['items']['enum'] ?? null)) {
                            $enum = $parameter['schema']['items']['enum'];
                            foreach ($enum as $item) {
                                if (in_array($item, $this->userFields)) {
                                    $enum[] = $item . '.' . self::ID_FIELD;
                                }
                            }
                            $parameter['schema']['items']['enum'] = $enum;
                        }
                    }
                } else {
                    $this->filterOpenAPI($value);
                }
            }
        }
    }

    /**
     * A higher order function for getting the middleware, useful for container config.
     *
     * @param SSOIDMiddleware $middleware
     * @return array
     */
    public static function filterOpenAPIFactory(SSOIDMiddleware $middleware) {
        return [$middleware, 'filterOpenAPI'];
    }
}
