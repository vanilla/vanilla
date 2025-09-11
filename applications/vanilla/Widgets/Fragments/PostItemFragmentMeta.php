<?php
/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Vanilla
 * @license gpl-2.0-only
 */

namespace Vanilla\Widgets\Fragments;

use DiscussionModel;
use Garden\Schema\Schema;
use Vanilla\Forum\Widgets\CategoriesWidget;
use Vanilla\Forum\Widgets\DiscussionDiscussionsWidget;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\React\FragmentMeta;

/**
 * Fragment for a single post item in a list.
 */
class PostItemFragmentMeta extends FragmentMeta
{
    /**
     * @param DiscussionModel $discussionModel
     */
    public function __construct(private DiscussionModel $discussionModel)
    {
    }

    /**
     * @inheritDoc
     */
    public static function getFragmentType(): string
    {
        return "PostItemFragment";
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return "Post Item";
    }

    /**
     * @inheritDoc
     */
    public function getPropSchema(): Schema
    {
        return Schema::parse([
            "discussion" => $this->discussionModel->schema(),
            "options" => SchemaUtils::composeSchemas(
                DiscussionDiscussionsWidget::optionsSchema(fieldName: null),
                Schema::parse([
                    "featuredImage?" => DiscussionDiscussionsWidget::featuredImageSchema(),
                ])
            ),
            "isChecked:b?",
        ]);
    }
}
