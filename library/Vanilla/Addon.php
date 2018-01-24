<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;


use Vanilla\Utility\CamelCaseScheme;

/**
 * Contains the information for a single addon.
 */
class Addon {
    const TYPE_ADDON = 'addon';
    const TYPE_LOCALE = 'locale';
    const TYPE_THEME = 'theme';

    const PATH_FULL = 'full'; // full path
    const PATH_ADDON = 'addon'; // path relative to PATH_ROOT
    const PATH_LOCAL = 'local'; // path relative to the addon's subdirectory
    const PATH_REAL = 'real'; // realpath()

    const PRIORITY_LOW = 10;
    const PRIORITY_NORMAL = 100;
    const PRIORITY_HIGH = 1000;

    const PRIORITY_THEME = Addon::PRIORITY_HIGH;
    const PRIORITY_PLUGIN = Addon::PRIORITY_NORMAL;
    const PRIORITY_LOCALE = 11;
    const PRIORITY_APPLICATION = Addon::PRIORITY_LOW;

    /**
     * @var array The addon's info array.
     */
    private $info = [];

    /**
     * @var array An array of classes.
     */
    private $classes = [];

    /**
     * @var string The root-relative directory of the addon.
     */
    private $subdir = '';

    /**
     * @var array An array of translation files indexed by locale.
     */
    private $translations = [];

    /**
     * @var array An array of special classes and files.
     */
    private $special = [];

    /**
     * Addon constructor.
     *
     * @param string $subdir The root-relative subdirectory of the addon.
     */
    public function __construct($subdir = '') {
        if (!empty($subdir)) {
            $this->scan($subdir);
        }
    }

    /**
     * Scan a subdirectory and setup this addon.
     *
     * @param string $subdir The root-relative subdirectory to scan.
     */
    public function scan($subdir) {
        $this->setSubdir($subdir);

        // Look for the addon info.
        $info = $this->scanInfo();
        $this->setInfo($info);

        // Scan for classes.
        if ($this->getType() !== static::TYPE_LOCALE) {
            $classes = $this->scanClasses();
        } else {
            $classes = [];
        }
        $this->setClasses($classes);

        // Scan for a structure file.
        if ($this->getType() === static::TYPE_ADDON) {
            if (file_exists($this->path('/settings/structure.php'))) {
                $this->special['structure'] = '/settings/structure.php';
            }

            if (file_exists($this->path('/settings/configuration.php'))) {
                $this->special['config'] = '/settings/configuration.php';
            }

            if (file_exists($this->path('/settings/bootstrap.php'))) {
                $this->special['bootstrap'] = '/settings/bootstrap.php';
            } elseif (file_exists($this->path('/bootstrap.php'))) {
                $this->special['bootstrap'] = '/bootstrap.php';
            }
        }

        // Scan for translations.
        $translations = $this->scanTranslations();
        $this->setTranslationPaths($translations);

        // Look for an icon.


        // Fix issues with the plugin that can be fixed.
        $this->check(true);

    }

    /**
     * Set the root-relative subdirectory of the addon.
     *
     * @param string $subdir The new subdirectory.
     * @return Addon Returns `$this` for fluent calls.
     */
    private function setSubdir($subdir) {
        $this->subdir = '/'.ltrim($subdir, '/\\');
        return $this;
    }

    /**
     * Get an addon's meta info.
     *
     * @return array|null Return the addon's info array or **null** if one could not be found.
     */
    private function scanInfo() {
        $subdir = $this->getSubdir();
        $dir = $this->path();

        // Look for an addon.json file.
        if (file_exists("$dir/addon.json")) {
            $addonJSON = file_get_contents("$dir/addon.json");
            if (!$addonJSON) {
                throw new \Exception("The addon at $subdir has an unreadable addon.json file.");
            }

            $info = json_decode($addonJSON, true);
            if (empty($info)) {
                throw new \Exception("The addon at $subdir has invalid JSON in addon.json.");
            }

            // Kludge that sets oldType until we unify applications and plugins into addon.
            list($addonParentFolder, $addonFolder) = explode('/', ltrim($subdir, '/'));
            if (in_array($addonParentFolder, ['applications', 'plugins'])) {
                $info['oldType'] = substr($addonParentFolder, 0, -1);

                // Kludge that sets keyRaw until we use key everywhere.
                if ($info['oldType'] === 'application') {
                    if (!isset($info['keyRaw'])) {
                        $info['keyRaw'] = $info['name'];
                    }
                } else {
                    if ($addonFolder !== $info['key']) {
                        $info['keyRaw'] = $addonFolder;
                    }
                }
            }

            return $info;
        }

        // Make a list of info array paths to scan.
        $infoArrayPaths = array_merge(
            $this->glob('/*plugin.php'),
            [
                "/default.php", // old plugin
                "/settings/about.php", // application
                "/about.php", // theme
                "/definitions.php", // locale
            ]
        );

        foreach ($infoArrayPaths as $path) {
            if ($info = $this->scanInfoArray($path)) {
                return $info;
            }
        }

        throw new \Exception("The addon at $subdir doesn't have any info.", 500);
    }

