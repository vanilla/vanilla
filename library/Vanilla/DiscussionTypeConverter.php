<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use DiscussionModel;
use Garden\Web\Exception\ClientException;
use Vanilla\Dashboard\Events\DiscussionPostTypeChangeEvent;
use Vanilla\Logging\AuditLogger;

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
    private array $typeHandlers = [];

    public function __construct(private DiscussionModel $discussionModel)
    {
    }

    /**
     * Add type handlers.
     *
     * @param AbstractTypeHandler $type
     */
    public function addTypeHandler(AbstractTypeHandler $type)
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
    private function getTypeHandler(string $type): ?AbstractTypeHandler
    {
        foreach ($this->typeHandlers as $typeHandler) {
            if (strtolower($typeHandler->getTypeHandlerName()) === strtolower($type)) {
                return $typeHandler;
            }
        }
        return null;
    }

    /**
     * Convert a record.
     */
    public function convert(PostTypeConversionPayload $payload)
    {
        if (in_array($payload->toBaseType, self::RESTRICTED_TYPES)) {
            throw new ClientException("{$payload->toBaseType} record type conversion are restricted.");
        }

        $toHandler = $this->getTypeHandler($payload->toBaseType);
        if (!$toHandler) {
            throw new ClientException("record type unavailable");
        }

        if (!$toHandler->canConvert($payload)) {
            throw new ClientException(
                "Category '{$payload->targetCategoryRow["Name"]}' does not allow for '{$payload->toPostTypeID}' type records."
            );
        }

        // First set the main type.
        $this->discussionModel->setType(
            $payload->discussionRow["DiscussionID"],
            $payload->toPostTypeID,
            postMeta: $payload->postMeta
        );
        $toHandler->convertTo($payload);

        if ($payload->fromBaseType !== null) {
            $this->getTypeHandler($payload->fromBaseType)?->cleanUpRelatedData($payload);
        }

        // Add it to the Audit log
        $postTypeChangedEvent = new DiscussionPostTypeChangeEvent(
            postTypeID: $payload->toPostTypeID,
            previousPostTypeID: $payload->fromPostTypeID ??
                ($payload->discussionRow["postTypeID"] ??
                    ($payload->discussionRow["Type"] ?? DiscussionModel::DISCUSSION_TYPE)),
            context: [
                "discussionID" => $payload->discussionRow["DiscussionID"],
            ]
        );
        AuditLogger::log($postTypeChangedEvent);
    }
}
