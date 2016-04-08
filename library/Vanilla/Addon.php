<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
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

    const PRIORITY_LOW = 100;
    const PRIORITY_NORMAL = 1000;
    const PRIORITY_HIGH = 10000;

    const PRIORITY_THEME = Addon::PRIORITY_HIGH;
    const PRIORITY_PLUGIN = Addon::PRIORITY_NORMAL;
    const PRIORITY_LOCALE = 110;
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
        }

        // Scan for translations.
        $translations = $this->scanTranslations();
        $this->setTranslations($translations);

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
            $info = json_decode(file_get_contents("$dir/addon.json"), true);

            if (empty($info)) {
                throw new \Exception("The addon at $subdir has an empty info array.");
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
     * @return string Returns a full path.
     */
    public function path($subpath = '') {
        return PATH_ROOT.$this->subdir.$subpath;
    }

    /**
     * Perform a glob from this addon's subdirectory.
     *
     * @param string $pattern The pattern to glob.
     * @return array Returns an array of root-relative paths.
     * @see glob()
     */
    private function glob($pattern) {
        $px = $this->path();
        $fullPattern = $px.$pattern;
        $strlen = strlen($px);
        $paths = glob($fullPattern, GLOB_NOSORT);
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
            eval($infoString);
        } else {
            return null;
        }

        // See which info array is defined.
        if (!empty($PluginInfo) && is_array($PluginInfo)) {
            $array = $PluginInfo;
            $type = static::TYPE_ADDON;
            $priority = static::PRIORITY_PLUGIN;
        } elseif (!empty($ApplicationInfo) && is_array($ApplicationInfo)) {
            $array = $ApplicationInfo;
            $type = static::TYPE_ADDON;
            $priority = static::PRIORITY_APPLICATION;
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

        $info['key'] = $key;
        $info['type'] = $type;
        if (empty($info['priority'])) {
            $info['priority'] = $priority;
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
        $require = $this->convertRequire($info, ['requiredPlugins', 'requiredApplications']);
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
        $paths = $this->getClassPaths();

        $classes = [];
        foreach ($paths as $path) {
            $declarations = static::scanFile($this->path($path));
            foreach ($declarations as $namespaceRow) {
                if (isset($namespaceRow['namespace']) && $namespaceRow) {
                    $namespace = rtrim($namespaceRow['namespace'], '\\').'\\';
                    $namespaceClasses = $namespaceRow['classes'];
                } else {
                    $namespace = '';
                    $namespaceClasses = $namespaceRow;
                }

                foreach ($namespaceClasses as $classRow) {
                    $className = $namespace.$classRow['name'];
                    $classes[strtolower($className)] = [$className, $path];

                    // Check to see if the class is a plugin or a hook.
                    if (strcasecmp(substr($className, -6), 'plugin') === 0
                        || strcasecmp(substr($className, -5), 'hooks') === 0
                    ) {

                        if (empty($this->special['plugin'])) {
                            $this->special['plugin'] = $className;
                        } else {
                            $this->special['otherPlugins'][] = $className;
                        }
                    }
                }
            }
        }
        return $classes;
    }

    private function getClassPaths() {
        $globs = [
            '/*.php',
            '/controllers/*.php',
            '/library/*.php',
            '/models/*.php',
            '/modules/*.php',
            '/settings/class.hooks.php'
        ];

        $result = [];
        foreach ($globs as $glob) {
            $paths = $this->glob($glob);
            $result = array_merge($result, $paths);
        }

        return $result;
    }

    /**
     * Looks what classes and namespaces are defined in a file and returns them.
     *
     * @param string $path Path to file.
     * @return array Returns an empty array if no classes are found or an array with namespaces and
     * classes found in the file.
     * @see http://stackoverflow.com/a/11114724/1984219
     */
    public static function scanFile($path) {
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
                    $classes[$ii][] = array('name' => $token[1], 'type' => 'ABSTRACT CLASS');
                } else {
                    $classes[$ii][] = array('name' => $token[1], 'type' => 'CLASS');
                }
            } elseif ($i - 2 >= 0 && $tokens[$i - 2][0] == T_INTERFACE && $tokens[$i - 1][0] == T_WHITESPACE && $token[0] == T_STRING) {
                $classes[$ii][] = array('name' => $token[1], 'type' => 'INTERFACE');
            } elseif ($i - 2 >= 0 && $tokens[$i - 2][0] == T_TRAIT && $tokens[$i - 1][0] == T_WHITESPACE && $token[0] == T_STRING) {
                $classes[$ii][] = array('name' => $token[1], 'type' => 'TRAIT');
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
                $final[$k] = array('namespace' => $ns, 'classes' => $classes[$k + 1]);
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

                $properPath = "/locale/$locale.php";
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
        $locale = str_replace(array('-', '@'), array('_', '__'), $locale);
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
     * Set the translations.
     *
     * @param array $translations
     * @return Addon Returns `$this` for fluent calls.
     */
    private function setTranslations($translations) {
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

        if (!empty($this->special['otherPlugins'])) {
            $plugins = implode(', ', array_merge([$this->special['plugin']], $this->special['otherPlugins']));
            $issues['multiple-plugins'] = "The addon should have at most one plugin class ($plugins).";
        }

        if ($trigger && $count = count($issues)) {
            $subdir = $this->getSubdir();

            trigger_error("The addon in $subdir has $count issues.", E_USER_NOTICE);
            foreach ($issues as $issue) {
                trigger_error($issue, E_USER_NOTICE);
            }
        }

        return $issues;
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
            ->setTranslations($array['translations'])
            ->setSpecial(empty($array['special']) ? [] : $array['special']);

        return $addon;
    }

    /**
     * Set the special array.
     *
     * @param array $special The new special array.
     * @return Addon Returns $this for fluent calls.
     */
    private function setSpecial(array $special) {
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
     * Get the info.
     *
     * @return array Returns the info.
     */
    public function getInfo() {
        return $this->info;
    }

    /**
     * Get the name of the plugin class for this addon, if any.
     *
     * @return string Returns the name of the class or an empty string if it doesn't have one.
     */
    public function getPluginClass() {
        return isset($this->special['plugin']) ? $this->special['plugin'] : '';
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
     * Get translation paths.
     *
     * @param string $locale If passed then only the translation paths for this locale will be returned.
     * @return array Returns an array of translation paths or an array of locale codes pointing to translation paths.
     */
    public function getTranslations($locale = '') {
        if (empty($locale)) {
            return $this->translations;
        } else {
            return isset($this->translations[$locale]) ? $this->translations[$locale] : [];
        }
    }
}
