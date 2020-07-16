<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\QnA\Models;

use Vanilla\Forum\Search\CommentSearchType;

/**
 * Search record type for a answer
 */
class AnswerSearchType extends CommentSearchType {
    /**
     * @inheritdoc
     */
    public function getKey(): string {
        return 'answer';
    }

    /**
     * @inheritdoc
     */
    public function getType(): string {
        return 'answer';
    }
}
