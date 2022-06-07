<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

/**
 * Interface used to expand record fragments with the ApiExpandMiddleware.
 */
abstract class AbstractApiExpander
{
    /** @var array<string, string> */
    private $expandFields = [];

    /**
     * Get the top level key used to expand all items in the expander.
     *
     * @return string
     */
    abstract public function getFullKey(): string;

    /**
     * Fetch fragments based on the recordIDs found.
     *
     * @param int[] $recordIDs The record IDs that need to be expanded.
     *
     * @return array{int, array}
     * @example
     * [
     *     5 => [
     *         'userID' => 5,
     *         'name' => 'Username'
     *     ]
     * ]
     */
    abstract public function resolveFragements(array $recordIDs): array;

    /**
     * Get a permission that a user must have on of in order to expand any of these.
     *
     * @return string|null
     */
    abstract public function getPermission(): ?string;

    /**
     * Get a default value to use if we couldn't locate a record.
     *
     * @return array|null
     */
    public function getDefaultRecord(): ?array
    {
        return null;
    }

    /**
     * Get a mapping of expanded field names to id field names.
     *
     * @return array{string, string}
     * @example
     * [
     *     'insertUser' => 'insertUserID'
     * ]
     */
    public function getExpandFields(): array
    {
        return $this->expandFields;
    }

    /**
     * Add an expandable field to the expander.
     *
     * @param string $destinationKey The key that the expandable record will be at.
     * @param string $idKey The key where the id for the expandable record will be at.
     *
     * @return $this The instance for fluent method chaining.
     */
    public function addExpandField(string $destinationKey, string $idKey): AbstractApiExpander
    {
        $this->expandFields[$destinationKey] = $idKey;
        return $this;
    }

    /**
     * Get a sourceID field name from the destination field name.
     *
     * @param string $destinationKey
     *
     * @return string|null
     */
    public function getFieldByDestination(string $destinationKey): ?string
    {
        return $this->expandFields[$destinationKey] ?? null;
    }
}
