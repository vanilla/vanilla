<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules\Triggers;

use Exception;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Vanilla\AutomationRules\Trigger\AutomationTrigger;
use Vanilla\Dashboard\AutomationRules\Models\UserRuleDataType;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Logger;

/**
 * Class UserEmailDomainTrigger
 */
class UserEmailDomainTrigger extends AutomationTrigger
{
    use UserSearchTrait;
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "emailDomainTrigger";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "New/Updated Email domain";
    }

    /**
     * @inheridoc
     */
    public static function getContentType(): string
    {
        return "users";
    }

    /**
     * @inheridoc
     */
    public static function getActions(): array
    {
        return UserRuleDataType::getActions();
    }

    /**
     * @inheridoc
     */
    public static function getSchema(): Schema
    {
        $schema = [
            "emailDomain" => [
                "type" => "string",
                "required" => true,
                "x-control" => SchemaForm::textBox(
                    new FormOptions("Email Domain", "Enter one or more comma-separated email domains"),
                    "string"
                ),
            ],
        ];

        return Schema::parse($schema);
    }

    /**
     * @inheridoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $emailDomainSchema = Schema::parse([
            "trigger:o" => Schema::parse([
                "triggerType:s" => [
                    "enum" => [self::getType()],
                ],
                "triggerValue:o" => Schema::parse([
                    "emailDomain" => [
                        "type" => "string",
                        "nullable" => false,
                    ],
                ])->addValidator("emailDomain", function ($emailDomains, ValidationField $field) {
                    $emailDomains = explode(",", $emailDomains);
                    if (empty($emailDomains[0])) {
                        $field->addError("You should provide at least one email domain.");
                        return Invalid::value();
                    }
                    $domainError = false;
                    foreach ($emailDomains as $emailDomain) {
                        $emailDomain = trim($emailDomain);
                        if (is_numeric($emailDomain) || !filter_var(gethostbyname($emailDomain), FILTER_VALIDATE_IP)) {
                            $field->addError("Invalid domain", [
                                "messageCode" => "Could not resolve domain $emailDomain.",
                                "code" => "403",
                            ]);
                            $domainError = true;
                        } elseif (filter_var($emailDomain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
                            $field->addError("You should provide a domain name, not an IP address.");
                        }
                    }
                    if ($domainError) {
                        return Invalid::value();
                    }
                    return $emailDomains;
                }),
            ]),
        ]);

        $schema->merge($emailDomainSchema);
    }

    /**
     * @inheridoc
     */
    public function getRecordCountsToProcess(array $where): int
    {
        $emailDomains = $where["emailDomain"] ?? "";
        if (empty($emailDomains)) {
            return 0;
        }
        $emailDomains = explode(",", $emailDomains);
        foreach ($emailDomains as $key => $emailDomain) {
            $emailDomains[$key] = trim($emailDomain);
        }
        $query = [
            "emailDomain" => $emailDomains,
            "emailConfirmed" => 1,
        ];
        try {
            return $this->getCount($query);
        } catch (Exception $e) {
            $this->getLogger()->error("Error getting record count for email domain trigger", [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                Logger::FIELD_EVENT => "emailDomainTrigger",
                Logger::FIELD_TAGS => ["automation rules"],
                Logger::ERROR => $e->getMessage(),
                "emailDomains" => $emailDomains,
            ]);
            throw new Exception("Failed to get record count. Please try again later.");
        }
    }

    /**
     * @inheridoc
     */
    public function getRecordsToProcess($lastRecordId, array $where): iterable
    {
        try {
            $emailDomains = $where["emailDomain"] ?? "";
            if (empty($emailDomains)) {
                return yield;
            }
            $emailDomains = explode(",", $emailDomains);
            foreach ($emailDomains as $key => $emailDomain) {
                $emailDomains[$key] = trim($emailDomain);
            }
            $query = [
                "emailDomain" => $emailDomains,
                "emailConfirmed" => 1,
            ];
            if ($lastRecordId) {
                $query["userID"] = $lastRecordId;
            }
            foreach ($this->getUserRecordIterator($query) as $key => $record) {
                yield $key => $record;
            }
        } catch (Exception $e) {
            $this->getLogger()->error("Error searching for records to process email domain.", [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                Logger::FIELD_EVENT => "emailDomainTrigger",
                Logger::FIELD_TAGS => ["automation rules"],
                Logger::ERROR => $e->getMessage(),
                "emailDomains" => $emailDomains,
            ]);
            throw new Exception("Failed to get records. Please try again later.");
        }
    }
}
