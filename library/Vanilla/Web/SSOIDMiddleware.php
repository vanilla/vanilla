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
 *
 * 1. Read the request query string.
 * 2. Find the "expand" parameter, if available.
 * 3. Look for one of the supported fields.
 * 4. Remove the values from the expand parameter.
 * 5. Reset the request query.
 */
class SSOIDMiddleware {

    use BasePathTrait;

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
        if ($this->inBasePath($request->getPath())) {
            $fields = $this->fieldsFromRequest($request);
            $this->scrubExpand($request, $fields);
        }

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
        foreach ($expand as $field) {
            if ($this->isValidField($field)) {
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
        if (is_string($expand)) {
            $fields = explode(",", $expand);
        }
        array_walk($fields, "trim");
        return $fields;
    }

    /**
     * Remove any ID field values from the expand parameter.
     *
     * @param RequestInterface $request
     * @param array $fields
     */
    private function scrubExpand(RequestInterface $request, array $fields): void {
        $query = $request->getQuery();
        $expand = $this->readExpand($request);
        if (empty($expand)) {
            return;
        }

        $scrubbedExpand = [];
        foreach ($expand as $field) {
            if (!$this->isValidField($field)) {
                $scrubbedExpand[] = $field;
            }
        }

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

    /**
    * Is the value a supported fully-qualified field?
    *
    * @param string $field
    * @return bool
    */
    private function isValidField(string $field): bool {
        foreach ($this->userFields as $userField) {
            $fullUserField = $userField . "." . self::ID_FIELD;
            if ($field === $fullUserField) {
                return true;
            }
        }

        return false;
    }
}
