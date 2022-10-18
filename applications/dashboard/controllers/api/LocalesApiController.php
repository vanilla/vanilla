<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\Permissions;
use Vanilla\Web\ApiFilterMiddleware;
use Vanilla\Web\Controller;

/**
 * Endpoint for getting translations.
 */
class LocalesApiController extends Controller
{
    const GET_ALL_REDUX_KEY = "@@locales/GET_ALL_DONE";

    /** @var \Gdn_Locale */
    private $locale;

    /** @var LocaleModel */
    private $localeModel;

    /**
     * LocalesApiController constructor.
     *
     * @param \Gdn_Locale $locale
     * @param LocaleModel $localeModel
     */
    public function __construct(\Gdn_Locale $locale, LocaleModel $localeModel)
    {
        $this->locale = $locale;
        $this->localeModel = $localeModel;
    }

    /**
     * Get all enabled locales for the site.
     *
     * @return array
     */
    public function index(): array
    {
        $this->permission(Permissions::BAN_PRIVATE);
        $schema = $this->schema($this->localeSchema(), ["LocaleConfig", "out"]);
        $out = $this->schema([":a" => $schema], "out");
        $enabled = $this->getEnabledLocales();
        $this->expandDisplayNames($enabled, array_column($enabled, "localeKey"));
        $locales = $this->getEventManager()->fireFilter("localesApiController_getOutput", $enabled, false);
        $locales = $out->validate($locales);

        return $locales;
    }

    /**
     * @return Schema
     */
    private function localeSchema()
    {
        return Schema::parse(["localeID:s", "localeKey:s", "regionalKey:s", "displayNames:o"]);
    }

    /**
     * Get all enabled locales of the site.
     *
     * @return array[]
     */
    private function getEnabledLocales(): array
    {
        $locales = $this->localeModel->enabledLocalePacks(true);
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

    /**
     * Expand display names for the locales.
     *
     * @param array $rows
     * @param array $locales
     */
    public function expandDisplayNames(array &$rows, array $locales)
    {
        if (count($rows) === 0) {
            return;
        }
        reset($rows);
        $single = is_string(key($rows));

        $populate = function (array &$row, array $locales) {
            $displayNames = [];
            foreach ($locales as $locale) {
                $displayName = \Locale::getDisplayLanguage($row["localeKey"], $locale);
                $displayNameRegion = \Locale::getDisplayRegion($row["localeKey"], $locale);
                $displayName = empty($displayNameRegion) ? $displayName : $displayName . " ($displayNameRegion)";
                // Standardize capitalization
                $displayName = mb_convert_case($displayName, MB_CASE_TITLE);

                // If I set the translation key
                // localeOverrides.zh.* = Override in all other langauges
                // localeOverrides.zh.zh_TW = Override in one specific language.
                $wildCardOverrideKey = "localeOverrides.{$row["localeKey"]}.*";
                $specificOverrideKey = "localeOverrides.{$row["localeKey"]}.$locale";
                $displayName = c($specificOverrideKey, c($wildCardOverrideKey, $displayName));

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
     * Get a single locale.
     *
     * @param string $id The locale to get.
     * @return Data
     * @throws \Garden\Web\Exception\HttpException Exception.
     * @throws \Vanilla\Exception\PermissionException Exception.
     */
    public function get(string $id): Data
    {
        $this->permission("Garden.Settings.Manage");

        $out = $this->schema($this->localeSchema(), ["LocaleConfig", "out"]);

        $allLocales = $this->getEnabledLocales();
        $this->checkLocaleExists($id, $allLocales);
        $locale = array_column($allLocales, null, "localeID")[$id];
        $this->expandDisplayNames($locale, array_column($allLocales, "localeKey"));

        $locale = $this->getEventManager()->fireFilter("localesApiController_getOutput", $locale, true);
        $locale = \Vanilla\ApiUtils::convertOutputKeys($locale);
        $out->validate($locale);
        return new Data($locale);
    }

    /**
     * Patch a single locale.
     *
     * @param string $id The locale to patch.
     * @param array $body The fields and values to patch.
     * @return Data
     * @throws \Garden\Schema\ValidationException Exception.
     * @throws \Garden\Web\Exception\HttpException Exception.
     * @throws \Vanilla\Exception\PermissionException Exception.
     */
    public function patch(string $id, array $body): Data
    {
        $this->permission("Garden.Settings.Manage");
        $in = $this->schema(["type" => "object"], ["LocaleConfigPatch", "in"]);
        $out = $this->schema($this->localeSchema(), ["LocaleConfig", "out"]);
        $body = $in->validate($body);

        // Validate the locale exists.
        $this->checkLocaleExists($id);

        $this->getEventManager()->fire("localesApiController_patchData", $id, $body, $in);

        $result = $this->get($id);
        $validatedResult = $out->validate($result);

        return new Data($validatedResult);
    }

    /**
     * Get the translations for a locale.
     *
     * @param string $locale The locale slug.
     * @param array $query Query string parameters.
     * @return Data Returns the translations.
     */
    public function index_translations(string $locale, array $query = [])
    {
        // Allowed even for geusts in a private community.
        // The locale is required to show the login page.
        $this->permission(Permissions::BAN_PRIVATE);

        $this->locale->set($locale);

        // Don't bother validating the translations since they are a free-form array.
        $translations = (array) $this->locale->getDefinitions();

        if (empty($translations)) {
            $translations = (object) [];
        }
        $r = new Data($translations);
        $r->setHeader("Cache-Control", "public, max-age=1800");
        // Translations may include these keywords.
        $r->setMeta(ApiFilterMiddleware::FIELD_ALLOW, ["password", "email", "insertipaddress", "updateipaddress"]);
        return $r;
    }

    /**
     * Get the translations for a locale in javascript.
     *
     * @param string $locale The locale slug.
     * @param array $query Query string parameters.
     * @return Data Returns the translations javascript.
     */
    public function index_translations_js(string $locale, array $query = [])
    {
        $this->permission(Permissions::BAN_PRIVATE);
        $translations = $this->index_translations($locale, $query);

        $js =
            "gdn.translations = " .
            json_encode($translations, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE) .
            ";";
        $translations->setData($js)->setHeader("Content-Type", "application/javascript; charset=utf-8");

        return $translations;
    }

    /**
     * Validator for locale field
     *
     * @param string $locale
     * @param \Garden\Schema\ValidationField $validationField
     * @return bool
     */
    public function validateLocale(string $locale, \Garden\Schema\ValidationField $validationField): bool
    {
        $locales = $this->getEnabledLocales();
        foreach ($locales as $localePack) {
            if (
                $localePack["localeID"] === $locale ||
                $localePack["localeKey"] === $locale ||
                $localePack["regionalKey"] === $locale
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check that the given ID corresponds to an enabled locale.
     *
     * @param string $id
     * @param array|null $enabledLocales
     * @throws \Garden\Web\Exception\NotFoundException Throws an exception if the locale isn't found.
     */
    private function checkLocaleExists(string $id, ?array $enabledLocales = null): void
    {
        if (is_null($enabledLocales)) {
            $enabledLocales = $this->getEnabledLocales();
        }

        if (!in_array($id, array_keys(array_column($enabledLocales, null, "localeID")))) {
            throw new \Garden\Web\Exception\NotFoundException("Locale");
        }
    }
}
