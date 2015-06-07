<?php
/**
 * Theme manager.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Manages available themes, enabling and disabling them.
 */
class Gdn_ThemeManager extends Gdn_Pluggable {

    /** @var array An array of search paths for themes and their files. */
    protected $ThemeSearchPaths = null;

    /** @var array */
    protected $AlternateThemeSearchPaths = null;

    /** @var array An array of available plugins. Never access this directly, instead use $this->AvailablePlugins(); */
    protected $ThemeCache = null;

    /** @var bool Whether to use APC for theme cache storage. */
    protected $Apc = false;

    /**
     *
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Sets up the theme framework
     *
     * This method indexes all available themes and extracts their information.
     * It then determines which plugins have been enabled, and includes them.
     * Finally, it parses all plugin files and extracts their events and plugged
     * methods.
     */
    public function start($Force = false) {

        if (function_exists('apc_fetch') && C('Garden.Apc', false)) {
            $this->Apc = true;
        }

        // Build list of all available themes
        $this->availableThemes($Force);

        // If there is a hooks file in the theme folder, include it.
        $ThemeName = $this->currentTheme();
        $ThemeInfo = $this->getThemeInfo($ThemeName);
        $ThemeHooks = val('RealHooksFile', $ThemeInfo, null);
        if (file_exists($ThemeHooks)) {
            include_once($ThemeHooks);
        }
    }

    /**
     * Looks through the themes directory for valid themes and returns them as
     * an associative array of "Theme Name" => "Theme Info Array". It also adds
     * a "Folder" definition to the Theme Info Array for each.
     */
    public function availableThemes($Force = false) {
        if (is_null($this->ThemeCache) || $Force) {
            $this->ThemeCache = array();

            // Check cache freshness
            foreach ($this->searchPaths() as $SearchPath => $Trash) {
                unset($SearchPathCache);

                // Check Cache
                $SearchPathCacheKey = 'Garden.Themes.PathCache.'.$SearchPath;
                if ($this->Apc) {
                    $SearchPathCache = apc_fetch($SearchPathCacheKey);
                } else {
                    $SearchPathCache = Gdn::cache()->get($SearchPathCacheKey, array(Gdn_Cache::FEATURE_NOPREFIX => true));
                }

                $CacheHit = ($SearchPathCache !== Gdn_Cache::CACHEOP_FAILURE);
                if ($CacheHit && is_array($SearchPathCache)) {
                    $CacheIntegrityCheck = (sizeof(array_intersect(array_keys($SearchPathCache), array('CacheIntegrityHash', 'ThemeInfo'))) == 2);
                    if (!$CacheIntegrityCheck) {
                        $SearchPathCache = array(
                            'CacheIntegrityHash' => null,
                            'ThemeInfo' => array()
                        );
                    }
                }

                $CacheThemeInfo = &$SearchPathCache['ThemeInfo'];
                if (!is_array($CacheThemeInfo)) {
                    $CacheThemeInfo = array();
                }

                $PathListing = scandir($SearchPath, 0);
                sort($PathListing);

                $PathIntegrityHash = md5(serialize($PathListing));
                if (val('CacheIntegrityHash', $SearchPathCache) != $PathIntegrityHash) {
                    // Trace('Need to re-index theme cache');
                    // Need to re-index this folder
                    $PathIntegrityHash = $this->indexSearchPath($SearchPath, $CacheThemeInfo, $PathListing);
                    if ($PathIntegrityHash === false) {
                        continue;
                    }

                    $SearchPathCache['CacheIntegrityHash'] = $PathIntegrityHash;
                    if ($this->Apc) {
                        apc_store($SearchPathCacheKey, $SearchPathCache);
                    } else {
                        Gdn::cache()->store($SearchPathCacheKey, $SearchPathCache, array(Gdn_Cache::FEATURE_NOPREFIX => true));
                    }
                }

                $this->ThemeCache = array_merge($this->ThemeCache, $CacheThemeInfo);
            }
        }

        return $this->ThemeCache;
    }

