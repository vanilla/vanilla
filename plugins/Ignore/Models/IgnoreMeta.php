<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Models;

use Vanilla\Models\SiteMetaExtra;
use Vanilla\Ignore\Models\IgnoreModel;

/**
 * Extra site meta for ignored users.
 */
class IgnoreMeta extends SiteMetaExtra
{
    /** @var IgnoreModel */
    protected $ignoreModel;

    /**
     * DI.
     *
     * @param IgnoreModel $ignoreModel
     */
    public function __construct(IgnoreModel $ignoreModel)
    {
        $this->ignoreModel = $ignoreModel;
    }

    /**
     * @return array
     */
    public function getValue(): array
    {
        $meta = [];
        $meta["ignoredUserIDs"] = $this->ignoreModel->getIgnoredUserIDs();
        return $meta;
    }
}
