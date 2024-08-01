<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Modules;

use Garden\Container\Reference;
use Vanilla\Forums\Modules\DiscussionTabFactory;

/**
 * Factories for QnA plugin tabs.
 */
class QnATabFactory extends DiscussionTabFactory
{
    public const PRESET_NEW_QUESTIONS = "new-questions";
    public const PRESET_UNANSWERED_QUESTIONS = "unanswered-questions";
    public const PRESET_RECENTLY_ANSWERED_QUESTIONS = "recently-answered-questions";

    /**
     * Get a reference for a factory of new question tabs.
     *
     * @return Reference
     */
    public static function getNewQuestionReference(): Reference
    {
        return new Reference(static::class, [
            self::PRESET_NEW_QUESTIONS,
            "Newest Questions",
            [
                "type" => "question",
                "sort" => "-dateInserted",
            ],
        ]);
    }

    /**
     * Get a reference for a factory of unanswered question tabs.
     *
     * @return Reference
     */
    public static function getUnansweredQuestionReference(): Reference
    {
        return new Reference(static::class, [
            self::PRESET_UNANSWERED_QUESTIONS,
            "Unanswered Questions",
            [
                "type" => "question",
                "status" => "unanswered",
                "sort" => "-dateLastComment",
            ],
        ]);
    }

    /**
     * Get a reference for a factory of recently answered tabs.
     *
     * We can't currently filtered on recently answered, so instead we sort on dateLastComment.
     *
     * @return Reference
     */
    public static function getRecentlyAnsweredReference(): Reference
    {
        return new Reference(static::class, [
            self::PRESET_RECENTLY_ANSWERED_QUESTIONS,
            "Recently Answered Questions",
            [
                "type" => "question",
                "status" => "answered",
                "sort" => "-dateLastComment",
            ],
        ]);
    }
}
