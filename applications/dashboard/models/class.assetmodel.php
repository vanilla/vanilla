<?php
/**
 * Contains functions for combining Javascript and CSS files.
 *
 * Use the AssetModel_StyleCss_Handler event to include CSS files in your plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.1
 */

/**
 * Manages Assets.
 */
class AssetModel extends Gdn_Model {

    /** @var array List of CSS files to serve. */
    protected $_CssFiles = array();

     /** @var string */
    public $UrlPrefix = '';

    /**
     * Add to the list of CSS files to serve.
     *
     * @param $Filename
     * @param bool $Folder
     * @param bool $Options
     */
    public function addCssFile($Filename, $Folder = false, $Options = false) {
        if (is_string($Options)) {
            $Options = array('Css' => $Options);
        }
        $this->_CssFiles[] = array($Filename, $Folder, $Options);
    }

    /**
     *
     *
     * @param $ThemeType
     * @param $Basename
     * @param $ETag
     * @param null $NotFound
     * @return array
     * @throws Exception
     */
    public function getCssFiles($ThemeType, $Basename, $ETag, &$NotFound = null) {
        $NotFound = array();

        // Gather all of the css paths.
        switch ($Basename) {
            case 'Style':
                $this->_CssFiles = array(
                    array('style.css', 'dashboard', array('Sort' => -10))
                );
                break;
            case 'Admin':
                $this->_CssFiles = array(
                    array('admin.css', 'dashboard', array('Sort' => -10))
                );
                break;
            default:
                $this->_CssFiles = array();
        }

        // Throw an event so that plugins can add their css too.
        $this->EventArguments['ETag'] = $ETag;
        $this->EventArguments['ThemeType'] = $ThemeType;
        $this->fireEvent($Basename.'Css');

        // Include theme customizations last so that they override everything else.
        switch ($Basename) {
            case 'Style':
                $this->addCssFile('custom.css', false, array('Sort' => 10));

                if (Gdn::controller()->Theme && Gdn::controller()->ThemeOptions) {
                    $Filenames = valr('Styles.Value', Gdn::controller()->ThemeOptions);
                    if (is_string($Filenames) && $Filenames != '%s') {
                        $this->addCssFile(changeBasename('custom.css', $Filenames), false, array('Sort' => 11));
                    }
                }

                break;
            case 'Admin':
                $this->addCssFile('customadmin.css', false, array('Sort' => 10));
                break;
        }

        $this->fireEvent('AfterGetCssFiles');

        // Hunt the css files down.
        $Paths = array();
        foreach ($this->_CssFiles as $Info) {
            $Filename = $Info[0];
            $Folder = val(1, $Info);
            $Options = val(2, $Info);
            $Css = val('Css', $Options);

            if ($Css) {
                // Add some literal Css.
                $Paths[] = array(false, $Folder, $Options);

            } else {
                list($Path, $UrlPath) = self::CssPath($Filename, $Folder, $ThemeType);
                if ($Path) {
                    $Paths[] = array($Path, $UrlPath, $Options);
                } else {
                    $NotFound[] = array($Filename, $Folder, $Options);
                }
            }
        }

        // Sort the paths.
        usort($Paths, array('AssetModel', '_ComparePath'));

        return $Paths;
    }

    /**
     *
     *
     * @param $A
     * @param $B
     * @return int
     */
    protected function _ComparePath($A, $B) {
        $SortA = val('Sort', $A[2], 0);
        $SortB = val('Sort', $B[2], 0);

        if ($SortA == $SortB) {
            return 0;
        }
        if ($SortA > $SortB) {
            return 1;
        }
        return -1;
    }

