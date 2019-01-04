<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Container\Container;
use Garden\Web\Data;
use Vanilla\Web\Controller;

/**
 * Endpoint for getting translations.
 */
class LocalesApiController extends Controller {
    /**
     * @var \Gdn_Locale
     */
    private $locale;

    /**
     * LocalesApiController constructor.
     *
     * @param \Gdn_Locale $locale
     */
    public function __construct(\Gdn_Locale $locale) {
        $this->locale = $locale;
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
