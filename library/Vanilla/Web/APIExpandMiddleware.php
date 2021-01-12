<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\BasePathTrait;
use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Gdn_Session;
use UserModel;
use Vanilla\Exception\PermissionException;
use Vanilla\Permissions;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;

/**
 * Middleware to lookup foreign user IDs and add them to API responses.
 *
 * 1. Read the request query string.
 * 2. Find the "expand" parameter, if available.
 * 3. Look for one of the supported fields.
 * 4. Remove the values from the expand parameter.
 * 5. Reset the request query.
 */
class APIExpandMiddleware {

    use BasePathTrait;

    private const EXPAND_FIELD = "expand";

    /** @var ExpandFieldConfig[] */
    private $supportedFields = [];

    /** @var string */
    private $permission;

    /** @var Gdn_Session */
    private $session;

    /** @var UserModel */
    private $userModel;

    /**
     * Setup the middleware.
     *
     * @param string $basePath
     * @param string $permission
     * @param UserModel $userModel
     * @param Gdn_Session $session
     */
    public function __construct(string $basePath, string $permission, UserModel $userModel, Gdn_Session $session) {
        $this->setBasePath($basePath);
        $this->permission = $permission;
        $this->userModel = $userModel;
        $this->session = $session;
    }

    /**
     * Invoke the middleware on a request.
     *
     * @param RequestInterface $request
     * @param callable $next
     * @return mixed
     */
    public function __invoke(RequestInterface $request, callable $next) {
        $expands = $this->inBasePath($request->getPath()) ? $this->extractExpands($request) : [];

        $response = $next($request);

        if (!empty($expands) &&
            (is_array($response) || ($response instanceof Data && $response->isSuccessful()))) {
            $this->verifyPermission();
            $response = $this->updateResponse($response, $expands);
        }

        return $response;
    }

    /**
     * Add support for a new field.
     *
     * @param string $fieldName
     * @param array $expandFields
     * @param callable $fetch
     * @param null $default
     */
    public function addExpandField(string $fieldName, array $expandFields, callable $fetch, $default = null): void {
        $this->supportedFields[$fieldName] = new ExpandFieldConfig(
            $expandFields,
            $fetch,
            $default
        );
    }

    /**
     * Gather the list of fields to expand and scrub the request.
     *
     * @param RequestInterface $request
     * @return array
     */
    private function extractExpands(RequestInterface $request): array {
        $result = [];

        $expand = $this->readExpand($request);
        if ($expand === null) {
            return [];
        }

        foreach ($expand as $expandField) {
            foreach ($this->supportedFields as $key => $spec) {
                if ($spec->getFieldByDestination($expandField)) {
                    $result[$key][$spec->getFieldByDestination($expandField)] = $expandField;
                }
            }
        }

        $this->scrubExpand($request, $result);
        return $result;
    }

    /**
     * Get all of the expand fields for a single depth one key.
     *
     * @param string $pk
     * @param bool $firstLevel
     * @return array
     */
    public function getExpandFieldsByKey(string $pk, bool $firstLevel = false): array {
        $result = [];

        foreach ($this->supportedFields as $key => $spec) {
            foreach ($spec->getFields() as $destination => $field) {
                if ($field === $pk && (!$firstLevel || strpos($destination, '.') === false)) {
                    $result[$key][$field] = $destination;
                }
            }
        }
        return $result;
    }

    /**
     * Does the current user have permission to use this functionality?
     *
     * @throws PermissionException If current user does not have the configured permission.
     */
    protected function verifyPermission(): void {
        if ($this->session->getPermissions()->hasRanked($this->permission) !== true) {
            $permission = Permissions::resolveRankedPermissionAlias($this->permission);
            throw new PermissionException($permission);
        }
    }

    /**
     * Extract the API expand array from a request.
     *
     * @param RequestInterface $request
     * @return array| null
     */
    private function readExpand(RequestInterface $request): ?array {
        $query = $request->getQuery();
        $expand = $query[self::EXPAND_FIELD] ?? null;

        if ($expand === null) {
            return null;
        }

        $fields = is_string($expand) ? explode(",", $expand) : [];
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

        $scrubbedExpand = $expand;
        foreach ($fields as $field) {
            $scrubbedExpand = array_diff($scrubbedExpand, array_values($field));
        }

        if (empty($scrubbedExpand)) {
            unset($query[self::EXPAND_FIELD]);
        } else {
            $query[self::EXPAND_FIELD] = implode(",", $scrubbedExpand);
        }
        $request->setQuery($query);
    }

    /**
     * Add the extra expand parameters to the
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
                                $expandFields = $this->getExpandFields($item);
                                $enum = array_merge($enum, $expandFields);
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
     * @param APIExpandMiddleware $middleware
     * @return array
     */
    public static function filterOpenAPIFactory(APIExpandMiddleware $middleware) {
        return [$middleware, 'filterOpenAPI'];
    }

    /**
     * Update a response to include the expanded fields.
     *
     * @param array|Data $response
     * @param array $fields The expand fields that were chosen with spec keys and join field values.
     * @return mixed
     */
    private function updateResponse($response, array $fields): Data {
        $response = Data::box($response);

        $data = $response->getData();
        if (ArrayUtils::isAssociative($data)) {
            $dataset = [&$data];
        } else {
            $dataset = &$data;
        }
        foreach ($fields as $key => $currentFields) {
            $spec = $this->supportedFields[$key];
            ModelUtils::leftJoin($dataset, $currentFields, $spec->getFetch(), $spec->getDefault());
        }

        $response->setData($data);
        return $response;
    }

    /**
     * Expand all of the fields possible based on a single primary key.
     *
     * @param array|Data $response
     * @param string $pk
     * @param bool $firstLevel
     * @returns Data
     */
    public function updateResponseByKey($response, string $pk, bool $firstLevel = false): Data {
        $fields = $this->getExpandFieldsByKey($pk, $firstLevel);
        $response = $this->updateResponse($response, $fields);
        return $response;
    }

    /**
     * Given an accepted expand field, return a nested expand field supported by this middleware.
     *
     * Example: If a resource has an expand field "insertUser" this would return "insertUser.ssoID".
     *
     * @param string $currentExpandField
     * @return string[]
     */
    private function getExpandFields(string $currentExpandField): array {
        $result = [];
        foreach ($this->supportedFields as $key => $spec) {
            foreach ($spec->getFields() as $expandField => $_) {
                if (str_starts_with($expandField, $currentExpandField.'.')) {
                    $result[] = $expandField;
                }
            }
        }
        return $result;
    }
}
