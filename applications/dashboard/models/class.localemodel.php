<?php
/**
 * Locale model.
 *
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Utility\ArrayUtils;

/**
 * Used to manage adding/removing different locale files.
 */
class LocaleModel extends FullRecordCacheModel
{
    //region Properties
    private const TABLE_NAME = "locale";

    const LOCALE_PREFERENCE_KEY = "NotificationLanguage";

    /** @var array|null Locales in the system.  */
    protected $_AvailableLocalePacks = null;

    /** @var Gdn_Cache */
    private \Gdn_Cache $cache;

    /** @var string */
    public string $defaultLocale = "";

    const FIELD_MAPPINGS = ["translationService" => "kbTranslationService", "displayNames" => "displayName"];

    /**
     * @param Gdn_Configuration $config
     * @param Gdn_Cache $cache
     */
    public function __construct(private Gdn_Configuration $config, \Gdn_Cache $cache)
    {
        $this->cache = $cache;
        parent::__construct(self::TABLE_NAME, $cache);
        $this->addPipelineProcessor(new CurrentDateFieldProcessor(["dateInserted", "dateUpdated"], ["dateUpdated"]));
        $userProcessor = new CurrentUserFieldProcessor(\Gdn::session());
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
        $this->defaultLocale = Gdn::config("Garden.Locale", "en");
    }

    /**
     * @return void
     */
    public function invalidateCache()
    {
        $this->modelCache->invalidateAll();
    }

    /**
     * Create the locals table.
     *
     * @param Gdn_Database $database
     * @return void
     */
    public static function structure(Gdn_Database $database, bool $explicit = false, bool $drop = false): void
    {
        $database
            ->structure()
            ->table("locale")
            ->column("localeID", "varchar(10)")
            ->column("localeKey", "varchar(10)")
            ->column("regionalKey", "varchar(10)")
            ->column("displayName", "Text")
            ->column("kbTranslationService", "varchar(255)")
            ->column("translatable", "tinyint(4)")
            ->column("machineTranslationService", "tinyint", 0)
            ->column("dateInserted", "datetime")
            ->column("insertUserID", "int")
            ->column("dateUpdated", "datetime", true)
            ->column("updateUserID", "int", true)
            ->set($explicit, $drop);
        if ($database->structure()->tableExists("locale")) {
            $locales = LocaleModel::getEnabledLocales();
            $defaultLocale = Gdn::config("Garden.Locale", "en");
            foreach ($locales as $locale) {
                $hasLocale = $database->sql()->getWhere("locale", ["localeID" => $locale["localeID"]]);
                if ($hasLocale->numRows() == 0) {
                    $displayName = \Locale::getDisplayLanguage($locale["localeKey"], $defaultLocale);
                    $displayNameRegion = \Locale::getDisplayRegion($locale["localeKey"], $defaultLocale);
                    $displayName = empty($displayNameRegion) ? $displayName : $displayName . " ($displayNameRegion)";
                    // Standardize capitalization
                    $displayName = mb_convert_case($displayName, MB_CASE_TITLE);
                    $translationService = Gdn::config("Locales.{$locale["localeID"]}.TranslationService", "none");

                    $database->sql()->insert("locale", [
                        "localeID" => $locale["localeID"],
                        "localeKey" => $locale["localeKey"],
                        "regionalKey" => $locale["regionalKey"],
                        "displayName" => $displayName,
                        "kbTranslationService" => $translationService,
                        "translatable" => 0,
                        "machineTranslationService" => 0,
                        "dateInserted" => Gdn_Format::toDateTime(),
                        "insertUserID" => Gdn::session()->UserID,
                    ]);
                }
            }
            Gdn::config()->saveToConfig("Locales.migrated", true);
        }
    }

    /**
     * Get All machine Locales from the DB.
     *
     * @return array
     */
    public function getMachineLocales(): array
    {
        $result = $this->normalizeOutput($this->select(["translatable" => 1]));

        return $result;
    }

