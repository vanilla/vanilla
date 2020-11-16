<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2.0-only
*/

namespace Vanilla;

use Garden\Web\RequestInterface;
use Symfony\Component\Yaml\Yaml;
use Vanilla\Utility\ArrayUtils;

/**
 * A class for building a full OpenAPI 3.0 spec by combining all of the add-on OpenAPI files.
 *
 * Add-ons place their OpenAPI specs inside an `openapi` folder in their add-on root. All files with a `.yml` or `.json`
 * are combined.
 */
class OpenAPIBuilder {
    /**
     * @var AddonManager
     */
    private $addonManager;

    /**
     * @var
     */
    private $cachePath;

    /** @var RequestInterface */
    private $request;

    /**
     * @var callable[]
     */
    private $filters = [];

    /**
     * OpenAPIBuilder constructor.
     *
     * @param AddonManager $addonManager The addon manager used to get a list of addons to combine.
     * @param RequestInterface $request The request to use for the URL base path.
     * @param string $cachePath The path to cache the built OpenAPI spec.
     */
    public function __construct(AddonManager $addonManager, RequestInterface $request, string $cachePath = '') {
        $this->addonManager = $addonManager;
        $this->cachePath = $cachePath ?: PATH_CACHE.'/openapi.php';
        $this->request = $request;
    }

    /**
     * Merge two Opan API schemas.
     *
     * Although this class uses this method to always merge top-level schemas, it should support any schema fragment so
     * long as both schemas are at the same level.
     *
     * @param array $schema1
     * @param array $schema2
     * @return array
     */
    public static function mergeSchemas(array $schema1, array $schema2): array {
        // This callback is on the conservative side. It has a whitelist of known numeric keys and their behavior.
        // Everything else uses plain old `array_merge()`.
        $merge = function (array $arr1, array $arr2, string $key) {
            switch ($key) {
                case 'required':
                    // Don't sort required because it's often in a logical order already.
                    $r = array_values(array_unique(array_merge($arr1, $arr2)));
                    break;
                case 'enum':
                case 'tags':
                    $r = array_unique(array_merge($arr1, $arr2));
                    sort($r);
                    break;
                case 'parameters':
                    // Parameters work a lot like associative arrays, but have to be made that way.
                    $arr1 = array_column($arr1, null, 'name');
                    $arr2 = array_column($arr2, null, 'name');
                    $r = array_values(self::mergeSchemas($arr1, $arr2));
                    break;
                default:
                    $r = array_merge($arr1, $arr2);
            }
            return $r;
        };

        $schema1 = ArrayUtils::mergeRecursive($schema1, $schema2, $merge);
        return $schema1;
    }

    /**
     * Get the enabled endpoints from the API.
     *
     * @param bool $disabled Pass **true** to show disabled add-on endpoints.
     * @param bool $hidden Pass **true** to show hidden endpoints.
     * @return array Returns an OpenAPI array.
     */
    public function getEnabledOpenAPI(bool $disabled = false, bool $hidden = false): array {
        $result = $this->getFullOpenAPI();

        $fn = function (array &$parent) use ($disabled, $hidden, &$fn) {
            foreach ($parent as $key => &$data) {
                if (is_array($data)) {
                    if (!$hidden && isset($data['x-hidden']) && $data['x-hidden']) {
                        unset($parent[$key]);
                    } elseif (!$disabled && !empty($data['x-addon']) && !$this->addonManager->isEnabled($data['x-addon'], Addon::TYPE_ADDON)) {
                        unset($parent[$key]);
                    } else {
                        $fn($data);
                        if (empty($data)) {
                            unset($parent[$key]);
                        }
                    }
                }
            }
        };

        $fn($result);

        return $result;
    }

    /**
     * Get the full OpenAPI spec, using a cached version if available.
     *
     * @return array Returns an OpenAPI array.
     */
    public function getFullOpenAPI(): array {
        if (!file_exists($this->cachePath)) {
            FileUtils::putExport($this->cachePath, $this->generateFullOpenAPI());
        }

        $result = FileUtils::getExport($this->cachePath);

        // Reapply URL even after pulling from cache.
        // A site may be accessed from multiple URLs and share the same cache.
        $result = $this->applyRequestBasedApiBasePath($result);
        return $result;
    }


    /**
     * Apply the request specific server root to the OpenAPI definition.
     *
     * @param array $openApi A built OpenAPI definition.
     * @return array The modified OpenAPI definition
     */
    private function applyRequestBasedApiBasePath(array $openApi): array {
        // Fix the server URL.
        $openApi['servers'] = [
            [
                'url' => $this->request->urlDomain(true) . $this->request->getAssetRoot() . '/api/v2',
            ]
        ];

        return $openApi;
    }

