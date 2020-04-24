<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Garden\BasePathTrait;
use UserModel;

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

    private const EXPAND_FIELD = "expand";

    private const ID_FIELD = "ssoID";

    /** @var string[] */
    private $userFields = ["insertUser", "updateUser"];

    /** @var UserModel */
    private $userModel;

    /**
     * Setup the middleware.
     *
     * @param string $basePath
     * @param UserModel $userModel
     */
    public function __construct(string $basePath, UserModel $userModel) {
        $this->setBasePath($basePath);
        $this->userModel = $userModel;
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

        if (empty($fields)) {
            return $response;
        }

        $response = $this->updateBody($response, $fields);
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
        foreach ($expand as $expandField) {
            if ($userField = $this->fieldFromExpand($expandField)) {
                $result[] = $userField;
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
            if ($this->fieldFromExpand($field) === null) {
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
                        if (self::EXPAND_FIELD === ($parameter['name'] ?? '') && is_array($parameter['schema']['items']['enum'] ?? null)) {
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
    private function fieldFromExpand(string $field): ?string {
        foreach ($this->userFields as $userField) {
            $fullUserField = $userField . "." . self::ID_FIELD;
            if ($field === $fullUserField) {
                return $userField;
            }
        }

        return null;
    }

    /**
     * Update the body of a response to include the expanded fields.
     *
     * @param array|Data $response
     * @param array $fields
     * @return mixed
     */
    private function updateBody($response, array $fields) {
        if (empty($fields)) {
            return $response;
        }

        $response = Data::box($response);
        $userIDs = $this->extractUserIDs($response, $fields);
        $userIDs = $this->joinSSOIDs($userIDs);
        $this->updateResponse($response, $fields, $userIDs);
        return $response;
    }

    /**
     * Extract user IDs from a response body, based on specified fields.
     *
     * @param Data $response
     * @param array $fields
     * @return int[]
     */
    private function extractUserIDs(Data $response, array $fields): array {
        $result = [];
        $idFields = array_map(function (string $value) {
            return "{$value}ID";
        }, $fields);

        array_walk_recursive($response, function ($value, $key) use (&$result, $idFields) {
            if (in_array($key, $idFields) && !in_array($value, $result)) {
                $result[] = $value;
            }
        }, $idFields);

        return $result;
    }

    /**
     * Grab users SSO IDs and return them mapped to the original user IDs.
     *
     * @param array $userIDs
     * @return array
     */
    protected function joinSSOIDs(array $userIDs): array {
        $result = $this->userModel->getDefaultSSOIDs($userIDs);
        return $result;
    }

    /**
     * Update a response to include SSO user IDs.
     *
     * @param Data $response
     * @param array $fields
     * @param array $userIDs
     */
    protected function updateResponse(Data $response, array $fields, array $userIDs): void {
    }
}