    /**
     * Get the subdir.
     *
     * @return string Returns the subdir.
     */
    public function getSubdir() {
        return $this->subdir;
    }

    /**
     * Make a full path from an addon-relative path.
     *
     * @param string $subpath The subpath to base the path on, starting with a "/".
     * @param string $relative One of the **Addon::PATH_*** constants.
     * @return string Returns a full path.
     */
    public function path($subpath = '', $relative = self::PATH_FULL) {
        $subpath = $subpath ? '/'.ltrim($subpath, '\\/') : '';

        switch ($relative) {
            case self::PATH_FULL:
                return PATH_ROOT.$this->subdir.$subpath;
            case self::PATH_ADDON:
                return $this->subdir.$subpath;
            case self::PATH_REAL:
                return realpath(PATH_ROOT.$this->subdir.$subpath);
            case self::PATH_LOCAL:
            case null:
                return $subpath;
            default:
                throw new \InvalidArgumentException("Invalid path relation: $relative.", 500);
        }
    }

    /**
     * Perform a glob from this addon's subdirectory.
     *
     * @param string $pattern The pattern to glob.
     * @param string $dirs Just directories.
     * @return array Returns an array of root-relative paths.
     * @see glob()
     */
    private function glob($pattern, $dirs = false) {
        $px = $this->path();
        $fullPattern = $px.$pattern;
        $strlen = strlen($px);
        $paths = glob($fullPattern, GLOB_NOSORT | ($dirs ? GLOB_ONLYDIR : 0));
        if (!is_array($paths)) {
            return [];
        }
        foreach ($paths as &$path) {
            $path = substr($path, $strlen);
        }
        return $paths;
    }

    /**
     * Scan an addon's info array.
     *
     * @param string $subpath The addon-relative path to the PHP file containing the info array.
     * @return array|null Returns the info array or **null** if there isn't one.
     */
    private function scanInfoArray($subpath) {
        $path = $this->path($subpath);
        if (!file_exists($path)) {
            return null;
        }

        // Extract the lines of the file that contain the info array.
        $lines = file($path);
        $infoString = '';
        $infoVarFound = false;

        foreach ($lines as $line) {
            if ($infoVarFound) {
                $infoString .= $line;
                if (substr(trim($line), -1) === ';') {
                    break;
                }
            } elseif (preg_match('`^\s*\$(Plugin|Application|Theme|Locale)Info\[`', $line)) {
                $infoVarFound = true;
                $infoString .= $line;
            }
        }
        if ($infoString != '') {
            try {
                eval($infoString);
            } catch (\Throwable $ex) {
                trigger_error("Error scanning info array in $path. ".$ex->getMessage(), E_USER_WARNING);
                return null;
            } catch (\Exception $ex) {
                trigger_error("Error scanning info array in $path. ".$ex->getMessage(), E_USER_WARNING);
                return null;
            }
        } else {
            return null;
        }

        $oldType = null;

        // See which info array is defined.
        if (!empty($PluginInfo) && is_array($PluginInfo)) {
            $array = $PluginInfo;
            $type = static::TYPE_ADDON;
            $priority = static::PRIORITY_PLUGIN;
            $oldType = 'plugin';
        } elseif (!empty($ApplicationInfo) && is_array($ApplicationInfo)) {
            $array = $ApplicationInfo;
            $type = static::TYPE_ADDON;
            $priority = static::PRIORITY_APPLICATION;
            $oldType = 'application';
        } elseif (!empty($ThemeInfo) && is_array($ThemeInfo)) {
            $array = $ThemeInfo;
            $type = static::TYPE_THEME;
            $priority = static::PRIORITY_THEME;
        } elseif (!empty($LocaleInfo) && is_array($LocaleInfo)) {
            $array = $LocaleInfo;
            $type = static::TYPE_LOCALE;
            $priority = static::PRIORITY_LOCALE;
        } else {
            return null;
        }

        $oldInfo = reset($array);
        $key = key($array);

        // Convert the info array to the new syntax.
        $nameScheme = new CamelCaseScheme();
        $info = $nameScheme->convertArrayKeys($oldInfo);
        if (isset($oldInfo['RegisterPermissions'])) {
            $info['registerPermissions'] = $oldInfo['RegisterPermissions'];
        }

        $info['key'] = $key;
        $info['type'] = $type;

        $oldInfo = reset($array);
        $key = key($array);


        if (empty($info['priority'])) {
            $info['priority'] = $priority;
        }

        if (isset($oldType)) {
            $info['oldType'] = $oldType;

            if ($oldType === 'application' && empty($info['name'])) {
                $info['name'] = $key;
            }
        }

        // Convert the author.
        if (!empty($info['author'])) {
            $author['name'] = $info['author'];
            unset($info['author']);
        }
        if (!empty($info['authorEmail'])) {
            $author['email'] = $info['authorEmail'];
            unset($info['authorEmail']);
        }
        if (!empty($info['authorUrl'])) {
            $author['homepage'] = $info['authorUrl'];
            unset($info['authorUrl']);
        }

        if (!empty($author)) {
            $authors = $this->splitAuthors($author);
            if (empty($info['authors']) || !is_array($info['authors'])) {
                $info['authors'] = $authors;
            } else {
                $info['authors'] = array_merge($info['authors'], $authors);
            }
        }

        // Convert the requires.
        $require = $this->convertRequire($oldInfo, ['RequiredPlugins', 'RequiredApplications']);
        if (!empty($require)) {
            $info['require'] = $require;
        }
        unset($info['requiredPlugins'], $info['requiredApplications']);

        return $info;
    }

