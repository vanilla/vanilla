<?php
/**
 * @author Dani Stark <dstark@higherlogic.com>
 * @copyright 2009-2021 Higher-Logic Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Garden\Web\Exception;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\PartialCompletionException;
use Garden\Web\Exception\ServerException;

/**
 * Test for the `PartialCompletionException` class.
 */
class PartialCompletionExceptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test PartialCompletionException.
     */
    public function testPartialCompletionException(): void
    {
        $exPartial_1 = new PartialCompletionException();
        $this->assertSame("Failed processing some resources.", $exPartial_1->getMessage());
        $this->assertEquals(408, $exPartial_1->getCode());

        $exServer = new ServerException("Test");
        $exClient = new ClientException();
        $exPartial_2 = new PartialCompletionException([$exServer, $exClient]);
        $this->assertEquals(500, $exPartial_2->getCode());
    }
}