    /**
     * Get Language Settings Locales from the DB.
     *
     * @return array
     */
    public function getLanguageSetting(): array
    {
        $result = (array) $this->config->get("EnabledLocales", []);
        if ($result) {
            $localIDs = array_keys($result);
            $result = $this->normalizeOutput($this->select(["localeID" => $localIDs]));
        }

        return $result;
    }

    /**
     * Normalize locale output.
     *
     * @param array $locales An array of locales to normalize.
     * @return array
     */
    public function normalizeOutput(array $locales): array
    {
        $normalizedLocales = [];

        foreach ($locales as $locale) {
            $normalized = \Vanilla\Models\LegacyModelUtils::normalizeApiOutput($locale, self::FIELD_MAPPINGS);
            $normalized["displayNames"] = json_decode($locale["displayName"], true);

            $normalizedLocales[] = $normalized;
        }
        LocaleModel::expandDisplayNames($normalizedLocales, array_column($normalizedLocales, "localeKey"));

        return $normalizedLocales;
    }

    /**
     * get Locale from DB.
     *
     * @param string $localeID
     *
     * @return array
     */
    public function getLocale(string $localeID): array
    {
        $localeData = $this->normalizeOutput($this->select(["localeID" => $localeID]));

        return count($localeData) > 0 ? $localeData[0] : [];
    }

    /**
     * @inheridoc
     */
    public function select(array $where = [], array $options = []): array
    {
        if (\Gdn::structure()->tableExists("locale")) {
            return parent::select($where, $options);
        }
        return [];
    }

    /**
     * Update or Add Locale in the DB for machine translation.
     *
     * @param string $localeID
     * @param bool $translatable True if the locale is translatable by machine translation.
     *
     * @return array
     */
    public function updateAddTranslatableLocale(string $localeID, bool $translatable): array
    {
        $locale = $this->getLocale($localeID);
        if ($locale === [] && $translatable) {
            $displayName = \Locale::getDisplayLanguage($localeID, $this->defaultLocale);
            $displayNameRegion = \Locale::getDisplayRegion($localeID, $this->defaultLocale);
            $displayName = empty($displayNameRegion) ? $displayName : $displayName . " ($displayNameRegion)";
            // Standardize capitalization
            $displayName = mb_convert_case($displayName, MB_CASE_TITLE);
            $this->insert([
                "localeID" => "vf-" . $localeID,
                "localeKey" => $localeID,
                "regionalKey" => $localeID,
                "displayName" => $displayName,
                "kbTranslationService" => "none",
                "translatable" => $translatable,
                "machineTranslationService" => 1,
                "dateInserted" => Gdn_Format::toDateTime(),
                "insertUserID" => Gdn::session()->UserID,
            ]);
        } elseif ($locale !== []) {
            $this->update(["translatable" => $translatable], ["localeID" => "vf-" . $localeID]);
        }
        $localeData = $this->normalizeOutput($this->select(["localeID" => "vf-" . $localeID]))[0];

        return $localeData;
    }

    /**
     * Inserting Locate entry.
     *
     * @param string $locale
     * @return array
     */
    public function enableLocale(string $locale): array
    {
        $addonManager = \Gdn::addonManager();
        $key = "vf_" . $locale;
        $addon = $addonManager->lookupLocale($key);
        if ($addon !== null) {
            $localeInfo = $addon->getInfo();
            $localeInfo = ArrayUtils::pascalCase($localeInfo);
            LocaleModel::calculateLocaleInfo($localeInfo);
            $this->insert([
                "localeID" => $key,
                "localeKey" => $localeInfo["Locale"],
                "regionalKey" => $localeInfo["Locale"],
                "displayName" => $localeInfo["EnName"],
                "kbTranslationService" => "",
                "translatable" => 0,
                "dateInserted" => Gdn_Format::toDateTime(),
                "insertUserID" => Gdn::session()->UserID,
            ]);
            $return = $this->getLocale($key);
        }
        return [];
    }