    /**
     * Split an author array that may contain multiple authors separated by commas.
     *
     * This method handles the older plugin info arrays.
     *
     * @param array $author The author array to split.
     * @return array[array[string]] Returns the authors array.
     */
    private function splitAuthors($author) {
        $authors = [];

        foreach ($author as $key => $value) {
            $parts = explode(',', $value);

            foreach ($parts as $i => $part) {
                $authors[$i][$key] = trim($part);
            }
        }

        return $authors;
    }

    /**
     * Convert the info array style requirements to the composer-like require format.
     *
     * @param array $info The addon info array.
     * @param array $keys The old requirement arrays.
     */
    private static function convertRequire(array $info, array $keys) {
        $require = [];

        foreach ($keys as $key) {
            if (empty($info[$key]) || !is_array($info[$key])) {
                continue;
            }

            foreach ($info[$key] as $addonKey => $version) {
                if (!preg_match('`^[<>]=?|!=|~|\^`', $version)) {
                    $version = '>='.$version;
                }
                $require[strtolower($addonKey)] = $version;
            }
        }

        return $require;
    }

    /**
     * Set the info.
     *
     * @param array $info The new info array to set.
     * @return Addon Returns `$this` for fluent calls.
     */
    private function setInfo($info) {
        $this->info = $info;
        return $this;
    }

    /**
     * Get the type of addon.
     *
     * @return string Returns one of the **Addon::TYPE_*** constants.
     */
    public function getType() {
        return empty($this->info['type']) ? '' : $this->info['type'];
    }

    /**
     * Scan for all of the classes in this addon.
     *
     * @return array Returns an array of subpaths.
     */
    private function scanClasses() {
        $paths = $this->scanClassPaths();

        $classes = [];
        foreach ($paths as $path) {
            $declarations = static::scanFile($this->path($path));
            foreach ($declarations as $namespaceRow) {
                if (isset($namespaceRow['namespace'])) {
                    $namespace = rtrim($namespaceRow['namespace'], '\\').'\\';
                    $namespaceClasses = $namespaceRow['classes'];
                } else {
                    $namespace = '';
                    $namespaceClasses = $namespaceRow;
                }

                foreach ($namespaceClasses as $classRow) {
                    $className = $classRow['name'];
                    // It is possible, in the same file, to have multiple classes with the same name
                    // but with different namespaces...
                    $classes[strtolower($className)][] = [
                        'namespace' => $namespace,
                        'className' => $className,
                        'path' => $path,
                    ];

                    // Check to see if the class is a plugin or a hook.
                    if (strcasecmp(substr($className, -6), 'plugin') === 0
                        || strcasecmp(substr($className, -5), 'hooks') === 0
                    ) {

                        if (empty($this->special['plugin'])) {
                            $this->special['plugin'] = $namespace.$className;
                        } else {
                            $this->special['otherPlugins'][] = $namespace.$className;
                        }
                    }
                }
            }
        }
        return $classes;
    }

    /**
     * Scan the addon for potential class paths.
     *
     * @return \Traversable Returns a list of paths to PHP files.
     */
    private function scanClassPaths() {
        $dirs = [
            '',
            '/controllers',
            '/Controllers',
            '/library',
            '/src',
            '/models',
            '/Models',
            '/modules',
            '/Modules',
            '/settings/class.hooks.php'
        ];

        foreach ($dirs as $dir) {
            foreach ($this->scanDirPhp($dir) as $path) {
                yield $path;
            }
        }
    }

