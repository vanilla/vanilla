<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Models;

use Vanilla\Contracts\Models\SiteTotalProviderInterface;

class CustomPageSiteTotalProvider implements SiteTotalProviderInterface
{
    public function __construct(private CustomPageModel $customPageModel)
    {
    }

    /**
     * @inheritdoc
     */
    public function calculateSiteTotalCount(): int
    {
        return $this->customPageModel->queryTotalCount();
    }

    /**
     * @inheritdoc
     */
    public function getTableName(): string
    {
        return "customPage";
    }

    /**
     * @inheritdoc
     */
    public function getSiteTotalRecordType(): string
    {
        return "customPage";
    }
}
