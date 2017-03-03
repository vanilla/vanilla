<?php
/**
 * Theme manager.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */
use Vanilla\Addon;
use Vanilla\AddonManager;

/**
 * Manages available themes, enabling and disabling them.
 */
class Gdn_ThemeManager extends Gdn_Pluggable {

    const ACTION_ENABLE = 1;

    const ACTION_DISABLE = 2;

    const DEFAULT_DESKTOP_THEME = 'default';
    const DEFAULT_MOBILE_THEME = 'mobile';

    /** @var array An array of search paths for themes and their files. */
    private $themeSearchPaths = null;

    /** @var array */
    private $alternateThemeSearchPaths = null;

    /** @var array An array of available plugins. Never access this directly, instead use $this->AvailablePlugins(); */
    private $themeCache = null;

    /** @var bool Whether to use APC for theme cache storage. */
    private $apc = false;

    /**
     * @var bool Whether or not the request object can be accessed.
     */
    private $hasRequest = true;

    /** @var array The layout options for a category list. */
    private $allowedCategoriesLayouts = ['table', 'modern', 'mixed'];

    /** @var array The layout options for a discussions list. */
    private $allowedDiscussionsLayouts = ['table', 'modern'];

    /**
     * @var AddonManager
     */
    private $addonManager;

    /**
     *
     */
    public function __construct(AddonManager $addonManager = null, $hasRequest = null) {
        parent::__construct();
        $this->addonManager = $addonManager;
        $this->hasRequest = !($hasRequest === false);
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
        // Do nothing. The plugin manager handles the theme hooks.
    }

    /**
     * Looks through the themes directory for valid themes.
     *
     * The themes are returned as an associative array of "Theme Name" => "Theme Info Array".
     *
     * @param bool $force Deprecated.
     * @return array Returns the available themes in an array.
     */
    public function availableThemes($force = false) {
        $addons = $this->addonManager->lookupAllByType(Addon::TYPE_THEME);
        $result = [];
        /* @var Addon $addon */
        foreach ($addons as $addon) {
            $result[$addon->getRawKey()] = Gdn::pluginManager()->calcOldInfoArray($addon);
        }
        return $result;
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
            $ThemeFiles = $this->findThemeFilesOld($ThemePath);

            if (val('about', $ThemeFiles) === false) {
                continue;
            }

            $ThemeAboutFile = val('about', $ThemeFiles);
            $SearchThemeInfo = $this->scanThemeFileOld($ThemeAboutFile);

            // Don't index archived themes.
//         if (val('Archived', $SearchThemeInfo, FALSE))
//            continue;

            // Add the screenshot.
            if (array_key_exists('screenshot', $ThemeFiles)) {
                $RelativeScreenshot = ltrim(str_replace(PATH_ROOT, '', val('screenshot', $ThemeFiles)), '/');
                $SearchThemeInfo['ScreenshotUrl'] = $this->asset($RelativeScreenshot, true);
            }

            // Add the mobile screenshot.
            if (array_key_exists('mobilescreenshot', $ThemeFiles)) {
                $RelativeScreenshot = ltrim(str_replace(PATH_ROOT, '', val('mobilescreenshot', $ThemeFiles)), '/');
                $SearchThemeInfo['MobileScreenshotUrl'] = $this->asset($RelativeScreenshot, true);
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
     * Clear the dependencies on {@link asset()} for unit testing.
     *
     * @param string $path The relative path of the asset.
     * @param string $withDomain Whether or not to include the domain.
     * @return string Returns the asset URL.
     */
    private function asset($path, $withDomain) {
        if ($this->hasRequest) {
            return asset($path, $withDomain);
        } else {
            return '/'.ltrim($path);
        }
    }

    /**
     * Deprecated.
     *
     * @deprecated
     */
    public function clearThemeCache() {
        deprecated('Gdn_PluginManager->clearThemeCache()');
    }

    /**
     * Deprecated.
     *
     * @deprecated
     */
    public function searchPaths() {
        return [];
    }

    /**
     * Deprecated.
     *
     * @return array Deprecated.
     */
    public function findThemeFiles() {
        deprecated('Gdn_ThemeManager->findThemeFiles');
        return [];
    }

    /**
     * Find the files associated with the theme.
     *
     * Please don't use this method.
     *
     * @param string $themePath The theme's path.
     * @return array|false Returns an array of paths or false if the {@link $themePath} is invalid.
     * @deprecated
     */
    private function findThemeFilesOld($themePath) {
        if (!is_dir($themePath)) {
            return false;
        }

        $ThemeFiles = scandir($themePath);
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
                    $MatchedThemeFiles[$FileType] = combinePaths(array($themePath, $ThemeFile));
                }
            }
        }