    /**
     * Recursively scan a directory for PHP files.
     *
     * @param string $dir The path to the directory to scan.
     * @return \Traversable Returns a list of paths to PHP files.
     */
    private function scanDirPhp($dir) {
        if (substr($dir, -4) === '.php') {
            if (file_exists($this->path($dir, Addon::PATH_FULL))) {
                yield $dir;
            }
            return;
        }

        // Get the php files in the directory.
        foreach ($this->glob("$dir/*.php") as $path) {
            yield $path;
        }

        // Don't recursively scan the root of an addon.
        if (empty($dir)) {
            return;
        }

        // Get all of the php files from subdirectories.
        foreach ($this->glob("$dir/*", true) as $subdir) {
            foreach ($this->scanDirPhp($subdir) as $path) {
                yield $path;
            }
        }
    }

    /**
     * Looks what classes and namespaces are defined in a file and returns them.
     *
     * @param string $path Path to file.
     * @return array Returns an empty array if no classes are found or an array with namespaces and
     * classes found in the file.
     * @see http://stackoverflow.com/a/11114724/1984219
     */
    private static function scanFile($path) {
        $classes = $nsPos = $final = [];
        $foundNamespace = false;
        $ii = 0;

        if (!file_exists($path)) {
            return [];
        }

        $er = error_reporting();
        error_reporting(E_ALL ^ E_NOTICE);

        $php_code = file_get_contents($path);
        $tokens = token_get_all($php_code);
//        $count = count($tokens);

        foreach ($tokens as $i => $token) { //} ($i = 0; $i < $count; $i++) {
            if (!$foundNamespace && $token[0] == T_NAMESPACE) {
                $nsPos[$ii]['start'] = $i;
                $foundNamespace = true;
            } elseif ($foundNamespace && ($token == ';' || $token == '{')) {
                $nsPos[$ii]['end'] = $i;
                $ii++;
                $foundNamespace = false;
            } elseif ($i - 2 >= 0 && $tokens[$i - 2][0] == T_CLASS && $tokens[$i - 1][0] == T_WHITESPACE && $token[0] == T_STRING) {
                if ($i - 4 >= 0 && $tokens[$i - 4][0] == T_ABSTRACT) {
                    $classes[$ii][] = ['name' => $token[1], 'type' => 'ABSTRACT CLASS'];
                } else {
                    $classes[$ii][] = ['name' => $token[1], 'type' => 'CLASS'];
                }
            } elseif ($i - 2 >= 0 && $tokens[$i - 2][0] == T_INTERFACE && $tokens[$i - 1][0] == T_WHITESPACE && $token[0] == T_STRING) {
                $classes[$ii][] = ['name' => $token[1], 'type' => 'INTERFACE'];
            } elseif ($i - 2 >= 0 && $tokens[$i - 2][0] == T_TRAIT && $tokens[$i - 1][0] == T_WHITESPACE && $token[0] == T_STRING) {
                $classes[$ii][] = ['name' => $token[1], 'type' => 'TRAIT'];
            }
        }
        error_reporting($er);
        if (empty($classes)) {
            return [];
        }

        if (!empty($nsPos)) {
            foreach ($nsPos as $k => $p) {
                $ns = '';
                for ($i = $p['start'] + 1; $i < $p['end']; $i++) {
                    $ns .= $tokens[$i][1];
                }

                $ns = trim($ns);
                if (!empty($classes[$k + 1])) {
                    $final[$k] = ['namespace' => $ns, 'classes' => $classes[$k + 1]];
                }
            }
            $classes = $final;
        }
        return $classes;
    }

    /**
     * Set the classes.
     *
     * @param array $classes
     * @return Addon Returns `$this` for fluent calls.
     */
    private function setClasses($classes) {
        $this->classes = $classes;
        return $this;
    }

    /**
     * Scan the addon for translation files.
     */
    private function scanTranslations() {
        $result = [];

        if ($this->getType() === static::TYPE_LOCALE) {
            // Locale files are a little different. Their translations are in the root.
            $locale = self::canonicalizeLocale($this->getInfoValue('locale', 'en'));
            $result[$locale] = $this->glob('/*.php');
        } else {
            // Look for individual locale files.
            $localePaths = $this->glob('/locale/*.php');
            foreach ($localePaths as $localePath) {
                $locale = self::canonicalizeLocale(basename($localePath, '.php'));
                $result[$locale][] = $localePath;
            }

            // Look for locale files in a directory. This scan method is deprecated, but still supported.
            $localePaths = $this->glob('/locale/*/definitions.php');
            foreach ($localePaths as $localePath) {
                $locale = self::canonicalizeLocale(basename(dirname($localePath)));
                $result[$locale][] = $localePath;

                $properPath = $this->path("/locale/$locale.php", self::PATH_ADDON);
                trigger_error("Locales in $localePath is deprecated. Use $properPath instead.", E_USER_DEPRECATED);
            }
        }

        return $result;
    }

