<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\QnA\Layout\View;

/**
 * Legacy view type for new question view.
 */
class LegacyNewQuestionLayoutView implements \Vanilla\Layout\View\LegacyLayoutViewInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "New Question Form";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return "newQuestion";
    }

    /**
     * @inheritDoc
     */
    public function getLegacyType(): string
    {
        return "Vanilla/Post/Question";
    }
}
