<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Garden\Web\Exception\ClientException;

/**
 * Class RecordTypeConverter
 *
 * @package Vanilla
 */
class DiscussionTypeConverter
{
    /**
     * Types that can't be converted
     * by DiscussionTypeConverter
     */
    const RESTRICTED_TYPES = ["Report", "Redirect"];

    /** @var AbstractTypeHandler[] */
    private $typeHandlers = [];

    /**
     * Add type handlers.
     *
     * @param string $type
     */
    public function addTypeHandler($type = "")
    {
        $this->typeHandlers[] = $type;
    }

    /**
     * Get a type handler.
     *
     * @param string $type
     *
     * @return AbstractTypeHandler|null
     */
    public function getTypeHandlers(string $type): ?AbstractTypeHandler
    {
        foreach ($this->typeHandlers as $typeHandler) {
            if ($typeHandler->getTypeHandlerName() === $type) {
                return $typeHandler;
            }
        }
        return null;
    }

    /**
     * Convert a record.
     *
     * @param array $from
     * @param string $to
     * @throws ClientException If record type is restricted or unavailable.
     */
    public function convert(array $from, string $to)
    {
        if (in_array($to, self::RESTRICTED_TYPES)) {
            throw new ClientException("{$to} record type conversion are restricted.");
        }

        // for DB compatibility
        $to = ucfirst($to);
        $toHandler = $this->getTypeHandlers($to);
        if (!$toHandler) {
            throw new ClientException("record type unavailable");
        }

        $toHandler->handleTypeConversion($from, $to);

        $type = $from["Type"] ?? "Discussion";
        $fromHandler = $this->getTypeHandlers($type);
        if ($fromHandler) {
            $fromHandler->cleanUpRelatedData($from, $to);
        }
    }
}
