<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\APIv2;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use UsersApiController;
use Vanilla\ApiUtils;
use Vanilla\Dashboard\Models\ProductMessageModel;
use Vanilla\Models\FormatSchema;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Permissions;

/**
 * /api/v2/product-messages
 */
class ProductMessagesApiController extends AbstractApiController
{
    public function __construct(
        private ProductMessageModel $productMessageModel,
        private UsersApiController $usersApiController
    ) {
    }

    /**
     * GET /api/v2/product-messages
     *
     * @return Data
     */
    public function index(): Data
    {
        $this->permission(["site.manage", "settings.view"]);
        $out = Schema::parse([":a" => $this->getReadSchema()]);

        $messages = $this->productMessageModel->select();
        $this->productMessageModel->normalizeRows($messages);

        $result = $out->validate($messages);

        return new Data($result);
    }

    /**
     * GET /api/v2/product-messages/{productMessageID}
     *
     * @param string $id
     * @return Data
     */
    public function get(string $id): Data
    {
        $this->permission(["site.manage", "settings.view"]);
        $out = $this->getReadSchema();

        $message = $this->productMessageModel->selectSingle(
            where: [
                "productMessageID" => $id,
            ]
        );
        $this->productMessageModel->normalizeRows($message);

        $result = $out->validate($message);

        return new Data($result);
    }

    /**
     * POST /api/v2/product-messages/{productMessageID}/dismiss
     *
     * @param string $id
     *
     * @return Data
     */
    public function post_dismiss(string $id): Data
    {
        $this->permission(["site.manage", "settings.view"]);

        // Make sure it exists.
        $this->productMessageModel->selectSingle(where: ["productMessageID" => $id]);
        $this->productMessageModel->dismissMessage($id);

        return new Data([
            "dismissed" => true,
        ]);
    }

    /**
     * POST /api/v2/product-messages/dismiss-all
     *
     * @return void
     */
    public function post_dismissAll(): void
    {
        $this->permission(["site.manage", "settings.view"]);

        $this->productMessageModel->dismissAll();
    }

    ///
    /// Administrative endpoints
    ///

    /**
     * POST /api/v2/product-messages
     *
     * @param array $body
     * @return Data
     */
    public function post(array $body): Data
    {
        $this->permission("admin.only");
        $in = $this->postPatchSchema();

        $body = $in->validate($body);

        $body["foreignInsertUser"] = $this->productMessageModel->getForeignUser($body["foreignInsertUserID"]);
        unset($body["foreignInsertUserID"]);
        $body["productMessageType"] = ProductMessageModel::TYPE_PERSONAL;

        $productMessageID = $this->productMessageModel->insert($body);
        return $this->get($productMessageID);
    }

    /**
     * PATCH /api/v2/product-messages/{productMessageID}
     *
     * @param string $id
     * @param array $body
     * @return Data
     */
    public function patch(string $id, array $body): Data
    {
        $this->permission("admin.only");
        $in = $this->postPatchSchema();

        // Ensure we have existing
        $existing = $this->get($id);

        // Ensure this is the right message type.
        if ($existing["productMessageType"] !== ProductMessageModel::TYPE_PERSONAL) {
            throw new ClientException("Only personal messages can be modified", 400);
        }

        $body = $in->validate($body, sparse: true);

        if (isset($body["foreignInsertUserID"])) {
            $body["foreignInsertUser"] = $this->productMessageModel->getForeignUser($body["foreignInsertUserID"]);
            unset($body["foreignInsertUserID"]);
        }

        $this->productMessageModel->update(
            where: [
                "productMessageID" => $id,
            ],
            set: $body
        );

        return $this->get($id);
    }

    /**
     * DELETE /api/v2/product-messages/{productMessageID}
     *
     * @param string $id
     *
     * @return Data
     */
    public function delete(string $id): Data
    {
        $this->permission("admin.only");
        // Ensure we have existing
        $existing = $this->get($id);

        // Ensure this is the right message type.
        if ($existing["productMessageType"] !== ProductMessageModel::TYPE_PERSONAL) {
            throw new ClientException("Only personal messages can be deleted.", 400);
        }

        $this->productMessageModel->delete(
            where: [
                "productMessageID" => $id,
            ]
        );

        return new Data([], ["status" => 204]);
    }

    /**
     * GET /api/v2/product-messages/{productMessageID}/edit
     *
     * @param string $id
     * @return Data
     * @throws \Exception
     */
    public function get_edit(string $id): Data
    {
        $this->permission("admin.only");
        $out = $this->postPatchSchema();
        $existing = $this->productMessageModel->selectSingle(
            where: [
                "productMessageID" => $id,
            ]
        );

        if ($existing["productMessageType"] !== ProductMessageModel::TYPE_PERSONAL) {
            throw new ClientException("Only personal messages can be edited.", 400);
        }

        $existing["foreignInsertUserID"] = $existing["foreignInsertUser"]["userID"];

        $result = $out->validate($existing);

        return new Data($result);
    }

    /**
     * GET /api/v2/product-messages/{productMessageID}/viewers
     *
     * @param string $id
     * @return Data
     */
    public function get_viewers(string $id): Data
    {
        $this->permission("admin.only");
        // Make sure it exists.
        $this->productMessageModel->selectSingle(
            where: [
                "productMessageID" => $id,
            ]
        );

        $viewerUserIDs = $this->productMessageModel->selectViewerUserIDs($id);
        if (empty($viewerUserIDs)) {
            return new Data([]);
        }

        return $this->usersApiController->index([
            "userID" => $viewerUserIDs,
        ]);
    }

    /**
     * POST /api/v2/product-messages/sync-announcements
     *
     * @return Data
     */
    public function post_syncAnnouncements(): Data
    {
        $this->permission("admin.only");
        $result = $this->productMessageModel->syncAnnouncements();
        return new Data($result);
    }

    /**
     * @return Data
     */
    public function get_foreignUsers(): Data
    {
        $this->permission("admin.only");
        $out = Schema::parse([":a" => $this->productMessageModel->foreignUserFragmentSchema()]);

        $users = $this->productMessageModel->listForeignUsers();

        $result = $out->validate($users);

        return new Data($result);
    }

    ///
    /// Schemas
    ///

    public function getReadSchema(): Schema
    {
        return Schema::parse([
            "productMessageID",
            "productMessageType",
            "announcementType:s",
            "name",
            "body",
            "foreignUrl",
            "dateInserted",
            "isDismissed",
            "dateDismissed?",
            "ctaUrl?",
            "ctaLabel?",
            "countViewers:i",
        ])
            ->add($this->productMessageModel->getReadSchema())
            ->merge(
                Schema::parse([
                    "foreignInsertUser" => $this->productMessageModel->foreignUserFragmentSchema(),
                ])
            );
    }

    /**
     * @return Schema
     */
    public function postPatchSchema(): Schema
    {
        return Schema::parse([
            "name:s",
            "body:s",
            "format" => new FormatSchema(),
            "announcementType:s" => [
                "enum" => ProductMessageModel::ANNOUNCEMENT_TYPES,
            ],
            "foreignInsertUserID:i",
            "ctaLabel:s?",
            "ctaUrl:s?",
        ]);
    }
}
