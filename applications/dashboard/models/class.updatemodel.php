<?php
/**
 * Update model.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */
use Vanilla\Addon;

/**
 * Handles updating.
 */
class UpdateModel extends Gdn_Model {

    // TODO Remove when removing other deprecated functions!
    /** @var string URL to the addons site. */
    public $AddonSiteUrl = 'http://vanilla.local';

    /**
     *
     *
     * @param $Addon
     * @param $Addons
     * @deprecated since 2.3
     */
    private static function addAddon($Addon, &$Addons) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        $Slug = strtolower($Addon['AddonKey']).'-'.strtolower($Addon['AddonType']);
        $Addons[$Slug] = $Addon;
    }

    /**
     * Find a list of filenames in a folder or zip.
     *
     * @param string $path Folder or zip file to look in.
     * @param array $fileNames List of files to attempt to locate inside $path.
     * @return array
     * @throws Exception
     * @throws Gdn_UserException
     * @deprecated since 2.3
     */
    public static function findFiles($path, $fileNames) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        // Get the list of potential files to analyze.
        if (is_dir($path)) {
            $entries = self::getInfoFiles($path, $fileNames);
        } else {
            $entries = self::getInfoZip($path, $fileNames);
        }

        return $entries;
    }

    /**
     * Coerces an addon.json into something we can check in the update model.
     *
     * @param $path The path to the addon directory
     * @return array The addon info array
     */
    private static function addonJsonConverter($path) {

        $addon = new Vanilla\Addon($path);
        $addonInfo = Gdn_PluginManager::calcOldInfoArray($addon);
        $slug = trim(substr($path, strrpos($path, '/') + 1));

        $validTypes = ['application', 'plugin', 'theme', 'locale'];

        // If the type is theme or locale then use that.
        $type = val('Type', $addonInfo, 'addon');

        // If oldType is present then use that.
        if (!in_array($type, $validTypes)) {
            $type = val('OldType', $addonInfo, false);
        }

        // If priority is lower than Addon::PRIORITY_PLUGIN then its an application.
        if (!in_array($type, $validTypes) && (val('Priority', $type, Addon::PRIORITY_HIGH) < Addon::PRIORITY_PLUGIN)) {
            $type = 'application';
        }

        // Otherwise, we got a plugin
        if (!in_array($type, $validTypes)) {
            $type = 'plugin';
        }

        $addonInfo['Variable'] = ucfirst($type).'Info';
        $info[$slug] = $addonInfo;

        return $info;
    }

    /**
     * Check an addon's file to extract the addon information out of it.
     *
     * @param string $Path The path to the file.
     * @param bool $ThrowError Whether or not to throw an exception if there is a problem analyzing the addon.
     * @return array An array of addon information.
     * @deprecated since 2.3
     */
    public static function analyzeAddon($Path, $ThrowError = true) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        if (!file_exists($Path)) {
            if ($ThrowError) {
                throw new Exception("$Path not found.", 404);
            }
            return false;
        }

        $Addon = [];
        $Result = [];

        $InfoPaths = array(
            '/settings/about.php', // application
            '/default.php', // plugin
            '/class.*.plugin.php', // plugin
            '/about.php', // theme
            '/definitions.php', // locale
            '/index.php', // vanilla core
            'vanilla2export.php' // porter
        );

        // Look for an addon.json file.
        if (file_exists("$Path/addon.json")) {

            $Info = self::addonJsonConverter($Path);

            $entry['Path'] = $Path;
            $entry['Name'] = val('Name', $Info[key($Info)]);
            $entry['Base'] = val('Key', $Info[key($Info)]);

            $Result = self::checkAddon($Info, $entry);
            if (empty($Result)) {
                $Addon = self::buildAddon($Info);
            }

        } else {
            // Get the list of potential files to analyze.
            if (is_dir($Path)) {
                $Entries = self::getInfoFiles($Path, $InfoPaths);
                $DeleteEntries = false;
            } else {
                $Entries = self::getInfoZip($Path, $InfoPaths, false, $ThrowError);
                $DeleteEntries = true;
            }

            foreach ($Entries as $entry) {
                if ($entry['Name'] == '/index.php') {
                    // This could be the core vanilla package.
                    $Version = self::parseCoreVersion($entry['Path']);

                    if (!$Version) {
                        continue;
                    }

                    // The application was confirmed.
                    $Addon = array(
                        'AddonKey' => 'vanilla',
                        'AddonTypeID' => ADDON_TYPE_CORE,
                        'Name' => 'Vanilla',
                        'Description' => 'Vanilla is an open-source, standards-compliant, multi-lingual, fully extensible discussion forum for the web. Anyone who has web-space that meets the requirements can download and use Vanilla for free!',
                        'Version' => $Version,
                        'License' => 'GPLv2',
                        'Path' => $entry['Path']);
                    break;
                } elseif ($entry['Name'] == 'vanilla2export.php') {
                    // This could be the vanilla porter.
                    $Version = self::parseCoreVersion($entry['Path']);

                    if (!$Version) {
                        continue;
                    }

                    $Addon = array(
                        'AddonKey' => 'porter',
                        'AddonTypeID' => ADDON_TYPE_CORE,
                        'Name' => 'Vanilla Porter',
                        'Description' => 'Drop this script in your existing site and navigate to it in your web browser to export your existing forum data to the Vanilla 2 import format.',
                        'Version' => $Version,
                        'License' => 'GPLv2',
                        'Path' => $entry['Path']);
                    break;
                } else {
                    // This could be an addon.
                    $Info = self::parseInfoArray($entry['Path']);
                    $Result = self::checkAddon($Info, $entry);
                    if (!empty($Result)) {
                        break;
                    }
                    $Addon = self::buildAddon($Info);
                }
            }

            if ($DeleteEntries) {
                $FolderPath = substr($Path, 0, -4);
                Gdn_FileSystem::removeFolder($FolderPath);
            }
        }

        // Add the addon requirements.
        if (!empty($Addon)) {
            $Requirements = arrayTranslate(
                $Addon,
                [
                    'RequiredApplications' => 'Applications',
                    'RequiredPlugins' => 'Plugins',
                    'RequiredThemes' => 'Themes',
                    'Require' => 'Addons'
                ]
            );
            foreach ($Requirements as $Type => $Items) {
                if (!is_array($Items)) {
                    unset($Requirements[$Type]);
                }
            }
            $Addon['Requirements'] = dbencode($Requirements);

            $Addon['Checked'] = true;
            $Addon['Path'] = $Path;
            $UploadsPath = PATH_UPLOADS.'/';
            if (stringBeginsWith($Addon['Path'], $UploadsPath)) {
                $Addon['File'] = substr($Addon['Path'], strlen($UploadsPath));
            }

            if (is_file($Path)) {
                $Addon['MD5'] = md5_file($Path);
                $Addon['FileSize'] = filesize($Path);
            }
        } elseif ($ThrowError) {
            $Msg = implode("\n", $Result);
            throw new Gdn_UserException($Msg, 400);
        } else {
            return false;
        }

        return $Addon;
    }

    /**
     * Takes an addon's info array and adds extra info to it that is expected by the update model.
     *
     * @param $info The addon info array. The expected format is `addon-key => addon-info`,
     *     where addon-info is the addon's info array.
     * @return array The addon with the extra info included, or an empty array if $info is bad.
     */
    private static function buildAddon($info) {
        if (!is_array($info) && count($info)) {
            return [];
        }

        $key = key($info);
        $variable = $info['Variable'];
        $info = $info[$key];

        $addon = array_merge(array('AddonKey' => $key, 'AddonTypeID' => ''), $info);
        switch ($variable) {
            case 'ApplicationInfo':
                $addon['AddonTypeID'] = ADDON_TYPE_APPLICATION;
                break;
            case 'LocaleInfo':
                $addon['AddonTypeID'] = ADDON_TYPE_LOCALE;
                break;
            case 'PluginInfo':
                $addon['AddonTypeID'] = ADDON_TYPE_PLUGIN;
                break;
            case 'ThemeInfo':
                $addon['AddonTypeID'] = ADDON_TYPE_THEME;
                break;
        }

        return $addon;
    }


    /**
     * Checks an addon. Returns a collection of errors in an array. If no errors exist, returns an empty array.
     *
     * @param $info The addon info array. The expected format is `addon-key => addon-info`,
     *     where addon-info is the addon's info array.
     * @param $entry Information on where the info was retrieved from. Should include the keys: 'Name' and 'Base',
     *     for the addon name and the addon folder, respectively.
     * @return array The errors with the addon, or an empty array.
     */
    private static function checkAddon($info, $entry) {
        $result = [];

        if (!is_array($info) && count($info)) {
            return ['Could not parse addon info array.'];
        }

        $key = key($info);
        $variable = $info['Variable'];
        $info = $info[$key];

        // Validate the addon.
        $name = $entry['Name'];
        if (!val('Name', $info)) {
            $info['Name'] = $key;
        }

        // Validate basic fields.
        $checkResult = self::checkRequiredFields($info);
        if (count($checkResult)) {
            $result = array_merge($result, $checkResult);
        }

        // Validate folder name matches key.
        if (isset($entry['Base']) && strcasecmp($entry['Base'], $key) != 0 && $variable != 'ThemeInfo') {
            $result[] = "$name: The addon's key is not the same as its folder name.";
        }

        return $result;
    }

    /**
     *
     *
     * @param string $Path
     * @param array $InfoPaths
     * @return array
     * @deprecated since 2.3
     */
    private static function getInfoFiles($Path, $InfoPaths) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        $Path = str_replace('\\', '/', rtrim($Path));

        $Result = [];
        // Check to see if the paths exist.
        foreach ($InfoPaths as $InfoPath) {
            $Glob = glob($Path.$InfoPath);
            if (is_array($Glob)) {
                foreach ($Glob as $GlobPath) {
                    $Result[] = ['Name' => substr($GlobPath, strlen($Path)), 'Path' => $GlobPath];
                }
            }
        }

        return $Result;
    }

    /**
     * Open a zip archive and inspect its contents for the requested paths.
     *
     * @param string $Path
     * @param array $InfoPaths
     * @param bool $TmpPath
     * @param bool $ThrowError
     * @return array
     * @throws Exception
     * @deprecated since 2.3
     */
    private static function getInfoZip($Path, $InfoPaths, $TmpPath = false, $ThrowError = true) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        // Extract the zip file so we can make sure it has appropriate information.
        $Zip = null;
        $ZipOpened = false;

        if (class_exists('ZipArchive', false)) {
            $Zip = new ZipArchive();
            $ZipOpened = $Zip->open($Path);
            if ($ZipOpened !== true) {
                $Zip = null;
            }
        }

        if (!$Zip) {
            $Zip = new PclZipAdapter();
            $ZipOpened = $Zip->open($Path);
        }

        if ($ZipOpened !== true) {
            if ($ThrowError) {
                $Errors = array(ZipArchive::ER_EXISTS => 'ER_EXISTS', ZipArchive::ER_INCONS => 'ER_INCONS', ZipArchive::ER_INVAL => 'ER_INVAL',
                    ZipArchive::ER_MEMORY => 'ER_MEMORY', ZipArchive::ER_NOENT => 'ER_NOENT', ZipArchive::ER_NOZIP => 'ER_NOZIP',
                    ZipArchive::ER_OPEN => 'ER_OPEN', ZipArchive::ER_READ => 'ER_READ', ZipArchive::ER_SEEK => 'ER_SEEK');
                $Error = val($ZipOpened, $Errors, 'Unknown Error');

                throw new Exception(t('Could not open addon file. Addons must be zip files.')." ($Path $Error)", 400);
            }
            return [];
        }

        if ($TmpPath === false) {
            $TmpPath = dirname($Path).'/'.basename($Path, '.zip').'/';
        }

        if (file_exists($TmpPath)) {
            Gdn_FileSystem::removeFolder($TmpPath);
        }

        $Result = [];
        for ($i = 0; $i < $Zip->numFiles; $i++) {
            $Entry = $Zip->statIndex($i);

            if (preg_match('#(\.\.[\\/])#', $Entry['name'])) {
                throw new Gdn_UserException("Invalid path in zip file: ".htmlspecialchars($Entry['name']));
            }

            $Name = '/'.ltrim($Entry['name'], '/');

            foreach ($InfoPaths as $InfoPath) {
                $Preg = '`('.str_replace(array('.', '*'), array('\.', '.*'), $InfoPath).')$`';
                if (preg_match($Preg, $Name, $Matches)) {
                    $Base = trim(substr($Name, 0, -strlen($Matches[1])), '/');

                    if (strpos($Base, '/') !== false) {
                        continue; // file nested too deep.
                    }
                    if (!file_exists($TmpPath)) {
                        mkdir($TmpPath, 0777, true);
                    }

                    $Zip->extractTo($TmpPath, $Entry['name']);
                    $Result[] = array('Name' => $Matches[1], 'Path' => $TmpPath.rtrim($Entry['name'], '/'), 'Base' => $Base);
                }
            }
        }

        return $Result;
    }

    /**
     * Parse the version out of the core's index.php file.
     *
     * @param string $Path The path to the index.php file.
     * @return string A string containing the version or empty if the file could not be parsed.
     * @deprecated since 2.3
     */
    public static function parseCoreVersion($Path) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        $fp = fopen($Path, 'rb');
        $Application = false;
        $Version = '';

        while (($Line = fgets($fp)) !== false) {
            if (preg_match("`define\\('(.*?)', '(.*?)'\\);`", $Line, $Matches)) {
                $Name = $Matches[1];
                $Value = $Matches[2];
                switch ($Name) {
                    case 'APPLICATION':
                        $Application = $Value;
                        break;
                    case 'APPLICATION_VERSION':
                        $Version = $Value;
                }
            }

            if ($Application !== false && $Version !== '') {
                break;
            }
        }
        fclose($fp);
        return $Version;
    }

    /**
     * Offers a quick and dirty way of parsing an addon's info array without using eval().
     *
     * @param string $Path The path to the info array.
     * @param string $Variable The name of variable containing the information.
     * @return array|false The info array or false if the file could not be parsed.
     * @deprecated since 2.3
     */
    public static function parseInfoArray($Path, $Variable = false) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        $fp = fopen($Path, 'rb');
        $Lines = array();
        $InArray = false;
        $GlobalKey = '';

        // Get all of the lines in the info array.
        while (($Line = fgets($fp)) !== false) {
            // Remove comments from the line.
            $Line = preg_replace('`\s//.*$`', '', $Line);
            if (!$Line) {
                continue;
            }

            if (!$InArray && preg_match('`\$([A-Za-z]+Info)\s*\[`', trim($Line), $Matches)) {
                $Variable = $Matches[1];
                if (preg_match('`\[\s*[\'"](.+?)[\'"]\s*\]`', $Line, $Matches)) {
                    $GlobalKey = $Matches[1];
                    $InArray = true;
                }
            } elseif ($InArray && StringEndsWith(trim($Line), ';')) {
                break;
            } elseif ($InArray) {
                $Lines[] = trim($Line);
            }
        }
        fclose($fp);

        if (count($Lines) == 0) {
            return false;
        }

        // Parse the name/value information in the arrays.
        $Result = array();
        foreach ($Lines as $Line) {
            // Get the name from the line.
            if (!preg_match('`[\'"](.+?)[\'"]\s*=>`', $Line, $Matches) || !substr($Line, -1) == ',') {
                continue;
            }
            $Key = $Matches[1];

            // Strip the key from the line.
            $Line = trim(trim(substr(strstr($Line, '=>'), 2)), ',');

            if (strlen($Line) == 0) {
                continue;
            }

            $Value = null;
            if (is_numeric($Line)) {
                $Value = $Line;
            } elseif (strcasecmp($Line, 'TRUE') == 0 || strcasecmp($Line, 'FALSE') == 0)
                $Value = $Line;
            elseif (in_array($Line[0], array('"', "'")) && substr($Line, -1) == $Line[0]) {
                $Quote = $Line[0];
                $Value = trim($Line, $Quote);
                $Value = str_replace('\\'.$Quote, $Quote, $Value);
            } elseif (stringBeginsWith($Line, 'array(') && substr($Line, -1) == ')') {
                // Parse the line's array.
                $Line = substr($Line, 6, strlen($Line) - 7);
                $Items = explode(',', $Line);
                $Array = array();
                foreach ($Items as $Item) {
                    $SubItems = explode('=>', $Item);
                    if (count($SubItems) == 1) {
                        $Array[] = trim(trim($SubItems[0]), '"\'');
                    } elseif (count($SubItems) == 2) {
                        $SubKey = trim(trim($SubItems[0]), '"\'');
                        $SubValue = trim(trim($SubItems[1]), '"\'');
                        $Array[$SubKey] = $SubValue;
                    }
                }
                $Value = $Array;
            }

            if ($Value != null) {
                $Result[$Key] = $Value;
            }
        }
        $Result = array($GlobalKey => $Result, 'Variable' => $Variable);
        return $Result;
    }

    /**
     *
     *
     * @param array $MyAddons
     * @param array $LatestAddons
     * @return bool
     * @deprecated since 2.3
     */
    public function compareAddons($MyAddons, $LatestAddons) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        $UpdateAddons = false;

        // Join the site addons with my addons.
        foreach ($LatestAddons as $Addon) {
            $Key = val('AddonKey', $Addon);
            $Type = val('Type', $Addon);
            $Slug = strtolower($Key).'-'.strtolower($Type);
            $Version = val('Version', $Addon);
            $FileUrl = val('Url', $Addon);

            if (isset($MyAddons[$Slug])) {
                $MyAddon = $MyAddons[$Slug];

                if (version_compare($Version, val('Version', $MyAddon, '999'), '>')) {
                    $MyAddon['NewVersion'] = $Version;
                    $MyAddon['NewDownloadUrl'] = $FileUrl;
                    $UpdateAddons[$Slug] = $MyAddon;
                }
            } else {
                unset($MyAddons[$Slug]);
            }
        }

        return $UpdateAddons;
    }

    /**
     * Check globally required fields in our addon info.
     *
     * @param $info
     * @return array $results
     * @deprecated since 2.3
     */
    protected static function checkRequiredFields($info) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        $results = array();

        if (!val('Description', $info)) {
            $results[] = sprintf(t('ValidateRequired'), t('Description'));
        }

        if (!val('Version', $info)) {
            $results[] = sprintf(t('ValidateRequired'), t('Version'));
        }

        if (!val('License', $info)) {
            $results[] = sprintf(t('ValidateRequired'), t('License'));
        }

        return $results;
    }

    /**
     * Deprecated.
     *
     * @param bool $Enabled Deprecated.
     * @return array Deprecated.
     * @deprecated since 2.3
     */
    public function getAddons($Enabled = false) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        return [];
    }

    /**
     * Deprecated.
     *
     * @param bool $Enabled Deprecated.
     * @return array|bool Deprecated.
     * @deprecated
     */
    public function getAddonUpdates($Enabled = false) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
    }

    /**
     * Run the structure for all addons.
     *
     * The structure runs the addons in priority order so that higher priority addons override lower priority ones.
     *
     * @param bool $captureOnly Run the structure or just capture the SQL changes.
     * @throws Exception Throws an exception if in debug mode and something goes wrong.
     */
    public function runStructure($captureOnly = false) {
        $addons = array_reverse(Gdn::addonManager()->getEnabled());

        // These variables are required for included structure files.
        $Database = Gdn::database();
        $SQL = $this->SQL;
        $SQL->CaptureModifications = $captureOnly;
        $Structure = Gdn::structure();
        $Structure->CaptureOnly = $captureOnly;

        /* @var Addon $addon */
        foreach ($addons as $addon) {
            // Look for a structure file.
            if ($structure = $addon->getSpecial('structure')) {
                Logger::event(
                    'addon_structure',
                    Logger::INFO,
                    "Executing structure for {addonKey}.",
                    ['addonKey' => $addon->getKey(), 'structureType' => 'file']
                );

                try {
                    include $addon->path($structure);
                } catch (\Exception $ex) {
                    if (debug()) {
                        throw $ex;
                    }
                }
            }

            // Look for a structure method on the plugin.
            if ($addon->getPluginClass()) {
                $plugin = Gdn::pluginManager()->getPluginInstance(
                    $addon->getPluginClass(),
                    Gdn_PluginManager::ACCESS_CLASSNAME
                );

                if (is_object($plugin) && method_exists($plugin, 'structure')) {
                    Logger::event(
                        'addon_structure',
                        Logger::INFO,
                        "Executing structure for {addonKey}.",
                        ['addonKey' => $addon->getKey(), 'structureType' => 'method']
                    );

                    try {
                        call_user_func([$plugin, 'structure']);
                    } catch (\Exception $ex) {
                        if (debug()) {
                            throw $ex;
                        }
                    }
                }
            }

            // Register permissions.
            $permissions = $addon->getInfoValue('registerPermissions');
            if (!empty($permissions)) {
                Logger::event(
                    'addon_permissions',
                    Logger::INFO,
                    "Defining permissions for {addonKey}.",
                    ['addonKey' => $addon->getKey(), 'permissions' => $permissions]
                );
                Gdn::permissionModel()->define($permissions);
            }
        }
        $this->fireEvent('AfterStructure');

        if ($captureOnly && property_exists($Structure->Database, 'CapturedSql')) {
            return $Structure->Database->CapturedSql;
        }
        return [];
    }
}