    /**
     * Canonicalize a locale string so different representations of the same locale can be used together.
     *
     * @param string $locale The locale code to canonicalize.
     * @return string Returns the canonicalized version of the locale code.
     */
    private static function canonicalizeLocale($locale) {
        $locale = str_replace(['-', '@'], ['_', '__'], $locale);
        $parts = explode('_', $locale, 2);
        if (isset($parts[1])) {
            $parts[1] = strtoupper($parts[1]);
        }
        $result = implode('_', $parts);
        // Remove everything from the string except letters, numbers, dashes, and underscores.
        $result = preg_replace('/([^\w-])/', '', $result);

        // This is a bit of a kludge, but we are deprecating en_CA in favour of just en.
        if ($result === 'en_CA') {
            $result = 'en';
        }

        return $result;
    }

    /**
     * Get a single value from the info array.
     *
     * @param string $key The key in the info array.
     * @param mixed $default The default value to return if there is no item.
     * @return mixed Returns the info value or {@link $default}.
     */
    public function getInfoValue($key, $default = null) {
        return isset($this->info[$key]) ? $this->info[$key] : $default;
    }

    /**
     * Set the translation paths.
     *
     * @param array $translations The new translation paths.
     * @return Addon Returns `$this` for fluent calls.
     */
    private function setTranslationPaths($translations) {
        $this->translations = $translations;
        return $this;
    }

    /**
     * Check the addon for data issues.
     *
     * @param bool $trigger Whether or not to trigger a notice if there are issues.
     * @return array Returns an array of issues with the addon.
     */
    public function check($trigger = false) {
        $issues = [];
        if (!isset($this->info['Issues'])) {
            $this->info['Issues'] = &$issues;
        }


        $rawKey = $this->getKey();
        $subdir = basename($this->getSubdir());

        // Check for missing fields.
        $required = ['key', 'type'];
        foreach ($required as $fieldName) {
            if (empty($this->info[$fieldName])) {
                $issues["required-$fieldName"] = "The $fieldName info field is required.";
            }
        }

        // Make sure the addon has a correct type.
        if ($this->getType()) {
            if (!in_array($this->getType(), [static::TYPE_ADDON, static::TYPE_THEME, static::TYPE_LOCALE])) {
                $type = $this->getType();
                $issues['type-invalid'] = "The addon has an invalid type ($type).";
            } elseif (empty($this->info['priority'])) {
                // Add a missing priority.
                $priorities = [
                    static::TYPE_ADDON => static::PRIORITY_NORMAL,
                    static::TYPE_LOCALE => static::PRIORITY_LOCALE,
                    static::TYPE_THEME => static::PRIORITY_THEME
                ];
                $this->info['priority'] = $priorities[$this->getType()];
            }
        }

        // Themes and locales must have a key that matches their subdirectories.
        if ($rawKey !== $subdir
            && in_array($this->getType(), [static::TYPE_LOCALE, static::TYPE_THEME])
        ) {

            $issues['key-subdir-mismatch'] = "The addon key must match it's subdirectory name ($rawKey vs. $subdir).";
        }

        if ($this->getType() === static::TYPE_ADDON) {
            // Lowercase the keys of the other types.
            $key = strtolower($rawKey);
            if ($key !== $rawKey) {
                $this->info['key'] = $key;
                $this->info['keyRaw'] = $rawKey;
            }

            if (strcasecmp($key, basename($this->getSubdir())) !== 0) {
                $issues['key-subdir-mismatch-case'] = "The addon key must match it's subdirectory name ($key vs. $subdir).";
            }
        }

        if (preg_match('`-(addon|theme|locale)$`', $rawKey)) {
            $issues['invalid-key-suffix'] = "The addon key cannot end with -addon, -theme, or -locale.";
        }

        if (!empty($this->special['otherPlugins'])) {
            $plugins = implode(', ', array_merge([$this->special['plugin']], $this->special['otherPlugins']));
            $issues['multiple-plugins'] = "The addon should have at most one plugin class ($plugins).";
        }

        if (isset($this->info['require']) && !is_array($this->info['require'])) {
            $issues['invalid-require'] = "The require key must be an array.";
        }

        if (isset($this->info['conflict']) && !is_array($this->info['conflict'])) {
            $issues['invalid-conflict'] = "The conflict key must be an array.";
        }

        if ($trigger) {
            $this->triggerIssues();
        }

        return $issues;
    }

