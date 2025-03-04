<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Garden\Web\Exception\ClientException;
use Vanilla\Forum\Models\PostTypeModel;

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

    public function __construct(private PostTypeModel $postTypeModel)
    {
    }

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
     * @param array $from The record we are converting.
     * @param string $to The identifier of the type we are converting this record to.
     * @param array|null $postMeta Optional array of post field data (used for custom post types).
     * @param bool $isCustomPostType Whether this is a custom post type.
     * @throws ClientException If record type is restricted or unavailable.
     */
    public function convert(array $from, string $to, ?array $postMeta = null, bool $isCustomPostType = false)
    {
        if (in_array($to, self::RESTRICTED_TYPES)) {
            throw new ClientException("{$to} record type conversion are restricted.");
        }

        if ($isCustomPostType) {
            // Look up the base type of this custom post type.
            $toPostType = $this->postTypeModel->getByID($to);
            $toBaseType = ucfirst($toPostType["baseType"]);
        } else {
            // for DB compatibility
            $toBaseType = $to = ucfirst($to);
        }

        $toHandler = $this->getTypeHandlers($toBaseType);
        if (!$toHandler) {
            throw new ClientException("record type unavailable");
        }

        $toHandler->handleTypeConversion($from, $to, $postMeta);

        if (isset($from["postTypeID"])) {
            // We are converting from a record with a custom post type.
            $fromPostType = $this->postTypeModel->getByID($to);
            $fromBaseType = $fromPostType["baseType"];
        } else {
            $fromBaseType = $from["Type"] ?? "Discussion";
        }

        $fromHandler = $this->getTypeHandlers($fromBaseType);
        if ($fromHandler) {
            $fromHandler->cleanUpRelatedData($from, $to);
        }
    }
}
