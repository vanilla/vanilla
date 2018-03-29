<?php
/**
 * Update model.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * @param $addon
     * @param $addons
     * @deprecated since 2.3
     */
    private static function addAddon($addon, &$addons) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        $slug = strtolower($addon['AddonKey']).'-'.strtolower($addon['AddonType']);
        $addons[$slug] = $addon;
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
        $info = [$slug => $addonInfo];

        return $info;
    }

    /**
     * Check an addon's file to extract the addon information out of it.
     *
     * @param string $path The path to the file.
     * @param bool $throwError Whether or not to throw an exception if there is a problem analyzing the addon.
     * @return array An array of addon information.
     * @deprecated since 2.3
     */
    public static function analyzeAddon($path, $throwError = true) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        if (!file_exists($path)) {
            if ($throwError) {
                throw new Exception("$path not found.", 404);
            }
            return false;
        }

        $addon = [];
        $result = [];

        $infoPaths = [
            '/addon.json', // addon
            '/settings/about.php', // application
            '/default.php', // plugin
            '/class.*.plugin.php', // plugin
            '/about.php', // theme
            '/definitions.php', // locale
            '/environment.php', // vanilla core
            'vanilla2export.php' // porter
        ];

        // Look for an addon.json file.
        if (file_exists("$path/addon.json")) {

            $info = self::addonJsonConverter($path);

            $entry = [
                'Path' => $path,
                'Name' => val('Name', $info[key($info)]),
                'Base' => val('Key', $info[key($info)])
            ];

            $result = self::checkAddon($info, $entry);
            if (empty($result)) {
                $addon = self::buildAddon($info);
            }

        } else {
            // Get the list of potential files to analyze.
            if (is_dir($path)) {
                $entries = self::getInfoFiles($path, $infoPaths);
                $deleteEntries = false;
            } else {
                $entries = self::getInfoZip($path, $infoPaths, false, $throwError);
                $deleteEntries = true;
            }

            foreach ($entries as $entry) {
                if ($entry['Name'] == '/environment.php') {
                    // This could be the core vanilla package.
                    $version = self::parseCoreVersion($entry['Path']);

                    if (!$version) {
                        continue;
                    }

                    // The application was confirmed.
                    $addon = [
                        'AddonKey' => 'vanilla',
                        'AddonTypeID' => ADDON_TYPE_CORE,
                        'Name' => 'Vanilla',
                        'Description' => 'Vanilla is an open-source, standards-compliant, multi-lingual, fully extensible discussion forum for the web. Anyone who has web-space that meets the requirements can download and use Vanilla for free!',
                        'Version' => $version,
                        'License' => 'GPLv2',
                        'Path' => $entry['Path']];
                    break;
                } elseif ($entry['Name'] == 'vanilla2export.php') {
                    // This could be the vanilla porter.
                    $version = self::parseCoreVersion($entry['Path']);

                    if (!$version) {
                        continue;
                    }

                    $addon = [
                        'AddonKey' => 'porter',
                        'AddonTypeID' => ADDON_TYPE_CORE,
                        'Name' => 'Vanilla Porter',
                        'Description' => 'Drop this script in your existing site and navigate to it in your web browser to export your existing forum data to the Vanilla 2 import format.',
                        'Version' => $version,
                        'License' => 'GPLv2',
                        'Path' => $entry['Path']];
                    break;
                } else {
                    // Support for newer addon.json info.
                    if ($entry['Name'] === '/addon.json') {
                        // Build a relative path to addon.json.
                        $addonDir = dirname($entry['Path']);
                        $addonDir = stringBeginsWith($addonDir, PATH_ROOT, false, true);
                        $info = self::addonJsonConverter($addonDir);
                    } else {
                        // This could be an addon.
                        $info = self::parseInfoArray($entry['Path']);
                    }

                    $result = self::checkAddon($info, $entry);
                    if (!empty($result)) {
                        break;
                    }
                    $addon = self::buildAddon($info);
                }
            }

            if ($deleteEntries) {
                $folderPath = substr($path, 0, -4);
                Gdn_FileSystem::removeFolder($folderPath);
            }
        }

        // Add the addon requirements.
        if (!empty($addon)) {
            $requirements = arrayTranslate(
                $addon,
                [
                    'RequiredApplications' => 'Applications',
                    'RequiredPlugins' => 'Plugins',
                    'RequiredThemes' => 'Themes',
                    'Require' => 'Addons'
                ]
            );
            foreach ($requirements as $type => $items) {
                if (!is_array($items)) {
                    unset($requirements[$type]);
                }
            }
            $addon['Requirements'] = dbencode($requirements);

            $addon['Checked'] = true;
            $addon['Path'] = $path;
            $uploadsPath = PATH_UPLOADS.'/';
            if (stringBeginsWith($addon['Path'], $uploadsPath)) {
                $addon['File'] = substr($addon['Path'], strlen($uploadsPath));
            }

            if (is_file($path)) {
                $addon['MD5'] = md5_file($path);
                $addon['FileSize'] = filesize($path);
            }
        } elseif ($throwError) {
            $msg = implode("\n", $result);
            throw new Gdn_UserException($msg, 400);
        } else {
            return false;
        }

        return $addon;
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

        // If there wasn't a "Variable" in the original $info, try the updated $info.
        if (empty($variable) && array_key_exists('Variable', $info)) {
            $variable = $info['Variable'];
        }

        $addon = array_merge(['AddonKey' => $key, 'AddonTypeID' => ''], $info);
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
     * @param string $path
     * @param array $infoPaths
     * @return array
     * @deprecated since 2.3
     */
    private static function getInfoFiles($path, $infoPaths) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        $path = str_replace('\\', '/', rtrim($path));

        $result = [];
        // Check to see if the paths exist.
        foreach ($infoPaths as $infoPath) {
            $glob = glob($path.$infoPath);
            if (is_array($glob)) {
                foreach ($glob as $globPath) {
                    $result[] = ['Name' => substr($globPath, strlen($path)), 'Path' => $globPath];
                }
            }
        }

        return $result;
    }

    /**
     * Open a zip archive and inspect its contents for the requested paths.
     *
     * @param string $path
     * @param array $infoPaths
     * @param bool $tmpPath
     * @param bool $throwError
     * @return array
     * @throws Exception
     * @deprecated since 2.3
     */
    private static function getInfoZip($path, $infoPaths, $tmpPath = false, $throwError = true) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        // Extract the zip file so we can make sure it has appropriate information.
        $zip = null;
        $zipOpened = false;

        if (class_exists('ZipArchive', false)) {
            $zip = new ZipArchive();
            $zipOpened = $zip->open($path);
            if ($zipOpened !== true) {
                $zip = null;
            }
        }

        if (!$zip) {
            $zip = new PclZipAdapter();
            $zipOpened = $zip->open($path);
        }

        if ($zipOpened !== true) {
            if ($throwError) {
                $errors = [ZipArchive::ER_EXISTS => 'ER_EXISTS', ZipArchive::ER_INCONS => 'ER_INCONS', ZipArchive::ER_INVAL => 'ER_INVAL',
                    ZipArchive::ER_MEMORY => 'ER_MEMORY', ZipArchive::ER_NOENT => 'ER_NOENT', ZipArchive::ER_NOZIP => 'ER_NOZIP',
                    ZipArchive::ER_OPEN => 'ER_OPEN', ZipArchive::ER_READ => 'ER_READ', ZipArchive::ER_SEEK => 'ER_SEEK'];
                $error = val($zipOpened, $errors, 'Unknown Error');

                throw new Exception(t('Could not open addon file. Addons must be zip files.')." ($path $error)", 400);
            }
            return [];
        }

        if ($tmpPath === false) {
            $tmpPath = dirname($path).'/'.basename($path, '.zip').'/';
        }

        if (file_exists($tmpPath)) {
            Gdn_FileSystem::removeFolder($tmpPath);
        }

        $result = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->statIndex($i);

            if (preg_match('#(\.\.[\\/])#', $entry['name'])) {
                throw new Gdn_UserException("Invalid path in zip file: ".$entry['name']);
            }

            $name = '/'.ltrim($entry['name'], '/');

            foreach ($infoPaths as $infoPath) {
                $preg = '`('.str_replace(['.', '*'], ['\.', '.*'], $infoPath).')$`';
                if (preg_match($preg, $name, $matches)) {
                    $base = trim(substr($name, 0, -strlen($matches[1])), '/');

                    if (strpos($base, '/') !== false) {
                        continue; // file nested too deep.
                    }
                    if (!file_exists($tmpPath)) {
                        mkdir($tmpPath, 0777, true);
                    }

                    $zip->extractTo($tmpPath, $entry['name']);
                    $result[] = ['Name' => $matches[1], 'Path' => $tmpPath.rtrim($entry['name'], '/'), 'Base' => $base];
                }
            }
        }

        return $result;
    }

    /**
     * Parse the version out of the core's index.php file.
     *
     * @param string $path The path to the index.php file.
     * @return string A string containing the version or empty if the file could not be parsed.
     * @deprecated since 2.3
     */
    public static function parseCoreVersion($path) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        $fp = fopen($path, 'rb');
        $application = false;
        $version = '';

        while (($line = fgets($fp)) !== false) {
            if (preg_match("`define\\('(.*?)', '(.*?)'\\);`", $line, $matches)) {
                $name = $matches[1];
                $value = $matches[2];
                switch ($name) {
                    case 'APPLICATION':
                        $application = $value;
                        break;
                    case 'APPLICATION_VERSION':
                        $version = $value;
                }
            }

            if ($application !== false && $version !== '') {
                break;
            }
        }
        fclose($fp);
        return $version;
    }

    /**
     * Offers a quick and dirty way of parsing an addon's info array without using eval().
     *
     * @param string $path The path to the info array.
     * @param string $variable The name of variable containing the information.
     * @return array|false The info array or false if the file could not be parsed.
     * @deprecated since 2.3
     */
    public static function parseInfoArray($path, $variable = false) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        $fp = fopen($path, 'rb');
        $lines = [];
        $inArray = false;
        $globalKey = '';

        // Get all of the lines in the info array.
        while (($line = fgets($fp)) !== false) {
            // Remove comments from the line.
            $line = preg_replace('`\s//.*$`', '', $line);
            if (!$line) {
                continue;
            }

            if (!$inArray && preg_match('`\$([A-Za-z]+Info)\s*\[`', trim($line), $matches)) {
                $variable = $matches[1];
                if (preg_match('`\[\s*[\'"](.+?)[\'"]\s*\]`', $line, $matches)) {
                    $globalKey = $matches[1];
                    $inArray = true;
                }
            } elseif ($inArray && stringEndsWith(trim($line), ';')) {
                break;
            } elseif ($inArray) {
                $lines[] = trim($line);
            }
        }
        fclose($fp);

        if (count($lines) == 0) {
            return false;
        }

        // Parse the name/value information in the arrays.
        $result = [];
        foreach ($lines as $line) {
            // Get the name from the line.
            if (!preg_match('`[\'"](.+?)[\'"]\s*=>`', $line, $matches) || !substr($line, -1) == ',') {
                continue;
            }
            $key = $matches[1];

            // Strip the key from the line.
            $line = trim(trim(substr(strstr($line, '=>'), 2)), ',');

            if (strlen($line) == 0) {
                continue;
            }

            $value = null;
            if (is_numeric($line)) {
                $value = $line;
            } elseif (strcasecmp($line, 'TRUE') == 0 || strcasecmp($line, 'FALSE') == 0)
                $value = $line;
            elseif (in_array($line[0], ['"', "'"]) && substr($line, -1) == $line[0]) {
                $quote = $line[0];
                $value = trim($line, $quote);
                $value = str_replace('\\'.$quote, $quote, $value);
            } elseif (stringBeginsWith($line, 'array(') && substr($line, -1) == ')') {
                // Parse the line's array.
                $line = substr($line, 6, strlen($line) - 7);
                $items = explode(',', $line);
                $array = [];
                foreach ($items as $item) {
                    $subItems = explode('=>', $item);
                    if (count($subItems) == 1) {
                        $array[] = trim(trim($subItems[0]), '"\'');
                    } elseif (count($subItems) == 2) {
                        $subKey = trim(trim($subItems[0]), '"\'');
                        $subValue = trim(trim($subItems[1]), '"\'');
                        $array[$subKey] = $subValue;
                    }
                }
                $value = $array;
            }

            if ($value != null) {
                $result[$key] = $value;
            }
        }
        $result = [$globalKey => $result, 'Variable' => $variable];
        return $result;
    }

    /**
     *
     *
     * @param array $myAddons
     * @param array $latestAddons
     * @return bool
     * @deprecated since 2.3
     */
    public function compareAddons($myAddons, $latestAddons) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        $updateAddons = false;

        // Join the site addons with my addons.
        foreach ($latestAddons as $addon) {
            $key = val('AddonKey', $addon);
            $type = val('Type', $addon);
            $slug = strtolower($key).'-'.strtolower($type);
            $version = val('Version', $addon);
            $fileUrl = val('Url', $addon);

            if (isset($myAddons[$slug])) {
                $myAddon = $myAddons[$slug];

                if (version_compare($version, val('Version', $myAddon, '999'), '>')) {
                    $myAddon['NewVersion'] = $version;
                    $myAddon['NewDownloadUrl'] = $fileUrl;
                    $updateAddons[$slug] = $myAddon;
                }
            } else {
                unset($myAddons[$slug]);
            }
        }

        return $updateAddons;
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
        $results = [];

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
     * @param bool $enabled Deprecated.
     * @return array Deprecated.
     * @deprecated since 2.3
     */
    public function getAddons($enabled = false) {
        deprecated(__CLASS__.'->'.__METHOD__.'()');
        return [];
    }

    /**
     * Deprecated.
     *
     * @param bool $enabled Deprecated.
     * @return array|bool Deprecated.
     * @deprecated
     */
    public function getAddonUpdates($enabled = false) {
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
