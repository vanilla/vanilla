<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Firebase\JWT\ExpiredException;
use Vanilla\CurrentTimeStamp;
use Vanilla\Web\RoleToken;
use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\Request;

/**
 * Tests for RoleToken
 */
class RoleTokenTest extends TestCase
{
    /** @var string $dummySecret */
    private static $dummySecret;

    /**
     * Setup before class
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::$dummySecret = "abcdefghijklmnopqrstuvwxyz";
    }

    /**
     * Test that the withSecret factory method throws a length exception when secret provided is too short
     *
     * @param string $secret
     * @dataProvider withSecretThrowsLengthExceptionDataProvider
     */
    public function testWithSecretThrowsLengthException(string $secret): void
    {
        $this->expectException(\LengthException::class);
        $_ = RoleToken::withSecret($secret);
    }

    /**
     * Data Provider for withSecretThrowsLengthException test method
     *
     * @return iterable
     * @codeCoverageIgnore
     */
    public function withSecretThrowsLengthExceptionDataProvider(): iterable
    {
        yield "empty secret" => [""];
        yield "single character" => ["a"];
        yield "a few characters" => ["abcde"];
    }

    /**
     * Test that forEncoding factory method throws Exception given invalid inputs
     *
     * @param string $secret
     * @param int $windowSec
     * @param int|null $rolloverSec
     * @param string $expectedException
     * @dataProvider forEncodingThrowsExceptionDataProvider
     */
    public function testForEncodingThrowsException(
        string $secret,
        int $windowSec,
        ?int $rolloverSec,
        string $expectedException
    ): void {
        $this->expectException($expectedException);
        $_ = RoleToken::forEncoding($secret, $windowSec, $rolloverSec);
    }

    /**
     * Data Provider for forEncodingThrowsException test
     *
     * @return iterable
     * @codeCoverageIgnore
     */
    public function forEncodingThrowsExceptionDataProvider(): iterable
    {
        $dummySecret = "abcdefghijklmnopqrstuvwxyz";
        yield "secret too short" => [
            "secret" => "abcde",
            "windowSec" => 10,
            "rolloverSec" => 5,
            "expectedException" => \LengthException::class,
        ];
        yield "zero window" => [
            "secret" => $dummySecret,
            "windowSec" => 0,
            "rolloverSec" => 5,
            "expectedException" => \DomainException::class,
        ];
        yield "negative window" => [
            "secret" => $dummySecret,
            "windowSec" => -5,
            "rolloverSec" => 5,
            "expectedException" => \DomainException::class,
        ];
        yield "negative rollover" => [
            "secret" => $dummySecret,
            "windowSec" => 5,
            "rolloverSec" => -3,
            "expectedException" => \DomainException::class,
        ];
        yield "rollover equals window" => [
            "secret" => $dummySecret,
            "windowSec" => 5,
            "rolloverSec" => 5,
            "expectedException" => \DomainException::class,
        ];
        yield "rollover greater than window" => [
            "secret" => $dummySecret,
            "windowSec" => 5,
            "rolloverSec" => 6,
            "expectedException" => \DomainException::class,
        ];
    }

    /**
     * Test that setRoleIDs throws exception on invalid input
     *
     * @param array $roleIDs
     * @param string $expectedException
     * @dataProvider setRoleIDsThrowsExceptionDataProvider
     */
    public function testSetRoleIDsThrowsException(array $roleIDs, string $expectedException)
    {
        $secret = static::$dummySecret;
        $this->expectException($expectedException);
        $_ = RoleToken::withSecret($secret)->setRoleIDs($roleIDs);
    }

    /**
     * Data Provider for setRoleIDsThrowsException tests
     *
     * @return iterable
     * @codeCoverageIgnore
     */
    public function setRoleIDsThrowsExceptionDataProvider(): iterable
    {
        yield "empty role IDs" => [
            "roleIDs" => [],
            "expectedException" => \LengthException::class,
        ];
        yield "contains non-numeric strings" => [
            "roleIDs" => [6, "foo", 18, 21, "bar"],
            "expectedException" => \InvalidArgumentException::class,
        ];
        yield "contains booleans" => [
            "roleIDs" => [6, true, 18, 21, false],
            "expectedException" => \InvalidArgumentException::class,
        ];
        yield "contains arrays" => [
            "roleIDs" => [6, ["foo" => "bar", 2, 7, "y"], 18, 21],
            "expectedException" => \InvalidArgumentException::class,
        ];
        yield "contains objects" => [
            "roleIDs" => [6, new \stdClass(), 18, 21],
            "expectedException" => \InvalidArgumentException::class,
        ];
        yield "contains negative integers" => [
            "roleIDs" => [6, -1, 18, 21, -5],
            "expectedException" => \InvalidArgumentException::class,
        ];
        yield "contains zero" => [
            "roleIDs" => [6, 1, 18, 0, 5],
            "expectedException" => \InvalidArgumentException::class,
        ];
    }

