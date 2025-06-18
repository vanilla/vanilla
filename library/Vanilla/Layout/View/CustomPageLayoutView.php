<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Layout\View;

use Garden\Schema\Schema;
use Vanilla\Dashboard\Controllers\API\CustomPagesApiController;
use Vanilla\Web\PageHeadInterface;

class CustomPageLayoutView extends AbstractCustomLayoutView
{
    const VIEW_TYPE = "customPage";

    public function __construct(protected CustomPagesApiController $customPagesApi)
    {
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return "Custom Page";
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return self::VIEW_TYPE;
    }

    /**
     * @inheritdoc
     */
    public function getTemplateID(): string
    {
        return self::VIEW_TYPE;
    }

    /**
     * @inheritdoc
     */
    public function getParamInputSchema(): Schema
    {
        return Schema::parse(["customPageID:i"]);
    }

    /**
     * Resolve parameters for the layout.
     *
     * @param array $paramInput
     * @param PageHeadInterface|null $pageHead
     * @return array
     */
    public function resolveParams(array $paramInput, ?PageHeadInterface $pageHead = null): array
    {
        $customPage = $this->customPagesApi->get($paramInput["customPageID"]);

        $pageHead
            ->setSeoTitle($customPage["seoTitle"], false)
            ->setSeoDescription($customPage["seoDescription"])
            ->setCanonicalUrl($customPage["url"]);

        return $paramInput;
    }
}
