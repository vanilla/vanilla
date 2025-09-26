<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\PostTypeConversionPayload;

/**
 * Class QnaTypeHandler
 */

class QnaTypeHandler extends \Vanilla\AbstractTypeHandler
{
    const HANDLER_TYPE = "Question";

    /**
     * DI.
     */
    public function __construct(private QnAPlugin $qnaPlugin)
    {
        $this->setTypeHandlerName(self::HANDLER_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function convertTo(PostTypeConversionPayload $payload): void
    {
        $this->qnaPlugin->recalculateDiscussionQnA($payload->discussionRow);
    }

    /**
     * @inheritdoc
     */
    public function cleanUpRelatedData(PostTypeConversionPayload $payload): void
    {
        // Nothing to do here.
    }
}