    /**
     * Trigger the plugin's issues
     *
     * @return Addon Returns $this for fluent calls.
     */
    protected function triggerIssues() {
        $issues = val('Issues', $this->info, []);
        if ($count = count($issues)) {
            $subdir = $this->getSubdir();

            trigger_error("The addon in $subdir has $count issue(s).", E_USER_NOTICE);
            foreach ($issues as $issue) {
                trigger_error($issue, E_USER_NOTICE);
            }
        }

        return $this;
    }

    /**
     * Get this addon's key.
     *
     * @return string Returns the key as a string.
     */
    public function getKey() {
        return empty($this->info['key']) ? '' : $this->info['key'];
    }

    /**
     * Get the global key of an addon.
     *
     * This method allows addons of all types to be keyed in a global namespace.
     *
     * Addons of type "addon" use their key as their global key. All other types have the "-<type>" suffix.
     *
     * @return string Returns a string key.
     */
    public function getGlobalKey(): string {
        if ($this->getType() === Addon::TYPE_ADDON) {
            return $this->getKey();
        } else {
            return $this->getKey().'-'.$this->getType();
        }
    }

    /**
     * Split a global key into an addon key and type.
     *
     * @param string $key They key to split.
     * @return string[2] Returns an array in the form [key, type].
     */
    public static function splitGlobalKey(string $key): array {
        if (preg_match('`^(.+)-(locale|theme)$`', $key, $m)) {
            return [$m[1], $m[2]];
        } else {
            return [$key, Addon::TYPE_ADDON];
        }
    }

    /**
     * Support {@link var_export()} for caching.
     *
     * @param array $array The array to load.
     * @return Addon Returns a new addon with the properties from {@link $array}.
     */
    public static function __set_state(array $array) {
        $array += ['subdir' => '', 'info' => [], 'classes' => [], 'translations' => []];

        $addon = new Addon();
        $addon
            ->setSubdir($array['subdir'])
            ->setInfo($array['info'])
            ->setClasses($array['classes'])
            ->setTranslationPaths($array['translations'])
            ->setSpecialArray(empty($array['special']) ? [] : $array['special'])
            ->triggerIssues();

        return $addon;
    }

    /**
     * Set the special array.
     *
     * @param array $special The new special array.
     * @return Addon Returns $this for fluent calls.
     */
    private function setSpecialArray(array $special) {
        $this->special = $special;
        return $this;
    }

