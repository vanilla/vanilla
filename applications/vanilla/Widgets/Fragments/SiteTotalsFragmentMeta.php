<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\Fragments;

use Garden\Schema\Schema;
use Vanilla\Forum\Widgets\SiteTotalsWidget;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\React\FragmentMeta;

class SiteTotalsFragmentMeta extends FragmentMeta
{
    public static function getFragmentType(): string
    {
        return "SiteTotalsFragment";
    }

    public static function getName(): string
    {
        return "Site Totals";
    }

    public function getPropSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            Schema::parse([
                "totals:a" => Schema::parse([
                    "recordType:s",
                    "label:s",
                    "iconName:s",
                    "count:i",
                    "isCalculating:b",
                    "isFiltered:b",
                ]),
            ]),
            SiteTotalsWidget::widgetContainerSchema(),
            SiteTotalswidget::formatNumbersSchema()
        );
    }
}
