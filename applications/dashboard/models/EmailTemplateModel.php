<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Exception;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Gdn;
use Gdn_Session;
use InvalidArgumentException;
use LogModel;
use UserModel;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Formatting\FormatService;
use Vanilla\Logger;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Models\LegacyModelUtils;
use Vanilla\Models\PipelineModel;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\SchemaFactory;

/**
 * EmailTemplateModel
 */
class EmailTemplateModel extends PipelineModel
{
    const MAX_LIMIT = 150;
    const STATUS_ACTIVE = "active";
    const STATUS_INACTIVE = "inactive";
    const STATUS_DELETED = "deleted";
    const STATUS_OPTIONS = [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED];

    const EMAIL_CONFIG_FIELDS = [
        "textColor",
        "backgroundColor",
        "pageColor",
        "buttonTextColor",
        "buttonBackgroundColor",
    ];

    /**
     * EmailTemplateModel constructor.
     *
     * @param Gdn_Session $session
     * @param UserModel $userModel
     * @param FormatService $formatterService
     */
    public function __construct(
        private Gdn_Session $session,
        private UserModel $userModel,
        private FormatService $formatterService
    ) {
        parent::__construct("emailTemplate");
        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);

        $jsonFields = new JsonFieldProcessor(["emailConfig"]);
        $this->addPipelineProcessor($jsonFields);
    }

    /**
     * Structure for the email Template table.
     *
     * @param \Gdn_Database $database
     * @param bool $explicit
     * @param bool $drop If true, and the table specified with $this->table() already exists,
     *  this method will drop the table before attempting to re-create it.
     * @return void
     * @throws Exception
     */
    public static function structure(\Gdn_Database $database, bool $explicit = false, bool $drop = false): void
    {
        $database
            ->structure()
            ->table("emailTemplate")
            ->primaryKey("emailTemplateID")
            ->column("emailType", "varchar(20)")
            ->column("fromEmail", "varchar(100)")
            ->column("fromName", "varchar(100)")
            ->column("name", "varchar(100)")
            ->column("subject", "varchar(255)")
            ->column("body", "mediumtext")
            ->column("footer", "mediumtext")
            ->column("emailLogo", "varchar(767)", true)
            ->column("emailConfig", "text", true)
            ->column("dateInserted", "datetime")
            ->column("insertUserID", "int")
            ->column("dateUpdated", "datetime", true)
            ->column("updateUserID", "int", true)
            ->column("dateLastSent", "datetime", true)
            ->column("status", ["active", "inactive", "deleted"], "inactive")
            ->set($explicit, $drop);
        self::createDefaultEmailTemplates($database);
    }

    /**
     * Check if the email templates are enabled.
     *
     * @return bool
     */
    public static function isEmailTemplatesEnabled(): bool
    {
        return FeatureFlagHelper::featureEnabled("EmailTemplate") &&
            FeatureFlagHelper::featureEnabled("AutomationRules");
    }

    /**
     * Create default Email Templates
     *
     * @param \Gdn_Database $database
     * @return void
     */
    private static function createDefaultEmailTemplates(\Gdn_Database $database): void
    {
        //check configs to see if we need to create default email templates
        if (
            FeatureFlagHelper::featureEnabled("EmailTemplate") &&
            !Gdn::config()->get("Preferences.EmailTemplate.Defaults", false)
        ) {
            // Placeholder for later 2025.009 spring has ticket to add some default email templates
        }
    }

    /**
     * Get the schema for the email template model.
     *
     * @return Schema
     */
    public function getEmailTemplateSchema(): Schema
    {
        return Schema::parse([
            "emailTemplateID:i",
            "name:s",
            "emailType:s",
            "fromEmail:s",
            "fromName:s",
            "subject:s",
            "body:s",
            "footer:s",
            "emailLogo:s|n",
            "emailConfig:a|n",
            "dateInserted:dt",
            "dateUpdated:dt",
            "insertUserID:i",
            "updateUserID:i|n",
            "insertUser?" => SchemaFactory::get(UserFragmentSchema::class),
            "updateUser?" => SchemaFactory::get(UserFragmentSchema::class),
            "status:s",
        ]);
    }

    /**
     * Return a list of email template based on query filters.
     *
     * @param array $query
     * @return array
     * @throws Exception
     */
    public function getEmailTemplates(array $query = []): array
    {
        $where = [];
        $sql = $this->database->sql();
        $sql->select(["et.*"])->from("emailTemplate et");
        foreach (["emailTemplateID" => "emailTemplateID", "name" => "name", "status" => "status"] as $column => $key) {
            if (!empty($query[$key])) {
                $where["et.$column"] = $query[$key];
            }
        }

        if (empty($query["status"])) {
            $where["et.status"] = [self::STATUS_ACTIVE, self::STATUS_INACTIVE];
        }
        if (!empty($query["sort"]) && is_array($query["sort"])) {
            foreach ($query["sort"] as $sort) {
                [$orderField, $orderDirection] = LegacyModelUtils::orderFieldDirection($sort);
                $sql->orderBy("et." . $orderField, $orderDirection);
            }
        }
        $sql->where($where)->limit($query["limit"] ?? self::MAX_LIMIT);
        $result = $sql->get()->resultArray();

        return $result;
    }

    /**
     * Get an email template by its ID.
     *
     * @param int $emailTemplateID
     * @return array
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     */
    public function getEmailTemplateByID(int $emailTemplateID): array
    {
        $result = $this->createSql()
            ->select(["et.*"])
            ->from("emailTemplate et")
            ->where(["et.emailTemplateID" => $emailTemplateID])
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);
        if (empty($result)) {
            throw new NoResultsException("Email template not found.");
        }
        $emailTemplate = [$result];

        return array_shift($emailTemplate);
    }

    /**
     * Get the current max email template ID.
     *
     * @return int
     * @throws Exception
     */
    public function getMaxEmailTemplateID(): int
    {
        return (int) $this->createSql()
            ->select("emailTemplateID", "max", "maxEmailTemplateID")
            ->get($this->getTable())
            ->column("maxEmailTemplateID")[0];
    }

    /**
     * Save the recipe
     *
     * @param array $emailTemplateData
     * @param int|null $emailTemplateID
     * @return int
     * @throws Exception
     */
    public function saveEmailTemplate(array $emailTemplateData, ?int $emailTemplateID = null): int
    {
        // If we have a valid status then we will use it
        if (!isset($emailTemplateData["status"]) || !in_array($emailTemplateData["status"], self::STATUS_OPTIONS)) {
            $emailTemplateData["status"] = self::STATUS_INACTIVE;
        }
        $insert = false;
        // If $emailTemplateID is not provided, this is insert, get new ID, and check on status.
        if (!$emailTemplateID) {
            $maxEmailTemplateID = $this->getMaxEmailTemplateID();
            $emailTemplateData["emailTemplateID"] = $emailTemplateID = ++$maxEmailTemplateID;
            // By default, when creating a new email template, we will set the status to inactive
            $emailTemplateData["status"] = $emailTemplateData["status"] ?? self::STATUS_INACTIVE;
            $insert = true;
            $emailTemplate = [];
        } else {
            $emailTemplate = $this->getEmailTemplateByID($emailTemplateID);
        }
        //Default Template name
        $emailTemplateData["name"] = $emailTemplateData["name"] ?? "Untitled template - {$maxEmailTemplateID}";
        try {
            $this->database->beginTransaction();
            if (!$insert) {
                $this->update($emailTemplateData, ["emailTemplateID" => $emailTemplateID]);
                LogModel::logChange(
                    "Edit",
                    "emailTemplate",
                    array_merge($emailTemplateData, ["RecordID" => $emailTemplateID])
                );
            } else {
                $emailTemplateID = $this->insert($emailTemplateData);
                LogModel::logChange(
                    "Insert",
                    "emailTemplate",
                    array_merge($emailTemplateData, ["RecordID" => $emailTemplateID])
                );
            }
            $this->database->commitTransaction();
            // Log the template status update, if the status is updated
            if (!empty($emailTemplateData["status"]) && !empty($emailTemplate["status"] ?? "")) {
                ErrorLogger::notice("Updated email template status", [
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                    Logger::FIELD_TAGS => ["email templates", "recipe"],
                    "emailTemplateID" => $emailTemplateID,
                    "status" => $emailTemplateData["status"],
                    "updatedUserID" => $this->session->UserID,
                ]);
            }

            return $emailTemplateID;
        } catch (Exception $e) {
            $this->database->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Convert the email template values from json to array
     *
     * @param array $emailTemplates
     * @return array
     */
    private function formatEmailTemplateValues(array $emailTemplates): array
    {
        $list = true;
        if (!empty($emailTemplates) && !is_numeric(key($emailTemplates))) {
            $list = false;
            $emailTemplates = [$emailTemplates];
        }

        return $list ? $emailTemplates : array_shift($emailTemplates);
    }

    /**
     * Delete an email template
     *
     * @param int $emailTemplateID
     * @return bool
     * @throws Exception
     */
    public function deleteEmailTemplate(int $emailTemplateID): bool
    {
        $template = $this->getEmailTemplateByID($emailTemplateID);
        if (empty($template)) {
            throw new NoResultsException("Email template not found.");
        } elseif ($template["emailType"] === "system") {
            throw new InvalidArgumentException("System email templates cannot be deleted.");
        } elseif ($template["status"] === self::STATUS_DELETED) {
            throw new NoResultsException("Email template not found.");
        }

        $result = $this->update(["status" => self::STATUS_DELETED], ["emailTemplateID" => $emailTemplateID]);
        ErrorLogger::notice("Deleted template.", [
            Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
            Logger::FIELD_TAGS => ["email templates", "recipe"],
            "emailTemplateID" => $emailTemplateID,
            "updatedUserID" => $this->session->UserID,
        ]);
        return $result;
    }

    /**
     * Check there is email template having the provided name, skipping current template ID if provided.  Duplicate check.
     *
     * @param string $name
     * @param int|null $emailTemplateID
     * @return bool
     * @throws Exception
     */
    public function isUniqueName(string $name, ?int $emailTemplateID = null): bool
    {
        $where = [
            "name" => $name,
            "status <>" => self::STATUS_DELETED,
        ];

        if (!empty($emailTemplateID)) {
            $where["emailTemplateID <>"] = $emailTemplateID;
        }

        $result = $this->createSql()->getCount($this->getTable(), $where);
        return $result == 0;
    }

    /**
     * Validate the template name is unique
     *
     * @param int|null $emailTemplateID
     * @return callable
     */
    public function validateName(?int $emailTemplateID = null): callable
    {
        return function (string $name, ValidationField $field) use ($emailTemplateID) {
            if (!$this->isUniqueName($name, $emailTemplateID)) {
                $field->addError("Email template name already exists. Enter a unique name to proceed.", [
                    "code" => 403,
                ]);
            }
        };
    }

    /**
     * Process sending the email template.
     *
     * @param int $emailTemplateID
     * @param array $body
     *
     * @return bool
     * @throws ContainerException
     * @throws NoResultsException
     * @throws NotFoundException
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendEmailTemplate(int $emailTemplateID, array $body): bool
    {
        $emailTemplate = $this->getEmailTemplateByID($emailTemplateID);

        $testEmail = $this->getTestEmail($emailTemplate);

        $testEmail->subject($emailTemplate["subject"]);
        if (($emailTemplate["fromEmail"] ?? null) && ($emailTemplate["fromName"] ?? null)) {
            $testEmail->from($emailTemplate["fromEmail"] ?? "", $emailTemplate["fromName"] ?? "");
        }
        $user = $this->userModel->getID($body["destinationUserID"], DATASET_TYPE_ARRAY);
        $testEmail->to($body["destinationAddress"], $user["Name"]);

        $testEmail->send();

        return true;
    }

    /**
     * Sets up a new Gdn_Email object with a test email.
     *
     * @param array $testEmailParams Data matching {@link self::testEmailSchema()}.
     *
     * @return \Gdn_Email The email object with the test colors set.
     */
    public function getTestEmail(array $testEmailParams): \Gdn_Email
    {
        $email = new \Gdn_Email();
        $email->getEmailTemplate()->setFooterHtml($testEmailParams["footer"]);
        if (is_array($testEmailParams["emailConfig"] ?? null)) {
            foreach (self::EMAIL_CONFIG_FIELDS as $config) {
                $value = $testEmailParams["emailConfig"][$config] ?? null;
                if ($value !== null) {
                    $method = "set" . ucfirst($config);
                    $email->getEmailTemplate()->$method($value);
                }
            }
        }
        if (($testEmailParams["emailLogo"] ?? null) !== null) {
            $email->getEmailTemplate()->setImage($testEmailParams["emailLogo"]);
        }
        $email
            ->getEmailTemplate()
            ->setMessage($this->formatterService->renderHTML($testEmailParams["body"], "rich2"))
            ->setTitle($testEmailParams["subject"]);

        return $email;
    }
}