    /**
     * Compare two addon's by priority so that they can be sorted.
     *
     * @param Addon $a The first addon to compare.
     * @param Addon $b The second addon to compare.
     * @return int Returns -1, 0, or 1.
     */
    public static function comparePriority(Addon $a, Addon $b) {
        if ($a->getPriority() > $b->getPriority()) {
            return -1;
        } elseif ($a->getPriority() < $b->getPriority()) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Get the priority of this addon.
     *
     * An addon's priority determines the order of things like translations, autoloading, and event firing.
     * Addons with higher priorities will generally override addons with lower priority.
     *
     * @return int Returns the priority.
     */
    public function getPriority() {
        return (int)$this->getInfoValue('priority', Addon::PRIORITY_NORMAL);
    }

    /**
     * Check a version against a version requirement.
     *
     * @param string $version The version to check.
     * @param string $requirement The version requirement.
     */
    public static function checkVersion($version, $requirement) {
        // Split the version up on operator boundaries.
        $final = self::splitRequirement($requirement);

        $valid = self::testRequirement($version, $final);
        return $valid;
    }

    /**
     * Split a requirements string into comparisons.
     *
     * @param string $requirement The requirement to split.
     * @return array
     */
    private static function splitRequirement($requirement) {
        $parts = preg_split(
            '`( - |\s*>=\s*|\s*<=\s*|\s*>\s*|\s*<\s*|\s*!=\s*|\s*,\s*|\|\|| )`',
            $requirement,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );
        $working = [];
        $j = -1;
        foreach ($parts as $i => $part) {
            if ($part !== ' ') {
                $part = trim($part);
            }

            switch ($part) {
                case '>':
                case '<':
                case '>=':
                case '<=':
                case '!=':
                    $j = count($working);
                    $working[$j] = ['op' => $part];
                    break;
                case '-':
                    // The last version can't have an operator already.
                    if (!empty($working[$j]['v']) && empty($working[$j]['op'])) {
                        $working[$j]['op'] = $part;
                    }
                    break;
                case ',':
                case ' ':
                case '||':
                    $logic = $part === '||' ? 'or' : 'and';
                    if (!empty($working[$j]['v'])) {
                        $working[$j]['logic'] = $logic;
                    }
                    break;
                default:
                    // This is a version.
                    if (isset($working[$j]['op']) && $working[$j]['op'] === '-') {
                        $working[$j]['v2'] = $part;
                    } elseif (!isset($working[$j]) || !empty($working[$j]['v'])) {
                        $j = count($working);
                        $working[$j]['v'] = $part;
                    } else {
                        $working[$j]['v'] = $part;
                    }
            }
        }

        $andGroups = [];
        $andStart = 0;
        foreach ($working as $i => $row) {
            $logic = isset($row['logic']) ? $row['logic'] : 'and';

            if ($logic === 'or') {
                // Split off the last and group.
                $andGroups[] = array_slice($working, $andStart, $i - $andStart + 1);
                $andStart = $i + 1;
            }
        }
        if ($andStart <= count($working)) {
            $andGroups[] = array_slice($working, $andStart);
        }

        if (count($andGroups) === 1) {
            $andGroup = reset($andGroups);
            if (count($andGroup) == 1) {
                $final = reset($andGroup);
            } else {
                $final = ['and', $andGroup];
            }
        } else {
            $items = [];
            foreach ($andGroups as $andGroup) {
                if (count($andGroup) === 1) {
                    $items[] = reset($andGroup);
                } else {
                    $items[] = ['and', $andGroup];
                }
            }

            $final = ['or', $items];
        }

        return $final;
    }

    /**
     * Test an individual requirement.
     *
     * Requirements are arrays in the following form:
     *
     * - `['and', [requirements]]`: All requirements mast be valid.
     * - `['or', [requirements]]`: One of the requirements must be valid.
     * - `['op' => '<comparison>', 'v' => '<version>']`: An operator and version to compare.
     * - `['op' => '-', 'v' => '<version>', 'v2' => '<version>']`: Compare a range of versions.
     *
     * @param string $version The version to test.
     * @param array $req The requirement to test.
     * @return bool Returns **true** if the test passes or **false** otherwise.
     */
    private static function testRequirement($version, $req) {
        if (isset($req[0])) {
            // This is a boolean group.
            $logic = $req[0];
            foreach ($req[1] as $part) {
                $valid = self::testRequirement($version, $part);

                if ($valid && $logic === 'or') {
                    return true;
                } elseif (!$valid && $logic === 'and') {
                    return false;
                }
            }

            return $logic === 'or' ? false : true;
        } else {
            // This is an individual requirement.
            $req += ['op' => '==', 'v' => '0.0', 'logic' => ',', 'v2' => '999999'];
            $op = $req['op'];

            if ($req['v'] === '*') {
                $valid = true;
            } elseif ($op === '-') {
                $valid = version_compare($version, $req['v'], '>=') && version_compare($version, $req['v2'], '<=');
            } else {
                $valid = version_compare($version, $req['v'], $op);
            }
            return $valid;
        }
    }

    /**
     * Return a function that can be used as a callback to filter arrays of {@link Addon} objects.
     *
     * @param array $where A where array that filters the info array.
     * @return \Closure Returns a new closure.
     */
    public static function makeFilterCallback($where) {
        return function (Addon $addon) use ($where) {
            foreach ($where as $key => $value) {
                if ($key === 'oldType') {
                    $valid = isset($addon->info['oldType']) && $addon->info['oldType'] === $value;
                } elseif ($value === null) {
                    $valid = !isset($addon->info[$key]);
                } else {
                    $valid = $addon->getInfoValue($key) == $value;
                }
                if (!$valid) {
                    return false;
                }
            }
            return true;
        };
    }

    /**
     * Get the version number of the addon.
     *
     * This is just a convenience method for grabbing the version number from the info array.
     *
     * @return string Returns a version number or an empty string if there isn't one.
     */
    public function getVersion() {
        return (string)$this->getInfoValue('version', '');
    }

    /**
     * Get this addon's human-readable name.
     *
     * @return string Returns the name of the addon or its key if it has no name.
     */
    public function getName() {
        return $this->getInfoValue('name', $this->getRawKey());
    }

    /**
     * Get this addon's raw case-sensitive key.
     *
     * Addon's have a lowercase key, but some places still require the uppercase one.
     *
     * @return string Returns the key as a string.
     */
    public function getRawKey() {
        return $this->getInfoValue('keyRaw', $this->getKey());
    }

    /**
     * Get the required addons for this addon.
     *
     * @return array Returns an array in the form addonKey => version.
     */
    public function getRequirements() {
        $result = $this->getInfoValue('require', []);
        if (!is_array($result)) {
            return [];
        }
        return $result;
    }

    /**
     * Get addons that conflict with this addon.
     *
     * @return array Returns an array in the form addonKey => version.
     */
    public function getConflicts() {
        $result = $this->getInfoValue('conflict', []);
        if (!is_array($result)) {
            return [];
        }
        return $result;
    }

    /**
     * Get the info.
     *
     * @return array Returns the info.
     */
    public function getInfo() {
        return $this->info;
    }

    /**
     * Get the classes.
     *
     * @return array Returns the classes.
     */
    public function getClasses() {
        return $this->classes;
    }

    /**
     * Do a very basic test of this addon.
     *
     * The test includes some of the files on this addon which will throw an exception if there are any major issues.
     *
     * @param bool $throw Whether or not to throw an exception.
     * @return bool Returns **true** if the addon was successfully tested or **false** otherwise.
     */
    public function test($throw = true) {
        try {
            // Include the plugin file.
            if ($className = $this->getPluginClass()) {
                $classInfo = self::parseFullyQualifiedClass($className);
                include_once $this->path($this->classes[strtolower($classInfo['className'])][0]['path']);
            }

            // Include the configuration file.
            if ($configPath = $this->getSpecial('config')) {
                include $this->path($configPath);
            }

            // Include locale files.
            foreach ($this->getTranslationPaths() as $paths) {
                foreach ($paths as $path) {
                    include $this->path($path);
                }
            }
            return true;
        } catch (\Throwable $ex) {
            // PHP 7 can trap more errors, so cast it into a PHP 5.x compatible exception.
            $ex2 = new \Exception($ex->getMessage(), $ex->getCode());
        } catch (\Exception $ex) {
            $ex2 = $ex;
        }

        if ($throw) {
            throw $ex2;
        } else {
            return false;
        }
    }

    /**
     * Get the name of the plugin class for this addon, if any.
     *
     * @return string Returns the fully qualified name of the class or an empty string if it doesn't have one.
     */
    public function getPluginClass() {
        return isset($this->special['plugin']) ? $this->special['plugin'] : '';
    }

    /**
     * Get an item from the special array.
     *
     * @param string $key The key in the special array.
     * @param mixed $default The default if the key isn't set.
     * @return mixed Returns the special item or {@link $default}.
     */
    public function getSpecial($key, $default = null) {
        return isset($this->special[$key]) ? $this->special[$key] : $default;
    }

    /**
     * Get translation paths.
     *
     * @param string $locale If passed then only the translation paths for this locale will be returned.
     * @return array Returns an array of translation paths or an array of locale codes pointing to translation paths.
     */
    public function getTranslationPaths($locale = '') {
        if (empty($locale)) {
            return $this->translations;
        } else {
            $safeLocale = self::canonicalizeLocale($locale);
            return isset($this->translations[$safeLocale]) ? $this->translations[$safeLocale] : [];
        }
    }

    /**
     * Get the path of a class within this addon.
     *
     * This is a case insensitive lookup.
     *
     * @param string $fullClassName Fully qualified class name.
     * @param string $relative One of the **Addon::PATH*** constants.
     * @return string Returns the path or an empty string of the class isn't found.
     */
    public function getClassPath($fullClassName, $relative = self::PATH_FULL) {
        $classInfo = self::parseFullyQualifiedClass($fullClassName);
        $key = strtolower($classInfo['className']);
        if (array_key_exists($key, $this->classes)) {
            foreach($this->classes[$key] as $classData) {
                if (strtolower($classInfo['namespace']) === strtolower($classData['namespace'])) {
                    $path = $this->path($classData['path'], $relative);
                    return $path;
                }
            }
        }
        return '';
    }

    /**
     * Get the path to the icon for this addon.
     *
     * @param string $relative One of the **Addon::PATH_*** constants.
     * @return string Returns the path of the icon relative to {@link $relative} or an empty string if there is no icon.
     */
    public function getIcon($relative = self::PATH_ADDON) {
        if ($icon = $this->getInfoValue('icon')) {
            return $this->path('/'.ltrim($icon, '\\/'), $relative);
        } else {
            $files = ['icon.png', 'screenshot.png', 'mobile.png'];
            foreach ($files as $file) {
                if (file_exists($this->path($file))) {
                    return $this->path($file, $relative);
                }
            }
        }
        return '';
    }

    /**
     * Parse a fully qualified class name and return the namespace and className of it.
     *
     * @param string $fullClassName Fully qualified class name.
     * @return array ['namespace' => $namespace, 'className' => $className]
     */
    public static function parseFullyQualifiedClass($fullClassName) {
        $lastNamespaceSeparatorPos = strrpos($fullClassName, '\\');
        if ($lastNamespaceSeparatorPos === false) {
            $namespace = '';
            $className = $fullClassName;
        } else {
            $namespace = substr($fullClassName, 0, $lastNamespaceSeparatorPos+1);
            $className = substr($fullClassName, $lastNamespaceSeparatorPos+1);
        }

        return [
            'namespace' => $namespace,
            'className' => $className,
        ];
    }
}
