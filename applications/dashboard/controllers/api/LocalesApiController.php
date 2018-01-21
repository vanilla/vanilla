<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
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
    public function get_translations($locale, array $query = []) {
        $this->permission();

        $in = $this->schema([
            'js:b?' => 'Whether or not to serve as javascript.'
        ], 'in');
        $out = $this->schema([':o'], 'out');

        $query = $in->validate($query);

        $this->locale->set($locale);

        // Don't bother validating the translations since they are a free-form array.
        $translations = (array)$this->locale->getDefinitions();

        if (!empty($query['js'])) {
            return $this->dumpJavascript($translations);
        }

        if (empty($translations)) {
            $translations = (object)[];
        }
        return new Data($translations);
    }

    private function dumpJavascript(array $translations) {
        $js = 'gdn.translations = '.json_encode($translations, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE).';';
        $r = new Data($js, ['CONTENT_TYPE' => 'application/javascript; charset=utf-8']);
        return $r;
    }
}