    /**
     *
     *
     * @param $SearchPath
     * @param $ThemeInfo
     * @param null $PathListing
     * @return bool|string
     */
    public function indexSearchPath($SearchPath, &$ThemeInfo, $PathListing = null) {
        if (is_null($PathListing) || !is_array($PathListing)) {
            $PathListing = scandir($SearchPath, 0);
            sort($PathListing);
        }

        if ($PathListing === false) {
            return false;
        }

        foreach ($PathListing as $ThemeFolderName) {
            if (substr($ThemeFolderName, 0, 1) == '.') {
                continue;
            }

            $ThemePath = CombinePaths(array($SearchPath, $ThemeFolderName));
            $ThemeFiles = $this->findThemeFiles($ThemePath);

            if (val('about', $ThemeFiles) === false) {
                continue;
            }

            $ThemeAboutFile = val('about', $ThemeFiles);
            $SearchThemeInfo = $this->scanThemeFile($ThemeAboutFile);

            // Don't index archived themes.
//         if (val('Archived', $SearchThemeInfo, FALSE))
//            continue;

            // Add the screenshot.
            if (array_key_exists('screenshot', $ThemeFiles)) {
                $RelativeScreenshot = ltrim(str_replace(PATH_ROOT, '', val('screenshot', $ThemeFiles)), '/');
                $SearchThemeInfo['ScreenshotUrl'] = Asset($RelativeScreenshot, true);
            }

            // Add the mobile screenshot.
            if (array_key_exists('mobilescreenshot', $ThemeFiles)) {
                $RelativeScreenshot = ltrim(str_replace(PATH_ROOT, '', val('mobilescreenshot', $ThemeFiles)), '/');
                $SearchThemeInfo['MobileScreenshotUrl'] = Asset($RelativeScreenshot, true);
            }

            if (array_key_exists('hooks', $ThemeFiles)) {
                $SearchThemeInfo['HooksFile'] = val('hooks', $ThemeFiles, false);
                $SearchThemeInfo['RealHooksFile'] = realpath($SearchThemeInfo['HooksFile']);
            }

            if ($SearchThemeInfo === false) {
                continue;
            }

            $ThemeInfo[$ThemeFolderName] = $SearchThemeInfo;
        }

        return md5(serialize($PathListing));
    }

    /**
     *
     *
     * @param null $SearchPaths
     */
    public function clearThemeCache($SearchPaths = null) {
        if (!is_null($SearchPaths)) {
            if (!is_array($SearchPaths)) {
                $SearchPaths = array($SearchPaths);
            }
        } else {
            $SearchPaths = $this->searchPaths();
        }

        foreach ($SearchPaths as $SearchPath => $SearchPathName) {
            $SearchPathCacheKey = "Garden.Themes.PathCache.{$SearchPath}";
            if ($this->Apc) {
                apc_delete($SearchPathCacheKey);
            } else {
                Gdn::cache()->remove($SearchPathCacheKey, array(Gdn_Cache::FEATURE_NOPREFIX => true));
            }
        }
    }

