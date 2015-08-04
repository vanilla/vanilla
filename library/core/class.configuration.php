<?php
/**
 * Gdn_Configuration & Gdn_ConfigurationSource
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
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
    public $Data = array();

    /** @var string The path to the default configuration file. */
    protected $defaultPath;

    /**
     * @var array Configuration Source List
     * Associative array of Gdn_ConfigurationSource objects indexed by their respective types and source URIs.
     * E.g:
     *   file:/path/to/config/file.php => ...
     *   string:tagname => ...
     */
    protected $sources = array();

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

    /**
     * Initialize a new instance of the {@link Gdn_Configuration} class.
     *
     * @param string $DefaultGroup
     */
    public function __construct($DefaultGroup = null) {
        parent::__construct();
        if (!is_null($DefaultGroup)) {
            $this->defaultGroup = $DefaultGroup;
        }

        if (defined('PATH_CONF_DEFAULT')) {
            $this->defaultPath = PATH_CONF_DEFAULT;
        } else {
            $this->defaultPath = PATH_CONF.'/config.php';
        }
    }

    /**
     *
     *
     * @param bool $AutoSave
     */
    public function autoSave($AutoSave = true) {
        $this->autoSave = (boolean)$AutoSave;
    }

    /**
     * Allow dot-delimited splitting on keys?
     *
     * @param boolean $Splitting
     */
    public function splitting($Splitting = true) {
        $this->splitting = (boolean)$Splitting;
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
     * @param boolean $Caching Whether to use caching.
     * @return boolean
     */
    public function caching($Caching = null) {
        if (!is_null($Caching)) {
            $this->useCaching = (bool)$Caching;
        }
        return $this->useCaching;
    }

    /**
     * Clear cache entry for this config file.
     *
     * @param string $ConfigFile
     * @return void
     */
    public function clearCache($ConfigFile) {
        $FileKey = sprintf(Gdn_Configuration::CONFIG_FILE_CACHE_KEY, $ConfigFile);
        if (Gdn::cache()->type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::cache()->activeEnabled()) {
            Gdn::cache()->remove($FileKey, array(
                Gdn_Cache::FEATURE_NOPREFIX => true
            ));
        }
    }

    /**
     * Gets or sets the path of the default configuration file.
     *
     * @param string $Value Pass a value to set a new default config path.
     * @return string Returns the current default config path.
     * @since 2.3
     */
    public function defaultPath($Value = null) {
        if ($Value !== null) {
            $this->defaultPath = $Value;
        }
        return $this->defaultPath;
    }

    /**
     * Finds the data at a given position and returns a reference to it.
     *
     * @param string $Name The name of the configuration using dot notation.
     * @param boolean $Create Whether or not to create the data if it isn't there already.
     * @return mixed A reference to the configuration data node.
     */
    public function &find($Name, $Create = true) {
        $Array = &$this->Data;

        if ($Name == '') {
            return $Array;
        }

        $Keys = explode('.', $Name);
        // If splitting is off, HANDLE IT
        if (!$this->splitting) {
            $FirstKey = val(0, $Keys);
            if ($FirstKey == $this->defaultGroup) {
                $Keys = array(array_shift($Keys), implode('.', $Keys));
            } else {
                $Keys = array($Name);
            }
        }
        $KeyCount = count($Keys);

        for ($i = 0; $i < $KeyCount; ++$i) {
            $Key = $Keys[$i];

            if (!array_key_exists($Key, $Array)) {
                if ($Create) {
                    if ($i == $KeyCount - 1) {
                        $Array[$Key] = null;
                    } else {
                        $Array[$Key] = array();
                    }
                } else {
                    $Array = &$this->NotFound;
                    break;
                }
            }
            $Array = &$Array[$Key];
        }
        return $Array;
    }

    /**
     *
     *
     * @param $Data
     * @param array $Options
     * @return string
     */
    public static function format($Data, $Options = array()) {
        if (is_string($Options)) {
            $Options = array('VariableName' => $Options);
        }

        $Defaults = array(
            'VariableName' => 'Configuration',
            'WrapPHP' => true,
            'SafePHP' => true,
            'Headings' => true,
            'ByLine' => true,
            'FormatStyle' => 'Array'
        );
        $Options = array_merge($Defaults, $Options);
        $VariableName = val('VariableName', $Options);
        $WrapPHP = val('WrapPHP', $Options, true);
        $SafePHP = val('SafePHP', $Options, true);
        $ByLine = val('ByLine', $Options, false);
        $Headings = val('Headings', $Options, true);
        $FormatStyle = val('FormatStyle', $Options);
        $Formatter = "Format{$FormatStyle}Assignment";

        $FirstLine = '';
        $Lines = array();
        if ($WrapPHP) {
            $FirstLine .= "<?php ";
        }
        if ($SafePHP) {
            $FirstLine .= "if (!defined('APPLICATION')) exit();";
        }

        if (!empty($FirstLine)) {
            $Lines[] = $FirstLine;
        }

        if (!is_array($Data)) {
            return $Lines[0];
        }

        $LastKey = false;
        foreach ($Data as $Key => $Value) {
            if ($Headings && $LastKey != $Key && is_array($Value)) {
                $Lines[] = '';
                $Lines[] = '// '.$Key;
                $LastKey = $Key;
            }

            if ($FormatStyle == 'Array') {
                $Prefix = '$'.$VariableName."[".var_export($Key, true)."]";
            }
            if ($FormatStyle == 'Dotted') {
                $Prefix = '$'.$VariableName."['".trim(var_export($Key, true), "'");
            }

            $Formatter($Lines, $Prefix, $Value);
        }

        if ($ByLine) {
            $Session = Gdn::session();
            $User = $Session->UserID > 0 && is_object($Session->User) ? $Session->User->Name : 'Unknown';
            $Lines[] = '';
            $Lines[] = '// Last edited by '.$User.' ('.RemoteIp().')'.Gdn_Format::toDateTime();
        }

        $Result = implode(PHP_EOL, $Lines);
        return $Result;
    }

    /**
     * Gets a setting from the configuration array. Returns $DefaultValue if the value isn't found.
     *
     * @param string $Name The name of the configuration setting to get. If the setting is contained
     * within an associative array, use dot denomination to get the setting. ie.
     * <code>$this->Get('Database.Host')</code> would retrieve <code>$Configuration[$Group]['Database']['Host']</code>.
     * @param mixed $DefaultValue If the parameter is not found in the group, this value will be returned.
     * @return mixed The configuration value.
     */
    public function get($Name, $DefaultValue = false) {
        // Shortcut, get the whole config
        if ($Name == '.') {
            return $this->Data;
        }

        $Keys = explode('.', $Name);
        // If splitting is off, HANDLE IT
        if (!$this->splitting) {
//         $FirstKey = GetValue(0, $Keys);
            $FirstKey = $Keys[0];
            if ($FirstKey == $this->defaultGroup) {
                $Keys = array(array_shift($Keys), implode('.', $Keys));
            } else {
                $Keys = array($Name);
            }
        }
        $KeyCount = count($Keys);

        $Value = $this->Data;
        for ($i = 0; $i < $KeyCount; ++$i) {
            if (is_array($Value) && array_key_exists($Keys[$i], $Value)) {
                $Value = $Value[$Keys[$i]];
            } else {
                return $DefaultValue;
            }
        }

        if (is_string($Value)) {
            $Result = Gdn_Format::unserialize($Value);
        } else {
            $Result = $Value;
        }

        return $Result;
    }

    /**
     * Get a reference to the internal ConfigurationSource for a given type and ID
     *
     * @param string $Type 'file' or 'string'
     * @param string $Identifier filename or string tag
     * @return ConfigurationSource
     */
    public function getSource($Type, $Identifier) {
        $SourceTag = "{$Type}:{$Identifier}";
        if (!array_key_exists($SourceTag, $this->sources)) {
            return false;
        }

        return $this->sources[$SourceTag];
    }

    /**
     * Assigns a setting to the configuration array.
     *
     * @param string $Name The name of the configuration setting to assign. If the setting is
     *   contained within an associative array, use dot denomination to get the
     *   setting. ie. $this->Set('Database.Host', $Value) would set
     *   $Configuration[$Group]['Database']['Host'] = $Value
     * @param mixed $Value The value of the configuration setting.
     * @param boolean $Overwrite If the setting already exists, should it's value be overwritten? Defaults to true.
     * @param boolean $AddToSave Whether or not to queue the value up for the next call to Gdn_Config::Save().
     */
    public function set($Name, $Value, $Overwrite = true, $Save = true) {
        // Make sure the config settings are in the right format
        if (!is_array($this->Data)) {
            $this->Data = array();
        }

        if (!is_array($Name)) {
            $Name = array(
                $Name => $Value
            );
        } else {
            $Overwrite = $Value;
        }

        $Data = $Name;
        foreach ($Data as $Name => $Value) {
            $Keys = explode('.', $Name);
            // If splitting is off, HANDLE IT
            if (!$this->splitting) {
                $FirstKey = val(0, $Keys);
                if ($FirstKey == $this->defaultGroup) {
                    $Keys = array(array_shift($Keys), implode('.', $Keys));
                } else {
                    $Keys = array($Name);
                }
            }
            $KeyCount = count($Keys);
            $Settings = &$this->Data;

            for ($i = 0; $i < $KeyCount; ++$i) {
                $Key = $Keys[$i];

                if (!is_array($Settings)) {
                    $Settings = array();
                }
                $KeyExists = array_key_exists($Key, $Settings);

                if ($i == $KeyCount - 1) {
                    // If we are on the last iteration of the key, then set the value.
                    if ($KeyExists === false || $Overwrite === true) {
                        $Settings[$Key] = $Value;
                    }
                } else {
                    // Build the array as we loop over the key. Doucement.
                    if ($KeyExists === false) {
                        $Settings[$Key] = array();
                    }

                    // Advance the pointer
                    $Settings = &$Settings[$Key];
                }
            }
        }

        if ($Save) {
            $this->dynamic->set($Name, $Value, $Overwrite);
        }
    }

    /**
     * Removes the specified key from the specified group (if it exists).
     *
     * Returns false if the key is not found for removal, true otherwise.
     *
     * @param string $Name The name of the configuration setting with dot notation.
     * @return boolean Wether or not the key was found.
     */
    public function remove($Name, $Save = true) {
        // Make sure the config settings are in the right format
        if (!is_array($this->Data)) {
            return false;
        }

        $Found = false;
        $Keys = explode('.', $Name);
        // If splitting is off, HANDLE IT
        if (!$this->splitting) {
            $FirstKey = GetValue(0, $Keys);
            if ($FirstKey == $this->defaultGroup) {
                $Keys = array(array_shift($Keys), implode('.', $Keys));
            } else {
                $Keys = array($Name);
            }
        }
        $KeyCount = count($Keys);
        $Settings = &$this->Data;

        for ($i = 0; $i < $KeyCount; ++$i) {
            $Key = $Keys[$i];

            // Key will always be in here if it is anywhere at all
            if (array_key_exists($Key, $Settings)) {
                if ($i == ($KeyCount - 1)) {
                    // We are at the setting, so unset it.
                    $Found = true;
                    unset($Settings[$Key]);
                } else {
                    // Advance the pointer
                    $Settings =& $Settings[$Key];
                }
            } else {
                $Found = false;
                break;
            }
        }

        if ($Save && $this->dynamic) {
            $this->dynamic->remove($Name);
        }

        return $Found;
    }

    /**
     * Loads an array of settings from a file into the object with the specified name;
     *
     * @param string $File A string containing the path to a file that contains the settings array.
     * @param string $Name The name of the variable and initial group settings.
     *   Note: When $Name is 'Configuration' then the data will be set to the root of the config.
     * @param boolean $Dynamic Optional, whether to treat this as the request's "dynamic" config, and
     *   to save config changes here. These settings will also be re-applied later when "OverlayDynamic"
     *   is called after all defaults are loaded.
     * @return boolean
     */
    public function load($File, $Name = 'Configuration', $Dynamic = false) {
        $ConfigurationSource = Gdn_ConfigurationSource::fromFile($this, $File, $Name);
        if (!$ConfigurationSource) {
            return false;
        }

        $UseSplitting = $this->splitting;
        $ConfigurationSource->splitting($UseSplitting);

        if (!$ConfigurationSource) {
            return false;
        }
        $SourceTag = "file:{$File}";
        $this->sources[$SourceTag] = $ConfigurationSource;

        if ($Dynamic) {
            $this->dynamic = $ConfigurationSource;
        }

        if (!$UseSplitting) {
            $this->massImport($ConfigurationSource->export());
        } else {
            $Loaded = $ConfigurationSource->export();
            self::mergeConfig($this->Data, $Loaded);
        }
    }

    /**
     * Loads settings from a string into the object with the specified name;
     *
     * This string should be a textual representation of a PHP config array, ready
     * to be eval()'d.
     *
     * @param string $String A string containing the php settings array.
     * @param string $Tag A string descriptor of this config set
     * @param string $Name The name of the variable and initial group settings.
     *   Note: When $Name is 'Configuration' then the data will be set to the root of the config.
     * @param boolean $Dynamic Optional, whether to treat this as the request's "dynamic" config, and
     *   to save config changes here. These settings will also be re-applied later when "OverlayDynamic"
     *   is called after all defaults are loaded.
     * @return boolean
     */
    public function loadString($String, $Tag, $Name = 'Configuration', $Dynamic = true, $SaveCallback = null, $CallbackOptions = null) {
        $ConfigurationSource = Gdn_ConfigurationSource::fromString($this, $String, $Tag, $Name);
        if (!$ConfigurationSource) {
            return false;
        }

        $UseSplitting = $this->splitting;
        $ConfigurationSource->splitting($UseSplitting);

        $SourceTag = "string:{$Tag}";
        $this->sources[$SourceTag] = $ConfigurationSource;

        if ($Dynamic) {
            $this->dynamic = $ConfigurationSource;
        }

        if (!$UseSplitting) {
            $this->massImport($ConfigurationSource->export());
        } else {
            self::mergeConfig($this->Data, $ConfigurationSource->export());
        }

        // Callback for saving
        if (!is_null($SaveCallback)) {
            $ConfigurationSource->assignCallback($SaveCallback, $CallbackOptions);
        }
    }


    /**
     * Loads settings from an array into the object with the specified name;
     *
     * This array should be a hierarchical Vanilla config.
     *
     * @param string $ConfigData An array containing the configuration data
     * @param string $Tag A string descriptor of this config set
     * @param string $Name The name of the variable and initial group settings.
     *   Note: When $Name is 'Configuration' then the data will be set to the root of the config.
     * @param boolean $Dynamic Optional, whether to treat this as the request's "dynamic" config, and
     *   to save config changes here. These settings will also be re-applied later when "OverlayDynamic"
     *   is called after all defaults are loaded.
     * @return boolean
     */
    public function loadArray($ConfigData, $Tag, $Name = 'Configuration', $Dynamic = true, $SaveCallback = null, $CallbackOptions = null) {
        $ConfigurationSource = Gdn_ConfigurationSource::fromArray($this, $ConfigData, $Tag, $Name);
        if (!$ConfigurationSource) {
            return false;
        }

        $UseSplitting = $this->splitting;
        $ConfigurationSource->splitting($UseSplitting);

        $SourceTag = "array:{$Tag}";
        $this->sources[$SourceTag] = $ConfigurationSource;

        if ($Dynamic) {
            $this->dynamic = $ConfigurationSource;
        }

        if (!$UseSplitting) {
            $this->massImport($ConfigurationSource->export());
        } else {
            self::mergeConfig($this->Data, $ConfigurationSource->export());
        }

        // Callback for saving
        if (!is_null($SaveCallback)) {
            $ConfigurationSource->assignCallback($SaveCallback, $CallbackOptions);
        }
    }

    /**
     * DO NOT USE, THIS IS RUBBISH
     *
     * @deprecated
     */
    public static function loadFile($Path, $Options = array()) {
        throw new Exception("DEPRECATED");
    }

    /**
     * Import a large pre-formatted set of configs efficiently
     *
     * NOTE: ONLY WORKS WHEN SPLITTING IS OFF!
     *
     * @param type $Data
     */
    public function massImport($Data) {
        if ($this->splitting) {
            return;
        }
        $this->Data = array_replace($this->Data, $Data);

        if ($this->dynamic instanceof Gdn_ConfigurationSource) {
            $this->dynamic->massImport($Data);
        }
    }

    /**
     * Merge a newly loaded config into the current active state
     *
     * Recursively
     *
     * @param array $Data Reference to the current active state
     * @param array $Loaded Reference to the new to-merge data
     */
    protected static function mergeConfig(&$Data, &$Loaded) {
        foreach ($Loaded as $Key => $Value) {
            if (array_key_exists($Key, $Data) && is_array($Data[$Key]) && is_array($Value) && !self::isList($Value)) {
                self::mergeConfig($Data[$Key], $Value);
            } else {
                $Data[$Key] = $Value;
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
            $Loaded = $this->dynamic->export();
            self::mergeConfig($this->Data, $Loaded);
        }
    }

    /**
     * Remove key (or keys) from config
     *
     * @param string|array $Name Key name, or assoc array of keys to unset
     * @param bool|array $Options Bool = whether to save or not. Assoc array =
     *   Save => Whether to save or not
     */
    public function removeFromConfig($Name, $Options = array()) {
        $Save = $Options === false ? false : val('Save', $Options, true);

        if (!is_array($Name)) {
            $Name = array($Name);
        }

        // Remove specified entries
        foreach ($Name as $k) {
            $this->remove($k, $Save);
        }
    }

    /**
     * Saves all settings in $Group to $File.
     *
     * @param string $File The full path to the file where the Settings should be saved.
     * @param string $Group The name of the settings group to be saved to the $File.
     * @return boolean
     */
    public function save($File = null, $Group = null) {

        // Plain calls to Gdn::Config()->Save() simply save the dynamic config and return
        if (is_null($File)) {
            return $this->dynamic->save();
        }

        // ... otherwise we're trying to extract some of the config for some reason
        if ($File == '') {
            trigger_error(errorMessage('You must specify a file path to be saved.', $Group, 'Save'), E_USER_ERROR);
        }

        if (!is_writable($File)) {
            throw new Exception(sprintf(t("Unable to write to config file '%s' when saving."), $File));
        }

        if (empty($Group)) {
            $Group = $this->defaultGroup;
        }

        $Data = &$this->Data;
        ksort($Data);

        // Check for the case when the configuration is the group.
        if (is_array($Data) && count($Data) == 1 && array_key_exists($Group, $Data)) {
            $Data = $Data[$Group];
        }

        // Do a sanity check on the config save.
        if ($File == $this->defaultPath()) {
            if (!isset($Data['Database'])) {
                if ($Pm = Gdn::pluginManager()) {
                    $Pm->EventArguments['Data'] = $Data;
                    $Pm->EventArguments['Backtrace'] = debug_backtrace();
                    $Pm->fireEvent('ConfigError');
                }
                return false;
            }
        }

        // Build string
        $FileContents = $this->format($Data, array(
            'VariableName' => $Group,
            'Headers' => true,
            'ByLine' => true,
            'WrapPHP' => true
        ));

        if ($FileContents === false) {
            trigger_error(ErrorMessage('Failed to define configuration file contents.', $Group, 'Save'), E_USER_ERROR);
        }

        $FileKey = sprintf(Gdn_Configuration::CONFIG_FILE_CACHE_KEY, $File);
        if ($this->caching() && Gdn::cache()->type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::cache()->activeEnabled()) {
            Gdn::cache()->store($FileKey, $Data, array(
                Gdn_Cache::FEATURE_NOPREFIX => true,
                Gdn_Cache::FEATURE_EXPIRY => 3600
            ));
        }

        // Infrastructure deployment. Use old method.
        $TmpFile = tempnam(PATH_CONF, 'config');
        $Result = false;
        if (file_put_contents($TmpFile, $FileContents) !== false) {
            chmod($TmpFile, 0664);
            $Result = rename($TmpFile, $File);
        }

        if ($Result) {
            if (function_exists('apc_delete_file')) {
                // This fixes a bug with some configurations of apc.
                @apc_delete_file($File);
            } elseif (function_exists('opcache_invalidate')) {
                @opcache_invalidate($File);
            }
        }

        return $Result;
    }

    /**
     *
     *
     * @param $Path
     * @param $Data
     * @param array $Options
     * @throws Exception
     */
    public static function saveFile($Path, $Data, $Options = array()) {
        throw new Exception("DEPRECATED");
    }

    /**
     *
     *
     * @param $Name
     * @param string $Value
     * @param array $Options
     * @return bool|int
     */
    public function saveToConfig($Name, $Value = '', $Options = array()) {
        $Save = $Options === false ? false : val('Save', $Options, true);
        $RemoveEmpty = val('RemoveEmpty', $Options);

        if (!is_array($Name)) {
            $Name = array($Name => $Value);
        }

        // Apply changes one by one
        $Result = true;
        foreach ($Name as $k => $v) {
            if (!$v && $RemoveEmpty) {
                $this->remove($k);
            } else {
                $Result = $Result & $this->set($k, $v, true, $Save);
            }
        }

        return $Result;
    }

    /**
     *
     */
    public function shutdown() {
        foreach ($this->sources as $Source) {
            $Source->shutdown();
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
     * @param $Configuration
     * @param $Type
     * @param $Source
     * @param $Group
     * @param $Settings
     */
    public function __construct($Configuration, $Type, $Source, $Group, $Settings) {
        parent::__construct();

        $this->Configuration = $Configuration;

        $this->Type = $Type;
        $this->Source = $Source;
        $this->Group = $Group;
        $this->Initial = $Settings;
        $this->Settings = $Settings;
        $this->Dirty = false;
        $this->Splitting = true;

        $this->Callback = false;
        $this->CallbackOptions = null;
    }

    /**
     * Set a save callback
     *
     * @param callback $Callback
     * @param array $Options Callback options
     * @return boolean
     */
    public function assignCallback($Callback, $Options = null) {
        if (!is_callable($Callback)) {
            return false;
        }

        $this->Callback = $Callback;
        $this->CallbackOptions = $Options;
    }

    /**
     *
     *
     * @param bool $Splitting
     */
    public function splitting($Splitting = true) {
        $this->Splitting = (boolean)$Splitting;
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
                $CachedConfigData = Gdn::cache()->get($FileKey, array(
                    Gdn_Cache::FEATURE_NOPREFIX => true
                ));
                $LoadedFromCache = ($CachedConfigData !== Gdn_Cache::CACHEOP_FAILURE);
            }
        }

        // If we're not loading config from cache, check that the file exists
        if (!$LoadedFromCache && !file_exists($File)) {
            return false;
        }

        // Define the variable properly.
        $$Name = null;

        // If we're not loading config from cache, directly include the conf file
        if ($LoadedFromCache) {
            $$Name = $CachedConfigData;
        }

        if (is_null($$Name) || !is_array($$Name)) {
            $LoadedFromCache = false;
            // Include the file.
            require($File);
        }

        // Make sure the config variable is here and is an array.
        if (!is_array($$Name)) {
            $$Name = array();
        }

        // We're caching, using the cache, and this data was not loaded from cache.
        // Write it there now.
        if ($Parent && $Parent->caching() && $UseCache && !$LoadedFromCache) {
            Gdn::cache()->store($FileKey, $$Name, array(
                Gdn_Cache::FEATURE_NOPREFIX => true,
                Gdn_Cache::FEATURE_EXPIRY => 3600
            ));
        }

        return new Gdn_ConfigurationSource($Parent, 'file', $File, $Name, $$Name);
    }

    /**
     * Load config data from a string
     *
     * @param Gdn_Configuration $Parent Parent config object
     * @param string $String Config data string
     * @param string $Tag Internal friendly name
     * @param string $Name Optional setting name
     * @return Gdn_ConfigurationSource
     */
    public static function fromString($Parent, $String, $Tag, $Name = 'Configuration') {
        $ConfigurationData = self::parseString($String, $Name);
        if ($ConfigurationData === false) {
            throw new Exception('Could not parse config string.');
        }

        return new Gdn_ConfigurationSource($Parent, 'string', $Tag, $Name, $ConfigurationData);
    }

    /**
     * Load config data from an array
     *
     * @param Gdn_Configuration $Parent Parent config object
     * @param array $ConfigData Config data array
     * @param string $Tag Internal friendly name
     * @param string $Name Optional setting name
     * @return Gdn_ConfigurationSource
     */
    public static function fromArray($Parent, $ConfigData, $Tag, $Name = 'Configuration') {
        if (!is_array($ConfigData)) {
            throw new Exception('Invalid config data.');
        }
        return new Gdn_ConfigurationSource($Parent, 'array', $Tag, $Name, $ConfigData);
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
            $String = trim(str_replace(array('<?php', '<?', '?>'), '', $String));
            $Parsed = eval($String);
            if ($Parsed === false) {
                return false;
            }
        }

        // Make sure the config variable is here and is an array.
        if (is_null($$Name) || !is_array($$Name)) {
            $$Name = array();
        }

        return $$Name;
    }

    /**
     * Import a large pre-formatted set of configs efficiently
     *
     * NOTE: ONLY WORKS WHEN SPLITTING IS OFF!
     *
     * @param type $Data
     */
    public function massImport($Data) {
        if ($this->Splitting) {
            return;
        }

        // Only do dirty checks if we aren't already dirty
        if (!$this->Dirty) {
            $CheckCopy = $this->Settings;
        }

        $this->Settings = array_replace($this->Settings, $Data);

        // Only do dirty checks if we aren't already dirty
        if (!$this->Dirty) {
            if ($CheckCopy != $this->Settings) {
                $this->Dirty = true;
            }
        }
    }

    /**
     *
     *
     * @param $File
     */
    public function toFile($File) {
        $this->Type = 'file';
        $this->Source = $File;
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
     * @param $Settings
     */
    public function import($Settings) {
        $this->Settings = $Settings;
        $this->Dirty = true;
    }

    /**
     * Removes the specified key from the config (if it exists).
     * Returns false if the key is not found for removal, true otherwise.
     *
     * @param string $Name The name of the configuration setting with dot notation.
     * @return boolean Whether or not the key was found.
     */
    public function remove($Name) {
        // Make sure this source' config settings are in the right format
        if (!is_array($this->Settings)) {
            $this->Settings = array();
        }

        $Found = false;
        $Keys = explode('.', $Name);
        // If splitting is off, HANDLE IT
        if (!$this->Splitting) {
            $FirstKey = val(0, $Keys);
            if ($FirstKey == $this->Group) {
                $Keys = array(array_shift($Keys), implode('.', $Keys));
            } else {
                $Keys = array($Name);
            }
        }
        $KeyCount = count($Keys);
        $Settings = &$this->Settings;

        for ($i = 0; $i < $KeyCount; ++$i) {
            $Key = $Keys[$i];

            // Key will always be in here if it is anywhere at all
            if (array_key_exists($Key, $Settings)) {
                if ($i == ($KeyCount - 1)) {
                    // We are at the setting, so unset it.
                    $Found = true;
                    unset($Settings[$Key]);
                    $this->Dirty = true;
                } else {
                    // Advance the pointer
                    $Settings = &$Settings[$Key];
                }
            } else {
                $Found = false;
                break;
            }
        }

        return $Found;
    }

    /**
     *
     *
     * @param $Name
     * @param null $Value
     * @param bool $Overwrite
     */
    public function set($Name, $Value = null, $Overwrite = true) {
        // Make sure this source' config settings are in the right format
        if (!is_array($this->Settings)) {
            $this->Settings = array();
        }

        if (!is_array($Name)) {
            $Name = array(
                $Name => $Value
            );
        } else {
            $Overwrite = $Value;
        }

        $Data = $Name;
        foreach ($Data as $Name => $Value) {
            $Keys = explode('.', $Name);
            // If splitting is off, HANDLE IT
            if (!$this->Splitting) {
                $FirstKey = val(0, $Keys);
                if ($FirstKey == $this->Group) {
                    $Keys = array(array_shift($Keys), implode('.', $Keys));
                } else {
                    $Keys = array($Name);
                }
            }
            $KeyCount = count($Keys);
            $Settings = &$this->Settings;

            for ($i = 0; $i < $KeyCount; ++$i) {
                $Key = $Keys[$i];

                if (!is_array($Settings)) {
                    $Settings = array();
                }
                $KeyExists = array_key_exists($Key, $Settings);

                if ($i == $KeyCount - 1) {
                    // If we are on the last iteration of the key, then set the value.
                    if ($KeyExists === false || $Overwrite === true) {
                        $OldVal = val($Key, $Settings, null);
                        $SetVal = $Value;

                        $Settings[$Key] = $SetVal;
                        if (!$KeyExists || $SetVal != $OldVal) {
                            $this->Dirty = true;
                        }
                    }
                } else {
                    // Build the array as we loop over the key.
                    if ($KeyExists === false) {
                        $Settings[$Key] = array();
                    }

                    // Advance the pointer
                    $Settings = &$Settings[$Key];
                }
            }
        }
    }

    /**
     * Gets a setting from the configuration array. Returns $DefaultValue if the value isn't found.
     *
     * @param string $Name The name of the configuration setting to get. If the setting is contained
     * within an associative array, use dot denomination to get the setting. ie.
     * <code>$this->Get('Database.Host')</code> would retrieve <code>$Configuration[$Group]['Database']['Host']</code>.
     * @param mixed $DefaultValue If the parameter is not found in the group, this value will be returned.
     * @return mixed The configuration value.
     */
    public function get($Name, $DefaultValue = false) {

        // Shortcut, get the whole config
        if ($Name == '.') {
            return $this->Settings;
        }

        $Keys = explode('.', $Name);
        $KeyCount = count($Keys);

        $Value = $this->Settings;
        for ($i = 0; $i < $KeyCount; ++$i) {
            if (is_array($Value) && array_key_exists($Keys[$i], $Value)) {
                $Value = $Value[$Keys[$i]];
            } else {
                return $DefaultValue;
            }
        }

        if (is_string($Value)) {
            $Result = Gdn_Format::unserialize($Value);
        } else {
            $Result = $Value;
        }

        return $Result;
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
            $CallbackOptions = array();
            if (!is_array($this->CallbackOptions)) {
                $this->CallbackOptions = array();
            }

            $CallbackOptions = array_merge($CallbackOptions, $this->CallbackOptions, array(
                'ConfigDirty' => $this->Dirty,
                'ConfigType' => $this->Type,
                'ConfigSource' => $this->Source,
                'ConfigData' => $this->Settings,
                'SourceObject' => $this
            ));

            $ConfigSaved = call_user_func($this->Callback, $CallbackOptions);

            if ($ConfigSaved) {
                $this->Dirty = false;
                return true;
            }
        }

        switch ($this->Type) {
            case 'file':
                if (empty($this->Source)) {
                    trigger_error(errorMessage('You must specify a file path to be saved.', 'Configuration', 'Save'), E_USER_ERROR);
                }

                $CheckWrite = $this->Source;
                if (!file_exists($CheckWrite)) {
                    $CheckWrite = dirname($CheckWrite);
                }

                if (!is_writable($CheckWrite)) {
                    throw new Exception(sprintf(t("Unable to write to config file '%s' when saving."), $this->Source));
                }

                $Group = $this->Group;
                $Data = &$this->Settings;
                ksort($Data);

                // Check for the case when the configuration is the group.
                if (is_array($Data) && count($Data) == 1 && array_key_exists($Group, $Data)) {
                    $Data = $Data[$Group];
                }

                // Do a sanity check on the config save.
                if ($this->Source == Gdn::config()->defaultPath()) {
                    // Log root config changes
                    try {
                        $LogData = $this->Initial;
                        $LogData['_New'] = $this->Settings;
                        LogModel::insert('Edit', 'Configuration', $LogData);
                    } catch (Exception $Ex) {
                    }

                    if (!isset($Data['Database'])) {
                        if ($Pm = Gdn::pluginManager()) {
                            $Pm->EventArguments['Data'] = $Data;
                            $Pm->EventArguments['Backtrace'] = debug_backtrace();
                            $Pm->fireEvent('ConfigError');
                        }
                        return false;
                    }
                }

                // Write config data to string format, ready for saving
                $FileContents = Gdn_Configuration::format($Data, array(
                    'VariableName' => $Group,
                    'WrapPHP' => true,
                    'ByLine' => true
                ));

                if ($FileContents === false) {
                    trigger_error(errorMessage('Failed to define configuration file contents.', 'Configuration', 'Save'), E_USER_ERROR);
                }

                // Save to cache if we're into that sort of thing
                $FileKey = sprintf(Gdn_Configuration::CONFIG_FILE_CACHE_KEY, $this->Source);
                if ($this->Configuration && $this->Configuration->caching() && Gdn::cache()->type() == Gdn_Cache::CACHE_TYPE_MEMORY && Gdn::cache()->activeEnabled()) {
                    $CachedConfigData = Gdn::cache()->store($FileKey, $Data, array(
                        Gdn_Cache::FEATURE_NOPREFIX => true,
                        Gdn_Cache::FEATURE_EXPIRY => 3600
                    ));
                }

                $TmpFile = tempnam(PATH_CONF, 'config');
                $Result = false;
                if (file_put_contents($TmpFile, $FileContents) !== false) {
                    chmod($TmpFile, 0775);
                    $Result = rename($TmpFile, $this->Source);
                }

                if ($Result) {
                    if (function_exists('apc_delete_file')) {
                        // This fixes a bug with some configurations of apc.
                        @apc_delete_file($this->Source);
                    } elseif (function_exists('opcache_invalidate')) {
                        @opcache_invalidate($this->Source);
                    }
                }

                $this->Dirty = false;
                return $Result;
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
