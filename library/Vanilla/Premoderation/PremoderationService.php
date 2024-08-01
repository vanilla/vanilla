<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Premoderation;

use Vanilla\Logging\ErrorLogger;

/**
 * Service to handle registration of {@link PremoderationHandlerInterface} instances.
 */
final class PremoderationService
{
    /** @var array<string, PremoderationHandlerInterface> */
    private array $handlers = [];

    public function registerHandler(PremoderationHandlerInterface $handler): void
    {
        $this->handlers[get_class($handler)] = $handler;
    }

    /**
     * Remove all registered handlers.
     *
     * @return void
     */
    public function clearHandlers(): void
    {
        $this->handlers = [];
    }

    /**
     * Premoderate an item and get a combined result of all handlers.
     *
     * @param PremoderationItem $item
     *
     * @return PremoderationResult
     */
    public function premoderateItem(PremoderationItem $item): PremoderationResult
    {
        $results = [];
        foreach ($this->handlers as $handler) {
            try {
                $results[] = $handler->premoderateItem($item);
            } catch (\Throwable $e) {
                // If one of these throws, don't block the post, just log it and allow it through.
                ErrorLogger::error($e, ["premoderation", get_class($handler)]);
            }
        }
        return new PremoderationResult($results);
    }
}
