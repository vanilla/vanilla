<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

/**
 * Use this trait on `AbstractResourceTest` classes that don't have a `GET /:id/edit` endpoint.
 * It will just use `GET /:id` instead.
 */
trait NoGetEditTestTrait {
    /**
     * Call `GET /:id/edit`
     *
     * @param mixed $rowOrID The PK value or a row.
     * @return HttpResponse
     */
    protected function getEdit($rowOrID): HttpResponse {
        if (is_array($rowOrID)) {
            $id = $rowOrID[$this->pk];
        } else {
            $id = $rowOrID;
        }

        $r = $this->api()->get("{$this->baseUrl}/$id");
        return $r;
    }

    /**
     * {@inheritDoc}
     */
    public function testGetEditFields() {
        TestCase::markTestSkipped("This resource doesn't have GET /:id/edit.");
    }
}
