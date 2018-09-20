<?php
/**
 * Gdn_Configuration & Gdn_ConfigurationSource
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * The Configuration class can be used to load configuration arrays from files,
 * retrieve settings from the arrays, assign new values to the arrays, and save
 * the arrays back to the files.
 */
class Gdn_Configuration extends Gdn_Pluggable {

    /** Cache key format. */
    const CONFIG_FILE_CACHE_KEY = 'garden.config.%s';

    /** @var string  */
    public $NotFound = 'NOT_FOUND';

    /**
     * @var array Holds the associative array of configuration data.
     * ie. <code>$this->_Data['Group0']['Group1']['ConfigurationName'] = 'Value';</code>
     */
    public $Data = [];

    /** @var string The path to the default configuration file. */
    protected $defaultPath;

    /**
     * @var array Configuration Source List
     * Associative array of Gdn_ConfigurationSource objects indexed by their respective types and source URIs.
     * E.g:
     *   file:/path/to/config/file.php => ...
     *   string:tagname => ...
     */
    protected $sources = [];

    /**
     * @var Gdn_ConfigurationSource Dynamic (writable) config source.
     * This is the configuration source that is written to when saves or removes are occurring.
     */
    protected $dynamic = null;

    /** @var boolean Use caching to load and save configs? */
    protected $useCaching = false;

    /** @var boolean Allow dot-delimited splitting? */
    protected $splitting = true;

    /** @var boolean Whether or not to autosave this config when it is destructed. */
    protected $autoSave = true;

    /** @var string The default top level group for new configs. */
    protected $defaultGroup = 'Configuration';

    /** @var null The sort flag to use with ksort. */
    private $sortFlag = null;

    /** @var array Format option overrides. */
    private $formatOptions = [];

    /**
     * Initialize a new instance of the {@link Gdn_Configuration} class.
     *
     * @param string $defaultGroup
     */
    public function __construct($defaultGroup = null) {
        parent::__construct();
        if (!is_null($defaultGroup)) {
            $this->defaultGroup = $defaultGroup;
        }

        if (defined('PATH_CONF_DEFAULT')) {
            $this->defaultPath = PATH_CONF_DEFAULT;
        } else {
            $this->defaultPath = PATH_CONF.'/config.php';
        }
    }

    /**
     * Format a string as a PHP comment.
     *
     * @param string $str The string to format.
     * @param string[] $lines The output buffer.
     */
    private static function formatComment(string $str, array &$lines) {
        $commentLines = explode("\n", str_replace("\r", '', $str));

        foreach ($commentLines as $line) {
            $lines[] = '// '.$line;
        }
    }

    /**
     * Set a format option to be used by the Gdn_Configuration::format function.
     *
     * @param string $formatOption The option in $allowedOptions that you want to update.
     * @param string|bool $value The value of the option you want to update.
     */
    public function setFormatOption($formatOption, $value) {
        $allowedOptions = ['VariableName', 'WrapPHP', 'SafePHP', 'Headings', 'ByLine', 'FormatStyle'];

        if (in_array($formatOption, $allowedOptions)) {
            $this->formatOptions[$formatOption] = $value;
        }
    }

    /**
     * Getter for formatOptions.
     */
    public function getFormatOptions() {
        return $this->formatOptions;
    }

    /**
     * Set the sort flag to be used with ksort.
     *
     * @link http://php.net/manual/en/function.ksort.php
     * @param int $sortFlag As defined in php standard definitions
     * @return Gdn_Configuration $this
     */
    public function setSortFlag($sortFlag) {
        $this->sortFlag = $sortFlag;
        return $this;
    }

    /**
     * @return null|int The sort flag to be used with ksort.
     */
    public function getSortFlag() {
        return $this->sortFlag;
    }

    /**
     *
     *
     * @param bool $autoSave
     */
    public function autoSave($autoSave = true) {
        $this->autoSave = (boolean)$autoSave;
    }

