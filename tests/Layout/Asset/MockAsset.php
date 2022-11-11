<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Layout\Asset;

use Garden\Schema\Schema;

class MockAsset extends \Vanilla\Layout\Asset\AbstractLayoutAsset
{
    /**
     * @inheritDoc
     */
    public static function getComponentName(): string
    {
        return "MockComponentWidget";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetIconPath(): ?string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema
    {
        return Schema::parse([
            "recordType:s?" => "The record type of the record to build breadcrumbs for.",
            "recordID:i?" => "The record type of the record to build breadcrumbs for.",
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "MockComponentStuff";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string
    {
        return "asset.mockComponent";
    }

    public function getProps(): ?array
    {
        $recordType = $this->props["recordType"] ?? null;
        $recordID = $this->props["recordID"] ?? null;
        if ($recordType === null || $recordID === null) {
            return null;
        }

        $mockProps = [
            "recordType" => $recordType,
            "recordID" => $recordID,
        ];

        return [
            "mockProps" => $mockProps,
        ];
    }
}
