<?php
/**
 * @author Raphaël Bergina <rbergina@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Models;

use Garden\Web\Data;
use Vanilla\Web\AbstractJsonLDItem;

/**
 * Item to transform a post into some JSON-LD data.
 *
 * @see https://schema.org/DiscussionForumPosting
 */
final class DiscussionJsonLD extends AbstractJsonLDItem
{
    const TYPE = "DiscussionForumPosting";

    /** @var array */
    private $discussion;

    /** @var \DiscussionModel */
    private $discussionModel;

    /**
     * QnAJsonLD constructor.
     *
     * @param array $discussion
     * @param \DiscussionModel $discussionModel
     *
     */
    public function __construct(array $discussion, \DiscussionModel $discussionModel)
    {
        $this->discussion = $discussion;
        $this->discussionModel = $discussionModel;
    }

    /**
     * @inheritdoc
     */
    public function calculateValue(): Data
    {
        $structuredData = $this->discussionModel->structuredData((array) $this->discussion);
        //        $structuredData['@context'] = 'https://schema.org';
        $structuredData["@type"] = self::TYPE;
        return new Data($structuredData);
    }
}