    /**
     *
     *
     * @return array|null
     */
    public function availableLocalePacks()
    {
        if ($this->_AvailableLocalePacks === null) {
            $localeInfoPaths = safeGlob(PATH_ROOT . "/locales/*/definitions.php");
            $availableLocales = [];
            foreach ($localeInfoPaths as $infoPath) {
                $localeInfo = Gdn::pluginManager()->scanPluginFile($infoPath, "LocaleInfo");
                $this->calculateLocaleInfo($localeInfo);

                if ($icon = val("Icon", $localeInfo)) {
                    $localeInfo["IconUrl"] = "/locales/" . basename(dirname($infoPath)) . "/$icon";
                } else {
                    $localeInfo["IconUrl"] = "/applications/dashboard/design/images/addon-placeholder.png";
                }

                if ($enName = val("EnName", $localeInfo)) {
                    $localeInfo["meta"][] = $enName;
                }

                $availableLocales[$localeInfo["Index"]] = $localeInfo;
            }
            $this->_AvailableLocalePacks = $availableLocales;
        }
        return $this->_AvailableLocalePacks;
    }

    /**
     *
     *
     * @return array
     */
    public function availableLocales()
    {
        // Get the list of locales that are supported.
        $locales = array_column($this->availableLocalePacks(), "Locale", "Locale");
        $locales["en"] = "en"; // the default locale is always available.
        ksort($locales);

        return $locales;
    }

    /**
     *
     *
     * @param $info
     */
    protected static function calculateLocaleInfo(&$info)
    {
        $canonicalLocale = Gdn_Locale::canonicalize($info["Locale"]);
        if ($canonicalLocale !== $info["Locale"]) {
            $info["LocaleRaw"] = $info["Locale"];
            $info["Locale"] = $canonicalLocale;
        }
    }

    /**
     *
     *
     * @param $sourcePath
     * @param $destPath
     * @return mixed
     * @throws Exception
     */
    public function copyDefinitions($sourcePath, $destPath)
    {
        // Load the definitions from the source path.
        $definitions = $this->loadDefinitions($sourcePath);

        $tmpPath = dirname($destPath) . "/tmp_" . randomString(10);
        $key = trim(strchr($sourcePath, "/"), "/");

        $fp = fopen($tmpPath, "wb");
        if (!$fp) {
            throw new Exception(sprintf(t("Could not open %s."), $tmpPath));
        }

        fwrite($fp, $this->getFileHeader());
        fwrite($fp, "/** Definitions copied from $key. **/\n\n");
        $this->writeDefinitions($fp, $definitions);
        fclose($fp);

        $result = rename($tmpPath, $destPath);
        if (!$result) {
            throw new Exception(sprintf(t("Could not open %s."), $destPath));
        }
        return $destPath;
    }

    /**
     *
     *
     * @param bool $getInfo
     * @return array
     */
    public static function enabledLocalePacks($getInfo = false)
    {
        $result = (array) Gdn::config("EnabledLocales", []);
        $translationDebug = Gdn::config("TranslationDebug");
        if ($getInfo) {
            $addonManager = \Gdn::addonManager();
            foreach ($result as $key => $locale) {
                $addon = $addonManager->lookupLocale("vf_" . $locale);
                if ($addon !== null) {
                    $localeInfo = $addon->getInfo();
                    $localeInfo = ArrayUtils::pascalCase($localeInfo);
                    LocaleModel::calculateLocaleInfo($localeInfo);
                    $result[$key] = $localeInfo;
                } else {
                    unset($result[$key]);
                }
                if ($localeInfo["Debug"] ?? null) {
                    if (!$translationDebug) {
                        unset($result[$key]);
                    }
                }
            }
        }
        return $result;
    }

