<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\Web\Controller;

/**
 * Endpoint for getting translations.
 */
class LocalesApiController extends Controller {

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
    public function __construct(\Gdn_Locale $locale, LocaleModel $localeModel) {
        $this->locale = $locale;
        $this->localeModel = $localeModel;
    }

    /**
     * Get all enabled locales for the site.
     *
     * @return array
     */
    public function index(): array {
        $this->permission();
        $out = $this->schema([":a" => $this->localeSchema()], 'out');
        $enabled = $this->getEnabledLocales();
        $this->expandDisplayNames($enabled, array_column($enabled, 'localeKey'));
        $locales = $out->validate($enabled);

        return $locales;
    }

    /**
     * @return Schema
     */
    private function localeSchema() {
        return Schema::parse([
            'localeID:s',
            'localeKey:s',
            'regionalKey:s',
            'displayNames:o',
        ]);
    }

    /**
     * Get all enabled locales of the site.
     *
     * @return array[]
     */
    private function getEnabledLocales(): array {
        $locales = [];
        $locales += $this->localeModel->enabledLocalePacks(true);
        $result = [[
            'localeID' => 'en',
            'localeKey' => 'en',
            'regionalKey' => 'en',
        ]];
        foreach ($locales as $localeID => $locale) {
            $result[] = [
                'localeID' => $localeID,
                'localeKey' => substr($locale['Locale'], 0, 2),
                'regionalKey' => $locale['Locale'],
            ];
        }

        return $result;
    }

    /**
     * Expand display names for the locales.
     *
     * @param array $rows
     * @param array $locales
     */
    public function expandDisplayNames(array &$rows, array $locales) {
        if (count($rows) === 0) {
            return;
        }
        reset($rows);
        $single = is_string(key($rows));

        $populate = function (array &$row, array $locales) {
            $displayNames = [];
            foreach ($locales as $locale) {
                $displayName = \Locale::getDisplayLanguage($row["localeKey"], $locale);

                // Standardize capitalization
                $displayName = mb_convert_case($displayName, MB_CASE_TITLE);

                $displayNames[$locale] = $displayName;
            }
            $row['displayNames'] = $displayNames;
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
     * Get the translations for a locale.
     *
     * @param string $locale The locale slug.
     * @param array $query Query string parameters.
     * @return Data Returns the translations.
     */
    public function index_translations(string $locale, array $query = []) {
        $this->permission();

        $in = $this->schema([
            'etag:s?' => 'Whether or not output is cached.'
        ], 'in');
        $out = $this->schema([':o'], 'out');

        $query = $in->validate($query);

        $this->locale->set($locale);

        // Don't bother validating the translations since they are a free-form array.
        $translations = (array)$this->locale->getDefinitions();

        if (empty($translations)) {
            $translations = (object)[];
        }
        $r = new Data($translations);
        if (!empty($query['etag'])) {
            $r->setHeader('Cache-Control', 'public, max-age=604800');
        }

        return $r;
    }

    /**
     * Get the translations for a locale in javascript.
     *
     * @param string $locale The locale slug.
     * @param array $query Query string parameters.
     * @return Data Returns the translations javascript.
     */
    public function index_translations_js(string $locale, array $query = []) {
        $translations = $this->index_translations($locale, $query);

        $js = 'gdn.translations = '.json_encode($translations, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE).';';
        $translations
            ->setData($js)
            ->setHeader('Content-Type', 'application/javascript; charset=utf-8');

        return $translations;
    }
}
