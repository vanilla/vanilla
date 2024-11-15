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
class AnswerSearchType extends CommentSearchType
{
    /**
     * @inheritdoc
     */
    public function getKey(): string
    {
        return "answer";
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return "answer";
    }

    public function getBoostValue(): ?float
    {
        return $this->config->get("Elastic.Boost.Answers", 0.6);
    }

    /**
     * @return string
     */
    public function getSingularLabel(): string
    {
        return \Gdn::translate("Answer");
    }

    /**
     * @return string
     */
    public function getPluralLabel(): string
    {
        return \Gdn::translate("Answers");
    }

    /**
     * @inheritdoc
     */
    public function getDTypes(): ?array
    {
        return [101];
    }
}
