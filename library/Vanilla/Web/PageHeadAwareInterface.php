<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Higher Logic.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

/**
 * Helper class to pass in PageHeadInterface.
 */
interface PageHeadAwareInterface
{
    /**
     * Set PageHeadInterface for data population.
     *
     * @param PageHeadInterface $pageHead
     * @return null
     */
    public function setPageHead(PageHeadInterface $pageHead);
}
