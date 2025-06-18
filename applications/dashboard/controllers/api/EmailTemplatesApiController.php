<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Container\ContainerException;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Dashboard\EmailTemplate\Schema\EmailTemplateInputSchema;
use Vanilla\Dashboard\Models\EmailTemplateModel;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\Web\Controller;

/**
 * Email Template api-controller
 */
class EmailTemplatesApiController extends Controller
{
    private Schema $idParamSchema;

    /**
     * Email Template constructor
     *
     * @param EmailTemplateModel $emailTemplateModel
     */
    public function __construct(private EmailTemplateModel $emailTemplateModel)
    {
    }

    /**
     * Get Email Template List
     *
     * @param array $query
     * @return Data
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function index(array $query): Data
    {
        $this->permission("Garden.Settings.Manage");
        if (!EmailTemplateModel::isEmailTemplatesEnabled()) {
            throw new ForbiddenException("Email Template not enabled.");
        }

        $in = $this->schema(new EmailTemplateInputSchema());
        $query = $in->validate($query);
        $out = $this->schema(
            [
                ":a" => $this->emailTemplateModel->getEmailTemplateSchema(),
            ],
            "out"
        );
        $result = $out->validate($this->emailTemplateModel->getEmailTemplates($query));
        return new Data($result);
    }

    /**
     * Get specific email template.
     *
     * @param int $emailTemplateID
     * @return Data
     */
    public function get(int $emailTemplateID): Data
    {
        $this->permission("Garden.Settings.Manage");
        if (!EmailTemplateModel::isEmailTemplatesEnabled()) {
            throw new ForbiddenException("Email Template not enabled.");
        }

        $out = $this->schema($this->emailTemplateModel->getEmailTemplateSchema(), "out");
        $result = $out->validate($this->emailTemplateModel->getEmailTemplateByID($emailTemplateID));
        return new Data($result);
    }

    /**
     * Add new Email Template
     *
     * @param array $body
     * @return Data
     * @throws ClientException
     * @throws ContainerException
     * @throws HttpException
     * @throws NoResultsException
     * @throws PermissionException
     * @throws ValidationException
     * @throws \Garden\Container\NotFoundException
     */
    public function post(array $body): Data
    {
        $this->permission("Garden.Settings.Manage");

        if (!EmailTemplateModel::isEmailTemplatesEnabled()) {
            throw new ForbiddenException("Email Template not enabled.");
        }

        $in = $this->schema($this->getEmailTemplatePostSchema())->addValidator(
            "name",
            $this->emailTemplateModel->validateName()
        );
        $body = $in->validate($body);
        $emailTemplateID = $this->emailTemplateModel->saveEmailTemplate($body);

        $out = $this->schema($this->emailTemplateModel->getEmailTemplateSchema(), "out");
        $emailTemplate = $this->emailTemplateModel->getEmailTemplateByID($emailTemplateID);
        return new Data($out->validate($emailTemplate));
    }

    /**
     * Update an existing email template
     *
     * @param int $id
     * @param array $body
     * @return Data
     * @throws ContainerException
     * @throws HttpException
     * @throws NoResultsException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
     * @throws \Garden\Container\NotFoundException
     */
    public function patch(int $id, array $body = []): Data
    {
        $this->permission("Garden.Settings.Manage");
        if (!EmailTemplateModel::isEmailTemplatesEnabled()) {
            throw new ForbiddenException("Email Template not enabled.");
        }

        $existingTemplate = $this->emailTemplateModel->getEmailTemplateByID($id);
        if ($existingTemplate["status"] === EmailTemplateModel::STATUS_DELETED) {
            throw new NotFoundException("Email Template not found.");
        }
        $in = $this->schema($this->getEmailTemplatePatchSchema())->addValidator(
            "name",
            $this->emailTemplateModel->validateName($id)
        );
        $body = $in->validate($body);
        $this->emailTemplateModel->saveEmailTemplate($body, $id);
        $out = $this->schema($this->emailTemplateModel->getEmailTemplateSchema(), "out");
        $template = $this->emailTemplateModel->getEmailTemplateByID($id);
        return new Data($out->validate($template));
    }

    /**
     * Delete an existing email template
     *
     * @param int $id
     * @return void
     * @throws ContainerException
     * @throws HttpException
     * @throws NoResultsException
     * @throws PermissionException
     * @throws \Garden\Container\NotFoundException
     */
    public function delete(int $id): void
    {
        $this->permission("Garden.Settings.Manage");
        if (!EmailTemplateModel::isEmailTemplatesEnabled()) {
            throw new ForbiddenException("Email Template not enabled.");
        }
        $this->idParamSchema()->setDescription("Delete an email template.");
        $this->emailTemplateModel->deleteEmailTemplate($id);
    }

    /**
     * POST /api/v2/email-templates/preview
     *
     * Send email template
     *
     * @param int $id
     * @param array $body
     *
     * @return void
     * @throws ForbiddenException
     * @throws HttpException
     * @throws PermissionException
     */
    public function post_preview(int $id, array $body): void
    {
        $this->permission("Garden.Settings.Manage");
        if (!EmailTemplateModel::isEmailTemplatesEnabled()) {
            throw new ForbiddenException("Email Template not enabled.");
        }
        $this->idParamSchema()->setDescription("Preview an email template.");
        $in = Schema::parse([
            "destinationAddress:s" => [
                "format" => "email",
            ],
            "destinationUserID:i" => [
                "format" => "userID",
            ],
        ]);
        $body = $in->validate($body);
        $this->emailTemplateModel->sendEmailTemplate($id, $body);
    }

    /**
     * Get an ID-only recipe record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema(string $type = "in"): Schema
    {
        if (empty($this->idParamSchema)) {
            $this->idParamSchema = $this->schema(Schema::parse(["id:i" => "The recipe ID."]), $type);
        }

        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * Return schema for the post email template endpoint
     *
     * @return Schema
     */
    private function getEmailTemplatePostSchema(): Schema
    {
        $schema = Schema::parse([
            "name:s",
            "emailType:s?",
            "fromEmail:s",
            "fromName:s",
            "subject:s",
            "body:s",
            "footer:s",
            "emailLogo:s?",
            "emailConfig:s?",
            "status:s?" => [
                "enum" => [EmailTemplateModel::STATUS_ACTIVE, EmailTemplateModel::STATUS_INACTIVE],
            ],
        ]);

        return $schema;
    }

    /**
     * Return schema for the patch email template endpoint
     *
     * @return Schema
     */
    private function getEmailTemplatePatchSchema(): Schema
    {
        $schema = Schema::parse([
            "name:s?",
            "emailType:s?",
            "fromEmail:s?",
            "fromName:s?",
            "subject:s?",
            "body:s?",
            "footer:s?",
            "emailLogo:s?",
            "emailConfig:s?",
            "status:s?" => [
                "enum" => [EmailTemplateModel::STATUS_ACTIVE, EmailTemplateModel::STATUS_INACTIVE],
            ],
        ]);

        return $schema;
    }
}