    /**
     * Test that decoding a role token that was encoded w/o its window of validity set
     * throws an exception indicating that the token is expired.
     */
    public function testEncodeWithoutValidWindowSetIssuesExpiredToken(): void
    {
        $nowTimestamp = time();
        CurrentTimeStamp::mockTime($nowTimestamp);
        $rt = RoleToken::withSecret(static::$dummySecret);
        $encoded = $rt->encode();
        $this->expectException(ExpiredException::class);
        $_ = $rt->decode($encoded);
    }

    /**
     * Test encoding a role token and verifying it can be decoded and its decoded state matches its state pre-encoding.
     */
    public function testEncodeAndDecode(): void
    {
        $dummyRoleIDs = [1234, "2345", 3456];
        $nowTimestamp = time();
        $mockRequest = new Request("/this/is/a/test", "POST");
        CurrentTimeStamp::mockTime($nowTimestamp);
        $rt = RoleToken::forEncoding(static::$dummySecret, 120, 60)
            ->setRoleIDs($dummyRoleIDs)
            ->setRequestor($mockRequest);
        $encoded = $rt->encode();
        $this->assertGreaterThan(CurrentTimeStamp::getDateTime(), $rt->getExpires());
        $decodedRt = RoleToken::withSecret(static::$dummySecret)->decode($encoded);
        $this->assertSame(
            array_map(function ($roleID) {
                return intval($roleID);
            }, $dummyRoleIDs),
            $decodedRt->getRoleIDs()
        );
        $decoded = $decodedRt->getDecoded();
        $this->assertArrayHasKey("nbf", $decoded);
        $this->assertLessThanOrEqual($nowTimestamp, intval($decoded["nbf"]));
        $this->assertArrayHasKey("exp", $decoded);
        $this->assertGreaterThan($nowTimestamp, intval($decoded["exp"]));
        $this->assertArrayHasKey("iss", $decoded);
        $this->assertStringEndsWith("/this/is/a/test", $decoded["iss"]);
    }

    /**
     * Test that when tokens with matching payload claims (outside of reserved claims
     * used for checking validity) are encoded at different times within the same window,
     * that the encoded values for token encoded later matches value for token encoded earlier.
     *
     * @param \DateInterval $intervalWithinWindow
     * @dataProvider tokensWithMatchingRoleIDsEncodedWithinWindowProducesSameEncodedValueDataProvider
     */
    public function testTokensWithMatchingRoleIDsEncodedWithinWindowProducesSameEncodedValue(
        \DateInterval $intervalWithinWindow
    ): void {
        $now = \DateTimeImmutable::createFromMutable(new \DateTime("2021-10-12T23:04:09Z"));
        $dummyRoleIDs = [1234, 2345, 3456];
        $mockRequest = new Request("/this/is/a/test", "POST");
        CurrentTimeStamp::mockTime($now);
        $roleToken = RoleToken::forEncoding(static::$dummySecret, 120, 60)
            ->setRoleIDs($dummyRoleIDs)
            ->setRequestor($mockRequest);
        $encodedNow = $roleToken->encode();
        CurrentTimeStamp::mockTime($now->add($intervalWithinWindow));
        $encodedLaterWithinWindow = $roleToken->encode();
        $this->assertEquals($encodedNow, $encodedLaterWithinWindow);
    }

    /**
     * Data Provider for tokensWithMatchingRoleIDsEncodedWithinWindowProducesSameEncodedValue test
     *
     * @return iterable
     */
    public function tokensWithMatchingRoleIDsEncodedWithinWindowProducesSameEncodedValueDataProvider(): iterable
    {
        yield "10 seconds from now" => [
            "intervalWithinWindow" => new \DateInterval("PT10S"),
        ];
        yield "30 seconds from now" => [
            "intervalWithinWindow" => new \DateInterval("PT30S"),
        ];
        yield "50 seconds from now" => [
            "intervalWithinWindow" => new \DateInterval("PT50S"),
        ];
    }

