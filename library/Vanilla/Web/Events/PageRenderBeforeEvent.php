<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Events;

use Vanilla\Web\Page;
use Vanilla\Web\PageHead;

/**
 * Event for before a page renders.
 */
final class PageRenderBeforeEvent
{
    /** @var PageHead */
    private $pageHead;

    /** @var Page */
    private $page;

    /**
     * Constructor.
     *
     * @param PageHead $pageHead
     * @param Page $page
     */
    public function __construct(PageHead $pageHead, Page $page)
    {
        $this->pageHead = $pageHead;
        $this->page = $page;
    }

    /**
     * @return PageHead
     */
    public function getPageHead(): PageHead
    {
        return $this->pageHead;
    }

    /**
     * @return Page
     */
    public function getPage(): Page
    {
        return $this->page;
    }
}
