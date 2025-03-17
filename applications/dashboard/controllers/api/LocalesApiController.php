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

    /**
     * LocalesApiController constructor.
     *
     * @param \Gdn_Locale $locale
     * @param LocaleModel $localeModel
     */
    public function __construct(
        private \Gdn_Locale $locale,
        private LocaleModel $localeModel,
        private Gdn_Configuration $config
    ) {
    }

    /**
     * Get all enabled locales for the site.
     *
     * @param array $body
     *
     * @return array
     */
    public function index(array $query = []): array
    {
        $this->permission(Permissions::BAN_PRIVATE);

        $in = $this->schema(["isMachineTranslations:b?"]);
        $body = $in->validate($query);

        $schema = $this->schema($this->localeSchema(), ["LocaleConfig", "out"]);
        $out = $this->schema([":a" => $schema], "out");

        $locales = [];
        if ($body["isMachineTranslations"] ?? false) {
            if ($this->config->get("Locales.migrated", false)) {
                $locales = $this->localeModel->getMachineLocales();
            }
        } else {
            $enabled = $this->localeModel->getEnabledLocales();
            $this->localeModel->expandDisplayNames($enabled, array_column($enabled, "localeKey"));
            $dbLocales = $this->getEventManager()->fireFilter("localesApiController_getOutput", $enabled, false);
            // Get Entries saved in config.
            $Locales = $this->localeModel->getLanguageSetting();
            $localeIDs = array_column($Locales, "localeID");
            // Combine the locales from the database with the locales from the config.
            $locales = array_merge(
                $dbLocales,
                array_filter($Locales, function ($Locales) use ($localeIDs) {
                    return !in_array($Locales["localeID"], $localeIDs);
                })
            );
            $locales = array_values($locales);
        }

        $locales = $out->validate($locales);

        return $locales;
    }

    /**
     * @return Schema
     */
    private function localeSchema()
    {
        return Schema::parse([
            "localeID:s",
            "localeKey:s",
            "regionalKey:s",
            "displayNames:o",
            "machineTranslationService:b?",
            "translatable:b?",
        ]);
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
        $locale = $this->localeModel->getLocale($id);

        if (count($locale) === 0) {
            $allLocales = $this->localeModel->getEnabledLocales();
            $this->checkLocaleExists($id, $allLocales);
            $locale = array_column($allLocales, null, "localeID")[$id];
            $this->localeModel->expandDisplayNames($locale, array_column($allLocales, "localeKey"));

            $locale = $this->getEventManager()->fireFilter("localesApiController_getOutput", $locale, true);
            $locale = \Vanilla\ApiUtils::convertOutputKeys($locale);
        }
        if (count($locale) === 0) {
            throw new \Garden\Web\Exception\NotFoundException("Locale");
        }
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
     * Post a single locale.
     *
     * @param array $body The fields and values to patch.
     * @return Data
     * @throws \Garden\Schema\ValidationException Exception.
     * @throws \Garden\Web\Exception\HttpException Exception.
     * @throws \Vanilla\Exception\PermissionException Exception.
     */
    public function post(array $body): Data
    {
        $this->permission("Garden.Settings.Manage");
        $in = $this->schema(["locale:s", "translatable:b"]);
        $out = $this->schema($this->localeSchema(), ["LocaleConfig", "out"]);
        $body = $in->validate($body);
        $translatable = $body["translatable"] ?? false;

        // Validate the locale exists.
        $result = $this->localeModel->updateAddTranslatableLocale($body["locale"], $translatable);
        $validatedResult = $out->validate($result);

        return new Data($validatedResult);
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
        $locales = $this->localeModel->getEnabledLocales();
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
            $enabledLocales = $this->localeModel->getEnabledLocales();
        }

        if (!in_array($id, array_keys(array_column($enabledLocales, null, "localeID")))) {
            throw new \Garden\Web\Exception\NotFoundException("Locale");
        }
    }

    /**
     * Check if a locale is valid.
     *
     * @param string $locale
     * @return bool
     */
    public function isValidLocale(string $locale): bool
    {
        return $this->localeModel->isEnabled($locale);
    }
}
