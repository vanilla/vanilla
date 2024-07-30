<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules\Models;

/**
 * Interface PostInterface
 */
interface PostInterface
{
    /**
     * Get the post record
     *
     * @return array
     */
    public function getPostRecord(): array;

    /**
     * Set the post record
     */
    public function setPostRecord(array $postRecord): void;
}
