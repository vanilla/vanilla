<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Http;

use Vanilla\Http\InternalResponse;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\VanillaTestCase;

class TestHttpResponse extends InternalResponse
{
    /**
     * @return $this
     */
    public function assertSuccess(): self
    {
        VanillaTestCase::assertTrue($this->isSuccessful(), "Response is not OK");
        return $this;
    }

    /**
     * @return $this
     */
    public function assertFailure(): self
    {
        VanillaTestCase::assertFalse($this->isSuccessful(), "Response is not OK");
        return $this;
    }

    /**
     * @param int $status
     * @return $this
     */
    public function assertStatus(int $status): self
    {
        VanillaTestCase::assertEquals($status, $this->getStatusCode(), "Response status code does not match");
        return $this;
    }

    /**
     * @param string $expectedHeaderName
     * @param string $expectedHeaderValue
     * @return $this
     */
    public function assertHeader(string $expectedHeaderName, string $expectedHeaderValue): self
    {
        VanillaTestCase::assertEquals(
            $expectedHeaderValue,
            $this->getHeader($expectedHeaderName),
            "Response header does not match"
        );
        return $this;
    }

    /**
     * @param int $expectedCode
     * @return $this
     */
    public function assertResponseCode(int $expectedCode): self
    {
        VanillaTestCase::assertEquals(
            $expectedCode,
            $this->getStatusCode(),
            "Expected response code $expectedCode, got {$this->getStatusCode()} instead."
        );
        return $this;
    }

    /**
     * @return $this
     */
    public function assertJsonObject(): self
    {
        VanillaTestCase::assertTrue(ArrayUtils::isAssociative($this->getBody()), "Response was not a JSON object.");

        return $this;
    }

    /**
     * Assert that the response is a json object containing properties with specific values.
     *
     * @param array $expected Map of 'path.to.data' => 'expected value'
     * @return $this
     */
    public function assertJsonObjectLike(array $expected, string $message = ""): self
    {
        $this->assertJsonObject();
        VanillaTestCase::assertDataLike($expected, $this->getBody(), $message);
        return $this;
    }

    /**
     * @return $this
     */
    public function assertJsonArray(): self
    {
        $body = $this->getBody();
        VanillaTestCase::assertTrue(ArrayUtils::isArray($body), "Response was not an array.");
        VanillaTestCase::assertFalse(ArrayUtils::isAssociative($this->getBody()), "Response was not a JSON object.");

        return $this;
    }

    /**
     * Assert that a json array is empty.
     *
     * @return $this
     */
    public function assertEmptyJsonArray(): self
    {
        $this->assertJsonArray();
        VanillaTestCase::assertEmpty($this->getBody(), "Response was not an empty array.");
        return $this;
    }

    /**
     * @param array $expected A mapping of "key" => ["row1Value", "row2Value"].
     * @param bool $strictOrder
     * @param int|null $count
     * @return $this
     */
    public function assertJsonArrayValues(array $expected, bool $strictOrder = false, ?int $count = null): self
    {
        $this->assertJsonArray();
        VanillaTestCase::assertRowsLike($expected, $this->getBody(), $strictOrder, $count);
        return $this;
    }

    /**
     * @param array $expected
     * @param string $message
     * @return $this
     */
    public function assertJsonArrayContains(array $expected, string $message = ""): self
    {
        $this->assertJsonArray();
        VanillaTestCase::assertDatasetHasRow($this->getBody(), $expected, $message);
        return $this;
    }

    /**
     * @param int $expectedCount
     * @return $this
     */
    public function assertCount(int $expectedCount, string $message = ""): self
    {
        $this->assertJsonArray();
        VanillaTestCase::assertCount($expectedCount, $this->getBody(), $message);
        return $this;
    }

    /**
     * @param array $expected
     * @param string $message
     * @return $this
     */
    public function assertJsonObjectHasKeys(array $expected, string $message = ""): self
    {
        $this->assertJsonObject();
        VanillaTestCase::assertArrayHasKeys($expected, $this->getBody(), $message);
        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function assertJsonArrayEmpty(string $message = ""): self
    {
        $this->assertJsonArray();
        VanillaTestCase::assertEmpty($this->getBody(), $message);
        return $this;
    }
}
