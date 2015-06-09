<?php
/**
 * Update model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles updating.
 */
class UpdateModel extends Gdn_Model {

    /** @var string URL to the addons site. */
    public $AddonSiteUrl = 'http://vanilla.local';

    /**
     *
     *
     * @param $Addon
     * @param $Addons
     */
    protected static function _AddAddon($Addon, &$Addons) {
        $Slug = strtolower($Addon['AddonKey']).'-'.strtolower($Addon['AddonType']);
        $Addons[$Slug] = $Addon;
    }

    /**
     * Check an addon's file to extract the addon information out of it.
     *
     * @param string $Path The path to the file.
     * @param bool $Fix Whether or not to fix files that have been zipped incorrectly.
     * @return array An array of addon information.
     */
    public static function analyzeAddon($Path, $ThrowError = true) {
        if (!file_exists($Path)) {
            if ($ThrowError) {
                throw new Exception("$Path not found.", 404);
            }
            return false;
        }

        $Result = array();

        $InfoPaths = array(
            '/settings/about.php', // application
            '/default.php', // plugin
            '/class.*.plugin.php', // plugin
            '/about.php', // theme
            '/definitions.php', // locale
            '/index.php', // vanilla core
            'vanilla2export.php' // porter
        );

        // Get the list of potential files to analyze.
        if (is_dir($Path)) {
            $Entries = self::_GetInfoFiles($Path, $InfoPaths);
        } else {
            $Entries = self::_GetInfoZip($Path, $InfoPaths, false, $ThrowError);
            $DeleteEntries = true;
        }

        foreach ($Entries as $Entry) {
            if ($Entry['Name'] == '/index.php') {
                // This could be the core vanilla package.
                $Version = self::ParseCoreVersion($Entry['Path']);

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
                    'Path' => $Entry['Path']);
                break;
            } elseif ($Entry['Name'] == 'vanilla2export.php') {
                // This could be the vanilla porter.
                $Version = self::ParseCoreVersion($Entry['Path']);

                if (!$Version) {
                    continue;
                }

                $Addon = array(
                    'AddonKey' => 'porter',
                    'AddonTypeID' => ADDON_TYPE_CORE,
                    'Name' => 'Vanilla Porter',
                    'Description' => 'Drop this script in your existing site and navigate to it in your web browser to export your existing forum data to the Vanilla 2 import format.',
                    'Version' => $Version,
                    'Path' => $Entry['Path']);
                break;
            } else {
                // This could be an addon.
                $Info = self::ParseInfoArray($Entry['Path']);
                if (!is_array($Info) && count($Info)) {
                    continue;
                }

                $Key = key($Info);
                $Variable = $Info['Variable'];
                $Info = $Info[$Key];

                // Validate the addon.
                $Name = $Entry['Name'];
                $Valid = true;
                if (!val('Name', $Info)) {
                    $Info['Name'] = $Key;
                }

                if (!val('Description', $Info)) {
                    $Result[] = $Name.': '.sprintf(t('ValidateRequired'), t('Description'));
                    $Valid = false;
                }

                if (!val('Version', $Info)) {
                    $Result[] = $Name.': '.sprintf(t('ValidateRequired'), t('Version'));
                    $Valid = false;
                }

                if (isset($Entry['Base']) && strcasecmp($Entry['Base'], $Key) != 0 && $Variable != 'ThemeInfo') {
                    $Result[] = "$Name: The addon's key is not the same as its folder name.";
                    $Valid = false;
                }

                if (!$Valid) {
                    continue;
                }

                // The addon is valid.
                $Addon = array_merge(array('AddonKey' => $Key, 'AddonTypeID' => ''), $Info);
                switch ($Variable) {
                    case 'ApplicationInfo':
                        $Addon['AddonTypeID'] = ADDON_TYPE_APPLICATION;
                        break;
                    case 'LocaleInfo':
                        $Addon['AddonTypeID'] = ADDON_TYPE_LOCALE;
                        break;
                    case 'PluginInfo':
                        $Addon['AddonTypeID'] = ADDON_TYPE_PLUGIN;
                        break;
                    case 'ThemeInfo':
                        $Addon['AddonTypeID'] = ADDON_TYPE_THEME;
                        break;
                }
            }
        }

