<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Zendesk\Events;

use DiscussionModel;
use InvalidArgumentException;

/**
 * Zendesk article discussion event that called while creating an article attachment.
 */
class ZendeskArticleDiscussionEvent
{
    /**
     * @param array  $record // Discussion record
     */
    public function __construct(private array $record)
    {
        $activeTypes = array_keys(DiscussionModel::discussionTypes());
        if (!in_array(ucfirst($this->record["type"]), $activeTypes)) {
            throw new InvalidArgumentException("Invalid discussion type: {$this->record["type"]}");
        }
    }

    /**
     * Get the discussion Record
     *
     * @return array
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    /**
     * @return string
     */
    public function getDiscussionType(): string
    {
        return $this->record["type"];
    }

    /**
     * Get the discussion ID
     *
     * @return int
     */
    public function getDiscussionID(): int
    {
        return $this->record["discussionID"];
    }

    /**
     * Append to current discussion body
     *
     * @param $value
     * @return void
     */
    public function appendToDiscussionBody($value): void
    {
        $this->record["body"] .= $value;
    }

    /**
     * Get a specific field from the record if it exists.
     *
     * @param string $field
     * @return mixed
     */
    public function getField(string $field)
    {
        if (array_key_exists($field, $this->record)) {
            return $this->record[$field];
        }
        return null;
    }
}
