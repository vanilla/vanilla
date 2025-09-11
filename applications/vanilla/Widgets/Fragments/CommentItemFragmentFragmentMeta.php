<?php
/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Widgets\Fragments;

use Garden\Schema\Schema;
use Vanilla\Forum\Widgets\OriginalPostAsset;
use Vanilla\Forum\Widgets\PostCommentThreadAsset;
use Vanilla\Widgets\React\FragmentMeta;

class CommentItemFragmentMeta extends FragmentMeta
{
    /**
     * @inheritDoc
     */
    public static function getFragmentType(): string
    {
        return "CommentItemFragment";
    }

    public static function getName(): string
    {
        return "Comment Item";
    }

    /**
     * @inheritDoc
     */
    public function getPropSchema(): Schema
    {
        return PostCommentThreadAsset::getWidgetSchema()
            ->setField("properties.titleType", null)
            ->setField("properties.descriptionType", null);
    }
}
