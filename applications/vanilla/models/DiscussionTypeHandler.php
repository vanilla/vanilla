<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use DiscussionStatusModel;

/**
 * Class DiscussionTypeHandler
 *
 * @package Vanilla
 */
class DiscussionTypeHandler extends AbstractTypeHandler
{
    const HANDLER_TYPE = "Discussion";

    /**
     * DI.
     */
    public function __construct(private DiscussionStatusModel $discussionStatusModel)
    {
        $this->setTypeHandlerName(self::HANDLER_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function convertTo(PostTypeConversionPayload $payload): void
    {
        $discussionStatusModel = \Gdn::getContainer()->get(\DiscussionStatusModel::class);
        $discussionStatusModel->determineAndUpdateDiscussionStatus($payload->discussionRow["DiscussionID"]);
    }

    /**
     * @inheritdoc
     */
    public function cleanUpRelatedData(PostTypeConversionPayload $payload): void
    {
        // Nothing to do.
    }
}
