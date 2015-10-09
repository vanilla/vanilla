<?php
/**
 * This file is for testing purposes only.
 * DO NOT USE THIS FILE IN PRODUCTION.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license GPLv2
 */

define('APPLICATION', 'Save Config');

/**
 * A class to save config values to Vanilla's config for testing purposes.
 */
class SimpleConfig {
    public $pathRoot;

    public function __construct() {
        $this->pathRoot = realpath(__DIR__.'/..');
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
            if (in_array($host, ['vanilla.local', 'vanilla.lc'])) {
                $slug = 'config';
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
            $contents = file_get_contents($path);
            $Configuration = [];
            include $path;
            return $Configuration;
        } else {
            return [];
        }
    }

    /**
     * Save some config values.
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
        $r = file_put_contents($path, $str);
        if ($r === false) {
            throw new Exception("Could not save: $path", 500);
        }
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
    protected static function setvalr($key, &$collection, $value = null) {
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
                        $selection[$subSelector] = array();
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
    function($errno, $errstr, $errfile, $errline, $errcontext) {
        throw new ErrorException($errstr, $errno, 1, $errfile, $errline);
    },
    E_ALL | ~E_NOTICE
);

ob_start();
header("Content-Type: application/json;charset=utf8");

try {
    throw new Exception("foo", 500);
    $input_raw = @file_get_contents('php://input');
    $data = @json_decode($input_raw, true);

    if (!$data) {
        throw new Exception('There was an error decoding the config data.', 400);
    }

    $config = new SimpleConfig();
    $config->saveToConfig($data);
    $data = $config->loadConfig();

    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Exception $ex) {
    ob_end_clean();
    http_response_code($ex->getCode());
    die(json_encode([
        'message' => $ex->getMessage(),
        'code' => $ex->getCode()
    ]));
}
ob_end_flush();