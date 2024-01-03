<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Data;
use Vanilla\Navigation\Breadcrumb;

/**
 * JSON-LD item to represent breadcrumbs.
 */
final class BreadcrumbJsonLD extends AbstractJsonLDItem
{
    /** @var array<Breadcrumb|array{name: string, url: string}> */
    private $crumbData;

    /**
     * Constructor.
     * @param array<Breadcrumb|array{name: string, url: string}> $crumbData
     */
    public function __construct(array $crumbData)
    {
        $this->crumbData = $crumbData;
    }

    /**
     * Convert an array of breadcrumbs into JSON-LD.
     */
    public function calculateValue(): Data
    {
        $crumbList = [];
        foreach ($this->crumbData as $index => $crumb) {
            if ($crumb instanceof Breadcrumb) {
                $crumb = $crumb->asArray();
            }

            $crumbList[] = [
                "@type" => "ListItem",
                "position" => $index,
                "name" => $crumb["name"],
                "item" => $crumb["url"],
            ];
        }

        return new Data([
            "@context" => "http://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => $crumbList,
        ]);
    }
}
