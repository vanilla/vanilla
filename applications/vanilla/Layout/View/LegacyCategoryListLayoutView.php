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
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Categories Page";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return "categoryList";
    }

    /**
     * @inheritDoc
     */
    public function getLegacyType(): string
    {
        return "Vanilla/Categories/Index";
    }
}
