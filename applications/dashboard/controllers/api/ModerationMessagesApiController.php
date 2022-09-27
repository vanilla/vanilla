<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use AbstractApiController;
use CategoryModel;
use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use MessageModel;
use Vanilla\ApiUtils;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\SchemaUtils;

/**
 * Api controller for moderation messages.
 */
class ModerationMessagesApiController extends AbstractApiController
{
    /** @var MessageModel */
    private $messageModel;

    /** @var CategoryModel */
    private $categoryModel;

    /**
     * {@inheritDoc}
     *
     * @param MessageModel $messageModel
     * @param CategoryModel $categoryModel
     */
    public function __construct(MessageModel $messageModel, CategoryModel $categoryModel)
    {
        $this->messageModel = $messageModel;
        $this->categoryModel = $categoryModel;
    }

    /**
     * Get a list of moderation messages.
     *
     * @param array $query
     * @return Data
     */
    public function index(array $query = []): Data
    {
        $this->permission("session.valid");

        $validatedQuery = $this->messageModel->getIndexSchema()->validate($query);

        // If the schema has set this to null, it means we should get all messages.
        if (isset($validatedQuery["isEnabled"]) && $validatedQuery["isEnabled"] === null) {
            unset($validatedQuery["isEnabled"]);
        }

        $normalizedQuery = $this->normalizeQuery($validatedQuery);

        $messages = $this->messageModel->getMessages($normalizedQuery);

        // Filter for category permissions.
        $permissionFilteredMessages = [];
        if (Gdn::session()->checkPermission("community.moderate")) {
            foreach ($messages as $message) {
                $permissionFilteredMessages[] = $this->messageModel->normalizeOutput($message);
            }
        } else {
            foreach ($messages as $message) {
                if ($this->categoryModel::checkPermission($message["RecordID"], "discussions.view")) {
                    $message = $this->messageModel->normalizeOutput($message);
                    $permissionFilteredMessages[] = $message;
                }
            }
        }

        SchemaUtils::validateArray($permissionFilteredMessages, $this->messageModel->getOutputSchema(), true);

        return new Data($permissionFilteredMessages);
    }

    /**
     * Get a single message.
     *
     * @param int $id
     * @return Data
     */
    public function get(int $id): Data
    {
        $this->permission("session.valid");
        $message = $this->lookupMessage($id);

        if (strtolower($message["recordType"]) === "category") {
            $this->permission("discussions.view", $message["recordID"]);
        }

        return new Data($message);
    }

    /**
     * Post a moderation message.
     *
     * @param array $body
     * @return Data
     */
    public function post(array $body): Data
    {
        $this->permission("community.moderate");
        $inputSchema = $this->messageModel->getPostSchema();
        $validatedPost = $inputSchema->validate($body);

        // Make sure the category actually exists
        if (isset($validatedPost["recordID"]) && $validatedPost["recordType"] === "category") {
            $category = CategoryModel::categories($validatedPost["recordID"]);
            if (!$category) {
                throw new NotFoundException("Category");
            }
        }

        $messageID = $this->messageModel->save($validatedPost);
        $this->validateModel($this->messageModel);
        $savedMessage = $this->lookupMessage($messageID);
        return new Data($savedMessage);
    }

    /**
     * Edit a moderation message.
     *
     * @param int $id
     * @param array $body
     * @return Data
     */
    public function patch(int $id, array $body): Data
    {
        $this->permission("community.moderate");
        $message = $this->messageModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$message) {
            throw new NotFoundException("Message");
        }
        $messageToPatch = $this->messageModel->normalizeOutput($message);
        $patchedMessage = array_merge($messageToPatch, $body);
        if (isset($body["recordID"]) && $patchedMessage["recordType"] === "category") {
            $category = CategoryModel::categories($body["recordID"]);
            if (!$category) {
                throw new NotFoundException("Category");
            }
        }
        $postSchema = $this->messageModel->getPostSchema();
        $postSchema->merge(Schema::parse(["moderationMessageID:i"]));
        $validatedPatchedMessage = $postSchema->validate($patchedMessage);
        $messageID = $this->messageModel->save($validatedPatchedMessage);
        $this->validateModel($this->messageModel);
        $savedMessage = $this->lookupMessage($messageID);
        return new Data($savedMessage);
    }

    /**
     * Delete a message.
     *
     * @param int $id
     * @throws NotFoundException Throws an exception if the message isn't found.
     */
    public function delete(int $id): void
    {
        $this->permission("community.moderate");

        $message = $this->lookupMessage($id);

        $this->messageModel->deleteID($message["moderationMessageID"]);
    }

    /**
     * Dismiss a message (for the session user).
     *
     * @param int $id
     * @return Data
     * @throws ForbiddenException Throws an exception if the message isn't found, the  or if it's not dismissible.
     */
    public function put_dismiss(int $id): Data
    {
        $this->permission("session.valid");
        $message = $this->messageModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$message["AllowDismiss"]) {
            throw new ForbiddenException("Message is not dismissible.");
        }

        if (strtolower($message["RecordType"]) === "category") {
            $this->permission("discussions.view", $message["RecordType"]);
        }

        $dismissedMessages = Gdn::session()->getPreference("DismissedMessages", []);
        $dismissedMessages[] = $id;
        Gdn::session()->setPreference("DismissedMessages", $dismissedMessages);
        return new Data(["dismissed" => true]);
    }

    /**
     * Normalizes the query so that the keys match the DB columns.
     *
     * @param array $query
     * @return array
     */
    private function normalizeQuery(array $query = []): array
    {
        if (empty($query)) {
            return [];
        }

        if (array_key_exists("isEnabled", $query)) {
            if ($query["isEnabled"] !== null) {
                $query["Enabled"] = $query["isEnabled"];
            }
            unset($query["isEnabled"]);
        }

        return ArrayUtils::pascalCase($query);
    }

    /**
     * Get a message from the model and perform normalization and validation.
     *
     * @param int $id
     * @return array
     * @throws NotFoundException Throws an exception if the message isn't found.
     */
    private function lookupMessage(int $id): array
    {
        $savedMessage = $this->messageModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$savedMessage) {
            throw new NotFoundException("Message");
        }
        $outputNormalizedMessage = $this->messageModel->normalizeOutput($savedMessage);
        $outputSchema = $this->messageModel->getOutputSchema();
        $validatedOutput = $outputSchema->validate($outputNormalizedMessage);
        return $validatedOutput;
    }
}
