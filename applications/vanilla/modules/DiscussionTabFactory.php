<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forums\Modules;

use Garden\Container\Reference;
use Vanilla\Forum\Modules\DiscussionWidgetModule;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\AbstractTabWidgetTabFactory;

/**
 * Tab factories for discussions.
 */
class DiscussionTabFactory extends AbstractTabWidgetTabFactory
{
    public const PRESET_RECENT_DISCUSSIONS = "recent-discussions";
    public const PRESET_TRENDING_DISCUSSIONS = "trending-discussions";
    public const PRESET_TOP_DISCUSSIONS = "top-discussions";
    public const PRESET_ANNOUNCEMENTS = "announcements";

    /** @var string */
    protected $presetID;

    /** @var string */
    protected $defaultLabel;

    /** @var array */
    protected $apiParams;

    /** @var bool */
    protected $isDefault;

    /**
     * Constructor.
     *
     * @param string $presetID
     * @param string $defaultLabel
     * @param array $apiParams
     * @param bool $isDefault
     */
    public function __construct(string $presetID, string $defaultLabel, array $apiParams, bool $isDefault = false)
    {
        $this->presetID = $presetID;
        $this->defaultLabel = $defaultLabel;
        $this->apiParams = $apiParams;
        $this->isDefault = $isDefault;
    }

    /**
     * @return string
     */
    public function getTabPresetID(): string
    {
        return $this->presetID;
    }

    /**
     * @return string
     */
    public function getWidgetClass(): string
    {
        return DiscussionWidgetModule::class;
    }

    /**
     * @return AbstractReactModule
     */
    public function getTabModule(): AbstractReactModule
    {
        /** @var DiscussionWidgetModule $module */
        $module = parent::getTabModule();
        $module->setApiParams($this->apiParams);
        return $module;
    }

    /**
     * @return string
     */
    public function getDefaultTabLabelCode(): string
    {
        return $this->defaultLabel;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    /**
     * Get a reference for a factory of recent discussion tabs.
     *
     * @return Reference
     */
    public static function getRecentReference(): Reference
    {
        return new Reference(static::class, [
            self::PRESET_RECENT_DISCUSSIONS,
            "Recent Discussions",
            [
                "excludeHiddenCategories" => true,
                "sort" => "-dateLastComment",
                "pinOrder" => "mixed",
            ],
            true,
        ]);
    }

    /**
     * Get a reference for a factory of trending discussion tabs.
     *
     * @return Reference
     */
    public static function getTrendingReference(): Reference
    {
        return new Reference(static::class, [
            self::PRESET_TRENDING_DISCUSSIONS,
            "Trending Discussions",
            [
                "slotType" => "w",
                "sort" => "-hot",
                "pinOrder" => "mixed",
            ],
            true,
        ]);
    }

    /**
     * Get a reference for a factory of top discussion tabs.
     *
     * @return Reference
     */
    public static function getTopReference(): Reference
    {
        return new Reference(static::class, [
            self::PRESET_TOP_DISCUSSIONS,
            "Top Discussions",
            [
                "slotType" => "m",
                "sort" => "-score",
                "pinOrder" => "mixed",
            ],
        ]);
    }

    /**
     * Get a reference for a factory of announced discussion tabs.
     *
     * @return Reference
     */
    public static function getAnnouncedReference(): Reference
    {
        return new Reference(static::class, [
            self::PRESET_ANNOUNCEMENTS,
            "Announced Discussions",
            [
                "pinned" => true,
            ],
            true,
        ]);
    }
}
