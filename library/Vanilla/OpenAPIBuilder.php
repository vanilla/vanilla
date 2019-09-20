<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2.0-only
*/

namespace Vanilla;

use Garden\Web\RequestInterface;
use Symfony\Component\Yaml\Yaml;

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
            $data = '<?php return '.var_export($this->generateFullOpenAPI(), true).";\n";
            static::filePutContents($this->cachePath, $data);
        }

        $result = require $this->cachePath;

        // Reapply URL even after pulling from cache.
        // A site may be accessed from multiple URLs and share the same cache.
        $result = $this->applyCorrectApiBaseUrl($result);
        return $result;
    }


    /**
     * Apply the correct server root to the OpenAPI definition.
     *
     * @param array $openApi A built OpenAPI definition.
     * @return array The modified OpenAPI definition
     */
    private function applyCorrectApiBaseUrl(array $openApi): array {
        // Fix the server URL.
        $openApi['servers'] = [
            [
                'url' => $this->request->urlDomain(true) . $this->request->getAssetRoot() . '/api/v2',
            ]
        ];

        return $openApi;
    }

    /**
     * A version of file_put_contents() that is multi-thread safe.
     *
     * @param string $filename Path to the file where to write the data.
     * @param mixed $data The data to write. Can be either a string, an array or a stream resource.
     * @param int $mode The permissions to set on a new file.
     * @return boolean
     * @category Filesystem Functions
     * @see http://php.net/file_put_contents
     */
    private static function filePutContents($filename, $data, $mode = 0644) {
        $temp = tempnam(dirname($filename), 'atomic');

        if (!($fp = @fopen($temp, 'wb'))) {
            $temp = dirname($filename).DIRECTORY_SEPARATOR.uniqid('atomic');
            if (!($fp = @fopen($temp, 'wb'))) {
                trigger_error("OpenAPIBuilder::filePutContents(): error writing temporary file '$temp'", E_USER_WARNING);
                return false;
            }
        }

        fwrite($fp, $data);
        fclose($fp);

        if (!@rename($temp, $filename)) {
            $r = @unlink($filename);
            $r &= @rename($temp, $filename);
            if (!$r) {
                trigger_error("OpenAPIBuilder::filePutContents(): error writing file '$filename'", E_USER_WARNING);
                return false;
            }
        }
        if (function_exists('apc_delete_file')) {
            // This fixes a bug with some configurations of apc.
            apc_delete_file($filename);
        } elseif (function_exists('opcache_invalidate')) {
            opcache_invalidate($filename);
        }

        @chmod($filename, $mode);
        return true;
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
                $result = array_replace_recursive($result, $data);
            }
        }

        // Sort the paths and components.
        ksort($result['paths']);

        foreach ($result['components'] as $key => $_) {
            ksort($result['components'][$key]);
        }

        $result = $this->applyCorrectApiBaseUrl($result);

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
            foreach ($data['paths'] as $path => $methods) {
                foreach ($methods as $method => $operation) {
                    if (is_array($operation) && !isset($operation['x-addon'])) {
                        $data['paths'][$path][$method]['x-addon'] = $addonKey;
                    }
                }
            }
        }

        if (!empty($data['components'])) {
            foreach ($data['components'] as $type => $components) {
                foreach ($components as $key => $component) {
                    if (!isset($component['x-addon'])) {
                        $data['components'][$type][$key]['x-addon'] = $addonKey;
                    }
                }
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
}
