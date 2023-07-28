<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use Garden\Schema\Schema;
use Vanilla\Forum\Controllers\Api\DiscussionsApiIndexSchema;
use Vanilla\Forum\Widgets\DiscussionListAsset;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Layout\View\AbstractCustomLayoutView;
use Vanilla\Layout\View\LegacyLayoutViewInterface;
use Vanilla\Web\PageHeadInterface;
use Vanilla\Formatting\Formats\HtmlFormat;

/**
 * Legacy view type for discussion list
 */
class DiscussionListLayoutView extends AbstractCustomLayoutView implements LegacyLayoutViewInterface
{
    use HydrateAwareTrait;

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
        return self::paramInputSchema();
    }

    /**
     * Statically expose input schema.
     *
     * @return Schema
     */
    public static function paramInputSchema(): Schema
    {
        $mainSchema = new DiscussionsApiIndexSchema(30);
        $schema = Schema::parse([
            "type?",
            "sort?",
            "followed?",
            "page?",
            "tagID?",
            "internalStatusID?",
            "statusID?",
        ])->add($mainSchema->withNoDefaults());
        return $schema;
    }

    /**
     * @inheritDoc
     */
    public function resolveParams(array $paramInput, ?PageHeadInterface $pageHead = null): array
    {
        $resolvedParams = parent::resolveParams($paramInput, $pageHead);
        return $resolvedParams;
    }
}
