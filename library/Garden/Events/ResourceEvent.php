<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Events;

use Exception;
use Garden\Web\RequestInterface;
use Gdn;
use Ramsey\Uuid\Uuid;
use Vanilla\Dashboard\Models\UserFragment;
use Vanilla\Events\EventAction;
use Vanilla\Logger;
use Vanilla\Logging\AuditLogEventInterface;
use Vanilla\Logging\BasicAuditLogTrait;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggerUtils;
use Vanilla\Logging\ResourceEventLogger;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\DebugUtils;
use Vanilla\Utility\ModelUtils;
use Vanilla\Utility\StringUtils;

/**
 * An event affecting a specific resource.
 */
abstract class ResourceEvent implements \JsonSerializable, AuditLogEventInterface
{
    use BasicAuditLogTrait {
        getSessionUserID as getTraitSessionUserID;
        getSessionUsername as getTraitSessionUsername;
    }

    /** A resource has been removed. */
    public const ACTION_DELETE = EventAction::DELETE;

    /** A resource has been created. */
    public const ACTION_INSERT = EventAction::ADD;

    /** An existing resource has been updated. */
    public const ACTION_UPDATE = EventAction::UPDATE;

    /** @var string */
    protected $action;

    /** @var array */
    protected $payload;

    /** @var array */
    protected $sender;

    /** @var string */
    protected $type;

    /** @var bool */
    protected bool $textUpdated;

    /** @var bool */
    protected bool $suppressWebhooks = false;

    /** @var array $apiParams */
    protected $apiParams;

    protected string $auditLogID;

    /** @var RequestInterface */
    protected RequestInterface $auditRequest;

    /**
     * Create the event.
     *
     * @param string $action
     * @param array $payload
     * @param array|object|null $sender
     * @throws Exception
     */
    public function __construct(string $action, array $payload, $sender = null)
    {
        $this->action = $action;
        $this->payload = $payload;
        $this->apiParams = [
            "expand" => [ModelUtils::EXPAND_CRAWL],
        ];
        $this->sender = $sender ?? \Gdn::userModel()->currentFragment();
        $this->type = $this->typeFromClass();
        $this->auditLogID = Uuid::uuid4()->toString();
    }

    /**
     * Return true to bypass {@link ResourceEventLogger} filters.
     *
     * @return bool
     */
    public function bypassLogFilters(): bool
    {
        return false;
    }

    /**
     * Get the event action.
     *
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }
    /**
     * Get the event payload.
     *
     * @return array|null
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /**
     * Set the event payload.
     *
     * @param array $payload The key => value pairs to set on the payload.
     */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * Get the event textUpdated.
     *
     * @return bool
     */
    public function getTextUpdated(): ?bool
    {
        return $this->textUpdated ?? false;
    }

    /**
     * Set the event textUpdated.
     *
     * @param bool $textUpdated
     */
    public function setTextUpdated(bool $textUpdated): void
    {
        $this->textUpdated = $textUpdated;
    }

    /**
     * Get the event resource api params.
     *
     * @return array|null
     */
    public function getApiParams(): ?array
    {
        return $this->apiParams;
    }

    /**
     * Set event resource api additional params.
     *
     * @param array $params
     */
    public function addApiParams(array $params)
    {
        $this->apiParams = array_merge($this->apiParams, $params);
    }

    /**
     * Get the entity responsible for triggering the event, if available.
     *
     * @return array|object|null
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Get the event type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get a unique primary key for the record.
     *
     * @return string
     */
    public function getUniquePrimaryKey(): string
    {
        return $this->getType() . "ID";
    }

    /**
     * Get a unique primary key for the record.
     *
     * @return string|int
     */
    public function getUniquePrimaryKeyValue()
    {
        return $this->getPayload()[$this->getType()][$this->getUniquePrimaryKey()];
    }

    /**
     * Get the full name of the event.
     *
     * @return string
     */
    public function getFullEventName(): string
    {
        return $this->getType() . "_" . $this->getAction();
    }

    /**
     * Derive the event type from the current class name.
     *
     * @return string
     */
    public static function typeFromClass(): string
    {
        $baseName = get_called_class();
        if (($namespaceEnd = strrpos($baseName, "\\")) !== false) {
            $baseName = substr($baseName, $namespaceEnd + 1);
        }
        $type = lcfirst(preg_replace('/Event$/', "", $baseName));
        return $type;
    }

