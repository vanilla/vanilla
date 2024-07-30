<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

use Garden\Web\RequestInterface;
use Ramsey\Uuid\Uuid;
use Vanilla\Models\SiteMetaExtra;
use Vanilla\Models\SiteMetaExtraArray;

/**
 * Trait for simple audit log events.
 * Implements most of {@link AuditLogEventInterface}
 */
trait BasicAuditLogTrait
{
    protected string $auditLogID;
    protected array $context = [];
    protected array $meta = [];
    protected int $sessionUserID;

    /**
     * Constructor.
     */
    public function __construct(array $context = [])
    {
        $this->context = $context;
        $this->auditLogID = Uuid::uuid4()->toString();
        $this->sessionUserID = \Gdn::session()->UserID;
    }

    /**
     * Get the event type.
     *
     * @return string
     */
    abstract public static function eventType(): string;

    /**
     * Basic equality check implementation.
     */
    public static function canFormatAuditMessage(string $eventType, array $context, array $meta): bool
    {
        return $eventType === static::eventType();
    }

    /**
     * Format the audit log message in a human-readable format.
     *
     * @param string $eventType
     * @param array $context
     *
     * @return string
     */
    abstract public static function formatAuditMessage(string $eventType, array $context, array $meta): string;

    /**
     * @return string
     */
    public function getAuditLogID(): string
    {
        return $this->auditLogID;
    }

    /**
     * @return string
     */
    public function getAuditEventType(): string
    {
        return static::eventType();
    }

    /**
     * @return array
     */
    public function getAuditContext(): array
    {
        return $this->context;
    }

    /**
     * Get meta for gdn.meta.audit.
     *
     * @return array
     */
    public function asPageMeta(): array
    {
        $request = \Gdn::request();
        return [
            "auditLogID" => $this->auditLogID,
            "requestMethod" => $request->getMethod(),
            "requestPath" => $request->getUri()->getPath(),
            "requestQuery" => $request->getQuery(),
        ];
    }

    /**
     * @return SiteMetaExtra
     */
    public function asSiteMetaExtra(): SiteMetaExtra
    {
        return new SiteMetaExtraArray([
            "auditLog" => $this->asPageMeta(),
        ]);
    }

    /**
     * @return RequestInterface
     */
    public function getAuditRequest(): RequestInterface
    {
        return \Gdn::request();
    }

    /**
     * @param string $auditLogID
     * @return void
     */
    public function setAuditLogID(string $auditLogID): void
    {
        $this->auditLogID = $auditLogID;
    }

    /**
     * @return array
     */
    public function getAuditMeta(): array
    {
        return $this->meta;
    }

    /**
     * @return int
     */
    public function getSessionUserID(): int
    {
        return \Gdn::session()->UserID;
    }

    /**
     * @return string
     */
    public function getSessionUsername(): string
    {
        $user = \Gdn::session()->User;
        if (!$user && $this->getSessionUserID() > 0) {
            $user = \Gdn::userModel()->getID($this->getSessionUserID());
        }
        $username = $user ? $user->Name : "Guest";
        return $username;
    }

    /**
     * Encode some data for an audit log message.
     *
     * @param mixed $data
     * @return string
     */
    protected static function formatAuditLogData(mixed $data): string
    {
        if (is_array($data)) {
            $pieces = [];
            foreach ($data as $value) {
                $pieces[] = self::formatAuditLogData($value);
            }
            return implode(", ", $pieces);
        } else {
            return "`" . (string) $data . "`";
        }
    }
}
