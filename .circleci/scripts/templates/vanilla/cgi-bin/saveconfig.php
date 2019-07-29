<?php
/**
 * This file is for testing purposes only.
 * DO NOT USE THIS FILE IN PRODUCTION.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT); //E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('display_errors', 'off');
ini_set('track_errors', 0);

define('APPLICATION', 'Save Config');
ob_start();

/**
 * A class to save config values to Vanilla's config for testing purposes.
 */
class SimpleConfig {
    public $pathRoot;

    /**
     * SimpleConfig constructor.
     */
    public function __construct() {
        $this->pathRoot = realpath(__DIR__ . '../');
        echo $this->pathRoot;
    }

    /**
     * A safe version of {@link file_put_contents()} that also clears op caches.
     *
     * @param string $path The path to save to.
     * @param string $contents The contents of the file.
     * @return bool Returns **true** on success or **false** on failure.
     */
    private function filePutContents($path, $contents) {
        $tmpPath = tempnam(dirname($path), 'config');
        $r = false;
        if (file_put_contents($tmpPath, $contents) !== false) {
            chmod($tmpPath, 0664);
            $r = rename($tmpPath, $path);
        }

        $this->flushPathCache($path);
        return $r;
    }

    /**
     * Delete a file and flush its op cache.
     *
     * @param string $path The file to delete.
     * @return bool Returns **true** on success or **false** on failure.
     */
    private function unlink($path) {
        $this->flushPathCache($path);
        $r = unlink($path);
        return $r;
    }

    /**
     * Flush the op cache for a file.
     *
     * @param string $path The location of the file to flush.
     */
    private function flushPathCache($path) {
        if (function_exists('apc_delete_file')) {
            // This fixes a bug with some configurations of apc.
            @apc_delete_file($path);
        } elseif (function_exists('opcache_invalidate')) {
            @opcache_invalidate($path, true);
        }
    }

    public function getConfigPath() {
        $host = $_SERVER['HTTP_HOST'];
        if (strpos($host, ':') !== false) {
            list($host, $_) = explode(':', $host, 2);
        }

        // Get the config.
        if (isset($_SERVER['NODE_SLUG'])) {
            // This is a site per folder setup.
            $slug = "$host-{$_SERVER['NODE_SLUG']}";
        } else {
            // This is a site per host setup.
            if (in_array($host, ['config'])) {
                throw new \Exception('Invalid config.');
            } else {
                $slug = $host;
            }
        }

        // Use a config specific to the site.
        $configPath = "{$this->pathRoot}/conf/$slug.php";
        return $configPath;
    }

    /**
     * Load the site's config and return it.
     *
     * @return array Returns the site's config.
     */
    public function loadConfig() {
        $path = $this->getConfigPath();

        if (file_exists($path)) {
            $Configuration = [];
            include $path;
            return $Configuration;
        } else {
            return [];
        }
    }

    /**
     * Save some config values.
     *
     * @param array $values An array of config keys and values where the keys are a dot-seperated array.
     */
    public function saveToConfig(array $values) {
        $config = $this->loadConfig();
        foreach ($values as $key => $value) {
            static::setvalr($key, $config, $value);
        }

        $path = $this->getConfigPath();

        $str = "<?php if (!defined('APPLICATION')) exit();\n\n".
            '$Configuration = '.var_export($config, true).";\n";
        $r = $this->filePutContents($path, $str);
        if ($r === false) {
            throw new Exception("Could not save: $path", 500);
        }

        return $config;
    }

    /**
     * Delete the config.
     *
     * @throws Exception Throws an exception if the config exists and could not be deleted.
     */
    public function deleteConfig() {
        $path = $this->getConfigPath();
        if (file_exists($path)) {
            if (!$this->unlink($path)) {
                throw new \Exception('Could not delete config.', 500);
            }
        }
    }

    /**
     * Return the value from an associative array or an object.
     *
     * This function differs from getValue() in that $Key can be a string consisting of dot notation that will be used
     * to recursively traverse the collection.
     *
     * @param string $key The key or property name of the value.
     * @param mixed $collection The array or object to search.
     * @param mixed $default The value to return if the key does not exist.
     * @return mixed The value from the array or object.
     */
    private function getvalr($key, $collection, $default = false) {
        $path = explode('.', $key);

        $value = $collection;
        for ($i = 0; $i < count($path); ++$i) {
            $subKey = $path[$i];

            if (is_array($value) && isset($value[$subKey])) {
                $value = $value[$subKey];
            } elseif (is_object($value) && isset($value->$subKey)) {
                $value = $value->$subKey;
            } else {
                return $default;
            }
        }
        return $value;
    }

    /**
     * Set a key to a value in a collection.
     *
     * Works with single keys or "dot" notation. If $key is an array, a simple
     * shallow array_merge is performed.
     *
     * @param string $key The key or property name of the value.
     * @param array &$collection The array or object to search.
     * @param mixed $value The value to set.
     * @return mixed Newly set value or if array merge.
     */
    private static function setvalr($key, &$collection, $value = null) {
        if (is_array($key)) {
            $collection = array_merge($collection, $key);
            return null;
        }

        if (strpos($key, '.')) {
            $path = explode('.', $key);

            $selection = &$collection;
            $mx = count($path) - 1;
            for ($i = 0; $i <= $mx; ++$i) {
                $subSelector = $path[$i];

                if (is_array($selection)) {
                    if (!isset($selection[$subSelector])) {
                        $selection[$subSelector] = [];
                    }
                    $selection = &$selection[$subSelector];
                } elseif (is_object($selection)) {
                    if (!isset($selection->$subSelector)) {
                        $selection->$subSelector = new stdClass();
                    }
                    $selection = &$selection->$subSelector;
                } else {
                    return null;
                }
            }
            return $selection = $value;
        } else {
            if (is_array($collection)) {
                return $collection[$key] = $value;
            } else {
                return $collection->$key = $value;
            }
        }
    }
}

set_error_handler(
    function ($errno, $errstr, $errfile, $errline, $errcontext) {
        throw new ErrorException($errstr, $errno, 1, $errfile, $errline);
    },
    E_ALL | ~E_NOTICE
);


header("Content-Type: application/json;charset=utf-8");

try {
    $input_raw = @file_get_contents('php://input');
    $data = @json_decode($input_raw, true);

    $config = new SimpleConfig();

    if ($data === false) {
        throw new Exception('There was an error decoding the config data.', 400);
    }

    if (!empty($_GET['deleteConfig'])) {
        $config->deleteConfig();
        $data = [];
    } else {
        $saved = $config->saveToConfig($data);
        $data = $config->loadConfig();

//        if ($saved != $data) {
//            throw new \Exception("The data did not save properly.", 500);
//        }
    }

    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Exception $ex) {
    ob_end_clean();
    http_response_code($ex->getCode() >= 400 ? $ex->getCode() : 500);
    die(json_encode([
        'message' => $ex->getMessage(),
        'code' => $ex->getCode()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
ob_end_flush();
