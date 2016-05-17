<?php
/**
 * Update model.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
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
    private static function addAddon($Addon, &$Addons) {
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
     */
    public static function findFiles($path, $fileNames) {
        // Get the list of potential files to analyze.
        if (is_dir($path)) {
            $entries = self::getInfoFiles($path, $fileNames);
        } else {
            $entries = self::getInfoZip($path, $fileNames);
        }

        return $entries;
    }

    /**
     * Check an addon's file to extract the addon information out of it.
     *
     * @param string $Path The path to the file.
     * @param bool $ThrowError Whether or not to throw an exception if there is a problem analyzing the addon.
     * @return array An array of addon information.
     */
    public static function analyzeAddon($Path, $ThrowError = true) {
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

        // Get the list of potential files to analyze.
        if (is_dir($Path)) {
            $Entries = self::getInfoFiles($Path, $InfoPaths);
            $DeleteEntries = false;
        } else {
            $Entries = self::getInfoZip($Path, $InfoPaths, false, $ThrowError);
            $DeleteEntries = true;
        }

        foreach ($Entries as $Entry) {
            if ($Entry['Name'] == '/index.php') {
                // This could be the core vanilla package.
                $Version = self::parseCoreVersion($Entry['Path']);

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
                    'Path' => $Entry['Path']);
                break;
            } elseif ($Entry['Name'] == 'vanilla2export.php') {
                // This could be the vanilla porter.
                $Version = self::parseCoreVersion($Entry['Path']);

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
                    'Path' => $Entry['Path']);
                break;
            } else {
                // This could be an addon.
                $Info = self::parseInfoArray($Entry['Path']);
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

                // Validate basic fields.
                $checkResult = self::checkRequiredFields($Info);
                if (count($checkResult)) {
                    $Result = array_merge($Result, $checkResult);
                    $Valid = false;
                }

                // Validate folder name matches key.
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
            Gdn_FileSystem::removeFolder($FolderPath);
        }

        // Add the addon requirements.
        if (!empty($Addon)) {
            $Requirements = arrayTranslate(
                $Addon,
                [
                    'RequiredApplications' => 'Applications',
                    'RequiredPlugins' => 'Plugins',
                    'RequiredThemes' => 'Themes'
                ]
            );
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
    }

    /**
     *
     *
     * @param string $Path
     * @param array $InfoPaths
     * @return array
     */
    private static function getInfoFiles($Path, $InfoPaths) {
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
     */
    private static function getInfoZip($Path, $InfoPaths, $TmpPath = false, $ThrowError = true) {
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
            require_once PATH_LIBRARY."/vendors/pclzip/class.pclzipadapter.php";
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
     */
    public static function parseCoreVersion($Path) {
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
     */
    public static function parseInfoArray($Path, $Variable = false) {
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
     */
    public function compareAddons($MyAddons, $LatestAddons) {
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
     */
    protected static function checkRequiredFields($info) {
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
     *
     *
     * @param bool $Enabled
     * @return array
     */
    public function getAddons($Enabled = false) {
        $Addons = array();

        // Get the core.
        self::addAddon(array('AddonKey' => 'vanilla', 'AddonType' => 'core', 'Version' => APPLICATION_VERSION, 'Folder' => '/'), $Addons);

        // Get a list of all of the applications.
        $ApplicationManager = new Gdn_ApplicationManager();
        if ($Enabled) {
            $Applications = $ApplicationManager->availableApplications();
        } else {
            $Applications = $ApplicationManager->enabledApplications();
        }

        foreach ($Applications as $Key => $Info) {
            // Exclude core applications.
            if (in_array(strtolower($Key), array('conversations', 'dashboard', 'skeleton', 'vanilla'))) {
                continue;
            }

            $Addon = array('AddonKey' => $Key, 'AddonType' => 'application', 'Version' => val('Version', $Info, '0.0'), 'Folder' => '/applications/'.GetValue('Folder', $Info, strtolower($Key)));
            self::addAddon($Addon, $Addons);
        }

        // Get a list of all of the plugins.
        $PluginManager = Gdn::pluginManager();
        if ($Enabled) {
            $Plugins = $PluginManager->enabledPlugins();
        } else {
            $Plugins = $PluginManager->availablePlugins();
        }

        foreach ($Plugins as $Key => $Info) {
            // Exclude core plugins.
            if (in_array(strtolower($Key), array())) {
                continue;
            }

            $Addon = [
                'AddonKey' => $Key,
                'AddonType' => 'plugin',
                'Version' => val('Version', $Info, '0.0'),
                'Folder' => '/applications/'.GetValue('Folder', $Info, $Key)
            ];
            self::addAddon($Addon, $Addons);
        }

        // Get a list of all the themes.
        $ThemeManager = new Gdn_ThemeManager();
        if ($Enabled) {
            $Themes = $ThemeManager->enabledThemeInfo(true);
        } else {
            $Themes = $ThemeManager->availableThemes();
        }

        foreach ($Themes as $Key => $Info) {
            // Exclude core themes.
            if (in_array(strtolower($Key), array('default'))) {
                continue;
            }

            $Addon = [
                'AddonKey' => $Key,
                'AddonType' => 'theme',
                'Version' => val('Version', $Info, '0.0'),
                'Folder' => '/themes/'.GetValue('Folder', $Info, $Key)
            ];
            self::addAddon($Addon, $Addons);
        }

        // Get a list of all locales.
        $LocaleModel = new LocaleModel();
        if ($Enabled) {
            $Locales = $LocaleModel->enabledLocalePacks(true);
        } else {
            $Locales = $LocaleModel->availableLocalePacks();
        }

        foreach ($Locales as $Key => $Info) {
            // Exclude core themes.
            if (in_array(strtolower($Key), array('skeleton'))) {
                continue;
            }

            $Addon = [
                'AddonKey' => $Key,
                'AddonType' => 'locale',
                'Version' => val('Version', $Info, '0.0'),
                'Folder' => '/locales/'.GetValue('Folder', $Info, $Key)
            ];
            self::addAddon($Addon, $Addons);
        }

        return $Addons;
    }

    /**
     *
     *
     * @param bool $Enabled
     * @return array|bool
     * @throws Exception
     */
    public function getAddonUpdates($Enabled = false) {
        // Get the addons on this site.
        $MyAddons = $this->getAddons($Enabled);

        // Build the query for them.
        $Slugs = array_keys($MyAddons);
        array_map('urlencode', $Slugs);
        $SlugsString = implode(',', $Slugs);

        $Url = $this->AddonSiteUrl.'/addon/getlist.json?ids='.$SlugsString;
        $SiteAddons = proxyRequest($Url);
        $UpdateAddons = array();

        if ($SiteAddons) {
            $SiteAddons = val('Addons', json_decode($SiteAddons, true));
            $UpdateAddons = $this->compareAddons($MyAddons, $SiteAddons);
        }
        return $UpdateAddons;
    }

    /**
     *
     * @throws Exception
     */
    public function runStructure() {
        // Get the structure files for all of the enabled applications.
        $ApplicationManager = new Gdn_ApplicationManager();
        $Apps = $ApplicationManager->enabledApplications();
        $AppNames = array_column($Apps, 'Folder');
        $Paths = array();
        foreach ($Apps as $Key => $AppInfo) {
            $Path = PATH_APPLICATIONS."/{$AppInfo['Folder']}/settings/structure.php";
            if (file_exists($Path)) {
                $Paths[] = $Path;
            }

            Gdn::applicationManager()->registerPermissions($Key);
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

        $Registered = $PluginManager->registeredPlugins();

        foreach ($Registered as $ClassName => $Enabled) {
            if (!$Enabled) {
                continue;
            }

            try {
                $Plugin = $PluginManager->getPluginInstance($ClassName, Gdn_PluginManager::ACCESS_CLASSNAME);
                if (method_exists($Plugin, 'Structure')) {
                    trace("{$ClassName}->Structure()");
                    $Plugin->structure();
                }
            } catch (Exception $Ex) {
                // Do nothing, plugin wouldn't load/structure.
                if (debug()) {
                    throw $Ex;
                }
            }
        }
        $this->fireEvent('AfterStructure');
    }
}
