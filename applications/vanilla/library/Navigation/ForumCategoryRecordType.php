<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Navigation;

use Vanilla\Contracts\RecordInterface;

/**
 * An instance of a knowledge category.
 */
class ForumCategoryRecordType implements RecordInterface {

    const TYPE = "category";

    /** @var int */
    private $categoryID;

    /**
     * Constructor.
     *
     * @param int $categoryID
     */
    public function __construct(int $categoryID) {
        $this->categoryID = $categoryID;
    }

    /**
     * @inheritDoc
     */
    public function getRecordID(): int {
        return $this->categoryID;
    }

    /**
     * @inheritDoc
     */
    public function getRecordType(): string {
        return self::TYPE;
    }
}
