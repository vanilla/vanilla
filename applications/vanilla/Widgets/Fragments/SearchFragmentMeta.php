<?php
/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Widgets\Fragments;

use Garden\Schema\Schema;
use Vanilla\Forum\Widgets\SearchWidget;
use Vanilla\Widgets\React\BannerFullWidget;
use Vanilla\Widgets\React\FragmentMeta;

class SearchFragmentMeta extends FragmentMeta
{
    /**
     * @inheritdoc
     */
    public static function getFragmentType(): string
    {
        return "SearchFragment";
    }

    public static function getName(): string
    {
        return "Search";
    }

    /**
     * @inheritdoc
     */
    public function getPropSchema(): Schema
    {
        return SearchWidget::getWidgetSchema()
            ->setField("properties.titleType", null)
            ->setField("properties.descriptionType", null);
    }
}