    /**
     * Create a normalized variation of the record's payload.
     *
     * @return array A tuple of [string, int]
     */
    public function getRecordTypeAndID(): array
    {
        $recordType = $this->getType();

        if ($idKey = $this->payload["documentIdField"] ?? false) {
            $idKey = $this->$idKey;
        } else {
            $idKey = $this->type . "ID";
        }

        $payloadRecord = $this->payload[$this->type] ?? $this->payload;
        $recordID = $payloadRecord["recordID"] ?? ($payloadRecord[$idKey] ?? null);

        return [$recordType, $recordID];
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return [
            "type" => $this->type,
            "action" => $this->action,
            "payload" => $this->getPayload(),
        ];
    }

    /**
     * Get the API URL for the resource.
     *
     * @return string
     */
    public function getApiUrl()
    {
        [$recordType, $recordID] = $this->getRecordTypeAndID();
        return "/api/v2/{$recordType}s/$recordID";
    }

    /**
     * Convert to string.
     */
    public function __toString()
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }

    /**
     * Get the site sectionID the event originated from.
     */
    public function getSiteSectionID(): ?string
    {
        $siteSection = Gdn::getContainer()
            ->get(SiteSectionModel::class)
            ->getCurrentSiteSection();

        return $siteSection->getSectionID() ?? null;
    }

    /**
     * Gets the "base" event action. Defaults to the same value of `getAction()`. Can be overridden by subclasses.
     *
     * @return string
     */
    public function getBaseAction(): string
    {
        return $this->getAction();
    }

    ///
    /// Audit Logging
    ///

    /**
     * We can format audit messages for all resource events.
     * {@inheritdoc}
     */
    public static function canFormatAuditMessage(string $eventType, array $context, array $meta): bool
    {
        $isResourceEvent = $meta["isResourceEvent"] ?? false;
        return $isResourceEvent;
    }

    /**
     * User the explicit log entry message if {@link LoggableEventInterface} is implemented.
     * {@inheritdoc}
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        if ($logEntryMessage = $meta["logEntryMessage"] ?? null) {
            return $logEntryMessage;
        }

        // Otherwise do our best with the event name.
        return StringUtils::labelize($eventType);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditEventType(): string
    {
        return $this->getFullEventName();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditContext(): array
    {
        if ($this instanceof LoggableEventInterface) {
            $logEntry = $this->getLogEntry();
            $context = $logEntry->getContext();
            // remove some common fields we don't care about
            unset(
                $context[Logger::FIELD_EVENT],
                $context[Logger::FIELD_CHANNEL],
                $context[Logger::FIELD_USERID],
                $context[Logger::FIELD_USERNAME],
                $context["resourceAction"],
                $context["resourceType"]
            );
            return $context;
        }
        return [];
    }

    /**
     * @return array
     */
    public function getAuditMeta(): array
    {
        $message =
            $this instanceof LoggableEventInterface
                ? $this->getLogEntry()->getMessage()
                : LoggerUtils::resourceEventLogMessage($this);
        return [
            "isResourceEvent" => true,
            "logEntryMessage" => $message,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditLogID(): string
    {
        return $this->auditLogID;
    }

    /**
     * @return array|null
     */
    public function getModifications(): ?array
    {
        $existingData = $this->getPayload()["existingData"] ?? null;
        if ($existingData === null) {
            return null;
        }

        $newData = $this->getPayload()[$this->getType()] ?? null;
        if ($newData === null) {
            return null;
        }

        foreach ($existingData as $key => $value) {
            if (str_contains(strtolower($key), "date")) {
                unset($existingData[$key]);
            }
        }

        $diff = LoggerUtils::diffArrays($existingData, $newData);
        return empty($diff) ? null : $diff;
    }

    /**
     * @return string
     */
    public static function eventType(): string
    {
        // Actually get's overridden.
        return "resourceEvent";
    }

    /**
     * @inheritdoc
     */
    public function getSessionUserID(): int
    {
        if (isset($this->sender["userID"])) {
            return $this->sender["userID"];
        }
        return $this->getTraitSessionUserID();
    }

    /**
     * @inheritdoc
     */
    public function getSessionUsername(): string
    {
        if (isset($this->sender["name"])) {
            return $this->sender["name"];
        }
        return $this->getTraitSessionUsername();
    }

    /**
     * Get the canonical ID for the resource.
     *
     * @return string
     */
    public function getCanonicalID(): string
    {
        $canonicalID = $this->getPayload()[$this->getType()]["canonicalID"] ?? null;
        if ($canonicalID) {
            return $canonicalID;
        }

        // Fallback to {recordType}_{recordID}
        [$recordType, $recordID] = $this->getRecordTypeAndID();
        return $recordType . "_" . $recordID;
    }
}
