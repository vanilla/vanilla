<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Vanilla\Logging\BasicAuditLogEvent;
use Vanilla\Logging\LoggerUtils;
use Vanilla\Utility\ArrayUtils;

/**
 * Event for when the site configuration is changed.
 */
class ConfigurationChangeEvent extends BasicAuditLogEvent
{
    /**
     * @param $oldConfig
     * @param $newConfig
     */
    public function __construct(protected $oldConfig, protected $newConfig)
    {
        $modifications = LoggerUtils::diffArrays($this->oldConfig, $this->newConfig);

        parent::__construct(["modifications" => $modifications]);
    }

    /**
     * @inheritDoc
     */
    public static function eventType(): string
    {
        return "configuration_change";
    }

    /**
     * @inheritDoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "Site configuration was modified.";
    }
}
