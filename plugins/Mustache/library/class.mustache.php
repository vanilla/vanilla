<?php

/**
 * @copyright 2010-2014 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 */

/**
 * Mustache abstraction layer
 *
 * Vanilla implementation of Mustache templating engine.
 *
 * @author Tim Gunter <markm@vanillaforums.com>
 * @package addons
 */
class Mustache {

    /**
     * The mustache object used for rendering
     * @var Mustache_Engine
     */
    protected $engine = null;

    /**
     * Client side template list
     * @var array
     */
    public $templates;

    public function __construct() {
        $this->reset();
    }

    /**
     * Reset clientside templates list
     */
    public function reset() {
        $this->templates = array();
    }

    /**
     * Render the given template
     *
     * @param string $path path to the template
     * @param Gdn_Controller $controller the controller that is rendering the template
     */
    public function render($path, $controller) {
        $template = file_exists($path) ? file_get_contents($path) : '';
        echo $this->engine()->render($template, $controller->Data);
    }

    /**
     * Get an instance of Mustache_Engine
     *
     * @return Mustache_Engine the mustache engine object
     */
    public function engine() {
        if (is_null($this->engine)) {
            $this->engine = Gdn::factory('Mustache_Engine');
            Gdn::pluginManager()->CallEventHandlers($this->engine, 'Mustache_Engine', 'init');
        }
        return $this->engine;
    }

    /**
     * See if the provided template causes any errors
     *
     * @param string $path path of template file to test
     * @return boolean TRUE if template loads successfully
     */
    public function testTemplate($path) {
        try {
            $template = file_exists($path) ? file_get_contents($path) : '';
            $result = $this->engine()->render($template, []);
            if ($result == '' || strpos($result, '<title>Fatal Error</title>') > 0 || strpos($result, '<h1>Something has gone wrong.</h1>') > 0) {
                return false;
            }
        } catch (Exception $ex) {
            return false;
        }
        return true;
    }

    /**
     * Add a Mustache template to controller output
     *
     * @param string $template
     * @param string $controllerName optional.
     * @param string $applicationFolder optional.
     * @return boolean
     */
    public static function addTemplateFile($template = '', $controllerName = null, $applicationFolder = false) {
        if (is_null($controllerName)) {
            $controllerName = stringEndsWith(Gdn::controller()->ControllerName, 'controller', true, true);
        }

        if ($controllerName) {
            $template = "{$controllerName}/{$template}";
        }

        $template = stringEndsWith($template, '.mustache', true, true);
        $fileName = "{$template}.mustache";
        $templateInfo = array(
            'FileName' => $fileName,
            'AppFolder' => $applicationFolder,
            'Options' => array(
                'name' => $template
            )
        );

        // Handle plugin-sourced views
        if (stringBeginsWith($applicationFolder, 'plugins/')) {
            $name = stringBeginsWith($applicationFolder, 'plugins/', true, true);
            $info = Gdn::pluginManager()->getPluginInfo($name, Gdn_PluginManager::ACCESS_PLUGINNAME);
            if ($info) {
                $templateInfo['Version'] = val('Version', $info);
            }
        } else {
            $info = Gdn::applicationManager()->getApplicationInfo($applicationFolder);
            if ($info) {
                $templateInfo['Version'] = val('Version', $info);
            } else {
                $templateInfo['Version'] = APPLICATION_VERSION;
            }
        }

        Gdn::factory('Mustache')->templates[] = $templateInfo;
    }

    /**
     * Removes a Template file from the collection.
     *
     * @param string $fileName The Template file to search for.
     */
    public static function removeTemplateFile($fileName) {
        $tpls = Gdn::factory('Mustache')->templates;
        foreach ($tpls as $key => $fileInfo) {
            if ($fileInfo['Template'] == $fileName) {
                unset(Gdn::factory('Mustache')->templates[$key]);
                return;
            }
        }
    }

