<?php
/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Widgets\Fragments;

use Garden\Schema\Schema;
use Vanilla\Widgets\React\BannerFullWidget;
use Vanilla\Widgets\React\FragmentMeta;

class BannerFragmentMeta extends FragmentMeta
{
    /**
     * @inheritdoc
     */
    public static function getFragmentType(): string
    {
        return "BannerFragment";
    }

    public static function getName(): string
    {
        return "Banner";
    }

    /**
     * @inheritdoc
     */
    public function getPropSchema(): Schema
    {
        return BannerFullWidget::getWidgetSchema()
            ->setField("properties.titleType", null)
            ->setField("properties.descriptionType", null);
    }
}