    /**
     * Lookup the path to a CSS file and return its info array
     *
     * @param string $Filename name/relative path to css file
     * @param string $Folder optional. app or plugin folder to search
     * @param string $ThemeType mobile or desktop
     * @return array|bool
     */
    public static function cssPath($Filename, $Folder = '', $ThemeType = '') {
        if (!$ThemeType) {
            $ThemeType = isMobile() ? 'mobile' : 'desktop';
        }

        // 1. Check for a url.
        if (isUrl($Filename)) {
            return array($Filename, $Filename);
        }

        $Paths = array();

        // 2. Check for a full path.
        if (strpos($Filename, '/') === 0) {
            $Filename = ltrim($Filename, '/');

            // Direct path was given
            $Filename = "/{$Filename}";
            $Path = PATH_ROOT.$Filename;
            if (file_exists($Path)) {
                Deprecated(htmlspecialchars($Path).": AssetModel::CssPath() with direct paths");
                return array($Path, $Filename);
            }
            return false;
        }

        // 3. Check the theme.
        $Theme = Gdn::ThemeManager()->ThemeFromType($ThemeType);
        if ($Theme) {
            $Path = "/$Theme/design/$Filename";
            $Paths[] = array(PATH_THEMES.$Path, "/themes{$Path}");
        }

        // 4. Static, Plugin, or App relative file
        if ($Folder) {
            if (in_array($Folder, array('resources', 'static'))) {
                $Path = "/resources/design/{$Filename}";
                $Paths[] = array(PATH_ROOT.$Path, $Path);

            // A plugin-relative path was given
            } elseif (stringBeginsWith($Folder, 'plugins/')) {
                $Folder = substr($Folder, strlen('plugins/'));
                $Path = "/{$Folder}/design/{$Filename}";
                $Paths[] = array(PATH_PLUGINS.$Path, "/plugins$Path");

                // Allow direct-to-file links for plugins
                $Paths[] = array(PATH_PLUGINS."/$Folder/$Filename", "/plugins/{$Folder}/{$Filename}", true); // deprecated

            // An app-relative path was given
            } else {
                $Path = "/{$Folder}/design/{$Filename}";
                $Paths[] = array(PATH_APPLICATIONS.$Path, "/applications{$Path}");
            }
        }

        // 5. Check the default application.
        if ($Folder != 'dashboard') {
            $Paths[] = array(PATH_APPLICATIONS."/dashboard/design/$Filename", "/applications/dashboard/design/$Filename", true); // deprecated
        }

        foreach ($Paths as $Info) {
            if (file_exists($Info[0])) {
                if (!empty($Info[2])) {
                    // This path is deprecated.
                    unset($Info[2]);
                    Deprecated("The css file '$Filename' in folder '$Folder'");
                }

                return $Info;
            }
        }
        if (!(StringEndsWith($Filename, 'custom.css') || StringEndsWith($Filename, 'customadmin.css'))) {
            trace("Could not find file '$Filename' in folder '$Folder'.");
        }

        return false;
    }