        if ($DeleteEntries) {
            $FolderPath = substr($Path, 0, -4);
            Gdn_FileSystem::RemoveFolder($FolderPath);
        }

        // Add the addon requirements.
        if ($Addon) {
            $Requirements = arrayTranslate($Addon, array('RequiredApplications' => 'Applications', 'RequiredPlugins' => 'Plugins', 'RequiredThemes' => 'Themes'));
            foreach ($Requirements as $Type => $Items) {
                if (!is_array($Items)) {
                    unset($Requirements[$Type]);
                }
            }
            $Addon['Requirements'] = serialize($Requirements);

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

        // Figure out what kind of addon this is.
        $Root = '';
        $NewRoot = '';
        $Addon = false;
        foreach ($Entries as $Entry) {
            $Name = '/'.ltrim($Entry['name'], '/');
            $Filename = basename($Name);
            $Folder = substr($Name, 0, -strlen($Filename));
            $NewRoot = '';

            // Check to see if the entry is a plugin file.
            if ($Filename == 'default.php' || StringEndsWith($Filename, '.plugin.php')) {
                if (count(explode('/', $Folder)) > 3) {
                    // The file is too deep to be a plugin file.
                    continue;
                }

                // This could be a plugin file, but we have to examine its info array.
                $Zip->extractTo($FolderPath, $Entry['name']);
                $FilePath = CombinePaths(array($FolderPath, $Name));
                $Info = self::ParseInfoArray($FilePath, 'PluginInfo');
                Gdn_FileSystem::RemoveFolder(dirname($FilePath));

                if (!is_array($Info) || !count($Info)) {
                    continue;
                }

                // Check to see if the info array conforms to a plugin spec.
                $Key = key($Info);
                $Info = $Info[$Key];
                $Root = trim($Folder, '/');

                $Valid = true;

                // Make sure the key matches the folder name.
                if ($Root && strcasecmp($Root, $Key) != 0) {
                    $Result[] = "$Name: The plugin's key is not the same as its folder name.";
                    $Valid = false;
                } else {
                    $NewRoot = $Root;
                }

                if (!val('Description', $Info)) {
                    $Result[] = $Name.': '.sprintf(t('ValidateRequired'), t('Description'));
                    $Valid = false;
                }

                if (!val('Version', $Info)) {
                    $Result[] = $Name.': '.sprintf(t('ValidateRequired'), t('Version'));
                    $Valid = false;
                }

                if ($Valid) {
                    // The plugin was confirmed.
                    $Addon = array(
                        'AddonKey' => $Key,
                        'AddonTypeID' => ADDON_TYPE_PLUGIN,
                        'Name' => val('Name', $Info) ? $Info['Name'] : $Key,
                        'Description' => $Info['Description'],
                        'Version' => $Info['Version'],
                        'Path' => $Path);
                    break;
                }
                continue;
            }

            // Check to see if the entry is an application file.
            if (StringEndsWith($Name, '/settings/about.php')) {
                if (count(explode('/', $Folder)) > 4) {
                    $Result[] = "$Name: The application's info array was not in the correct location.";
                    // The file is too deep to be a plugin file.
                    continue;
                }

                // This could be a plugin file, but we have to examine its info array.
                $Zip->extractTo($FolderPath, $Entry['name']);
                $FilePath = CombinePaths(array($FolderPath, $Name));
                $Info = self::ParseInfoArray($FilePath, 'ApplicationInfo');
                Gdn_FileSystem::RemoveFolder(dirname($FilePath));

                if (!is_array($Info) || !count($Info)) {
                    $Result[] = "$Name: The application's info array could not be parsed.";
                    continue;
                }

                $Key = key($Info);
                $Info = $Info[$Key];
                $Root = trim(substr($Name, 0, -strlen('/settings/about.php')), '/');
                $Valid = true;

                // Make sure the key matches the folder name.
                if ($Root && strcasecmp($Root, $Key) != 0) {
                    $Result[] = "$Name: The application's key is not the same as its folder name.";
                    $Valid = false;
                } else {
                    $NewRoot = $Root;
                }

                if (!val('Description', $Info)) {
                    $Result[] = $Name.': '.sprintf(t('ValidateRequired'), t('Description'));
                    $Valid = false;
                }

                if (!val('Version', $Info)) {
                    $Result[] = $Name.': '.sprintf(t('ValidateRequired'), t('Version'));
                    $Valid = false;
                }

                if ($Valid) {
                    // The application was confirmed.
                    $Addon = array(
                        'AddonKey' => $Key,
                        'AddonTypeID' => ADDON_TYPE_APPLICATION,
                        'Name' => val('Name', $Info) ? $Info['Name'] : $Key,
                        'Description' => $Info['Description'],
                        'Version' => $Info['Version'],
                        'Path' => $Path);
                    break;
                }
                continue;
            }

            // Check to see if the entry is a theme file.
            if (StringEndsWith($Name, '/about.php')) {
                if (count(explode('/', $Folder)) > 3) {
                    // The file is too deep to be a plugin file.
                    continue;
                }

                // This could be a theme file, but we have to examine its info array.
                $Zip->extractTo($FolderPath, $Entry['name']);
                $FilePath = CombinePaths(array($FolderPath, $Name));
                $Info = self::ParseInfoArray($FilePath, 'ThemeInfo');
                Gdn_FileSystem::RemoveFolder(dirname($FilePath));

                if (!is_array($Info) || !count($Info)) {
                    continue;
                }

                $Key = key($Info);
                $Info = $Info[$Key];
                $Valid = true;

                $Root = trim(substr($Name, 0, -strlen('/about.php')), '/');
                // Make sure the theme is at least one folder deep.
                if (strlen($Root) == 0) {
                    $Result[] = $Name.': The theme must be in a folder.';
                    $Valid = false;
                }

                if (!val('Description', $Info)) {
                    $Result[] = $Name.': '.sprintf(t('ValidateRequired'), t('Description'));
                    $Valid = false;
                }

                if (!val('Version', $Info)) {
                    $Result[] = $Name.': '.sprintf(t('ValidateRequired'), t('Version'));
                    $Valid = false;
                }

                if ($Valid) {
                    // The application was confirmed.
                    $Addon = array(
                        'AddonKey' => $Key,
                        'AddonTypeID' => ADDON_TYPE_THEME,
                        'Name' => val('Name', $Info) ? $Info['Name'] : $Key,
                        'Description' => $Info['Description'],
                        'Version' => $Info['Version'],
                        'Path' => $Path);
                    break;
                }
            }

            if (StringEndsWith($Name, '/definitions.php')) {
                if (count(explode('/', $Folder)) > 3) {
                    // The file is too deep to be a plugin file.
                    continue;
                }

                // This could be a locale pack, but we have to examine its info array.
                $Zip->extractTo($FolderPath, $Entry['name']);
                $FilePath = CombinePaths(array($FolderPath, $Name));
                $Info = self::ParseInfoArray($FilePath, 'LocaleInfo');
                Gdn_FileSystem::RemoveFolder(dirname($FilePath));

                if (!is_array($Info) || !count($Info)) {
                    continue;
                }

                $Key = key($Info);
                $Info = $Info[$Key];
                $Valid = true;

                $Root = trim(substr($Name, 0, -strlen('/definitions.php')), '/');
                // Make sure the locale is at least one folder deep.
                if ($Root != $Key) {
                    $Result[] = $Name.': The locale pack\'s key must be the same as its folder name.';
                    $Valid = false;
                }

                if (!val('Locale', $Info)) {
                    $Result[] = $Name.': '.sprintf(t('ValidateRequired'), t('Locale'));
                    $Valud = false;
                } elseif (strcasecmp($Info['Locale'], $Key) == 0) {
                    $Result[] = $Name.': '.t('The locale\'s key cannot be the same as the name of the locale.');
                    $Valid = false;
                }

                if (!val('Description', $Info)) {
                    $Result[] = $Name.': '.sprintf(t('ValidateRequired'), t('Description'));
                    $Valid = false;
                }

                if (!val('Version', $Info)) {
                    $Result[] = $Name.': '.sprintf(t('ValidateRequired'), t('Version'));
                    $Valid = false;
                }

                if ($Valid) {
                    // The locale pack was confirmed.
                    $Addon = array(
                        'AddonKey' => $Key,
                        'AddonTypeID' => ADDON_TYPE_LOCALE,
                        'Name' => val('Name', $Info) ? $Info['Name'] : $Key,
                        'Description' => $Info['Description'],
                        'Version' => $Info['Version'],
                        'Path' => $Path);
                    break;
                }
            }

            // Check to see if the entry is a core file.
            if (StringEndsWith($Name, '/index.php')) {
                if (count(explode('/', $Folder)) != 3) {
                    // The file is too deep to be the core's index.php
                    continue;
                }

                // This could be a theme file, but we have to examine its info array.
                $Zip->extractTo($FolderPath, $Entry['name']);
                $FilePath = CombinePaths(array($FolderPath, $Name));

                // Get the version number from the core.
                $Version = self::ParseCoreVersion($FilePath);

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
                    'Path' => $Path);
                $Info = array();
                break;
            }

        }

