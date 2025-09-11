<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Widgets\Fragments\PostItemFragmentMeta;
use Vanilla\Widgets\TabWidgetModule;
use Vanilla\Widgets\TabWidgetTabService;

/**
 * Class TabsWidget
 */
class TabsWidget extends TabWidgetModule implements HydrateAwareInterface
{
    use HydrateAwareTrait;

    public function __construct(TabWidgetTabService $tabService, SiteSectionModel $siteSectionModel)
    {
        parent::__construct($tabService, $siteSectionModel);
        $this->addChildComponentName("DiscussionListModule");
    }

    /**
     * @inheritdoc
     */
    public static function getFragmentClasses(): array
    {
        return [PostItemFragmentMeta::class];
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "tabs";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Tabbed Posts";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetGroup(): string
    {
        return "Community";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/tabs.svg";
    }
}
