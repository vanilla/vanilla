<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\Fragments;

use Garden\Schema\Schema;
use Vanilla\Forum\Widgets\CallToActionWidget;
use Vanilla\Widgets\React\FragmentMeta;

class CallToActionFragmentMeta extends FragmentMeta
{
    /**
     * @inheritdoc
     */
    public static function getFragmentType(): string
    {
        return "CallToActionFragment";
    }

    public static function getName(): string
    {
        return "Call to Action";
    }

    /**
     * @inheritdoc
     */
    public function getPropSchema(): Schema
    {
        return CallToActionWidget::getWidgetSchema()
            ->setField("properties.titleType", null)
            ->setField("properties.descriptionType", null);
    }
}
