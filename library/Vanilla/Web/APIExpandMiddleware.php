<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\BasePathTrait;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Gdn_Session;
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
class APIExpandMiddleware
{
    use BasePathTrait;

    public const META_EXPAND_PREFIXES = "expand_prefixes";
    public const META_EXTRA_ITERABLES = "expand_extra_iterables";
    private const EXPAND_FIELD = "expand";

    /** @var array<string, AbstractApiExpander> */
    private $expanders = [];

    /** @var Gdn_Session */
    private $session;

    /**
     * Setup the middleware.
     *
     * @param string $basePath
     * @param Gdn_Session $session
     */
    public function __construct(string $basePath, Gdn_Session $session)
    {
        $this->setBasePath($basePath);
        $this->session = $session;
    }

    /**
     * Add an expander.
     *
     * @param AbstractApiExpander $expander
     */
    public function addExpander(AbstractApiExpander $expander)
    {
        $this->expanders[$expander->getFullKey()] = $expander;
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
        $rawExpands = $request->getQuery()["expand"] ?? "";
        $request->setMeta("expand", $rawExpands);
        $expands = $this->inBasePath($request->getPath()) ? $this->extractExpands($request) : [];
        $keysToExpand = [];
        foreach ($expands as $nestedExpands) {
            foreach ($nestedExpands as $expandedKey) {
                $keysToExpand[] = $expandedKey;
            }
        }
        $this->verifyPermissions($keysToExpand);

        $response = Data::box($next($request));

        if (!empty($expands) && (is_array($response) || ($response instanceof Data && $response->isSuccessful()))) {
            // Stash our expand values
            $response->stashMiddlewareQueryParameter("expand", $rawExpands);
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
    private function extractExpands(RequestInterface $request): array
    {
        $result = [];

        $expand = $this->readExpand($request);
        if ($expand === null) {
            return [];
        }

        if (in_array(ModelUtils::EXPAND_ALL, $expand)) {
            foreach ($this->enabledExpanders() as $expander) {
                $hasPermission =
                    $expander->getPermission() == null ||
                    $this->session->getPermissions()->hasRanked($expander->getPermission());
                if ($hasPermission) {
                    $result[$expander->getFullKey()] = array_flip($expander->getExpandFields());
                }
            }
        }

        foreach ($expand as $expandField) {
            $wholeMatchingExpandSpec = $this->enabledExpanders()[$expandField] ?? null;
            if ($wholeMatchingExpandSpec instanceof AbstractApiExpander) {
                $fields = $wholeMatchingExpandSpec->getExpandFields();
                // We need to flip these because our result is "sourcefield" => "destinationField"
                // And our input is inverted.
                $result[$expandField] = array_flip($fields);
            }

            foreach ($this->enabledExpanders() as $key => $expander) {
                $sourceField = $expander->getFieldByDestination($expandField);
                if ($sourceField !== null) {
                    $result[$key][$sourceField] = $expandField;
                }
            }
        }

        $this->scrubExpand($request, $result);
        return $result;
    }

    /**
     * Get all expand fields for a single depth one key.
     *
     * @param string $pk
     * @param bool $firstLevel
     * @return array
     */
    public function getExpandFieldsByKey(string $pk, bool $firstLevel = false): array
    {
        $result = [];
        if (count($this->enabledExpanders()) === 0) {
            return $result;
        }
        foreach ($this->enabledExpanders() as $key => $expander) {
            foreach ($expander->getExpandFields() as $destination => $field) {
                if ($field === $pk && (!$firstLevel || strpos($destination, ".") === false)) {
                    $result[$key][$field] = $destination;
                }
            }
        }
        return $result;
    }

    /**
     * Does the current user have permission to use this functionality?
     *
     * @param array<string> $keysToExpand The expands from the request.
     *
     * @throws PermissionException If current user does not have the configured permission.
     */
    protected function verifyPermissions(array $keysToExpand): void
    {
        foreach ($this->enabledExpanders() as $expander) {
            $permissionToCheck = $expander->getPermission();
            if ($permissionToCheck === null) {
                // No permissions to check for this expander.
                continue;
            }

            $allValidExpands = array_merge([$expander->getFullKey()], array_keys($expander->getExpandFields()));
            if (empty(array_intersect($keysToExpand, $allValidExpands))) {
                // This expander didn't match.
                continue;
            }

            if ($this->session->getPermissions()->hasRanked($permissionToCheck) !== true) {
                $permission = Permissions::resolveRankedPermissionAlias($permissionToCheck);
                throw new PermissionException($permission, [
                    "expandFields" => $allValidExpands,
                ]);
            }
        }
    }

    /**
     * Extract the API expand array from a request.
     *
     * @param RequestInterface $request
     * @return array| null
     */
    private function readExpand(RequestInterface $request): ?array
    {
        $query = $request->getQuery();
        $schema = Schema::parse([
            "expand:a?" => [
                "items" => [
                    "type" => "string",
                ],
                "style" => "form",
            ],
        ]);

        try {
            $query = $schema->validate($query);
        } catch (ValidationException $ex) {
            // If it doesn't validate, let it pass. The endpoint itself will handle validation.
            return null;
        }

        $expand = $query[self::EXPAND_FIELD] ?? null;

        if (empty($expand)) {
            return null;
        }

        $fields = array_map("trim", $expand);
        return $fields;
    }

    /**
     * Remove any ID field values from the expand parameter.
     *
     * @param RequestInterface $request
     * @param array $fields
     */
    private function scrubExpand(RequestInterface $request, array $fields): void
    {
        $query = $request->getQuery();
        $expand = $this->readExpand($request);
        if (empty($expand)) {
            return;
        }

        $scrubbedExpand = $expand;
        foreach ($fields as $fieldTopLevel => $fieldValues) {
            $fieldValues[] = $fieldTopLevel;
            $scrubbedExpand = array_diff($scrubbedExpand, array_values($fieldValues));
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
    public function filterOpenAPI(array &$openAPI)
    {
        foreach ($openAPI as $key => &$value) {
            if (is_array($value)) {
                if (isset($value["parameters"]) && is_array($value["parameters"])) {
                    foreach ($value["parameters"] as &$parameter) {
                        if (
                            self::EXPAND_FIELD === ($parameter["name"] ?? "") &&
                            is_array($parameter["schema"]["items"]["enum"] ?? null)
                        ) {
                            $enum = $parameter["schema"]["items"]["enum"];
                            foreach ($enum as $item) {
                                $expandFields = $this->getExpandFields($item);
                                $enum = array_merge($enum, $expandFields);
                            }
                            $parameter["schema"]["items"]["enum"] = $enum;
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
    public static function filterOpenAPIFactory(APIExpandMiddleware $middleware)
    {
        return [$middleware, "filterOpenAPI"];
    }

    /**
     * Update a response to include the expanded fields.
     *
     * @param array|Data $response
     * @param array $fields The expand fields that were chosen with spec keys and join field values.
     * @return mixed
     */
    private function updateResponse($response, array $fields): Data
    {
        $response = Data::box($response);

        $data = $response->getData();
        if (ArrayUtils::isAssociative($data)) {
            $dataset = [&$data];
        } else {
            $dataset = &$data;
        }
        foreach ($fields as $key => $currentFields) {
            $expander = $this->enabledExpanders()[$key];

            $prefixes = $response->getMeta(self::META_EXPAND_PREFIXES) ?? [];
            $fieldMapping = $currentFields;
            foreach ($prefixes as $prefix) {
                foreach ($currentFields as $key => $val) {
                    $fieldMapping["{$prefix}.{$key}"] = "{$prefix}.{$val}";
                }
            }

            ModelUtils::leftJoin(
                $dataset,
                $fieldMapping,
                [$expander, "resolveFragments"],
                $expander->getDefaultRecord()
            );

            $extraIterables = $response->getMeta(self::META_EXTRA_ITERABLES) ?? [];
            if (!empty($extraIterables)) {
                foreach ($extraIterables as $iterable) {
                    if (empty($data[$iterable])) {
                        continue;
                    }
                    ModelUtils::leftJoin(
                        $data[$iterable],
                        $fieldMapping,
                        [$expander, "resolveFragments"],
                        $expander->getDefaultRecord()
                    );
                }
            }
        }

        $response->setData($data);
        return $response;
    }

    /**
     * Expand all the fields possible based on a single primary key.
     *
     * @param array|Data $response
     * @param string $pk
     * @param bool $firstLevel
     * @param string[] $excludedExpanders An array of expander names to exclude. Exaple: 'users', 'users.extended'
     *
     * @return Data
     */
    public function updateResponseByKey(
        $response,
        string $pk,
        bool $firstLevel = false,
        array $excludedExpanders = []
    ): Data {
        $fields = $this->getExpandFieldsByKey($pk, $firstLevel);
        foreach ($excludedExpanders as $excludedExpander) {
            unset($fields[$excludedExpander]);
        }
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
    private function getExpandFields(string $currentExpandField): array
    {
        $result = [];
        foreach ($this->enabledExpanders() as $key => $expander) {
            foreach ($expander->getExpandFields() as $expandField => $_) {
                if (str_starts_with($expandField, $currentExpandField . ".")) {
                    $result[] = $expandField;
                }
            }
        }
        return $result;
    }

    /**
     * @return AbstractApiExpander[]
     */
    private function enabledExpanders(): array
    {
        return array_filter($this->expanders, function (AbstractApiExpander $expander) {
            return $expander->isEnabled();
        });
    }
}
