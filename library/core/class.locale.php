<?php
/**
 * Gdn_Locale.
 *
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */
use Vanilla\AddonManager;

/**
 * The Locale class is used to load, define, change, and render translations
 * for different locales. It is a singleton class.
 */
class Gdn_Locale extends Gdn_Pluggable {

    /**  @var string The name of the currently loaded Locale. */
    public $Locale = '';

    /** @var Gdn_Configuration Holds all locale sources. */
    public $LocaleContainer = null;

    /** @var boolean Whether or not to record core translations. */
    public $DeveloperMode = false;

    /** @var Gdn_Configuration Core translations, and untranslated codes. */
    public $DeveloperContainer = null;

    /**
     * @var AddonManager
     */
    private $addonManager = null;

    /** @var array  */
    public static $SetLocales = [
        'ar' => 'ar_SA',
        'az' => 'az_AZ',
        'bg' => 'bg_BG',
        'bs' => 'bs_BA',
        'ca' => 'ca_ES',
        'cs' => 'cs_CZ',
        'da' => 'da_DK',
        'de' => 'de_DE',
        'el' => 'el_GR',
        'en' => 'en_US',
        'es' => 'es_ES',
        'fa' => 'fa_IR',
        'fi' => 'fi_FI',
        'fr' => 'fr_FR',
        'gd' => 'gd_GB',
        'gu' => 'gu_IN',
        'he' => 'he_IL',
        'hi' => 'hi_IN',
        'hr' => 'hr_HR',
        'hu' => 'hu_HU',
        'id' => 'id_ID',
        'it' => 'it_IT',
        'ja' => 'ja_JP',
        'km' => 'km_KH',
        'ko' => 'ko_KR',
        'lt' => 'lt_LT',
        'my' => 'my_MM',
        'nb' => 'nb_NO',
        'nl' => 'nl_NL',
        'no' => ['no_NO', 'nn_NO'],
        'pl' => 'pl_PL',
        'pt' => 'pt_BR',
        'ro' => 'ro_RO',
        'ru' => 'ru_RU',
        'sk' => 'sk_SK',
        'sl' => 'sl_SI',
        'sr' => 'sr_RS',
        'sv' => 'sv_SE',
        'th' => 'th_TH',
        'tl' => 'tl_PH',
        'tr' => 'tr_TR',
        'uk' => 'uk_UA',
        'ur' => 'ur_PL',
        'vi' => 'vi_VN',
        'zh' => 'zh_CN'
    ];

    /** @var int  */
    public $SavedDeveloperCalls = 0;

    /**
     * Setup the default locale.
     *
     * @param $localeName
     * @param $ApplicationWhiteList
     * @param $PluginWhiteList
     * @param bool $ForceRemapping
     */
    public function __construct($localeName, AddonManager $addonManager = null) {
        parent::__construct();
        $this->ClassName = __CLASS__;

        if ($addonManager instanceof AddonManager) {
            $this->addonManager = $addonManager;
        }

        $this->set($localeName);
    }

