<?php
/**
 * Gdn_Locale.
 *
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
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
    public static $SetLocales = array(
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
        'no' => array('no_NO', 'nn_NO'),
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
    );

    /** @var int  */
    public $SavedDeveloperCalls = 0;

    /**
     * Setup the default locale.
     *
     * @param $LocaleName
     * @param $ApplicationWhiteList
     * @param $PluginWhiteList
     * @param bool $ForceRemapping
     */
    public function __construct($LocaleName, $addonManager = null) {
        parent::__construct();
        $this->ClassName = __CLASS__;

        if ($addonManager instanceof AddonManager) {
            $this->addonManager = $addonManager;
        }

        $this->set($LocaleName);
    }

    /**
     * Canonicalize a locale string so different representations of the same locale can be used together.
     *
     * Example:
     *
     *     echo Gdn_Locale::Canonicalize('en-us');
     *     // prints en_US
     *
     * @param string $locale The locale code to canonicalize.
     * @return string Returns the canonicalized version of the locale code.
     */
    public static function canonicalize($locale) {
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
     * Reload the locale and its translations.
     */
    public function refresh() {
        $locale = $this->current();
        $this->set($locale);
    }

    /**
     *
     *
     * @param $Translations
     * @param bool $LocaleName
     * @throws Exception
     */
    public function saveTranslations($Translations, $LocaleName = false) {
        $this->LocaleContainer->save();
    }

    /**
     * Defines and loads the locale.
     *
     * Locale definitions are kept in each addon's locale folder. For example:
     *
     * ```
     * /dashboard/locale/$LocaleName.php
     * /vanilla/locale/$LocaleName.php
     * ```
     *
     * @param string $LocaleName The name of the locale to load.
     */
    public function set($LocaleName) {
        $CurrentLocale = self::canonicalize($LocaleName);

        // Get locale sources
        $this->Locale = $CurrentLocale;
        if ($this->addonManager !== null) {
            $LocaleSources = $this->addonManager->getEnabledTranslationPaths($CurrentLocale);
        } else {
            $LocaleSources = [];
        }

        $Codeset = c('Garden.LocaleCodeset', 'UTF8');

        $SetLocale = array(
            LC_TIME,
            "$CurrentLocale.$Codeset",
            $CurrentLocale
        );

        list($Language) = explode('_', $CurrentLocale, 2);
        if (isset(self::$SetLocales[$Language])) {
            $FullLocales = (array)self::$SetLocales[$Language];

            foreach ($FullLocales as $FullLocale) {
                $SetLocale[] = "$FullLocale.$Codeset";
                $SetLocale[] = $FullLocale;
            }
        }

        $r = call_user_func_array('setlocale', $SetLocale);

        if (!is_array($LocaleSources)) {
            $LocaleSources = array();
        }

        // Create a locale config container
        $this->unload();

        $ConfLocaleOverride = PATH_CONF.'/locale.php';
        foreach ($LocaleSources as $localeSource) {
            if ($ConfLocaleOverride != $localeSource && file_exists($localeSource)) { // Don't double include the conf override file... and make sure it comes last
                $this->load($localeSource, false);
            }
        }

        // Also load any custom defined definitions from the conf directory
        if (file_exists($ConfLocaleOverride)) {
            $this->load($ConfLocaleOverride, true);
        }

        // Prepare developer mode if needed
        $this->DeveloperMode = c('Garden.Locales.DeveloperMode', false);
        if ($this->DeveloperMode) {
            $this->DeveloperContainer = new Gdn_Configuration();
            $this->DeveloperContainer->splitting(false);
            $this->DeveloperContainer->caching(false);

            $DeveloperCodeFile = PATH_CACHE."/locale-developer-{$LocaleName}.php";
            if (!file_exists($DeveloperCodeFile)) {
                touch($DeveloperCodeFile);
            }

            $this->DeveloperContainer->load($DeveloperCodeFile, 'Definition', true);
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
     * @param string $Path The path to the locale.
     * @param boolean $Dynamic Whether this locale file should be the dynamic one.
     */
    public function load($Path, $Dynamic = false) {
        $this->LocaleContainer->load($Path, 'Definition', $Dynamic);
    }

    /**
     * Assigns a translation code.
     *
     * These DO NOT PERSIST.
     *
     * @param mixed $Code The code to provide a translation for, or an array of code => translation
     * values to be set.
     * @param string $Translation The definition associated with the specified code. If $Code is an array
     *  of definitions, this value will not be used.
     */
    public function setTranslation($Code, $Translation = '', $Save = false) {
        if (!is_array($Code)) {
            $Code = array($Code => $Translation);
        }

        $this->LocaleContainer->saveToConfig($Code, null, $Save);
    }

    /**
     * Translates a code into the selected locale's definition.
     *
     * @param string $Code The code related to the language-specific definition.
     * Codes that begin with an '@' symbol are treated as literals and not translated.
     * @param string $Default The default value to be displayed if the translation code is not found.
     * @return string
     */
    public function translate($Code, $Default = false) {
        if ($Default === false) {
            $Default = $Code;
        }

        // Codes that begin with @ are considered literals.
        if (substr_compare('@', $Code, 0, 1) == 0) {
            return substr($Code, 1);
        }

        $Translation = $this->LocaleContainer->get($Code, $Default);

        // If developer mode is on, and this translation returned the default value,
        // remember it and save it to the developer locale.
        if ($this->DeveloperMode && $Translation == $Default) {
            $DevKnows = $this->DeveloperContainer->get($Code, false);
            if ($DevKnows === false) {
                $this->DeveloperContainer->saveToConfig($Code, $Default);
            }
        }

        return $Translation;
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
     * @return boolean
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
        return $this->LocaleContainer->Get('.');
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