    /**
     *
     *
     * @param $Path
     * @param null $Skip
     * @return array
     */
    public function loadDefinitions($Path, $Skip = null)
    {
        $Skip = (array) $Skip;

        $Paths = safeGlob($Path . "/*.php");
        $Definition = [];
        foreach ($Paths as $Path) {
            if (in_array($Path, $Skip)) {
                continue;
            }
            include $Path;
        }
        return $Definition;
    }

    /**
     *
     *
     * @param $path
     * @param $basePath
     * @param null $destPath
     * @return null|string
     * @throws Exception
     */
    public function generateChanges($path, $basePath, $destPath = null)
    {
        if ($destPath == null) {
            $destPath = $basePath . "/changes.php";
        }

        // Load the given locale pack.
        $definitions = $this->loadDefinitions($path, $destPath);
        $baseDefinitions = $this->loadDefinitions($basePath, $destPath);

        // Figure out the missing definitions.
        $missingDefinitions = array_diff_key($baseDefinitions, $definitions);

        // Figure out the extraneous definitions.
        $extraDefinitions = array_diff($definitions, $baseDefinitions);

        // Generate the changes file.
        $tmpPath = dirname($basePath) . "/tmp_" . randomString(10);
        $fp = fopen($tmpPath, "wb");
        if (!$fp) {
            throw new Exception(sprintf(t("Could not open %s."), $tmpPath));
        }

        $key = trim(strchr($path, "/"), "/");
        $baseKey = trim(strchr($basePath, "/"), "/");

        fwrite($fp, $this->getFileHeader());
        fwrite($fp, "/** Changes file comparing $key to $baseKey. **/\n\n\n");

        fwrite($fp, "/** Missing definitions that are in the $baseKey, but not $key. **/\n");
        $this->writeDefinitions($fp, $missingDefinitions);

        fwrite($fp, "\n\n/** Extra definitions that are in the $key, but not the $baseKey. **/\n");
        $this->writeDefinitions($fp, $extraDefinitions);

        fclose($fp);

        $result = rename($tmpPath, $destPath);
        if (!$result) {
            throw new Exception(sprintf(t("Could not open %s."), $destPath));
        }
        return $destPath;
    }

    /**
     * @return string
     */
    protected function getFileHeader()
    {
        $now = Gdn_Format::toDateTime();

        $result = "<?php if (!defined('APPLICATION')) exit();
/** This file was generated by the LocaleModel on $now **/\n\n";

        return $result;
    }

    /**
     * Temporarily enable a locale pack without installing it/
     *
     * @param string $localeKey The key of the folder.
     */
    public function testLocale($localeKey)
    {
        $available = $this->availableLocalePacks();
        if (!isset($available[$localeKey])) {
            throw notFoundException("Locale");
        }

        // Grab all of the definition files from the locale.
        $paths = safeGlob(PATH_ROOT . "/locales/{$localeKey}/*.php");

        // Unload the dynamic config
        Gdn::locale()->unload();

        // Load each locale file, checking for errors
        foreach ($paths as $path) {
            Gdn::locale()->load($path, false);
        }
    }

    /**
     * Write a locale's definitions to a file.
     *
     * @param resource $fp The file to write to.
     * @param array $definitions The definitions to write.
     */
    public static function writeDefinitions($fp, $definitions)
    {
        // Write the definitions.
        uksort($definitions, "strcasecmp");
        $lastC = "";
        foreach ($definitions as $key => $value) {
            // Add a blank line between letters of the alphabet.
            if (isset($key[0]) && strcasecmp($lastC, $key[0]) != 0) {
                fwrite($fp, "\n");
                $lastC = $key[0];
            }

            $str = '$Definition[' . var_export($key, true) . "] = " . var_export($value, true) . ";\n";
            fwrite($fp, $str);
        }
    }

    /**
     * Check if the site has multi locales enabled
     *
     * @return bool
     */
    public function hasMultiLocales(): bool
    {
        return (bool) count($this->enabledLocalePacks());
    }

