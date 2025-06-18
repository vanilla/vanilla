<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\Fragments;

use Garden\Schema\Schema;
use Vanilla\Widgets\React\FragmentMeta;
use Vanilla\Widgets\TitleBarWidget;

/**
 * Fragment metadata for titlebar.
 */
class TitleBarFragmentMeta extends FragmentMeta
{
    /**
     * @inheritdoc
     */
    public static function getFragmentType(): string
    {
        return "TitleBarFragment";
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return "Title Bar";
    }

    /**
     * @inheritdoc
     */
    public static function isAvailableInStyleguide(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getPropSchema(): Schema
    {
        return TitleBarWidget::getWidgetSchema(forFragment: true);
    }
}
