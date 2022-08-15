<?php
/**
 * @author Gary Pomerant <gpomerant@higerlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Resolvers;

use Garden\Hydrate\Resolvers\AbstractDataResolver;
use Garden\Schema\Schema;
use Gdn;

/**
 * Resolver for hydrating API responses.
 */
class TranslateResolver extends AbstractDataResolver
{
    public const TYPE = "translate";

    /** @var \GDN_Locale $locale */
    public $locale;

    /**
     * DI.
     *
     * @param \GDN_Locale $locale
     */
    public function __construct(\Gdn_Locale $locale)
    {
        $this->locale = $locale;
    }

    /**
     * Determine locale to apply.
     *
     * Note: no data, just hydrate,source & code
     * return just transplate string
     *
     * @param array $data
     * @param array $params
     * @return string
     */
    protected function resolveInternal(array $data, array $params): string
    {
        $code = $data["code"] ?? false;
        $default = $data["default"] ?? false;
        $localeSite = $this->locale->language();
        $locale = $params["locale"] ?? ($data["locale"] ?? $this->locale->Locale);

        // only set() locale IF we are not already set to the correct locate
        if ($localeSite != $locale) {
            $this->locale->set($locale);
        }
        $translatedString = $this->locale->translate($code, $default);
        return $translatedString;
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * @inheritdoc
     */
    public function getSchema(): ?Schema
    {
        $schema = Schema::parse([
            "code:s" => "The translation source string. If no default is specified, this will be used as the default.",
            "default:s?" => "A default value to use if there is no translation found for the source string.",
            "locale:s?" => "Use a specific locale for the translation. By default it will the pages locale.",
        ]);
        return $schema;
    }
}
