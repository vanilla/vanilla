<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Vanilla\Logging\BasicAuditLogEvent;
use Vanilla\Theme\Theme;

/**
 * Audit Event for when a theme is applied.
 */
class ThemeApplyEvent extends BasicAuditLogEvent
{
    /**
     * @param Theme $theme
     */
    public function __construct(Theme $theme)
    {
        parent::__construct([
            "themeName" => $theme->getName(),
            "themeID" => $theme->getThemeID(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function eventType(): string
    {
        return "theme_apply";
    }

    /**
     * @inheritDoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        $themeName = $context["themeName"];
        return "Styleguide `{$themeName}` was applied to the site.";
    }
}
