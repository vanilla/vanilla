<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

/**
 * Interface to manage User Mentions.
 */
interface UserMentionsInterface
{
    /**
     * Add a userMention record for a specified record.
     *
     * @param array $user
     * @param array $record
     * @return bool
     */
    public function insertUserMentions(array $user, array $record): bool;
}