    /**
     * Canonicalize a locale string so different representations of the same locale can be used together.
     *
     * Example:
     *
     *     echo Gdn_Locale::canonicalize('en-us');
     *     // prints en_US
     *
     * @param string $locale The locale code to canonicalize.
     * @return string Returns the canonicalized version of the locale code.
     */
    public static function canonicalize($locale) {
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
     * Reload the locale and its translations.
     */
    public function refresh() {
        $locale = $this->current();
        $this->set($locale);
    }

    /**
     *
     *
     * @param $translations
     * @param bool $localeName
     * @throws Exception
     */
    public function saveTranslations($translations, $localeName = false) {
        $this->LocaleContainer->save();
    }

    /**
     * Defines and loads the locale.
     *
     * Locale definitions are kept in each addon's locale folder. For example:
     *
     * ```
     * /dashboard/locale/$localeName.php
     * /vanilla/locale/$localeName.php
     * ```
     *
     * @param string $localeName The name of the locale to load.
     */
    public function set($localeName) {
        $currentLocale = self::canonicalize($localeName);

        // Get locale sources
        $this->Locale = $currentLocale;
        if ($this->addonManager !== null) {
            $localeSources = $this->addonManager->getEnabledTranslationPaths($currentLocale);
        } else {
            $localeSources = [];
        }

        $codeset = c('Garden.LocaleCodeset', 'UTF8');

        $setLocale = [
            LC_TIME,
            "$currentLocale.$codeset",
            $currentLocale
        ];

        list($language) = explode('_', $currentLocale, 2);
        if (isset(self::$SetLocales[$language])) {
            $fullLocales = (array)self::$SetLocales[$language];

            foreach ($fullLocales as $fullLocale) {
                $setLocale[] = "$fullLocale.$codeset";
                $setLocale[] = $fullLocale;
            }
        }

        $r = call_user_func_array('setlocale', $setLocale);

        if (!is_array($localeSources)) {
            $localeSources = [];
        }

        // Create a locale config container
        $this->unload();

        $confLocaleOverride = PATH_CONF.'/locale.php';
        foreach ($localeSources as $localeSource) {
            if ($confLocaleOverride != $localeSource && file_exists($localeSource)) { // Don't double include the conf override file... and make sure it comes last
                $this->load($localeSource, false);
            }
        }

        // Also load any custom defined definitions from the conf directory
        if (file_exists($confLocaleOverride)) {
            $this->load($confLocaleOverride, true);
        }

        // Prepare developer mode if needed
        $this->DeveloperMode = c('Garden.Locales.DeveloperMode', false);
        if ($this->DeveloperMode) {
            $this->DeveloperContainer = new Gdn_Configuration();
            $this->DeveloperContainer->splitting(false);
            $this->DeveloperContainer->caching(false);

            $developerCodeFile = PATH_CACHE."/locale-developer-{$localeName}.php";
            if (!file_exists($developerCodeFile)) {
                touch($developerCodeFile);
            }

            $this->DeveloperContainer->load($developerCodeFile, 'Definition', true);
        }

        // Import core (static) translations
        if ($this->DeveloperMode) {
            $this->DeveloperContainer->massImport($this->LocaleContainer->get('.'));
        }

        // Allow hooking custom definitions
        $this->fireEvent('AfterSet');
    }

    /**
     * Crawl the various addons and locales for all of the applicable translation files.
     *
     * @return array Returns an array keyed by locale names where each value is an array of translation paths for that locale.
     * @deprecated This methods was added to help debug locale canonicalization so should be able to be removed.
     */
    public function crawlAllLocaleSources() {
        deprecated('Gdn_Locale->crawlAllLocaleSources()');

        $addons = array_reverse($this->getEnabled(), true);

        $result = [];
        /* @var \Vanilla\Addon $addon */
        foreach ($addons as $addon) {
            foreach ($addon->getTranslationPaths() as $locale => $paths) {
                foreach ($paths as $path) {
                    $result[$locale][] = $addon->path($path);
                }
                $result[] = $addon->path($path);
            }
        }
        return $result;
    }

    /**
     * Gets the locale sources for a given locale.
     *
     * @param string $locale The name of the locale.
     * @return array Returns an array of paths to the translations for the locale.
     * @deprecated Use the {@link AddonManager} for this.
     */
    public function getLocaleSources($locale) {
        deprecated('Gdn_PluginManager->getLocaleSources()', 'AddonManager->getEnabledLocaleSources()');
        $result = $this->addonManager->getEnabledTranslationPaths($locale);
        return $result;
    }

    /**
     * Return the first 2 letters of the current locale (the language code).
     *
     * @param bool $iso6391 Attempt to use ISO 639-1 language codes.
     * @return bool|string Language code on success, false on failure.
     */
    public function language($iso6391 = false) {
        if ($this->Locale == '') {
            return false;
        } else {
            if ($iso6391) {
                // ISO 639-1 has some special exceptions for language codes.
                $locale = strtolower($this->Locale);
                switch ($locale) {
                    case 'zh_tw':
                        return 'zh-Hant';
                }
            }

            return substr($this->Locale, 0, 2);
        }
    }

    /**
     * Load a locale definition file.
     *
     * @param string $path The path to the locale.
     * @param boolean $dynamic Whether this locale file should be the dynamic one.
     */
    public function load($path, $dynamic = false) {
        $this->LocaleContainer->load($path, 'Definition', $dynamic);
    }

    /**
     * Assigns a translation code.
     *
     * These DO NOT PERSIST.
     *
     * @param mixed $code The code to provide a translation for, or an array of code => translation
     * values to be set.
     * @param string $translation The definition associated with the specified code. If $code is an array
     *  of definitions, this value will not be used.
     */
    public function setTranslation($code, $translation = '', $save = false) {
        if (!is_array($code)) {
            $code = [$code => $translation];
        }

        $this->LocaleContainer->saveToConfig($code, null, $save);
    }

    /**
     * Translates a code into the selected locale's definition.
     *
     * @param string $code The code related to the language-specific definition.
     * Codes that begin with an '@' symbol are treated as literals and not translated.
     * @param string $default The default value to be displayed if the translation code is not found.
     * @return string
     */
    public function translate($code, $default = false) {
        if ($default === false) {
            $default = $code;
        }

        // Codes that begin with @ are considered literals.
        if (substr_compare('@', $code, 0, 1) == 0) {
            return substr($code, 1);
        }

        $translation = $this->LocaleContainer->get($code, $default);

        // If developer mode is on, and this translation returned the default value,
        // remember it and save it to the developer locale.
        if ($this->DeveloperMode && $translation == $default) {
            $devKnows = $this->DeveloperContainer->get($code, false);
            if ($devKnows === false) {
                $this->DeveloperContainer->saveToConfig($code, $default);
            }
        }

        $this->EventArguments['Code'] = $code;
        $this->EventArguments['Default'] = $default;
        $this->fireEvent('BeforeTranslate');

        return $translation;
    }

    /**
     *  Clears out the currently loaded locale settings.
     */
    public function unload() {
        // If we're unloading, don't save first
        if ($this->LocaleContainer instanceof Gdn_Configuration) {
            $this->LocaleContainer->autoSave(false);
        }

        $this->LocaleContainer = new Gdn_Configuration();
        $this->LocaleContainer->splitting(false);
        $this->LocaleContainer->caching(false);
    }

    /**
     * Returns the name of the currently loaded locale.
     *
     * @return string
     */
    public function current() {
        if ($this->Locale == '') {
            return false;
        } else {
            return $this->Locale;
        }
    }

    /**
     * Get all definitions from the loaded locale.
     */
    public function getDefinitions() {
        return $this->LocaleContainer->get('.');
    }

    /**
     * Get all known core.
     */
    public function getDeveloperDefinitions() {
        if (!$this->DeveloperMode) {
            return false;
        }

        return $this->DeveloperContainer->get('.');
    }
}