    /**
     * Allow dot-delimited splitting on keys?
     *
     * @param boolean $splitting
     */
    public function splitting($splitting = true) {
        $this->splitting = (boolean)$splitting;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function clearSaveData() {
        throw new Exception('DEPRECATED');
    }

    /**
     * Use caching when loading/saving configs.
     *
     * @param boolean $caching Whether to use caching.
     * @return boolean
     */
    public function caching($caching = null) {
        if (!is_null($caching)) {
            $this->useCaching = (bool)$caching;
        }
        return $this->useCaching;
    }

    /**
     * Clear cache entry for this config file.
     *
     * @param string $configFile
     * @return void
     */
    public function clearCache($configFile) {
        $fileKey = sprintf(Gdn_Configuration::CONFIG_FILE_CACHE_KEY, $configFile);
        if (Gdn::cache()->type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::cache()->activeEnabled()) {
            Gdn::cache()->remove($fileKey, [
                Gdn_Cache::FEATURE_NOPREFIX => true
            ]);
        }
    }

    /**
     * Gets or sets the path of the default configuration file.
     *
     * @param string $value Pass a value to set a new default config path.
     * @return string Returns the current default config path.
     * @since 2.3
     */
    public function defaultPath($value = null) {
        if ($value !== null) {
            $this->defaultPath = $value;
        }
        return $this->defaultPath;
    }

    /**
     * Finds the data at a given position and returns a reference to it.
     *
     * @param string $name The name of the configuration using dot notation.
     * @param boolean $create Whether or not to create the data if it isn't there already.
     * @return mixed A reference to the configuration data node.
     */
    public function &find($name, $create = true) {
        $array = &$this->Data;

        if ($name == '') {
            return $array;
        }

        $keys = explode('.', $name);
        // If splitting is off, HANDLE IT
        if (!$this->splitting) {
            $firstKey = val(0, $keys);
            if ($firstKey == $this->defaultGroup) {
                $keys = [array_shift($keys), implode('.', $keys)];
            } else {
                $keys = [$name];
            }
        }
        $keyCount = count($keys);

        for ($i = 0; $i < $keyCount; ++$i) {
            $key = $keys[$i];

            if (!array_key_exists($key, $array)) {
                if ($create) {
                    if ($i == $keyCount - 1) {
                        $array[$key] = null;
                    } else {
                        $array[$key] = [];
                    }
                } else {
                    $array = &$this->NotFound;
                    break;
                }
            }
            $array = &$array[$key];
        }
        return $array;
    }

    /**
     *
     *
     * @param $data
     * @param array $options
     * @return string
     */
    public static function format($data, $options = []) {
        if (is_string($options)) {
            $options = ['VariableName' => $options];
        }

        $defaults = [
            'VariableName' => 'Configuration',
            'WrapPHP' => true,
            'SafePHP' => true,
            'Headings' => true,
            'ByLine' => true,
            'FormatStyle' => 'Array'
        ];
        $options = array_merge($defaults, $options);
        $variableName = val('VariableName', $options);
        $wrapPHP = val('WrapPHP', $options, true);
        $safePHP = val('SafePHP', $options, true);
        $byLine = val('ByLine', $options, false);
        $headings = val('Headings', $options, true);
        $formatStyle = val('FormatStyle', $options);
        $formatter = "Format{$formatStyle}Assignment";

        $firstLine = '';
        $lines = [];
        if ($wrapPHP) {
            $firstLine .= "<?php ";
        }
        if ($safePHP) {
            $firstLine .= "if (!defined('APPLICATION')) exit();";
        }

        if (!empty($firstLine)) {
            $lines[] = $firstLine;
        }

        if (!is_array($data)) {
            return $lines[0];
        }

        $lastKey = false;
        foreach ($data as $key => $value) {
            if ($headings && $lastKey != $key && is_array($value)) {
                $lines[] = '';
                self::formatComment($key, $lines);
                $lastKey = $key;
            }

            if ($formatStyle == 'Array') {
                $prefix = '$'.$variableName."[".var_export($key, true)."]";
            }
            if ($formatStyle == 'Dotted') {
                $prefix = '$'.$variableName."['".trim(var_export($key, true), "'");
            }

            $formatter($lines, $prefix, $value);
        }

        if ($byLine) {
            $session = Gdn::session();
            $user = $session->UserID > 0 && is_object($session->User) ? $session->User->Name : 'Unknown';
            $lines[] = '';
            self::formatComment('Last edited by '.$user.' ('.remoteIp().') '.Gdn_Format::toDateTime(), $lines);
        }

        $result = implode(PHP_EOL, $lines);
        return $result;
    }

    /**
     * Gets a setting from the configuration array. Returns $defaultValue if the value isn't found.
     *
     * @param string $name The name of the configuration setting to get. If the setting is contained
     * within an associative array, use dot denomination to get the setting. ie.
     * <code>$this->get('Database.Host')</code> would retrieve <code>$Configuration[$Group]['Database']['Host']</code>.
     * @param mixed $defaultValue If the parameter is not found in the group, this value will be returned.
     * @return mixed The configuration value.
     */
    public function get($name, $defaultValue = false) {
        // Shortcut, get the whole config
        if ($name == '.') {
            return $this->Data;
        }

        $keys = explode('.', $name);
        // If splitting is off, HANDLE IT
        if (!$this->splitting) {
//         $FirstKey = getValue(0, $Keys);
            $firstKey = $keys[0];
            if ($firstKey == $this->defaultGroup) {
                $keys = [array_shift($keys), implode('.', $keys)];
            } else {
                $keys = [$name];
            }
        }
        $keyCount = count($keys);

        $value = $this->Data;
        for ($i = 0; $i < $keyCount; ++$i) {
            if (is_array($value) && array_key_exists($keys[$i], $value)) {
                $value = $value[$keys[$i]];
            } else {
                return $defaultValue;
            }
        }

        if (is_string($value)) {
            $result = self::unserialize($value);
        } else {
            $result = $value;
        }

        return $result;
    }

    /**
     * Get a reference to the internal ConfigurationSource for a given type and ID
     *
     * @param string $type 'file' or 'string'
     * @param string $identifier filename or string tag
     * @return ConfigurationSource
     */
    public function getSource($type, $identifier) {
        $sourceTag = "{$type}:{$identifier}";
        if (!array_key_exists($sourceTag, $this->sources)) {
            return false;
        }

        return $this->sources[$sourceTag];
    }

    /**
     * Assigns a setting to the configuration array.
     *
     * @param string $name The name of the configuration setting to assign. If the setting is
     *   contained within an associative array, use dot denomination to get the
     *   setting. ie. $this->set('Database.Host', $value) would set
     *   $Configuration[$Group]['Database']['Host'] = $value
     * @param mixed $value The value of the configuration setting.
     * @param boolean $overwrite If the setting already exists, should it's value be overwritten? Defaults to true.
     * @param boolean $AddToSave Whether or not to queue the value up for the next call to Gdn_Config::save().
     */
    public function set($name, $value, $overwrite = true, $save = true) {
        // Make sure the config settings are in the right format
        if (!is_array($this->Data)) {
            $this->Data = [];
        }

        if (!is_array($name)) {
            $name = [
                $name => $value
            ];
        } else {
            $overwrite = $value;
        }

        $data = $name;
        foreach ($data as $name => $value) {
            $keys = explode('.', $name);
            // If splitting is off, HANDLE IT
            if (!$this->splitting) {
                $firstKey = val(0, $keys);
                if ($firstKey == $this->defaultGroup) {
                    $keys = [array_shift($keys), implode('.', $keys)];
                } else {
                    $keys = [$name];
                }
            }
            $keyCount = count($keys);
            $settings = &$this->Data;

            for ($i = 0; $i < $keyCount; ++$i) {
                $key = $keys[$i];

                if (!is_array($settings)) {
                    $settings = [];
                }
                $keyExists = array_key_exists($key, $settings);

                if ($i == $keyCount - 1) {
                    // If we are on the last iteration of the key, then set the value.
                    if ($keyExists === false || $overwrite === true) {
                        $settings[$key] = $value;
                    }
                } else {
                    // Build the array as we loop over the key. Doucement.
                    if ($keyExists === false) {
                        $settings[$key] = [];
                    }

                    // Advance the pointer
                    $settings = &$settings[$key];
                }
            }
        }

        if ($save) {
            $this->dynamic->set($name, $value, $overwrite);
        }
    }

    /**
     * Removes the specified key from the specified group (if it exists).
     *
     * Returns false if the key is not found for removal, true otherwise.
     *
     * @param string $name The name of the configuration setting with dot notation.
     * @return boolean Wether or not the key was found.
     */
    public function remove($name, $save = true) {
        // Make sure the config settings are in the right format
        if (!is_array($this->Data)) {
            return false;
        }

        $found = false;
        $keys = explode('.', $name);
        // If splitting is off, HANDLE IT
        if (!$this->splitting) {
            $firstKey = getValue(0, $keys);
            if ($firstKey == $this->defaultGroup) {
                $keys = [array_shift($keys), implode('.', $keys)];
            } else {
                $keys = [$name];
            }
        }
        $keyCount = count($keys);
        $settings = &$this->Data;

        for ($i = 0; $i < $keyCount; ++$i) {
            $key = $keys[$i];

            // Key will always be in here if it is anywhere at all
            if (array_key_exists($key, $settings)) {
                if ($i == ($keyCount - 1)) {
                    // We are at the setting, so unset it.
                    $found = true;
                    unset($settings[$key]);
                } else {
                    // Advance the pointer
                    $settings =& $settings[$key];
                }
            } else {
                $found = false;
                break;
            }
        }

        if ($save && $this->dynamic) {
            $this->dynamic->remove($name);
        }

        return $found;
    }

    /**
     * Loads an array of settings from a file into the object with the specified name;
     *
     * @param string $file A string containing the path to a file that contains the settings array.
     * @param string $name The name of the variable and initial group settings.
     *   Note: When $name is 'Configuration' then the data will be set to the root of the config.
     * @param boolean $dynamic Optional, whether to treat this as the request's "dynamic" config, and
     *   to save config changes here. These settings will also be re-applied later when "OverlayDynamic"
     *   is called after all defaults are loaded.
     * @return boolean
     */
    public function load($file, $name = 'Configuration', $dynamic = false) {
        $configurationSource = Gdn_ConfigurationSource::fromFile($this, $file, $name);
        if (!$configurationSource) {
            return false;
        }

        $useSplitting = $this->splitting;
        $configurationSource->splitting($useSplitting);

        if (!$configurationSource) {
            return false;
        }
        $sourceTag = "file:{$file}";
        $this->sources[$sourceTag] = $configurationSource;

        if ($dynamic) {
            $this->dynamic = $configurationSource;
        }

        if (!$useSplitting) {
            $this->massImport($configurationSource->export());
        } else {
            $loaded = $configurationSource->export();
            self::mergeConfig($this->Data, $loaded);
        }
    }

    /**
     * Loads settings from a string into the object with the specified name;
     *
     * This string should be a textual representation of a PHP config array, ready
     * to be eval()'d.
     *
     * @param string $string A string containing the php settings array.
     * @param string $tag A string descriptor of this config set
     * @param string $name The name of the variable and initial group settings.
     *   Note: When $name is 'Configuration' then the data will be set to the root of the config.
     * @param boolean $dynamic Optional, whether to treat this as the request's "dynamic" config, and
     *   to save config changes here. These settings will also be re-applied later when "OverlayDynamic"
     *   is called after all defaults are loaded.
     * @return boolean
     */
    public function loadString($string, $tag, $name = 'Configuration', $dynamic = true, $saveCallback = null, $callbackOptions = null) {
        $configurationSource = Gdn_ConfigurationSource::fromString($this, $string, $tag, $name);
        if (!$configurationSource) {
            return false;
        }

        $useSplitting = $this->splitting;
        $configurationSource->splitting($useSplitting);

        $sourceTag = "string:{$tag}";
        $this->sources[$sourceTag] = $configurationSource;

        if ($dynamic) {
            $this->dynamic = $configurationSource;
        }

        if (!$useSplitting) {
            $this->massImport($configurationSource->export());
        } else {
            self::mergeConfig($this->Data, $configurationSource->export());
        }

        // Callback for saving
        if (!is_null($saveCallback)) {
            $configurationSource->assignCallback($saveCallback, $callbackOptions);
        }
    }


    /**
     * Loads settings from an array into the object with the specified name;
     *
     * This array should be a hierarchical Vanilla config.
     *
     * @param string $configData An array containing the configuration data
     * @param string $tag A string descriptor of this config set
     * @param string $name The name of the variable and initial group settings.
     *   Note: When $name is 'Configuration' then the data will be set to the root of the config.
     * @param boolean $dynamic Optional, whether to treat this as the request's "dynamic" config, and
     *   to save config changes here. These settings will also be re-applied later when "OverlayDynamic"
     *   is called after all defaults are loaded.
     * @return boolean
     */
    public function loadArray($configData, $tag, $name = 'Configuration', $dynamic = true, $saveCallback = null, $callbackOptions = null) {
        $configurationSource = Gdn_ConfigurationSource::fromArray($this, $configData, $tag, $name);
        if (!$configurationSource) {
            return false;
        }

        $useSplitting = $this->splitting;
        $configurationSource->splitting($useSplitting);

        $sourceTag = "array:{$tag}";
        $this->sources[$sourceTag] = $configurationSource;

        if ($dynamic) {
            $this->dynamic = $configurationSource;
        }

        if (!$useSplitting) {
            $this->massImport($configurationSource->export());
        } else {
            self::mergeConfig($this->Data, $configurationSource->export());
        }

        // Callback for saving
        if (!is_null($saveCallback)) {
            $configurationSource->assignCallback($saveCallback, $callbackOptions);
        }
    }

    /**
     * DO NOT USE, THIS IS RUBBISH
     *
     * @deprecated
     */
    public static function loadFile($path, $options = []) {
        throw new Exception("DEPRECATED");
    }

    /**
     * Import a large pre-formatted set of configs efficiently
     *
     * NOTE: ONLY WORKS WHEN SPLITTING IS OFF!
     *
     * @param type $data
     */
    public function massImport($data) {
        if ($this->splitting) {
            return;
        }
        $this->Data = array_replace($this->Data, $data);

        if ($this->dynamic instanceof Gdn_ConfigurationSource) {
            $this->dynamic->massImport($data);
        }
    }

    /**
     * Merge a newly loaded config into the current active state
     *
     * Recursively
     *
     * @param array $data Reference to the current active state
     * @param array $loaded Data to merge
     */
    protected static function mergeConfig(&$data, $loaded) {
        foreach ($loaded as $key => $value) {
            if (array_key_exists($key, $data) && is_array($data[$key]) && is_array($value) && !self::isList($value)) {
                self::mergeConfig($data[$key], $value);
            } else {
                $data[$key] = $value;
            }
        }
    }

    /**
     * Determine if a given array is a list (or a hash)
     *
     * @param array $list
     * @return boolean
     */
    protected static function isList(&$list) {
        $n = count($list);
        for ($i = 0; $i < $n; $i++) {
            if (!isset($list[$i]) && !array_key_exists($i, $list)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get current dynamic ConfigurationSource
     *
     * @return Gdn_ConfigurationSource
     */
    public function dynamic() {
        return $this->dynamic;
    }

    /**
     * Re-apply the settings from the current dynamic config source
     *
     * It may be necessary to load some default configs after loading the client
     * config. These defaults may be overridden in the client's initial config,
     * so that will need to be re-applied once the correct defaults are included.
     *
     * This method does that
     */
    public function overlayDynamic() {
        if ($this->dynamic instanceof Gdn_ConfigurationSource) {
            $loaded = $this->dynamic->export();
            self::mergeConfig($this->Data, $loaded);
        }
    }

    /**
     * Remove key (or keys) from config
     *
     * @param string|array $name Key name, or assoc array of keys to unset
     * @param bool|array $options Bool = whether to save or not. Assoc array =
     *   Save => Whether to save or not
     */
    public function removeFromConfig($name, $options = []) {
        $save = $options === false ? false : val('Save', $options, true);

        if (!is_array($name)) {
            $name = [$name];
        }

        // Remove specified entries
        foreach ($name as $k) {
            $this->remove($k, $save);
        }
    }

    /**
     * Saves all settings in $group to $file.
     *
     * @param string $file The full path to the file where the Settings should be saved.
     * @param string $group The name of the settings group to be saved to the $file.
     * @return boolean
     */
    public function save($file = null, $group = null) {

        // Plain calls to Gdn::config()->save() simply save the dynamic config and return
        if (is_null($file)) {
            return $this->dynamic->save();
        }

        // ... otherwise we're trying to extract some of the config for some reason
        if ($file == '') {
            trigger_error(errorMessage('You must specify a file path to be saved.', $group, 'Save'), E_USER_ERROR);
        }

        if (!is_writable($file)) {
            throw new Exception(sprintf(t("Unable to write to config file '%s' when saving."), $file));
        }

        if (empty($group)) {
            $group = $this->defaultGroup;
        }

        $data = &$this->Data;
        ksort($data, $this->getSortFlag());

        // Check for the case when the configuration is the group.
        if (is_array($data) && count($data) == 1 && array_key_exists($group, $data)) {
            $data = $data[$group];
        }

        // Do a sanity check on the config save.
        if ($file == $this->defaultPath()) {
            if (!isset($data['Database'])) {
                if ($pm = Gdn::pluginManager()) {
                    $pm->EventArguments['Data'] = $data;
                    $pm->EventArguments['Backtrace'] = debug_backtrace();
                    $pm->fireEvent('ConfigError');
                }
                return false;
            }
        }

        // Build string
        $fileContents = $this->format($data, [
            'VariableName' => $group,
            'Headers' => true,
            'ByLine' => true,
            'WrapPHP' => true
        ]);

        if ($fileContents === false) {
            trigger_error(errorMessage('Failed to define configuration file contents.', $group, 'Save'), E_USER_ERROR);
        }

        $fileKey = sprintf(Gdn_Configuration::CONFIG_FILE_CACHE_KEY, $file);
        if ($this->caching() && Gdn::cache()->type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::cache()->activeEnabled()) {
            Gdn::cache()->store($fileKey, $data, [
                Gdn_Cache::FEATURE_NOPREFIX => true,
                Gdn_Cache::FEATURE_EXPIRY => 3600
            ]);
        }

        // Infrastructure deployment. Use old method.
        $tmpFile = tempnam(PATH_CONF, 'config');
        $result = false;
        if (file_put_contents($tmpFile, $fileContents) !== false) {
            chmod($tmpFile, 0664);
            $result = rename($tmpFile, $file);
        }

        if ($result) {
            if (function_exists('apc_delete_file')) {
                // This fixes a bug with some configurations of apc.
                @apc_delete_file($file);
            } elseif (function_exists('opcache_invalidate')) {
                @opcache_invalidate($file);
            }
        }

        return $result;
    }

    /**
     *
     *
     * @param $path
     * @param $data
     * @param array $options
     * @throws Exception
     */
    public static function saveFile($path, $data, $options = []) {
        throw new Exception("DEPRECATED");
    }

    /**
     *
     *
     * @param $name
     * @param string $value
     * @param array $options
     * @return bool|int
     */
    public function saveToConfig($name, $value = '', $options = []) {
        $save = $options === false ? false : val('Save', $options, true);
        $removeEmpty = val('RemoveEmpty', $options);

        if (!is_array($name)) {
            $name = [$name => $value];
        }

        // Apply changes one by one
        $result = true;
        foreach ($name as $k => $v) {
            if (!$v && $removeEmpty) {
                $this->remove($k);
            } else {
                $result = $result & $this->set($k, $v, true, $save);
            }
        }

        return $result;
    }

    /**
     * Takes a serialized variable and unserializes it back into its original state.
     *
     * @param string $serializedString A json or php serialized string to be unserialized.
     * @return mixed
     * @deprecated
     */
    private static function unserialize($serializedString) {
        $result = $serializedString;

        if (is_string($serializedString)) {
            if (substr_compare('a:', $serializedString, 0, 2) === 0 || substr_compare('O:', $serializedString, 0, 2) === 0) {
                $result = unserialize($serializedString, ['allowed_classes' => false]);
            } elseif (substr_compare('obj:', $serializedString, 0, 4) === 0) {
                $result = json_decode(substr($serializedString, 4), false);
            } elseif (substr_compare('arr:', $serializedString, 0, 4) === 0) {
                $result = json_decode(substr($serializedString, 4), true);
            }
        }
        return $result;
    }

    /**
     *
     */
    public function shutdown() {
        foreach ($this->sources as $source) {
            $source->shutdown();
        }
    }

    /**
     *
     */
    public function __destruct() {
        if ($this->autoSave) {
            $this->shutdown();
        }
    }
}

/**
 * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
 * keys to arrays rather than overwriting the value in the first array with the duplicate
 * value in the second array, as array_merge does. I.e., with array_merge_recursive,
 * this happens (documented behavior):
 *
 * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('org value', 'new value'));
 *
 * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
 * Matching keys' values in the second array overwrite those in the first array, as is the
 * case with array_merge, i.e.:
 *
 * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('new value'));
 *
 * Parameters are passed by reference, though only for performance reasons. They're not
 * altered by this function.
 *
 * @param array $array1
 * @param mixed $array2
 * @return array
 * @author daniel@danielsmedegaardbuus.dk
 */
function &arrayMergeRecursiveDistinct(array &$array1, &$array2 = null) {
    $merged = $array1;

    if (is_array($array2)) {
        foreach ($array2 as $key => $val) {
            if (is_array($array2[$key])) {
                $merged[$key] = is_array($merged[$key]) ? arrayMergeRecursiveDistinct($merged[$key], $array2[$key]) : $array2[$key];
            } else {
                $merged[$key] = $val;
            }
        }
    }

    return $merged;
}

/**
 * Class Gdn_ConfigurationSource
 */
class Gdn_ConfigurationSource extends Gdn_Pluggable {

    /** @var Gdn_Configuration Top level configuration object to reference. */
    protected $Configuration;

    /**  @var string Type of source (e.g. file or string). */
    protected $Type;

    /** @var string Name of source (e.g. filename, or string source tag). */
    protected $Source;

    /** @var string Group name for this config source (e.g. Configuration). */
    protected $Group;

    /** @var array Settings as they were when loaded (to facilitate logging config change diffs). */
    protected $Initial;

    /** @var array Current array of live config settings for this source. */
    protected $Settings;

    /** @var boolean Whether this config source has been modified since loading. */
    protected $Dirty;

    /** @var boolean Allow key splitting on dots. */
    protected $Splitting;

    /** @var callback Save callback. */
    protected $Callback;

    /** @var array Save callback options. */
    protected $CallbackOptions;

    /**
     *
     *
     * @param $configuration
     * @param $type
     * @param $source
     * @param $group
     * @param $settings
     */
    public function __construct($configuration, $type, $source, $group, $settings) {
        parent::__construct();

        $this->Configuration = $configuration;

        $this->Type = $type;
        $this->Source = $source;
        $this->Group = $group;
        $this->Initial = $settings;
        $this->Settings = $settings;
        $this->Dirty = false;
        $this->Splitting = true;

        $this->Callback = false;
        $this->CallbackOptions = null;
    }

    /**
     * Set a save callback
     *
     * @param callback $callback
     * @param array $options Callback options
     * @return boolean
     */
    public function assignCallback($callback, $options = null) {
        if (!is_callable($callback)) {
            return false;
        }

        $this->Callback = $callback;
        $this->CallbackOptions = $options;
    }

    /**
     *
     *
     * @param bool $splitting
     */
    public function splitting($splitting = true) {
        $this->Splitting = (boolean)$splitting;
    }

    /**
     *
     *
     * @return string
     */
    public function identify() {
        return __METHOD__.":{$this->Type}:{$this->Source}:".(int)$this->Dirty;
    }

    /**
     *
     *
     * @return string
     */
    public function group() {
        return $this->Group;
    }

    /**
     * Load config data from a file.
     *
     * @param Gdn_Configuration $Parent Parent config object
     * @param string $File Path to config file to load
     * @param string $Name Optional setting name
     * @return Gdn_ConfigurationSource
     */
    public static function fromFile($Parent, $File, $Name = 'Configuration') {
        $LoadedFromCache = false;
        $UseCache = false;
        if ($Parent && $Parent->caching()) {
            $FileKey = sprintf(Gdn_Configuration::CONFIG_FILE_CACHE_KEY, $File);
            if (Gdn::cache()->type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::cache()->activeEnabled()) {
                $UseCache = true;
                $CachedConfigData = Gdn::cache()->get($FileKey, [
                    Gdn_Cache::FEATURE_NOPREFIX => true
                ]);
                $LoadedFromCache = ($CachedConfigData !== Gdn_Cache::CACHEOP_FAILURE);
            }
        }

        // Define the variable properly.
        $$Name = null;

        // If we're not loading config from cache, directly include the conf file
        if ($LoadedFromCache) {
            $$Name = $CachedConfigData;
        }

        if ((is_null($$Name) || !is_array($$Name)) && file_exists($File)) {
            $LoadedFromCache = false;
            // Include the file.
            require $File;
        }

        // Make sure the config variable is here and is an array.
        if (!is_array($$Name)) {
            $$Name = [];
        }

        // We're caching, using the cache, and this data was not loaded from cache.
        // Write it there now.
        if ($Parent && $Parent->caching() && $UseCache && !$LoadedFromCache) {
            Gdn::cache()->store($FileKey, $$Name, [
                Gdn_Cache::FEATURE_NOPREFIX => true,
                Gdn_Cache::FEATURE_EXPIRY => 3600
            ]);
        }

        return new Gdn_ConfigurationSource($Parent, 'file', $File, $Name, $$Name);
    }

    /**
     * Load config data from a string
     *
     * @param Gdn_Configuration $parent Parent config object
     * @param string $string Config data string
     * @param string $tag Internal friendly name
     * @param string $name Optional setting name
     * @return Gdn_ConfigurationSource
     */
    public static function fromString($parent, $string, $tag, $name = 'Configuration') {
        $configurationData = self::parseString($string, $name);
        if ($configurationData === false) {
            throw new Exception('Could not parse config string.');
        }

        return new Gdn_ConfigurationSource($parent, 'string', $tag, $name, $configurationData);
    }

    /**
     * Load config data from an array
     *
     * @param Gdn_Configuration $parent Parent config object
     * @param array $configData Config data array
     * @param string $tag Internal friendly name
     * @param string $name Optional setting name
     * @return Gdn_ConfigurationSource
     */
    public static function fromArray($parent, $configData, $tag, $name = 'Configuration') {
        if (!is_array($configData)) {
            throw new Exception('Invalid config data.');
        }
        return new Gdn_ConfigurationSource($parent, 'array', $tag, $name, $configData);
    }

    /**
     *
     *
     * @param $String
     * @param $Name
     * @return bool
     */
    public static function parseString($String, $Name) {
        // Define the variable properly.
        $$Name = null;

        // Parse the string
        if (!empty($String)) {
            $String = trim(str_replace(['<?php', '<?', '?>'], '', $String));
            $Parsed = eval($String);
            if ($Parsed === false) {
                return false;
            }
        }

        // Make sure the config variable is here and is an array.
        if (is_null($$Name) || !is_array($$Name)) {
            $$Name = [];
        }

        return $$Name;
    }

    /**
     * Import a large pre-formatted set of configs efficiently
     *
     * NOTE: ONLY WORKS WHEN SPLITTING IS OFF!
     *
     * @param type $data
     */
    public function massImport($data) {
        if ($this->Splitting) {
            return;
        }

        // Only do dirty checks if we aren't already dirty
        if (!$this->Dirty) {
            $checkCopy = $this->Settings;
        }

        $this->Settings = array_replace($this->Settings, $data);

        // Only do dirty checks if we aren't already dirty
        if (!$this->Dirty) {
            if ($checkCopy != $this->Settings) {
                $this->Dirty = true;
            }
        }
    }

    /**
     *
     *
     * @param $file
     */
    public function toFile($file) {
        $this->Type = 'file';
        $this->Source = $file;
        $this->Dirty = true;
    }

    /**
     *
     *
     * @return array
     */
    public function export() {
        return $this->Settings;
    }

    /**
     *
     *
     * @param $settings
     */
    public function import($settings) {
        $this->Settings = $settings;
        $this->Dirty = true;
    }

    /**
     * Removes the specified key from the config (if it exists).
     * Returns false if the key is not found for removal, true otherwise.
     *
     * @param string $name The name of the configuration setting with dot notation.
     * @return boolean Whether or not the key was found.
     */
    public function remove($name) {
        // Make sure this source' config settings are in the right format
        if (!is_array($this->Settings)) {
            $this->Settings = [];
        }

        $found = false;
        $keys = explode('.', $name);
        // If splitting is off, HANDLE IT
        if (!$this->Splitting) {
            $firstKey = val(0, $keys);
            if ($firstKey == $this->Group) {
                $keys = [array_shift($keys), implode('.', $keys)];
            } else {
                $keys = [$name];
            }
        }
        $keyCount = count($keys);
        $settings = &$this->Settings;

        for ($i = 0; $i < $keyCount; ++$i) {
            $key = $keys[$i];

            // Key will always be in here if it is anywhere at all
            if (array_key_exists($key, $settings)) {
                if ($i == ($keyCount - 1)) {
                    // We are at the setting, so unset it.
                    $found = true;
                    unset($settings[$key]);
                    $this->Dirty = true;
                } else {
                    // Advance the pointer
                    $settings = &$settings[$key];
                }
            } else {
                $found = false;
                break;
            }
        }

        return $found;
    }

    /**
     *
     *
     * @param $name
     * @param null $value
     * @param bool $overwrite
     */
    public function set($name, $value = null, $overwrite = true) {
        // Make sure this source' config settings are in the right format
        if (!is_array($this->Settings)) {
            $this->Settings = [];
        }

        if (!is_array($name)) {
            $name = [
                $name => $value
            ];
        } else {
            $overwrite = $value;
        }

        $data = $name;
        foreach ($data as $name => $value) {
            $keys = explode('.', $name);
            // If splitting is off, HANDLE IT
            if (!$this->Splitting) {
                $firstKey = val(0, $keys);
                if ($firstKey == $this->Group) {
                    $keys = [array_shift($keys), implode('.', $keys)];
                } else {
                    $keys = [$name];
                }
            }
            $keyCount = count($keys);
            $settings = &$this->Settings;

            for ($i = 0; $i < $keyCount; ++$i) {
                $key = $keys[$i];

                if (!is_array($settings)) {
                    $settings = [];
                }
                $keyExists = array_key_exists($key, $settings);

                if ($i == $keyCount - 1) {
                    // If we are on the last iteration of the key, then set the value.
                    if ($keyExists === false || $overwrite === true) {
                        $oldVal = val($key, $settings, null);
                        $setVal = $value;

                        $settings[$key] = $setVal;
                        if (!$keyExists || $setVal != $oldVal) {
                            $this->Dirty = true;
                        }
                    }
                } else {
                    // Build the array as we loop over the key.
                    if ($keyExists === false) {
                        $settings[$key] = [];
                    }

                    // Advance the pointer
                    $settings = &$settings[$key];
                }
            }
        }
    }

    /**
     * Gets a setting from the configuration array. Returns $defaultValue if the value isn't found.
     *
     * @param string $name The name of the configuration setting to get. If the setting is contained
     * within an associative array, use dot denomination to get the setting. ie.
     * <code>$this->get('Database.Host')</code> would retrieve <code>$Configuration[$Group]['Database']['Host']</code>.
     * @param mixed $defaultValue If the parameter is not found in the group, this value will be returned.
     * @return mixed The configuration value.
     */
    public function get($name, $defaultValue = false) {

        // Shortcut, get the whole config
        if ($name == '.') {
            return $this->Settings;
        }

        $keys = explode('.', $name);
        $keyCount = count($keys);

        $value = $this->Settings;
        for ($i = 0; $i < $keyCount; ++$i) {
            if (is_array($value) && array_key_exists($keys[$i], $value)) {
                $value = $value[$keys[$i]];
            } else {
                return $defaultValue;
            }
        }

        if (is_string($value)) {
            $result = self::unserialize($value);
        } else {
            $result = $value;
        }

        return $result;
    }

    /**
     *
     *
     * @return bool|null
     * @throws Exception
     */
    public function save() {
        if (!$this->Dirty) {
            return null;
        }

        $this->EventArguments['ConfigDirty'] = &$this->Dirty;
        $this->EventArguments['ConfigNoSave'] = false;
        $this->EventArguments['ConfigType'] = $this->Type;
        $this->EventArguments['ConfigSource'] = $this->Source;
        $this->EventArguments['ConfigData'] = $this->Settings;
        $this->fireEvent('BeforeSave');

        if ($this->EventArguments['ConfigNoSave']) {
            $this->Dirty = false;
            return true;
        }

        // Check for and fire callback if one exists
        if ($this->Callback && is_callable($this->Callback)) {
            $callbackOptions = [];
            if (!is_array($this->CallbackOptions)) {
                $this->CallbackOptions = [];
            }

            $callbackOptions = array_merge($callbackOptions, $this->CallbackOptions, [
                'ConfigDirty' => $this->Dirty,
                'ConfigType' => $this->Type,
                'ConfigSource' => $this->Source,
                'ConfigData' => $this->Settings,
                'SourceObject' => $this
            ]);

            $configSaved = call_user_func($this->Callback, $callbackOptions);

            if ($configSaved) {
                $this->Dirty = false;
                return true;
            }
        }

        switch ($this->Type) {
            case 'file':
                if (empty($this->Source)) {
                    trigger_error(errorMessage('You must specify a file path to be saved.', 'Configuration', 'Save'), E_USER_ERROR);
                }

                $checkWrite = $this->Source;
                if (!file_exists($checkWrite)) {
                    $checkWrite = dirname($checkWrite);
                }

                if (!is_writable($checkWrite)) {
                    throw new Exception(sprintf(t("Unable to write to config file '%s' when saving."), $this->Source));
                }

                $group = $this->Group;
                $data = &$this->Settings;
                if ($this->Configuration) {
                    ksort($data, $this->Configuration->getSortFlag());
                }

                // Check for the case when the configuration is the group.
                if (is_array($data) && count($data) == 1 && array_key_exists($group, $data)) {
                    $data = $data[$group];
                }

                // Do a sanity check on the config save.
                if ($this->Source == Gdn::config()->defaultPath()) {
                    // Log root config changes
                    try {
                        $logData = $this->Initial;
                        $logData['_New'] = $this->Settings;
                        LogModel::insert('Edit', 'Configuration', $logData);
                    } catch (Exception $ex) {
                    }

                    if (!isset($data['Database'])) {
                        if ($pm = Gdn::pluginManager()) {
                            $pm->EventArguments['Data'] = $data;
                            $pm->EventArguments['Backtrace'] = debug_backtrace();
                            $pm->fireEvent('ConfigError');
                        }
                        return false;
                    }
                }

                $options = [
                    'VariableName' => $group,
                    'WrapPHP' => true,
                    'ByLine' => true
                ];

                if ($this->Configuration) {
                    $options = array_merge($options, $this->Configuration->getFormatOptions());
                }

                // Write config data to string format, ready for saving
                $fileContents = Gdn_Configuration::format($data, $options);

                if ($fileContents === false) {
                    trigger_error(errorMessage('Failed to define configuration file contents.', 'Configuration', 'Save'), E_USER_ERROR);
                }

                // Save to cache if we're into that sort of thing
                $fileKey = sprintf(Gdn_Configuration::CONFIG_FILE_CACHE_KEY, $this->Source);
                if ($this->Configuration && $this->Configuration->caching() && Gdn::cache()->type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::cache()->activeEnabled()) {
                    $cachedConfigData = Gdn::cache()->store($fileKey, $data, [
                        Gdn_Cache::FEATURE_NOPREFIX => true,
                        Gdn_Cache::FEATURE_EXPIRY => 3600
                    ]);
                }

                $tmpFile = tempnam(PATH_CONF, 'config');
                $result = false;
                if (file_put_contents($tmpFile, $fileContents) !== false) {
                    chmod($tmpFile, 0775);
                    $result = rename($tmpFile, $this->Source);
                }

                if ($result) {
                    if (function_exists('apc_delete_file')) {
                        // This fixes a bug with some configurations of apc.
                        @apc_delete_file($this->Source);
                    } elseif (function_exists('opcache_invalidate')) {
                        @opcache_invalidate($this->Source);
                    }
                }

                $this->Dirty = false;
                return $result;
                break;

            case 'json':
            case 'array':
            case 'string':
                /**
                 * How would these even save? String config data must be handled by
                 * an event hook or callback, if at all.
                 */
                $this->Dirty = false;
                return false;
                break;
        }
    }

    /**
     * Takes a serialized variable and unserializes it back into its original state.
     *
     * @param string $serializedString A json or php serialized string to be unserialized.
     * @return mixed
     * @deprecated
     */
    private static function unserialize($serializedString) {
        $result = $serializedString;

        if (is_string($serializedString)) {
            if (substr_compare('a:', $serializedString, 0, 2) === 0 || substr_compare('O:', $serializedString, 0, 2) === 0) {
                $result = unserialize($serializedString, ['allowed_classes' => false]);
            } elseif (substr_compare('obj:', $serializedString, 0, 4) === 0) {
                $result = json_decode(substr($serializedString, 4), false);
            } elseif (substr_compare('arr:', $serializedString, 0, 4) === 0) {
                $result = json_decode(substr($serializedString, 4), true);
            }
        }
        return $result;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function shutdown() {
        if ($this->Dirty) {
            $this->save();
        }
    }
}
