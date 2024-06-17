<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Http\HttpRequest;
use JetBrains\PhpStorm\ExpectedValues;
use PHPUnit\Framework\Assert;
use Vanilla\Logging\AuditLogEventInterface;
use Vanilla\Logging\AuditLogService;
use Vanilla\Utility\ArrayUtils;

/**
 * Class used for asserting audit logs with {@link AuditLogTestTrait::assertAuditLogged()}
 */
class ExpectedAuditLog
{
    public ?array $expectedContext = null;
    public ?string $expectedRequestMethod = null;
    public ?string $expectedRequestPath = null;
    public ?string $expectedMessage = null;

    public ?string $expectedClass = null;

    private ?array $expectedModifications = [];

    private ?int $expectedSpoofUserID = null;

    private ?string $expectedOrcEmail = null;

    /**
     * @param string $expectedEventType
     */
    private function __construct(public string $expectedEventType)
    {
    }

    /**
     * Static constructor so we can chain method calls.
     *
     * @param string $eventType
     *
     * @return self
     */
    public static function create(string $eventType): self
    {
        return new self($eventType);
    }

    /**
     * @param class-string<AuditLogEventInterface> $expectedClass
     * @return $this
     */
    public function withClass(string $expectedClass): static
    {
        $this->expectedClass = $expectedClass;
        return $this;
    }

    /**
     * @param array $context
     * @return $this
     */
    public function withContext(array $context): static
    {
        $this->expectedContext = $context;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function withMessage(string $name): static
    {
        $this->expectedMessage = $name;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $oldValue
     * @param mixed $newValue
     *
     * @return $this
     */
    public function withModification(string $key, mixed $oldValue, mixed $newValue): static
    {
        $this->expectedModifications[$key] = [
            "old" => $oldValue,
            "new" => $newValue,
        ];
        return $this;
    }

    /**
     * @param int $userID
     * @return $this
     */
    public function withSpoofedUserID(int $userID): static
    {
        $this->expectedSpoofUserID = $userID;
        return $this;
    }

    /**
     * @param string $orcEmail
     * @return $this
     */
    public function withOrcEmail(string $orcEmail): static
    {
        $this->expectedOrcEmail = $orcEmail;
        return $this;
    }

    /**
     * @param string $method
     * @param string $path
     * @return $this
     */
    public function withRequest(
        #[
            ExpectedValues([
                HttpRequest::METHOD_GET,
                HttpRequest::METHOD_DELETE,
                HttpRequest::METHOD_PATCH,
                HttpRequest::METHOD_POST,
                HttpRequest::METHOD_PATCH,
            ])
        ]
        string $method,
        string $path
    ): static {
        $this->expectedRequestMethod = $method;
        $this->expectedRequestPath = $path;
        return $this;
    }

    /**
     * Get the expected row for this audit log.
     *
     * @return string[]
     */
    public function getExpectedRow(): array
    {
        $expected = [
            "eventType" => $this->expectedEventType,
        ];

        if ($this->expectedClass !== null) {
            $expected["class"] = $this->expectedClass;
        }

        if ($this->expectedRequestMethod !== null) {
            $expected["requestMethod"] = $this->expectedRequestMethod;
        }
        if ($this->expectedRequestMethod !== null) {
            $expected["requestMethod"] = $this->expectedRequestMethod;
        }
        if ($this->expectedMessage !== null) {
            $expected["message"] = $this->expectedMessage;
        }

        if ($this->expectedContext !== null) {
            $expected["context"] = $this->expectedContext;
        }

        if (!empty($this->expectedModifications)) {
            $expected["context"]["modifications"] = $this->expectedModifications;
        }

        if ($this->expectedSpoofUserID !== null) {
            $expected["spoofUserID"] = $this->expectedSpoofUserID;
        }

        if ($this->expectedOrcEmail !== null) {
            $expected["orcUserEmail"] = $this->expectedOrcEmail;
        }

        return $expected;
    }

    /**
     * Check if this expected event matches an audit log event from the database.
     *
     * @param array $event
     * @return bool
     */
    public function matches(array $event): bool
    {
        $expectedKeyVals = ArrayUtils::flattenArray(".", $this->getExpectedRow(), false);
        $actual = $this->formatActualEventRow($event);

        foreach ($expectedKeyVals as $expectedKey => $expectedVal) {
            if (ArrayUtils::getByPath($expectedKey, $actual) !== $expectedVal) {
                return false;
            }
        }
        return true;
    }

    /**
     * Format an audit log event for {@link self::matches()}
     *
     * @param array $event
     *
     * @return array
     */
    public function formatActualEventRow(array $event): array
    {
        $auditLogService = \Gdn::getContainer()->get(AuditLogService::class);
        $actualMessage = $auditLogService->formatEventMessage(
            $event["eventType"],
            (array) $event["context"],
            (array) $event["meta"]
        );
        $actualClass = $auditLogService->findClass(
            $event["eventType"],
            (array) $event["context"],
            (array) $event["meta"]
        );

        if ($actualClass === null) {
            Assert::fail(
                "Could not find class for audit log of type `{$event["eventType"]}`. Did you forget to register it?"
            );
        }

        $event["class"] = $actualClass;
        $event["message"] = $actualMessage;

        $flattened = ArrayUtils::flattenArray(".", $event);

        $expectedKeys = array_keys(ArrayUtils::flattenArray(".", $this->getExpectedRow()));

        $actual = [];
        foreach ($expectedKeys as $expectedKey) {
            ArrayUtils::setByPath($expectedKey, $actual, $flattened[$expectedKey] ?? null);
        }
        return $actual;
    }
}