    /**
     * @return array
     */
    public static function getRTLLocales(): array
    {
        return ["ar", "fa", "he", "ku", "ps", "sd", "ug", "ur", "yi"];
    }

    /**
     * Check if the current locale is an enabled locale
     *
     * @param string $selectedLocale
     * @return bool
     */
    public function isEnabled(string $selectedLocale): bool
    {
        $enabledLocales = $this->enabledLocalePacks();
        if (!in_array("en", $enabledLocales)) {
            $enabledLocales["en"] = "en";
        }
        return in_array($selectedLocale, $enabledLocales);
    }

    /**
     * Get the locale prefered by the user.
     *
     * @return string|null
     */
    public static function getUserPreferedLocale(): ?string
    {
        $locale = Gdn::session()->getPreference(self::LOCALE_PREFERENCE_KEY, null);
        return $locale;
    }

    /**
     * Get translatable locales.
     *
     * We currently only support GPT for Community Machine Translation.
     *
     * @param bool $includeSiteLocale
     * @return array
     */
    public static function getTranslatableLocales(bool $includeSiteLocale = true): array
    {
        $locales = Gdn::config()->get("MachineTranslation.translationServices.Gpt.locales", []);

        if ($includeSiteLocale) {
            $locales[] = Gdn::locale()->getSiteLocale();
        }
        return $locales;
    }

    /**
     * Check if a language is translatable.
     *
     * @param string $locale
     * @return bool
     */
    public function isTranslatableLocale(string $locale): bool
    {
        $locales = $this->getTranslatableLocales();
        return in_array($locale, $locales);
    }

    /**
     * Expand display names for the locales.
     *
     * @param array $rows
     * @param array $locales
     */
    public static function expandDisplayNames(array &$rows, array $locales)
    {
        if (count($rows) === 0) {
            return;
        }
        reset($rows);
        $single = is_string(key($rows));
        $locales[] = Gdn::config("Garden.Locale", "en");
        $populate = function (array &$row, array $locales) {
            $displayNames = [];
            foreach ($locales as $locale) {
                $displayName = \Locale::getDisplayLanguage($row["localeKey"], $locale);
                $displayNameRegion = \Locale::getDisplayRegion($row["localeKey"], $locale);
                $displayName = empty($displayNameRegion) ? $displayName : $displayName . " ($displayNameRegion)";
                // Standardize capitalization
                $displayName = mb_convert_case($displayName, MB_CASE_TITLE);

                // If I set the translation key
                // localeOverrides.zh.* = Override in all other languages
                // localeOverrides.zh.zh_TW = Override in one specific language.
                $wildCardOverrideKey = "localeOverrides.{$row["localeKey"]}.*";
                $specificOverrideKey = "localeOverrides.{$row["localeKey"]}.$locale";
                $displayName = Gdn::config($specificOverrideKey, c($wildCardOverrideKey, $displayName));

                $displayNames[$locale] = $displayName;
            }
            $row["displayNames"] = $displayNames;
        };

        if ($single) {
            $populate($rows, $locales);
        } else {
            foreach ($rows as &$row) {
                $populate($row, $locales);
            }
        }
    }

    /**
     * Get all enabled locales of the site.
     *
     * @return array[]
     */
    public static function getEnabledLocales(): array
    {
        $locales = LocaleModel::enabledLocalePacks(true);
        $hasEnLocale = false;
        $result = [];
        foreach ($locales as $localeID => $locale) {
            $localeItem = [
                "localeID" => $localeID,
                "localeKey" => $locale["Locale"],
                "regionalKey" => $locale["Locale"],
            ];

            if ($localeItem["localeKey"] === "en") {
                $hasEnLocale = true;
            }
            $result[] = $localeItem;
        }

        if (!$hasEnLocale) {
            $result = array_merge(
                [
                    [
                        "localeID" => "en",
                        "localeKey" => "en",
                        "regionalKey" => "en",
                    ],
                ],
                $result
            );
        }

        return $result;
    }
}