    /**
     * Lookup the path to a JS file and return its info array
     *
     * @param string $filename name/relative path to js file
     * @param string $folder optional. app or plugin folder to search
     * @param string $themeType mobile or desktop
     * @return array|bool
     */
    public static function jsPath($filename, $folder = '', $themeType = '') {
        if (!$themeType) {
            $themeType = isMobile() ? 'mobile' : 'desktop';
        }

        // 1. Check for a url.
        if (isUrl($filename)) {
            return array($filename, $filename);
        }

        $paths = array();

        // 2. Check for a full path.
        if (strpos($filename, '/') === 0) {
            $filename = ltrim($filename, '/');

            // Direct path was given
            $filename = "/{$filename}";
            $path = PATH_ROOT.$filename;
            if (file_exists($path)) {
                deprecated(htmlspecialchars($path).": AssetModel::JsPath() with direct paths");
                return array($path, $filename);
            }
            return false;
        }

        // 3. Check the theme.
        $theme = Gdn::themeManager()->themeFromType($themeType);
        if ($theme) {
            $path = "/{$theme}/js/{$filename}";
            $paths[] = array(PATH_THEMES.$path, "/themes{$path}");
        }

        // 4. Static, Plugin, or App relative file
        if ($folder) {
            if (in_array($folder, array('resources', 'static'))) {
                $path = "/resources/js/{$filename}";
                $paths[] = array(PATH_ROOT.$path, $path);

            // A plugin-relative path was given
            } elseif (stringBeginsWith($folder, 'plugins/')) {
                $folder = substr($folder, strlen('plugins/'));
                $path = "/{$folder}/js/{$filename}";
                $paths[] = array(PATH_PLUGINS.$path, "/plugins{$path}");

                // Allow direct-to-file links for plugins
                $paths[] = array(PATH_PLUGINS."/{$folder}/{$filename}", "/plugins/{$folder}/{$filename}", true); // deprecated

            // An app-relative path was given
            } else {

                // App-relative path under the theme
                if ($theme) {
                    $path = "/{$theme}/{$folder}/js/{$filename}";
                    $paths[] = array(PATH_THEMES.$path, "/themes{$path}");
                }

                $path = "/{$folder}/js/{$filename}";
                $paths[] = array(PATH_APPLICATIONS.$path, "/applications{$path}");
            }
        }

        // 5. Check the global js folder.
        $paths[] = array(PATH_ROOT."/js/{$filename}", "/js/{$filename}");
        $paths[] = array(PATH_ROOT."/js/library/{$filename}", "/js/library/{$filename}");

        foreach ($paths as $info) {
            if (file_exists($info[0])) {
                if (!empty($info[2])) {
                    // This path is deprecated.
                    unset($info[2]);
                    deprecated("The js file '$filename' in folder '$folder'");
                }

                return $info;
            }
        }
        if (!stringEndsWith($filename, 'custom.js')) {
            trace("Could not find file '$filename' in folder '$folder'.");
        }

        return false;
    }

    /**
     * Generate an e-tag for the application from the versions of all of its enabled applications/plugins.
     **/
    public static function eTag() {
        $Data = array();
        $Data['vanilla-core-'.APPLICATION_VERSION] = true;

        $Plugins = Gdn::pluginManager()->EnabledPlugins();
        foreach ($Plugins as $Info) {
            $Data[strtolower("{$Info['Index']}-plugin-{$Info['Version']}")] = true;
        }
//      echo(Gdn_Upload::FormatFileSize(strlen(serialize($Plugins))));
//      decho($Plugins);

        $Applications = Gdn::ApplicationManager()->EnabledApplications();
        foreach ($Applications as $Info) {
            $Data[strtolower("{$Info['Index']}-app-{$Info['Version']}")] = true;
        }

        // Add the desktop theme version.
        $Info = Gdn::ThemeManager()->GetThemeInfo(Gdn::ThemeManager()->DesktopTheme());
        if (!empty($Info)) {
            $Version = val('Version', $Info, 'v0');
            $Data[strtolower("{$Info['Index']}-theme-{$Version}")] = true;

            if (Gdn::controller()->Theme && Gdn::controller()->ThemeOptions) {
                $Filenames = valr('Styles.Value', Gdn::controller()->ThemeOptions);
                $Data[$Filenames] = true;
            }
        }

        // Add the mobile theme version.
        $Info = Gdn::ThemeManager()->GetThemeInfo(Gdn::ThemeManager()->MobileTheme());
        if (!empty($Info)) {
            $Version = val('Version', $Info, 'v0');
            $Data[strtolower("{$Info['Index']}-theme-{$Version}")] = true;
        }

        Gdn::pluginManager()->EventArguments['ETagData'] =& $Data;

        $Suffix = '';
        Gdn::pluginManager()->EventArguments['Suffix'] =& $Suffix;
        Gdn::pluginManager()->FireAs('AssetModel')->fireEvent('GenerateETag');
        unset(Gdn::pluginManager()->EventArguments['ETagData']);

        ksort($Data);
        $Result = substr(md5(implode(',', array_keys($Data))), 0, 8).$Suffix;
//      decho($Data);
//      die();
        return $Result;
    }
}
