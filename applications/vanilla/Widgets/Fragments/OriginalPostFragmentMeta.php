<?php
/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Widgets\Fragments;

use Garden\Schema\Schema;
use Vanilla\Forum\Widgets\OriginalPostAsset;
use Vanilla\Widgets\React\FragmentMeta;

class OriginalPostFragmentMeta extends FragmentMeta
{
    /**
     * @inheritdoc
     */
    public static function getFragmentType(): string
    {
        return "OriginalPostFragment";
    }

    public static function getName(): string
    {
        return "Original Post";
    }

    /**
     * @inheritdoc
     */
    public function getPropSchema(): Schema
    {
        return OriginalPostAsset::getWidgetSchema()
            ->setField("properties.titleType", null)
            ->setField("properties.descriptionType", null);
    }
}
