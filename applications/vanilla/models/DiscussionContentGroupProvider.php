<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Vanilla\Models\ContentGroupRecordProviderInterface;

/**
 * Provide discussion content group records.
 */
class DiscussionContentGroupProvider implements ContentGroupRecordProviderInterface
{
    /** @var \DiscussionModel */
    private $discussionModel;

    /**
     * DI.
     *
     * @param \DiscussionModel $discussionModel
     */
    public function __construct(\DiscussionModel $discussionModel)
    {
        $this->discussionModel = $discussionModel;
    }

    /**
     * @inheritDoc
     */
    public function getRecordType(): string
    {
        return \DiscussionModel::RECORD_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function filterValidRecordIDs(array $recordIDs): array
    {
        return $this->discussionModel->filterExistingRecordIDs($recordIDs);
    }
}