    /**
     * Get the current search paths
     *
     * By default, get all the paths as built by the constructor. Includes the two (or one) default plugin paths
     * of PATH_PLUGINS and PATH_LOCAL_PLUGINS, as well as any extra paths defined in the config variable.
     *
     * @param boolean $OnlyCustom whether or not to exclude the two default paths and return only config paths
     * @return array Search paths
     */
    public function searchPaths($OnlyCustom = false) {
        if (is_null($this->ThemeSearchPaths) || is_null($this->AlternateThemeSearchPaths)) {
            $this->ThemeSearchPaths = array();
            $this->AlternateThemeSearchPaths = array();

            // Add default search path(s) to list
            $this->ThemeSearchPaths[rtrim(PATH_THEMES, '/')] = 'core';

            // Check for, and load, alternate search paths from config
            $RawAlternatePaths = C('Garden.PluginManager.Search', null);
            if (!is_null($RawAlternatePaths)) {
                /*
                            // Handle serialized and unserialized alternate path arrays
                            $AlternatePaths = unserialize($RawAlternatePaths);
                            if ($AlternatePaths === FALSE && is_array($RawAlternatePaths))
                */
                $AlternatePaths = $RawAlternatePaths;

                if (!is_array($AlternatePaths)) {
                    $AlternatePaths = array($AlternatePaths => 'alternate');
                }

                foreach ($AlternatePaths as $AltPath => $AltName) {
                    $this->AlternateThemeSearchPaths[rtrim($AltPath, '/')] = $AltName;
                    if (is_dir($AltPath)) {
                        $this->ThemeSearchPaths[rtrim($AltPath, '/')] = $AltName;
                    }
                }
            }
        }

        if (!$OnlyCustom) {
            return $this->ThemeSearchPaths;
        }

        return $this->AlternateThemeSearchPaths;
    }

    /**
     *
     *
     * @param $ThemePath
     * @return array|bool
     */
    public function findThemeFiles($ThemePath) {
        if (!is_dir($ThemePath)) {
            return false;
        }

        $ThemeFiles = scandir($ThemePath);
        $TestPatterns = array(
            'about\.php' => 'about',
            '.*\.theme\.php' => 'about',
            'class\..*themehooks\.php' => 'hooks',
            'screenshot\.(gif|jpg|jpeg|png)' => 'screenshot',
            'mobile\.(gif|jpg|jpeg|png)' => 'mobilescreenshot'
        );

        $MatchedThemeFiles = array();
        foreach ($ThemeFiles as $ThemeFile) {
            foreach ($TestPatterns as $TestPattern => $FileType) {
                if (preg_match('!'.$TestPattern.'!', $ThemeFile)) {
                    $MatchedThemeFiles[$FileType] = combinePaths(array($ThemePath, $ThemeFile));
                }
            }
        }

        return array_key_exists('about', $MatchedThemeFiles) ? $MatchedThemeFiles : false;
    }

    /**
     *
     *
     * @param $ThemeFile
     * @param null $VariableName
     * @return null|void
     */
    public function scanThemeFile($ThemeFile, $VariableName = null) {
        // Find the $PluginInfo array
        if (!file_exists($ThemeFile)) {
            return;
        }
        $Lines = file($ThemeFile);

        $InfoBuffer = false;
        $ClassBuffer = false;
        $ClassName = '';
        $ThemeInfoString = '';
        if (!$VariableName) {
            $VariableName = 'ThemeInfo';
        }

        $ParseVariableName = '$'.$VariableName;
        ${$VariableName} = array();

        foreach ($Lines as $Line) {
            if ($InfoBuffer && substr(trim($Line), -2) == ');') {
                $ThemeInfoString .= $Line;
                $ClassBuffer = true;
                $InfoBuffer = false;
            }

            if (stringBeginsWith(trim($Line), $ParseVariableName)) {
                $InfoBuffer = true;
            }

            if ($InfoBuffer) {
                $ThemeInfoString .= $Line;
            }

            if ($ClassBuffer && strtolower(substr(trim($Line), 0, 6)) == 'class ') {
                $Parts = explode(' ', $Line);
                if (count($Parts) > 2) {
                    $ClassName = $Parts[1];
                }

                break;
            }

        }
        unset($Lines);
        if ($ThemeInfoString != '') {
            @eval($ThemeInfoString);
        }

        // Define the folder name and assign the class name for the newly added item
        if (isset(${$VariableName}) && is_array(${$VariableName})) {
            $Item = array_pop($Trash = array_keys(${$VariableName}));

            ${$VariableName}[$Item]['Index'] = $Item;
            ${$VariableName}[$Item]['AboutFile'] = $ThemeFile;
            ${$VariableName}[$Item]['RealAboutFile'] = realpath($ThemeFile);
            ${$VariableName}[$Item]['ThemeRoot'] = dirname($ThemeFile);

            if (!array_key_exists('Name', ${$VariableName}[$Item])) {
                ${$VariableName}[$Item]['Name'] = $Item;
            }

            if (!array_key_exists('Folder', ${$VariableName}[$Item])) {
                ${$VariableName}[$Item]['Folder'] = basename(dirname($ThemeFile));
            }

            return ${$VariableName}[$Item];
        } elseif ($VariableName !== null) {
            if (isset(${$VariableName})) {
                return ${$VariableName};
            }
        }

        return null;
    }