        return array_key_exists('about', $MatchedThemeFiles) ? $MatchedThemeFiles : false;
    }

    /**
     * Deprecated.
     *
     * @param string $ThemeFile The path to the theme file.
     * @param string $VariableName The name of the theme info variable name.
     * @return null|array Returns the theme info.
     * @deprecated
     */
    private function scanThemeFileOld($ThemeFile, $VariableName = '') {
        // Find the $PluginInfo array
        if (!file_exists($ThemeFile)) {
            return null;
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

        // Define the folder name and assign the class name for the newly added item.
        $var = ${$VariableName};
        if (isset($var) && is_array($var)) {
            reset($var);
            $name = key($var);
            $var = current($var);

            $var['Index'] = $name;
            $var['AboutFile'] = $ThemeFile;
            $var['RealAboutFile'] = realpath($ThemeFile);
            $var['ThemeRoot'] = dirname($ThemeFile);
            touchValue('Name', $var, $name);
            touchValue('Folder', $var, basename(dirname($ThemeFile)));

            return $var;
        } elseif ($VariableName !== null) {
            if (isset($var)) {
                return $var;
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
        $theme = $this->addonManager->lookupTheme($ThemeName);
        if ($theme) {
            return Gdn_PluginManager::calcOldInfoArray($theme);
        } else {
            return false;
        }
    }

    /**
     * Sets the layout for the theme preview without saving the config values.
     *
     * @param string $themeName The name of the theme.
     */
    private function preparePreview($themeName) {
        $themeInfo = $this->getThemeInfo($themeName);
        $this->setLayout($themeInfo, false);
    }

    /**
     *
     *
     * @return mixed
     */
    public function currentTheme() {
        if (IsMobile()) {
            if ($this->hasMobilePreview()) {
                return $this->getMobilePreview();
            }
            return $this->getEnabledMobileThemeKey();
        } else {
            if ($this->hasPreview()) {
                $preview = $this->getPreview();
                $this->preparePreview($preview);
                return $preview;
            }
            return $this->getEnabledDesktopThemeKey();
        }
    }

    /**
     *
     *
     * @return mixed
     */
    public function desktopTheme() {
        if ($this->hasPreview()) {
            $preview = $this->getPreview();
            $this->preparePreview($preview);
            return $preview;
        }
        return $this->getEnabledDesktopThemeKey();
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
        $oldTheme = $this->getEnabledDesktopThemeKey();
        RemoveFromConfig('Garden.Theme');
        $newTheme = $this->getEnabledDesktopThemeKey();

        if ($oldTheme != $newTheme) {
            $this->themeHook($oldTheme, self::ACTION_DISABLE, true);
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
     * Hooks to the various actions, i.e. enable, disable and load.
     *
     * @param string $themeName The name of the plugin.
     * @param string $forAction Which action to hook it to, i.e. enable, disable or load.
     * @param boolean $callback whether to perform the hook method.
     * @return void
     */
    private function themeHook($themeName, $forAction, $callback = false) {
        switch ($forAction) {
            case self::ACTION_ENABLE:
                $methodName = 'setup';
                break;
            case self::ACTION_DISABLE:
                $methodName = 'onDisable';
                break;
            default:
                $methodName = '';
        }

        $info = $this->getThemeInfo($themeName);
        $pluginClass = val('ClassName', $info, '');
        $path = val('RealHooksFile', $info , '');
        if (!empty($path)) {
            include_once $path;
        }

        if ($callback && !empty($pluginClass) && class_exists($pluginClass)) {
            $plugin = new $pluginClass();
            if (method_exists($pluginClass, $methodName)) {
                $plugin->$methodName();
            }
        }
    }

    /**
     * Retrieves the key for the current desktop theme.
     *
     * @return string
     */
    public function enabledTheme() {
        deprecated('enabledTheme', 'getEnabledDesktopThemeKey', 'March 2017');
        return $this->getEnabledDesktopThemeKey();
    }

    /**
     * Retrieves the key for the current desktop theme.
     *
     * @return string
     */
    public function getEnabledDesktopThemeKey() {
        $themeName = Gdn::config('Garden.Theme');
        // Does it actually exist?
        if ($themeName && (Gdn::addonManager()->lookupTheme($themeName) === null)) {
            return self::DEFAULT_DESKTOP_THEME;
        }
        return $themeName;
    }


    /**
     * Retrieves the key for the current mobile theme.
     *
     * @return string
     */
    public function getEnabledMobileThemeKey() {
        $themeName = Gdn::config('Garden.MobileTheme');
        // Does it actually exist?
        if ($themeName && (Gdn::addonManager()->lookupTheme($themeName) === null)) {
            return self::DEFAULT_MOBILE_THEME;
        }
        return $themeName;
    }

    /**
     *
     *
     * @param bool $ReturnInSourceFormat
     * @return array|mixed
     */
    public function enabledThemeInfo($ReturnInSourceFormat = false) {
        $EnabledThemeName = $this->getEnabledDesktopThemeKey();
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
     * Set the layout config settings based on a theme's specifications.
     *
     * @param array $themeInfo A theme info array retrieved from `getThemeInfo()`.
     * @param bool $save Whether to save the layout to config.
     */
    private function setLayout($themeInfo, $save = true) {
        if ($layout = val('Layout', $themeInfo, false)) {
            $discussionsLayout = strtolower(val('Discussions', $layout, ''));
            $categoriesLayout = strtolower(val('Categories', $layout, ''));
            if ($discussionsLayout && in_array($discussionsLayout, $this->allowedDiscussionsLayouts)) {
                saveToConfig('Vanilla.Discussions.Layout', $discussionsLayout, $save);
            }
            if ($categoriesLayout && in_array($categoriesLayout, $this->allowedCategoriesLayouts)) {
                saveToConfig('Vanilla.Categories.Layout', $categoriesLayout, $save);
            }
        }
    }

    /**
     * Checks if a theme has theme options.
     *
     * @param $themeKey The key value of the theme we're checking.
     * @return bool Whether the given theme has theme options.
     */
    public function hasThemeOptions($themeKey) {
        $themeInfo = $this->getThemeInfo($themeKey);
        $options = val('Options', $themeInfo, []);
        return !empty($options);
    }

    /**
     *
     *
     * @param $ThemeName
     * @param bool $IsMobile Whether to enable the theme as the mobile theme or not.
     * @return bool
     * @throws Exception
     */
    public function enableTheme($ThemeName, $IsMobile = false) {
        // Make sure to run the setup
        $this->testTheme($ThemeName);

        // Set the theme.
        $ThemeInfo = $this->getThemeInfo($ThemeName);
        $ThemeFolder = val('Folder', $ThemeInfo, '');

        $oldTheme = $IsMobile ? c('Garden.MobileTheme', self::DEFAULT_MOBILE_THEME) : c('Garden.Theme', self::DEFAULT_DESKTOP_THEME);

        if ($ThemeFolder == '') {
            throw new Exception(t('The theme folder was not properly defined.'));
        } else {
            if ($IsMobile) {
                saveToConfig('Garden.MobileTheme', $ThemeName);
            } else {
                saveToConfig('Garden.Theme', $ThemeName);
            }

            $this->setLayout($ThemeInfo);
        }

        if ($oldTheme !== $ThemeName) {
            $this->themeHook($ThemeName, self::ACTION_ENABLE, true);
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
     * Test a theme for dependencies and parse errors.
     *
     * @param string $themeName The case-sensitive theme name.
     * @return bool Returns
     * @throws Gdn_UserException Throws an exception when there was an issue testing the theme.
     */
    public function testTheme($themeName) {
        $addon = $this->addonManager->lookupTheme($themeName);
        if (!$addon) {
            throw notFoundException('Plugin');
        }

        try {
            $this->addonManager->checkRequirements($addon, true);
            $addon->test(true);
        } catch (\Exception $ex) {
            throw new Gdn_UserException($ex->getMessage(), $ex->getCode());
        }
        return true;
    }

    /**
     *
     *
     * @return mixed
     */
    public function mobileTheme() {
        if ($this->hasMobilePreview()) {
            return $this->getMobilePreview();
        }
        return $this->getEnabledMobileThemeKey();
    }

    /**
     * Returns the folder name (aka slug) of the previewed theme, or an empty string if there is no previewed theme.
     *
     * @return string The folder name of the previewed mobile theme or an empty string.
     */
    public function getPreview() {
        return htmlspecialchars(Gdn::session()->getPreference('PreviewThemeFolder', ''));
    }

    /**
     * Returns whether there's a theme being previewed.
     *
     * @return bool Whether there's a theme being previewed.
     */
    public function hasPreview() {
        return Gdn::session()->getPreference('PreviewThemeFolder', '') !== '';
    }

    /**
     * Returns whether there's a mobile theme being previewed.
     *
     * @return bool Whether there's a mobile theme being previewed.
     */
    public function hasMobilePreview() {
        return Gdn::session()->getPreference('PreviewMobileThemeFolder', '') !== '';
    }

    /**
     * Returns the folder name (aka slug) of the previewed mobile theme, or an empty string if there is no
     * previewed mobile theme.
     *
     * @return string The folder name of the previewed mobile theme or an empty string.
     */
    public function getMobilePreview() {
        return htmlspecialchars(Gdn::session()->getPreference('PreviewMobileThemeFolder', ''));
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