    /**
     * Test that when tokens with differing roleIDs are encoded at different times within the same window,
     * the encoded values for token encoded later does not match value for token encoded earlier.
     *
     * @param \DateInterval $intervalWithinWindow
     * @dataProvider tokensWithDifferentRoleIDsEncodedWithinWindowProducesDifferentEncodedValuesDataProvider
     */
    public function testTokensWithDifferentRoleIDsEncodedWithinWindowProducesDifferentEncodedValues(
        \DateInterval $intervalWithinWindow
    ): void {
        $now = \DateTimeImmutable::createFromMutable(new \DateTime("2021-10-12T23:04:09Z"));
        $dummyRoleIDs = [1234, 2345, 3456];
        $mockRequest = new Request("/this/is/a/test", "POST");
        CurrentTimeStamp::mockTime($now);
        $roleToken = RoleToken::forEncoding(static::$dummySecret, 120, 60)
            ->setRoleIDs($dummyRoleIDs)
            ->setRequestor($mockRequest);
        $encodedNow = $roleToken->encode();
        //Replace first element
        array_shift($dummyRoleIDs);
        array_unshift($dummyRoleIDs, 4321);
        $roleToken = $roleToken->setRoleIDs($dummyRoleIDs);
        CurrentTimeStamp::mockTime($now->add($intervalWithinWindow));
        $encodedLaterWithinWindow = $roleToken->encode();
        $this->assertNotEquals($encodedNow, $encodedLaterWithinWindow);
    }

    /**
     * Data Provider for tokensWithDifferentRoleIDsEncodedWithinWindowProducesDifferentEncodedValues test
     *
     * @return iterable
     */
    public function tokensWithDifferentRoleIDsEncodedWithinWindowProducesDifferentEncodedValuesDataProvider(): iterable
    {
        yield "10 seconds from now" => [
            "intervalWithinWindow" => new \DateInterval("PT10S"),
        ];
        yield "30 seconds from now" => [
            "intervalWithinWindow" => new \DateInterval("PT30S"),
        ];
        yield "50 seconds from now" => [
            "intervalWithinWindow" => new \DateInterval("PT50S"),
        ];
    }

    /**
     * Test that when multiple tokens with matching payload claims, outside of reserved claims
     * used for checking validity, are encoded at times where the windows differ,
     * that the encoded values for the token generated later does not match encoded value for token encoded first.
     *
     * @param \DateInterval $intervalAfterWindow
     * @dataProvider tokensWithMatchingRoleIDsEncodedInDifferentWindowProduceDifferentEncodedValuesDataProvider
     */
    public function testTokensWithMatchingRoleIDsEncodedInDifferentWindowProduceDifferentEncodedValues(
        \DateInterval $intervalAfterWindow
    ): void {
        $now = \DateTimeImmutable::createFromMutable(new \DateTime("2021-10-12T23:04:09Z"));
        $dummyRoleIDs = [1234, 2345, 3456];
        $mockRequest = new Request("/this/is/a/test", "POST");
        CurrentTimeStamp::mockTime($now);
        $roleToken = RoleToken::forEncoding(static::$dummySecret, 120, 60)
            ->setRoleIDs($dummyRoleIDs)
            ->setRequestor($mockRequest);
        $encodedNow = $roleToken->encode();
        CurrentTimeStamp::mockTime($now->add($intervalAfterWindow));
        $encodedLaterAfterWindow = $roleToken->encode();
        $this->assertNotEquals($encodedNow, $encodedLaterAfterWindow);
    }

    /**
     * Data Provider for tokensWithMatchingRoleIDsEncodedInDifferentWindowProduceDifferentEncodedValues test
     *
     * @return iterable
     * @codeCoverageIgnore
     */
    public function tokensWithMatchingRoleIDsEncodedInDifferentWindowProduceDifferentEncodedValuesDataProvider(): iterable
    {
        yield "at start of rollover period" => [
            "intervalAfterWindow" => new \DateInterval("PT51S"),
        ];
        yield "1 min from now, during rollover period" => [
            "intervalAfterWindow" => new \DateInterval("PT1M"),
        ];
        yield "1 min 30 seconds from now" => [
            "intervalAfterWindow" => new \DateInterval("PT1M30S"),
        ];
        yield "2 min from now, new window" => [
            "intervalAfterWindow" => new \DateInterval("PT2M"),
        ];
    }
}