    /**
     * Resolve relative static resources into full paths
     *
     * This method is used to translate CSS, Js and Template relative file lists
     * into absolute paths.
     *
     * Element values should conform to the following format:
     *
     * [] => array(
     *    'FileName'     => // filename (relative, absolute, or URL)
     *    'AppFolder'    => // optional application folder to target (default controller app)
     * );
     *
     * @param array $resourceList
     * @param string $stub
     * @param array $options Optional. List of check options.
     *   - 'GlobalLibrary'  // Check $Stub/library in global section
     *   - 'StripRoot'      // Strip PATH_ROOT from final results
     *   - 'CDNS'           // List of external CDN replacements
     * @param array $checkLocations Optional. List of locations to check.
     *   - 'themes'
     *   - 'plugins'
     *   - 'applications'
     *   - 'global'
     */
    public static function resolveStaticResources($resourceList, $stub, $options = null, $checkLocations = null) {

        // All locations by default
        if (!is_array($checkLocations)) {
            $checkLocations = array('themes', 'plugins', 'applications', 'global');
        }

        // Default options
        $defaultOptions = array(
            'GlobalLibrary' => true,
            'StripRoot' => true,
            'CDNS' => array(),
            'AutoVersion' => true
        );
        if (!is_array($options)) {
            $options = array();
        }
        $options = array_merge($defaultOptions, $options);

        // Parse options
        $checkGlobalLibrary = val('GlobalLibrary', $options);
        $stripRoot = val('StripRoot', $options);
        $autoDetectVersion = val('AutoVersion', $options);

        // See if we're allowing any CDN replacements
        $CDNs = val('CDNS', $options, array());

        // Pre-get controller info
        $controllerAppFolder = false;
        $controllerTheme = false;
        if (Gdn::Controller() instanceof Gdn_Controller) {
            $controllerAppFolder = Gdn::controller()->ApplicationFolder;
            $controllerTheme = Gdn::controller()->Theme;
        }

        $fileList = array();
        foreach ($resourceList as $index => $resourceInfo) {

            $resourceFile = $resourceInfo['FileName'];
            $resourceFolder = val('AppFolder', $resourceInfo);
            $resourceOptions = (array)val('Options', $resourceInfo, false);

            if ($resourceFile === false) {
                if (!$resourceOptions) {
                    continue;
                }

                $rawCSS = val('Css', $resourceOptions, false);
                if (!$rawCSS) {
                    continue;
                }

                $cssHash = md5($rawCSS);
                $fileList[$resourceFolder] = array(
                    'options' => $resourceOptions
                );
                continue;
            }

            $skipFileCheck = false;

            // Resolve CDN resources
            if (array_key_exists($resourceFile, $CDNs)) {
                $resourceFile = $CDNs[$resourceFile];
            }

            if (strpos($resourceFile, '//') !== false) {

                // This is a link to an external file.
                $skipFileCheck = true;
                $testPaths = array($resourceFile);
            } elseif (strpos($resourceFile, '/') === 0) {

                // A direct path to the file was given.
                $testPaths = array(paths(PATH_ROOT, $resourceFile));
            } elseif (strpos($resourceFile, '~') === 0) {

                $skipFileCheck = true;
                $resourceFile = substr($resourceFile, 1);
                $testPaths = array(paths(PATH_ROOT, $resourceFile));
            } else {

                // Relative path
                $appFolder = val('AppFolder', $resourceInfo, false);
                if ($appFolder == '') {
                    $appFolder = $controllerAppFolder;
                }

                if ($appFolder == 'false') {
                    $appFolder = false;
                }

                // Resources can come from:
                //   - a theme
                //   - an application
                //   - a plugin
                //   - global garden resource-specific folder
                //   - global garden resource-specific library folder
                $testPaths = array();

                // Theme
                if (in_array('themes', $checkLocations) && $controllerTheme) {

                    // Application-specific theme override
                    if ($appFolder) {
                        $testPaths[] = paths(PATH_THEMES, $controllerTheme, $appFolder, $stub, $resourceFile);
                    }

                    // Garden-wide theme override
                    $testPaths[] = paths(PATH_THEMES, $controllerTheme, $stub, $resourceFile);
                }

                // Application or plugin
                $isPluginFolder = stringBeginsWith(trim($appFolder, '/'), 'plugins/', true, false);
                if ($isPluginFolder) {
                    $pluginFolder = stringBeginsWith(trim($appFolder, '/'), 'plugins/', true, true);
                }
                if (in_array('plugins', $checkLocations) && $isPluginFolder) {

                    // Plugin
                    $testPaths[] = paths(PATH_PLUGINS, $pluginFolder, $stub, $resourceFile);
                    $testPaths[] = paths(PATH_PLUGINS, $pluginFolder, $resourceFile);
                }

                if (in_array('applications', $checkLocations) && !$isPluginFolder) {

                    // Application
                    if ($appFolder) {
                        $testPaths[] = paths(PATH_APPLICATIONS, $appFolder, $stub, $resourceFile);
                    }

                    // Dashboard app is added by default
                    if ($appFolder != 'dashboard') {
                        $testPaths[] = paths(PATH_APPLICATIONS, 'dashboard', $stub, $resourceFile);
                    }
                }

                if (in_array('global', $checkLocations)) {

                    // Global folder. eg. root/js/
                    $testPaths[] = paths(PATH_ROOT, $stub, $resourceFile);

                    if ($checkGlobalLibrary) {
                        // Global library folder. eg. root/js/library/
                        $testPaths[] = paths(PATH_ROOT, $stub, 'library', $resourceFile);
                    }
                }
            }

            // Find the first file that matches the path.
            $resourcePath = false;
            if (!$skipFileCheck) {
                foreach ($testPaths as $glob) {
                    $paths = safeGlob($glob);
                    if (is_array($paths) && count($paths) > 0) {
                        $resourcePath = $paths[0];
                        break;
                    }
                }

                // Get version
                $version = val('Version', $resourceInfo, false);

                // If a path was matched, make sure it has a version
                if ($resourcePath && !$version && $autoDetectVersion) {

                    // Theme file
                    if (!$version && preg_match('`themes/([^/]+)/`i', $resourcePath, $matches)) {
                        $themeName = $matches[1];
                        $themeInfo = Gdn::themeManager()->getThemeInfo($themeName);
                        $version = val('Version', $themeInfo);
                        $versionSource = "theme {$themeName}";
                    }

                    // Plugin file
                    if (!$version && preg_match('`plugins/([^/]+)/`i', $resourcePath, $matches)) {
                        $pluginName = $matches[1];
                        $pluginInfo = Gdn::pluginManager()->getPluginInfo($pluginName, Gdn_PluginManager::ACCESS_PLUGINNAME);
                        $version = val('Version', $pluginInfo);
                        $versionSource = "plugin {$pluginName}";
                    }

                    // Application file
                    if (!$version && preg_match('`applications/([^/]+)/`i', $resourcePath, $matches)) {
                        $applicationName = $matches[1];
                        $applicationInfo = Gdn::applicationManager()->getApplicationInfo($applicationName);
                        $version = val('Version', $applicationInfo);
                        $versionSource = "app {$applicationName}";
                    }
                }
            } else {
                $version = null;
            }

            // Global file
            if (!$version) {
                $version = APPLICATION_VERSION;
            }

            // If a path was succesfully matched
            if ($resourcePath !== false || $skipFileCheck) {

                // We enact SkipFileCheck for virtual paths, targeting controllers
                // perhaps, or full URLs from the CDN resolver.
                if ($skipFileCheck) {
                    $resourcePath = array_pop($testPaths);
                }

                // Strip PATH_ROOT from absolute path
                $resourceResolved = $resourcePath;
                if ($stripRoot) {
                    $resourceResolved = str_replace(
                        array(PATH_ROOT, DS), array('', '/'), $resourcePath
                    );
                }

                // Bring options into response structure
                $resource = array(
                    'path' => $resourcePath
                );

                $resourceOptions = (array)val('Options', $resourceInfo, array());
                touchValue('version', $resource, $version);
                if ($resourceOptions) {
                    touchValue('options', $resource, $resourceOptions);
                }

                $fileList[$resourceResolved] = $resource;
            }
        }

        return $fileList;
    }

}
