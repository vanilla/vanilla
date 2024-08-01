<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Higher Logic..
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

/**
 * Trait for Page Head population.
 */
trait PageHeadAwareTrait
{
    /** @var PageHeadInterface */
    protected $pageHead;

    /**
     * Set PageHeadInterface for header population.
     *
     * @param PageHeadInterface $pageHead
     */
    public function setPageHead(PageHeadInterface $pageHead)
    {
        $this->pageHead = $pageHead;
    }
}