        if ($Addon) {
            // Add the requirements.
            $Requirements = arrayTranslate($Info, array('RequiredApplications' => 'Applications', 'RequiredPlugins' => 'Plugins', 'RequiredThemes' => 'Themes'));
            foreach ($Requirements as $Type => $Items) {
                if (!is_array($Items)) {
                    unset($Requirements[$Type]);
                }
            }
            $Addon['Requirements'] = serialize($Requirements);

            $Addon['Checked'] = true;


            $UploadsPath = PATH_ROOT.'/uploads/';
            if (stringBeginsWith($Addon['Path'], $UploadsPath)) {
                $Addon['File'] = substr($Addon['Path'], strlen($UploadsPath));
            }
            if ($Fix) {
                // Delete extraneous files.
                foreach ($Deletes as $Delete) {
                    $Zip->deleteName($Delete['name']);
                }
            }
        }

        $Zip->close();

        if (file_exists($FolderPath)) {
            Gdn_FileSystem::RemoveFolder($FolderPath);
        }


        if ($Addon) {
            $Addon['MD5'] = md5_file($Path);
            $Addon['FileSize'] = filesize($Path);
            return $Addon;
        } else {
            if ($ThrowError) {
                $Msg = implode("\n", $Result);
                throw new Exception($Msg, 400);
            } else {
                return false;
            }
        }
    }

    /**
     *
     *
     * @param $Path
     * @param $InfoPaths
     * @return array
     */
    protected static function _GetInfoFiles($Path, $InfoPaths) {
        $Path = str_replace('\\', '/', rtrim($Path));

        $Result = array();
        // Check to see if the paths exist.
        foreach ($InfoPaths as $InfoPath) {
            $Glob = glob($Path.$InfoPath);
            if (is_array($Glob)) {
                foreach ($Glob as $GlobPath) {
                    $Result[] = array('Name' => substr($GlobPath, strlen($Path)), 'Path' => $GlobPath);
                }
            }
        }

        return $Result;
    }

    /**
     *
     *
     * @param $Path
     * @param $InfoPaths
     * @param bool $TmpPath
     * @param bool $ThrowError
     * @return array|bool
     * @throws Exception
     */
    protected static function _GetInfoZip($Path, $InfoPaths, $TmpPath = false, $ThrowError = true) {
        // Extract the zip file so we can make sure it has appropriate information.
        $Zip = null;

        if (class_exists('ZipArchive', false)) {
            $Zip = new ZipArchive();
            $ZipOpened = $Zip->open($Path);
            if ($ZipOpened !== true) {
                $Zip = null;
            }
        }

        if (!$Zip) {
            require_once PATH_LIBRARY."/vendors/pclzip/class.pclzipadapter.php";
            $Zip = new PclZipAdapter();
            $ZipOpened = $Zip->open($Path);
        }

        if ($ZipOpened !== true) {
            if ($ThrowError) {
                $Errors = array(ZIPARCHIVE::ER_EXISTS => 'ER_EXISTS', ZIPARCHIVE::ER_INCONS => 'ER_INCONS', ZIPARCHIVE::ER_INVAL => 'ER_INVAL',
                    ZIPARCHIVE::ER_MEMORY => 'ER_MEMORY', ZIPARCHIVE::ER_NOENT => 'ER_NOENT', ZIPARCHIVE::ER_NOZIP => 'ER_NOZIP',
                    ZIPARCHIVE::ER_OPEN => 'ER_OPEN', ZIPARCHIVE::ER_READ => 'ER_READ', ZIPARCHIVE::ER_SEEK => 'ER_SEEK');

                throw new Exception(t('Could not open addon file. Addons must be zip files.').' ('.$Path.' '.GetValue($ZipOpened, $Errors, 'Unknown Error').')'.$Worked, 400);
            }
            return false;
        }

        if ($TmpPath === false) {
            $TmpPath = dirname($Path).'/'.basename($Path, '.zip').'/';
        }
        if (file_exists($TmpPath)) {
            Gdn_FileSystem::RemoveFolder($TmpPath);
        }

        $Result = array();
        for ($i = 0; $i < $Zip->numFiles; $i++) {
            $Entry = $Zip->statIndex($i);
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
     * @return string|false A string containing the version or false if the file could not be parsed.
     */
    public static function parseCoreVersion($Path) {
        $fp = fopen($Path, 'rb');
        $Application = false;
        $Version = false;

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

            if ($Application !== false && $Version !== false) {
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
     */
    public static function parseInfoArray($Path, $Variable = false) {
        $fp = fopen($Path, 'rb');
        $Lines = array();
        $InArray = false;

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

    public function compareAddons($MyAddons, $LatestAddons, $OnlyUpdates = true) {
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
     *
     *
     * @param bool $Enabled
     * @return array
     */
    public function getAddons($Enabled = false) {
        $Addons = array();

        // Get the core.
        self::_AddAddon(array('AddonKey' => 'vanilla', 'AddonType' => 'core', 'Version' => APPLICATION_VERSION, 'Folder' => '/'), $Addons);

        // Get a list of all of the applications.
        $ApplicationManager = new Gdn_ApplicationManager();
        if ($Enabled) {
            $Applications = $ApplicationManager->AvailableApplications();
        } else {
            $Applications = $ApplicationManager->EnabledApplications();
        }

        foreach ($Applications as $Key => $Info) {
            // Exclude core applications.
            if (in_array(strtolower($Key), array('conversations', 'dashboard', 'skeleton', 'vanilla'))) {
                continue;
            }

            $Addon = array('AddonKey' => $Key, 'AddonType' => 'application', 'Version' => val('Version', $Info, '0.0'), 'Folder' => '/applications/'.GetValue('Folder', $Info, strtolower($Key)));
            self::_AddAddon($Addon, $Addons);
        }

        // Get a list of all of the plugins.
        $PluginManager = Gdn::pluginManager();
        if ($Enabled) {
            $Plugins = $PluginManager->EnabledPlugins();
        } else {
            $Plugins = $PluginManager->AvailablePlugins();
        }

        foreach ($Plugins as $Key => $Info) {
            // Exclude core plugins.
            if (in_array(strtolower($Key), array())) {
                continue;
            }

            $Addon = array('AddonKey' => $Key, 'AddonType' => 'plugin', 'Version' => val('Version', $Info, '0.0'), 'Folder' => '/applications/'.GetValue('Folder', $Info, $Key));
            self::_AddAddon($Addon, $Addons);
        }

        // Get a list of all the themes.
        $ThemeManager = new Gdn_ThemeManager();
        if ($Enabled) {
            $Themes = $ThemeManager->EnabledThemeInfo(true);
        } else {
            $Themes = $ThemeManager->AvailableThemes();
        }

        foreach ($Themes as $Key => $Info) {
            // Exclude core themes.
            if (in_array(strtolower($Key), array('default'))) {
                continue;
            }

            $Addon = array('AddonKey' => $Key, 'AddonType' => 'theme', 'Version' => val('Version', $Info, '0.0'), 'Folder' => '/themes/'.GetValue('Folder', $Info, $Key));
            self::_AddAddon($Addon, $Addons);
        }

        // Get a list of all locales.
        $LocaleModel = new LocaleModel();
        if ($Enabled) {
            $Locales = $LocaleModel->EnabledLocalePacks(true);
        } else {
            $Locales = $LocaleModel->AvailableLocalePacks();
        }

        foreach ($Locales as $Key => $Info) {
            // Exclude core themes.
            if (in_array(strtolower($Key), array('skeleton'))) {
                continue;
            }

            $Addon = array('AddonKey' => $Key, 'AddonType' => 'locale', 'Version' => val('Version', $Info, '0.0'), 'Folder' => '/locales/'.GetValue('Folder', $Info, $Key));
            self::_AddAddon($Addon, $Addons);
        }

        return $Addons;
    }

    /**
     *
     *
     * @param bool $Enabled
     * @param bool $OnlyUpdates
     * @return array|bool
     * @throws Exception
     */
    public function getAddonUpdates($Enabled = false, $OnlyUpdates = true) {
        // Get the addons on this site.
        $MyAddons = $this->GetAddons($Enabled);

        // Build the query for them.
        $Slugs = array_keys($MyAddons);
        array_map('urlencode', $Slugs);
        $SlugsString = implode(',', $Slugs);

        $Url = $this->AddonSiteUrl.'/addon/getlist.json?ids='.$SlugsString;
        $SiteAddons = ProxyRequest($Url);
        $UpdateAddons = array();

        if ($SiteAddons) {
            $SiteAddons = val('Addons', json_decode($SiteAddons, true));
            $UpdateAddons = $this->CompareAddons($MyAddons, $SiteAddons);
        }
        return $UpdateAddons;
    }

    /**
     *
     *
     * @param null $AddonCode
     * @param bool $Explicit
     * @param bool $Drop
     * @throws Exception
     */
    public function runStructure($AddonCode = null, $Explicit = false, $Drop = false) {
        // Get the structure files for all of the enabled applications.
        $ApplicationManager = new Gdn_ApplicationManager();
        $Apps = $ApplicationManager->EnabledApplications();
        $AppNames = consolidateArrayValuesByKey($Apps, 'Folder');
        $Paths = array();
        foreach ($Apps as $Key => $AppInfo) {
            $Path = PATH_APPLICATIONS."/{$AppInfo['Folder']}/settings/structure.php";
            if (file_exists($Path)) {
                $Paths[] = $Path;
            }

            Gdn::ApplicationManager()->RegisterPermissions($Key, $this->Validation);
        }

        // Execute the structures.
        $Database = Gdn::database();
        $SQL = Gdn::sql();
        $Structure = Gdn::structure();

        foreach ($Paths as $Path) {
            include $Path;
        }

        // Execute the structures for all of the plugins.
        $PluginManager = Gdn::pluginManager();

        $Registered = $PluginManager->RegisteredPlugins();

        foreach ($Registered as $ClassName => $Enabled) {
            if (!$Enabled) {
                continue;
            }

            try {
                $Plugin = $PluginManager->GetPluginInstance($ClassName, Gdn_PluginManager::ACCESS_CLASSNAME);
                if (method_exists($Plugin, 'Structure')) {
                    trace("{$ClassName}->Structure()");
                    $Plugin->Structure();
                }
            } catch (Exception $Ex) {
                // Do nothing, plugin wouldn't load/structure.
                if (Debug()) {
                    throw $Ex;
                }
            }
        }
    }
}
