<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Vanilla\Web\Pagination;

/**
 * An API pagination iterator that yields each row one-by-one.
 */
class FlatApiPaginationIterator extends ApiPaginationIterator {
    /**
     * {@inheritDoc}
     */
    protected function internalGenerator(): \Generator {
        foreach (parent::internalGenerator() as $page) {
            yield from $page;
        }
    }
}
