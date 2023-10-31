<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Formatting\Html\HtmlDocument;

/**
 * Model for getting sanitized meta values from the `seo.metaHtml` config.
 */
class SeoMetaModel
{
    private ConfigurationInterface $config;

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     */
    public function __construct(ConfigurationInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Get a list of meta properties configured for the site.
     *
     * @return array<array{name: string, property: string, content: string}>
     */
    public function getMetas(): array
    {
        $configValue = $this->config->get("seo.metaHtml", "");
        if (!is_string($configValue) || empty($configValue)) {
            return [];
        }

        // Parse this for meta tags.
        $htmlDocument = new HtmlDocument($configValue);
        $metaTags = $htmlDocument->queryCssSelector("meta");

        $validProperties = ["name", "content", "property"];

        $result = [];
        /**
         * @var \DOMElement $metaTag
         */
        foreach ($metaTags as $metaTag) {
            $metaItem = [];
            foreach ($validProperties as $validProperty) {
                $propertyValue = $metaTag->getAttribute($validProperty);
                if (!empty($propertyValue)) {
                    $metaItem[$validProperty] = $propertyValue;
                }
            }

            if (!empty($metaItem)) {
                $result[] = $metaItem;
            }
        }

        return $result;
    }
}