    /**
     *
     *
     * @param $ThemeName
     * @return mixed
     */
    public function getThemeInfo($ThemeName) {
        return val($ThemeName, $this->availableThemes(), false);
    }

    /**
     *
     *
     * @return mixed
     */
    public function currentTheme() {
        return C(!IsMobile() ? 'Garden.Theme' : 'Garden.MobileTheme', 'default');
    }

    /**
     *
     *
     * @return mixed
     */
    public function desktopTheme() {
        return C('Garden.Theme', 'default');
    }

    /**
     *
     *
     * @throws Gdn_UserException
     */
    public function disableTheme() {
        if ($this->currentTheme() == 'default') {
            throw new Gdn_UserException(T('You cannot disable the default theme.'));
        }
        $oldTheme = $this->enabledTheme();
        RemoveFromConfig('Garden.Theme');
        $newTheme = $this->enabledTheme();

        if ($oldTheme != $newTheme) {
            Logger::event(
                'theme_changed',
                'The {themeType} theme was changed from {oldTheme} to {newTheme}.',
                array(
                    'themeType' => 'desktop',
                    'oldTheme' => $oldTheme,
                    'newTheme' => $newTheme
                )
            );
        }
    }

    /**
     *
     *
     * @return Gdn_Config|mixed
     */
    public function enabledTheme() {
        $ThemeName = Gdn::config('Garden.Theme', 'default');
        return $ThemeName;
    }

    /**
     *
     *
     * @param bool $ReturnInSourceFormat
     * @return array|mixed
     */
    public function enabledThemeInfo($ReturnInSourceFormat = false) {
        $EnabledThemeName = $this->enabledTheme();
        $ThemeInfo = $this->getThemeInfo($EnabledThemeName);

        if ($ThemeInfo === false) {
            return array();
        }

        if ($ReturnInSourceFormat) {
            return $ThemeInfo;
        }

        // Update the theme info for a format consumable by views.
        if (is_array($ThemeInfo) & isset($ThemeInfo['Options'])) {
            $Options =& $ThemeInfo['Options'];
            if (isset($Options['Styles'])) {
                foreach ($Options['Styles'] as $Key => $Params) {
                    if (is_string($Params)) {
                        $Options['Styles'][$Key] = array('Basename' => $Params);
                    } elseif (is_array($Params) && isset($Params[0])) {
                        $Params['Basename'] = $Params[0];
                        unset($Params[0]);
                        $Options['Styles'][$Key] = $Params;
                    }
                }
            }
            if (isset($Options['Text'])) {
                foreach ($Options['Text'] as $Key => $Params) {
                    if (is_string($Params)) {
                        $Options['Text'][$Key] = array('Type' => $Params);
                    } elseif (is_array($Params) && isset($Params[0])) {
                        $Params['Type'] = $Params[0];
                        unset($Params[0]);
                        $Options['Text'][$Key] = $Params;
                    }
                }
            }
        }
        return $ThemeInfo;
    }

