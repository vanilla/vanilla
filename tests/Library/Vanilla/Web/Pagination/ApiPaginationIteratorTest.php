<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Pagination;

use Garden\Http\HttpResponse;
use Vanilla\Web\Pagination\ApiPaginationIterator;
use Vanilla\Web\Pagination\WebLinking;
use VanillaTests\Fixtures\MockHttpClient;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for our api paging iterator.
 */
class ApiPaginationIteratorTest extends MinimalContainerTestCase {

    /**
     * Test that we can iterate over some API responses.
     */
    public function testIteration() {
        $mockClient = new MockHttpClient();

        $mockClient
            ->addMockResponse('/test?page=1', new HttpResponse(200, [
                WebLinking::HEADER_NAME => (new WebLinking())
                    ->addLink('next', '/test?page=2')
                    ->getLinkHeaderValue(),
            ], 'page 1'))
            ->addMockResponse('/test?page=2', new HttpResponse(200, [
                WebLinking::HEADER_NAME => (new WebLinking())
                    ->addLink('next', '/test?page=3')
                    ->addLink('prev', '/test?page=1')
                    ->getLinkHeaderValue(),
            ], 'page 2'))
            ->addMockResponse('/test?page=3', new HttpResponse(200, [
                WebLinking::HEADER_NAME => (new WebLinking())
                    ->addLink('prev', '/test?page=2')
                    ->getLinkHeaderValue(),
            ], 'page 3'))
        ;

        $expectedResponses = ['page 1', 'page 2', 'page 3'];

        $iterator = new ApiPaginationIterator($mockClient, '/test?page=1');
        $actualResponses = [];
        foreach ($iterator as $value) {
            $actualResponses[] = $value;
        }

        $this->assertEquals($expectedResponses, $actualResponses);
    }
}