    /**
     * Generate the full OpenAPI data.
     *
     * @return array Returns an array representation of the OpenAPI spec.
     */
    public function generateFullOpenAPI(): array {
        $addons = $this->addonManager->lookupAllByType(Addon::TYPE_ADDON);

        $result = [
            'openapi' => '3.0.2',
            'info' => [],
            'paths' => [],
            'components' => [],
        ];
        $results = [];

        foreach ($addons as $addon) {
            /* @var Addon $addon */

            if (defined("GLOB_BRACE")) {
                $glob = $addon->path('/openapi/*.{json,yml,yaml}', Addon::PATH_FULL);
                $paths = glob($glob, GLOB_BRACE);
            } else {
                // GLOB_BRACE not available on this platform? Got to do it the longform way.
                $paths = array_merge(
                    glob($addon->path('/openapi/*.json', Addon::PATH_FULL)),
                    glob($addon->path('/openapi/*.yml', Addon::PATH_FULL)),
                    glob($addon->path('/openapi/*.yaml', Addon::PATH_FULL))
                );
            }

            foreach ($paths as $path) {
                $data = $this->getFileData($path);
                $this->cleanData($data);
                $this->annotateData($data, $addon);
                $results[] = $data;
                $result = self::mergeSchemas($result, $data);
            }
        }

        // Sort the paths and components.
        ksort($result['paths']);

        foreach ($result['components'] as $key => $_) {
            ksort($result['components'][$key]);
        }

        $result = $this->applyRequestBasedApiBasePath($result);

        foreach ($this->filters as $callback) {
            $callback($result);
        }

        return $result;
    }


    /**
     * Load and parse an OpenAPI file.
     *
     * @param string $path The path to the file. The path must exist.
     * @return array Returns the data from the file after parsing.
     */
    private function getFileData(string $path): array {
        switch (pathinfo($path, PATHINFO_EXTENSION)) {
            case 'json':
                $result = json_decode(file_get_contents($path), true);
                break;
            case 'yml':
            case 'yaml':
                try {
                    $result = Yaml::parseFile($path);
                } catch (\Throwable $ex) {
                    throw new \Exception("Error parsing $path: ".$ex->getMessage(), 500, $ex);
                }
                break;
            default:
                throw new \InvalidArgumentException("Unrecognized OpenAPI file extension for $path", 500);
        }
        if (!is_array($result)) {
            throw new \Exception("Error parsing $path.", 500);
        }

        return $result;
    }

    /**
     * Annotate a partial OpenAPI file with the addon that owns it.
     *
     * This method adds special `x-addon` properties to the data to allow for filtering later.
     *
     * @param array $data The data to annotate.
     * @param Addon $addon The addon that owns the data.
     */
    private function annotateData(array &$data, Addon $addon) {
        $addonKey = $addon->getGlobalKey();

        if (!empty($data['paths'])) {
            foreach ($data['paths'] as $path => &$methods) {
                foreach ($methods as $method => &$operation) {
                    if ($method === 'parameters') {
                        $this->annotateDataset($operation, $addonKey);
                    } elseif (is_array($operation) && !isset($operation['x-addon'])) {
                        $operation['x-addon'] = $addonKey;
                    }
                }
            }
        }

        if (!empty($data['components'])) {
            foreach ($data['components'] as $type => &$components) {
                foreach ($components as $key => &$component) {
                    if (!isset($component['x-addon'])) {
                        $component['x-addon'] = $addonKey;
                    }
                }
            }
        }
    }

    /**
     * Add addon annotations to an array.
     *
     * @param array $data
     * @param string $addonKey
     */
    private function annotateDataset(array &$data, string $addonKey): void {
        foreach ($data as $key => &$row) {
            if (is_array($row) && !isset($row['x-addon'])) {
                $row['x-addon'] = $addonKey;
            }
        }
    }

    /**
     * Clean the OpenAPI data.
     *
     * This method is used for any miscellaneous data cleanup.
     *
     * @param array $data The data to clean.
     */
    private function cleanData(array &$data) {
        // Remove empty paths and components.
        if (empty($data['paths'])) {
            unset($data['paths']);
        }

        if (!empty($data['components'])) {
            foreach ($data['components'] as $type => $components) {
                if (empty($components)) {
                    unset($data['components'][$type]);
                }
            }
        }
        if (empty($data['components'])) {
            unset($data['components']);
        }

        array_walk_recursive($data, function (&$value, $key) {
            // Remove the files in references to make them local references instead.
            if ($key === '$ref' && ($pos = strpos($value, '#')) !== false) {
                $value = substr($value, $pos);
            }
        });

        $data['info'] = $data['info'] ?: [];
    }

    /**
     * Add a filter to augment the generated OpenAPI.
     *
     * Filters are all called on the generated OpenAPI definition.
     *
     * @param callable $filter
     */
    public function addFilter(callable  $filter): void {
        $this->filters[] = $filter;
    }

    /**
     * Remove a filter.
     *
     * @param callable $filter
     */
    public function removeFilter(callable $filter): void {
        foreach ($this->filters as $i => $row) {
            if ($row === $filter) {
                unset($this->filters[$i]);
            }
        }
    }
}
