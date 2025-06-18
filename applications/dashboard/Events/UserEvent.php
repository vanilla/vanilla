<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Garden\Events\ResourceEvent;
use Psr\Log\LogLevel;
use Vanilla\AliasLoader;
use Vanilla\Events\EventAction;
use Vanilla\Logger;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggerUtils;
use Vanilla\Utility\ModelUtils;

/**
 * Represent a user resource event.
 */
class UserEvent extends ResourceEvent implements LoggableEventInterface
{
    private array $originalProfileFields = [];
    private array $updatedProfileFields = [];

    /**
     * The users API needs expand=all to be applied so certain fields work correctly.
     *
     * @return array|null
     */
    public function getApiParams(): ?array
    {
        return [
            "expand" => implode(",", [ModelUtils::EXPAND_CRAWL, ModelUtils::EXPAND_ALL]),
        ];
    }

    /**
     * @param array $original
     * @param array $updated
     *
     * @return void
     */
    public function auditProfileFieldChange(array $original, array $updated): void
    {
        $this->originalProfileFields = $original;
        $this->updatedProfileFields = $updated;
    }

    /**
     * @return array
     */
    public function getUser(): array
    {
        return $this->getPayload()["user"];
    }

    /**
     * @inheritdoc
     */
    public function getLogEntry(): LogEntry
    {
        $context = LoggerUtils::resourceEventLogContext($this);
        $context[Logger::FIELD_TARGET_USERID] = $this->getUser()["userID"];
        $context[Logger::FIELD_TARGET_USERNAME] =
            $this->getPayload()["existingData"]["name"] ?? $this->getUser()["name"];

        $log = new LogEntry(
            LogLevel::INFO,
            $this->makeLogMessage(
                $this->getAction(),
                $context[Logger::FIELD_TARGET_USERNAME] ?? null,
                $context[Logger::FIELD_USERNAME] ?? null
            ),
            $context
        );
        return $log;
    }

    /**
     * Make a nice log message depending on who the acting and target users are.
     *
     * @param string $action
     * @param string $targetName
     * @param string|null $username
     * @return string
     */
    protected function makeLogMessage(string $action, string $targetName, ?string $username): string
    {
        $isSsoConnect = str_starts_with($this->getAuditRequest()->getPath(), "/entry/connect");

        switch ($action) {
            case ResourceEvent::ACTION_INSERT:
                $isGuest = $this->sender["userID"] === \UserModel::GUEST_USER_ID;
                return !$isGuest
                    ? "User `{$targetName}` was added by `{$username}`."
                    : "User `{$targetName}` registered.";
            case ResourceEvent::ACTION_UPDATE:
                if ($isSsoConnect) {
                    return "User `{$targetName}` was updated during SSO connection.";
                }

                return $username !== $targetName
                    ? "User `{$targetName}` was updated by `{$username}`."
                    : "User `{$targetName}` was updated.";
            case ResourceEvent::ACTION_DELETE:
                return $username !== $targetName
                    ? "User `{$targetName}` was deleted by `{$username}`."
                    : "User `{$targetName}` was deleted.";
        }
        return "";
    }

    /**
     * Return true to bypass {@link ResourceEventLogger} filters.
     *
     * @return bool
     */
    public function bypassLogFilters(): bool
    {
        return true;
    }

    /**
     * Override audit event type to differentiate user_add from user_register.
     *
     * @return string
     */
    public function getAuditEventType(): string
    {
        if ($this->getAction() === EventAction::ADD && $this->sender["userID"] === \UserModel::GUEST_USER_ID) {
            return "user_register";
        }
        return parent::getAuditEventType();
    }

    /**
     * Return modified user profile fields in addition to regular fields in an update event.
     *
     * @inheritdoc
     */
    public function getModifications(): ?array
    {
        $modifications = parent::getModifications();

        // Add profile fields.
        $diff = LoggerUtils::diffArrays($this->originalProfileFields, $this->updatedProfileFields);
        foreach ($diff as $field => $change) {
            $modifications["profileFields.{$field}"] = $change;
        }
        if (empty($modifications)) {
            return null;
        }
        return $modifications;
    }
}

AliasLoader::createAliases(UserEvent::class);
