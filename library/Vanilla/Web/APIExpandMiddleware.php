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

    private const ID_FIELD = "ssoID";

    /** @var string[] */
    private $supportedFields = [
        "firstInsertUser.ssoID" => "firstInsertUserID",
        "insertUser.ssoID" => "insertUserID",
        "lastInsertUser.ssoID" => "lastInsertUserID",
        "lastPost.insertUser.ssoID" => "lastPost.insertUserID",
        "lastUser.ssoID" => "lastUserID",
        "updateUser.ssoID" => "updateUserID",
        "user.ssoID" => "userID",
        "ssoID" => "userID",
    ];

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
     * Gather the list of fields to expand and scrub the request.
     *
     * @param RequestInterface $request
     * @return array
     */
    private function extractExpands(RequestInterface $request): array {
        $result = [];

        $expand = $this->readExpand($request);
        $supportedFields = array_keys($this->supportedFields);
        foreach ($expand as $expandField) {
            if (in_array($expandField, $supportedFields)) {
                $result[] = $expandField;
            }
        }

        $this->scrubExpand($request, $result);
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
     * @return array
     */
    private function readExpand(RequestInterface $request): array {
        $query = $request->getQuery();
        $expand = $query[self::EXPAND_FIELD] ?? "";
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

        $scrubbedExpand = [];
        foreach ($expand as $field) {
            if (!in_array($field, $fields)) {
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
                                if (array_key_exists($item . '.' . self::ID_FIELD, $this->supportedFields)) {
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
     * @param APIExpandMiddleware $middleware
     * @return array
     */
    public static function filterOpenAPIFactory(APIExpandMiddleware $middleware) {
        return [$middleware, 'filterOpenAPI'];
    }

    /**
     * Generate an array of resources, grouped by field.
     *
     * @param Data $response
     * @param array $fields
     * @return array
     */
    private function resourceIDs(Data $response, array $fields): array {
        if (empty($this->supportedFields)) {
            return [];
        }

        $idFields = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $this->supportedFields)) {
                $idFields[$field] = $this->supportedFields[$field];
            }
        }

        $result = [];
        $idLookup = function (array $array) use ($idFields, &$result) {
            foreach ($idFields as $field => $idField) {
                $id = ArrayUtils::getByPath($idField, $array);
                if ($id !== null) {
                    if (!array_key_exists($field, $result)) {
                        $result[$field] = [];
                    }
                    if (!in_array($id, $result[$field])) {
                        $result[$field][] = $id;
                    }
                }
            }
        };

        $data = $response->getData();
        if (ArrayUtils::isAssociative($data)) {
            $idLookup($data);
        } else {
            foreach ($data as $row) {
                $idLookup($row);
            }
        }
        return $result;
    }

    /**
     * Update a response to include the expanded fields.
     *
     * @param array|Data $response
     * @param array $fields
     * @return mixed
     */
    private function updateResponse($response, array $fields): Data {
        if (empty($fields)) {
            return Data::box($response);
        }

        $response = Data::box($response);
        $resourceIDs = $this->resourceIDs($response, $fields);
        $resources = [];
        foreach ($resourceIDs as $field => $IDs) {
            $resources[$field] = $this->joinSSOIDs($IDs);
        }

        $updateRow = function (array &$row) use ($resources, $fields) {
            foreach ($fields as $field) {
                $idField = $this->supportedFields[$field] ?? null;
                if (!$idField) {
                    continue;
                }
                $id = ArrayUtils::getByPath($idField, $row);
                if ($id !== null) {
                    $fieldResources = $resources[$field] ?? [];
                    $rowResource = $fieldResources[$id] ?? null;
                    $row = ArrayUtils::setByPath($rowResource, $field, $row);
                }
            }
        };

        $data = $response->getData();
        if (ArrayUtils::isAssociative($data)) {
            $updateRow($data);
        } else {
            foreach ($data as &$row) {
                $updateRow($row);
            }
        }
        $response->setData($data);
        return $response;
    }

    /**
     * Grab users SSO IDs and return them mapped to the original user IDs.
     *
     * @param int[] $userIDs
     * @return array
     */
    public function joinSSOIDs(array $userIDs): array {
        $result = $this->userModel->getDefaultSSOIDs($userIDs);
        return $result;
    }
}
