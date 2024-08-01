<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Garden\Events\ResourceEvent;
use Psr\Log\LogLevel;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggerUtils;
use Vanilla\Theme\Theme;

/**
 * Event fired when a theme is created, updated, or deleted.
 */
class ThemeEvent extends ResourceEvent implements LoggableEventInterface
{
    protected Theme $theme;

    public function __construct(string $action, Theme $theme)
    {
        $this->theme = $theme;
        parent::__construct($action, ["theme" => $theme->jsonSerialize()]);
    }

    public function getLogEntry(): LogEntry
    {
        $themeName = $this->theme->getName();
        $message = match ($this->getAction()) {
            ResourceEvent::ACTION_INSERT => "Styleguide `{$themeName}` was created.",
            ResourceEvent::ACTION_UPDATE => "Styleguide `{$themeName}` was updated.",
            ResourceEvent::ACTION_DELETE => "Styleguide `{$themeName}` was deleted.",
            default => LoggerUtils::resourceEventLogMessage($this),
        };

        $context = LoggerUtils::resourceEventLogContext($this);
        $context["themeID"] = $this->theme->getThemeID();
        $logEntry = new LogEntry(LogLevel::INFO, $message, $context);
        return $logEntry;
    }
}
