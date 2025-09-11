<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use Vanilla\Layout\View\LegacyLayoutViewInterface;

/**
 * Legacy view type for category list.
 */
class LegacyCategoryListLayoutView implements LegacyLayoutViewInterface
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return "Categories Page";
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return "categoryList";
    }

    /**
     * @inheritdoc
     */
    public function getLegacyType(): string
    {
        return "Vanilla/Categories/Index";
    }
}
