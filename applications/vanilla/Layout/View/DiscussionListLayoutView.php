<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use Garden\Schema\Schema;
use Vanilla\Forum\Widgets\DiscussionListAsset;
use Vanilla\Layout\View\AbstractCustomLayoutView;
use Vanilla\Layout\View\LegacyLayoutViewInterface;

/**
 * Legacy view type for discussion list
 */
class DiscussionListLayoutView extends AbstractCustomLayoutView implements LegacyLayoutViewInterface
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->registerAssetClass(DiscussionListAsset::class);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Discussions Page";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return "discussionList";
    }

    /**
     * @inheritDoc
     */
    public function getLegacyType(): string
    {
        return "Vanilla/Discussions/Index";
    }

    /**
     * @inheritDoc
     */
    public function getLayoutID(): string
    {
        return "discussionList";
    }

    /**
     * @inheritDoc
     */
    public function getParamInputSchema(): Schema
    {
        return Schema::parse([
            "type:s?" => [
                "enum" => \DiscussionModel::apiDiscussionTypes(),
            ],
            "status:s?",
        ]);
    }
}
