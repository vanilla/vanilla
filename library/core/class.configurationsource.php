<?php
/**
 * Gdn_Configuration & Gdn_ConfigurationSource
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

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

    /** @var callable Save callback. */
    protected $Callback;

    /** @var array Save callback options. */
    protected $CallbackOptions;

    /**
     * Gdn_ConfigurationSource constructor.
     *
     * @param Gdn_Configuration $configuration Top level configuration object to reference.
     * @param string $type Type of source (e.g. file or string).
     * @param string $source Name of source (e.g. filename, or string source tag).
     * @param string $group Group name for this config source (e.g. Configuration).
     * @param array $settings Current array of live config settings for this source.
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
     * @param callable $callback
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
     * Allow key splitting on dots.
     *
     * @param bool $splitting The new value.
     */
    public function splitting($splitting = true) {
        $this->Splitting = (boolean)$splitting;
    }

    /**
     * Identify this object for debugging.
     *
     * @return string Returns an identity as a string.
     * @deprecated
     */
    public function identify() {
        return __METHOD__.":{$this->Type}:{$this->Source}:".(int)$this->Dirty;
    }

    /**
     * Group name for this config source (e.g. Configuration).
     *
     * @return string Returns the group name.
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
     * Parse a config string.
     *
     * @param string $String The string to parse.
     * @param string $Name The name of the configuration variable within the string.
     * @return array|false Returns the parsed string or **false** if the string was malformed.
     * @deprecated
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
     * @param array $data
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
     * Specify this configuration source should be a file.
     *
     * @param string $file The path to the config file.
     */
    public function toFile($file) {
        $this->Type = 'file';
        $this->Source = $file;
        $this->Dirty = true;
    }

    /**
     * Export settings.
     *
     * @return array Returns the settings.
     */
    public function export() {
        return $this->Settings;
    }

    /**
     * Import settings.
     *
     * @param array $settings The settings to import.
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
     * Set a config value.
     *
     * @param array|string $name The name of the config setting or an array of config settings.
     * @param mixed $value The new value.
     * @param bool $overwrite Whether or not to overwrite an existing config value.
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

        return $value;
    }

    /**
     * Save the config.
     *
     * @return bool|null Returns **null** of the config doesn't need to be saved or a bool indicating success.
     * @throws Exception Throws an exception if something goes wrong while saving.
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
     * Handle flushing the config to its source during app shutdown.
     */
    public function shutdown() {
        if ($this->Dirty) {
            $this->save();
        }
    }
}