    /**
     *
     *
     * @param $ThemeName
     * @param bool $IsMobile
     * @return bool
     * @throws Exception
     */
    public function enableTheme($ThemeName, $IsMobile = false) {
        // Make sure to run the setup
        $this->testTheme($ThemeName);

        // Set the theme.
        $ThemeInfo = $this->getThemeInfo($ThemeName);
        $ThemeFolder = val('Folder', $ThemeInfo, '');

        $oldTheme = $IsMobile ? c('Garden.MobileTheme', 'mobile') : c('Garden.Theme', 'default');

        if ($ThemeFolder == '') {
            throw new Exception(t('The theme folder was not properly defined.'));
        } else {
            $Options = valr("{$ThemeName}.Options", $this->AvailableThemes());
            if ($Options) {
                if ($IsMobile) {
                    saveToConfig(array(
                        'Garden.MobileTheme' => $ThemeName,
                        'Garden.MobileThemeOptions.Name' => valr("{$ThemeName}.Name", $this->availableThemes(), $ThemeFolder)
                    ));
                } else {
                    saveToConfig(array(
                        'Garden.Theme' => $ThemeName,
                        'Garden.ThemeOptions.Name' => valr("{$ThemeName}.Name", $this->availableThemes(), $ThemeFolder)
                    ));
                }
            } else {
                if ($IsMobile) {
                    saveToConfig('Garden.MobileTheme', $ThemeName);
                    removeFromConfig('Garden.MobileThemeOptions');
                } else {
                    saveToConfig('Garden.Theme', $ThemeName);
                    removeFromConfig('Garden.ThemeOptions');
                }
            }
        }

        if ($oldTheme !== $ThemeName) {
            Logger::event(
                'theme_changed',
                Logger::NOTICE,
                'The {themeType} theme changed from {oldTheme} to {newTheme}.',
                array(
                    'themeType' => $IsMobile ? 'mobile' : 'desktop',
                    'oldTheme' => $oldTheme,
                    'newTheme' => $ThemeName
                )
            );
        }

        // Tell the locale cache to refresh itself.
        Gdn::locale()->refresh();
        return true;
    }

    /**
     *
     *
     * @param $ThemeName
     * @return bool
     * @throws Gdn_UserException
     */
    public function testTheme($ThemeName) {
        // Get some info about the currently enabled theme.
        $EnabledTheme = $this->enabledThemeInfo();
        $EnabledThemeFolder = val('Folder', $EnabledTheme, '');
        $OldClassName = $EnabledThemeFolder.'ThemeHooks';

        // Make sure that the theme's requirements are met
        $ApplicationManager = new Gdn_ApplicationManager();
        $EnabledApplications = $ApplicationManager->enabledApplications();

        $NewThemeInfo = $this->getThemeInfo($ThemeName);
        $ThemeName = val('Index', $NewThemeInfo, $ThemeName);
        $RequiredApplications = arrayValue('RequiredApplications', $NewThemeInfo, false);
        $ThemeFolder = arrayValue('Folder', $NewThemeInfo, '');
        checkRequirements($ThemeName, $RequiredApplications, $EnabledApplications, 'application'); // Applications

        // If there is a hooks file, include it and run the setup method.
        $ClassName = "{$ThemeFolder}ThemeHooks";
        $HooksFile = val("HooksFile", $NewThemeInfo, null);
        if (!is_null($HooksFile) && file_exists($HooksFile)) {
            include_once($HooksFile);
            if (class_exists($ClassName)) {
                $ThemeHooks = new $ClassName();
                $ThemeHooks->Setup();
            }
        }

        // If there is a hooks in the old theme, include it and run the ondisable method.
        if (class_exists($OldClassName)) {
            $ThemeHooks = new $OldClassName();
            if (method_exists($ThemeHooks, 'OnDisable')) {
                $ThemeHooks->OnDisable();
            }
        }

        return true;
    }

    /**
     *
     *
     * @return mixed
     */
    public function mobileTheme() {
        return C('Garden.MobileTheme', 'default');
    }

    /**
     *
     *
     * @param $Type
     * @return mixed
     */
    public function themeFromType($Type) {
        if ($Type === 'mobile') {
            return $this->mobileTheme();
        } else {
            return $this->desktopTheme();
        }
    }
}
