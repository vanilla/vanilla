<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\Exception\ConfigurationException;
use Vanilla\ApiUtils;

/**
 * API Controller for the `/messages` resource.
 */
class MessagesApiController extends AbstractApiController {

    /** @var Gdn_Configuration */
    private $config;

    /** @var ConversationMessageModel */
    private $conversationMessageModel;

    /** @var ConversationModel */
    private $conversationModel;

    /** @var UserModel */
    private $userModel;

    /**
     * MessagesApiController constructor.
     *
     * @param Gdn_Configuration $config
     * @param ConversationMessageModel $conversationMessageModel
     * @param ConversationModel $conversationModel
     * @param UserModel $userModel
     */
    public function __construct(
        Gdn_Configuration $config,
        ConversationModel $conversationModel,
        ConversationMessageModel $conversationMessageModel,
        UserModel $userModel
    ) {
        $this->config = $config;
        $this->conversationMessageModel = $conversationMessageModel;
        $this->conversationModel = $conversationModel;
        $this->userModel = $userModel;
    }

    /**
     * Check that the user has moderation rights over conversations.
     *
     * @throw Exception
     */
    private function checkModerationPermission() {
        if (!$this->config->get('Conversations.Moderation.Allow', false)) {
            throw new ConfigurationException(t('The site is not configured for moderating conversations.'));
        }
        $this->permission('Conversations.Moderation.Manage');
    }

    /**
     * Get a conversation by its numeric ID.
     *
     * @param int $id The conversation ID.
     * @throws NotFoundException if the conversation could not be found.
     * @return array
     */
    private function conversationByID($id) {
        $row = $this->conversationModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Conversation');
        }

        return $row;
    }

//
//    Uncomment once ConversationMessagesModel::delete() is properly implemented.
//    See https://github.com/vanilla/vanilla/issues/5897 for details.
//
//    /**
//     * Delete a message.
//     *
//     * @param int $id The ID of the message.
//     * @throws NotFoundException if the message could not be found.
//     * @throws MethodNotAllowedException if Conversations.Moderation.Allow !== true.
//     */
//    public function delete($id) {
//        if (!$this->config->get('Conversations.Moderation.Allow', false)) {
//            throw new MethodNotAllowedException();
//        }
//
//        $this->permission('Conversations.Moderation.Manage');
//
//        $this->schema(['id:i' => 'The message ID'])->setDescription('Delete a message.');
//        $this->schema([], 'out');
//
//        $this->messageByID($id);
//        $this->conversationMessagesModel->deleteID($id);
//    }

    /**
     * Get the schema definition comprised of all available message fields.
     *
     * @return Schema
     */
    private function fullSchema() {
        /** @var Schema $schema */
        static $schema;

        if ($schema === null) {
            // Name this schema so that it can be read by swagger.
            $schema = $this->schema([
                'messageID:i' => 'The ID of the message.',
                'conversationID:i' => 'The ID of the conversation.',
                'body:s' => [
                    'maxLength' => $this->config->get('MaxLength', 2000),
                    'description' => 'The body of the message.',
                ],
                'insertUserID:i' => 'The user that created the message.',
                'insertUser?' => $this->getUserFragmentSchema(),
                'dateInserted:dt' => 'When the message was created.',
            ], 'Message');
        }

        return $schema;
    }

    /**
     * Get a message.
     *
     * @param int $id The ID of the message.
     * @throws NotFoundException if the message could not be found.
     * @return array
     */
    public function get($id) {
        $this->permission('Conversations.Conversations.Add');

        $this->schema([
            'id:i' => 'The message ID.'
        ], 'in')->setDescription('Get a message.');
        $out = $this->schema($this->fullSchema(), 'out');

        $message = $this->messageByID($id);

        $isInConversation = $this->conversationModel->inConversation($message['ConversationID'], $this->getSession()->UserID);

        if (!$isInConversation) {
            $this->checkModerationPermission();
        }

        $this->userModel->expandUsers($message, ['InsertUserID']);

        $message = $this->normalizeOutput($message);
        return $out->validate($message);
    }

    /**
     * List messages of a user.
     *
     * @param array $query The query string.
     * @return Data
     */
    public function index(array $query) {
        $this->permission('Conversations.Conversations.Add');

        $in = $this->schema([
                'conversationID:i?'=> 'Filter by conversation.',
                'insertUserID:i?' => 'Filter by author.',
                'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                    'default' => 1,
                    'minimum' => 1,
                ],
                'limit:i?' => [
                    'description' => 'Desired number of items per page.',
                    'default' => $this->config->get('Conversations.Messages.PerPage', 50),
                    'minimum' => 1,
                    'maximum' => 100
                ],
                'expand?' => ApiUtils::getExpandDefinition(['insertUser'])
            ], 'in')
            ->requireOneOf(['conversationID', 'insertUserID'])
            ->setDescription('List user messages.');
        $out = $this->schema([':a' => $this->fullSchema()], 'out');

        $query = $this->filterValues($query);
        $query = $in->validate($query);

        $where = [];
        $requireModerationPermission = false;

        if (!empty($query['insertUserID'])) {
            if ($query['insertUserID'] !== $this->getSession()->UserID) {
                $requireModerationPermission = true;
            }
            $userID = $query['insertUserID'];
            $where['InsertUserID'] = $userID;
        }

        if (!empty($query['conversationID'])) {
            $this->conversationByID($query['conversationID']);

            $isInConversation = $this->conversationModel->inConversation($query['conversationID'], $this->getSession()->UserID);
            if (!$isInConversation) {
                $requireModerationPermission = true;
            }

            $where['ConversationID'] = $query['conversationID'];
        }

        if ($requireModerationPermission) {
            $this->checkModerationPermission();
        }

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        $messages = $this->conversationMessageModel->getWhere(
            $where,
            'DateInserted',
            'desc',
            $limit,
            $offset
        )->resultArray();

        // Expand associated rows.
        $this->userModel->expandUsers(
            $messages,
            $this->resolveExpandFields($query, ['insertUser' => 'InsertUserID'])
        );

        array_walk($messages, function(&$message) {
            $message = $this->normalizeOutput($message);
        });

        $result = $out->validate($messages);

        $paging = ApiUtils::numberedPagerInfo(
            $this->conversationMessageModel->getCountWhere($where),
            '/api/v2/messages',
            $query,
            $in
        );

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Get a message by its numeric ID.
     *
     * @param int $id The message ID.
     * @throws NotFoundException if the message could not be found.
     * @return array
     */
    private function messageByID($id) {
        $message = $this->conversationMessageModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$message) {
            throw new NotFoundException('Message');
        }

        return $message;
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @return array Return a Schema record.
     */
    public function normalizeOutput(array $dbRecord) {
        $this->formatField($dbRecord, 'Body', $dbRecord['Format']);

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        return $schemaRecord;
    }

//
//    Uncomment and test once ConversationMessagesModel handles messages update.
//    See https://github.com/vanilla/vanilla/issues/5913 for details.
//
//    /**
//     * Update a message.
//     *
//     * @param int $id The ID of the message.
//     * @param array $body The request body.
//     * @throws NotFoundException If the message was not found.
//     * @throws ServerException If the message could not be updated.
//     */
//    public function patch($id, array $body) {
//        $this->permission('Conversations.Conversations.Add');
//
//        $in = $this->schema(['format', 'body'],'in')
//            ->add($this->fullSchema())
//            ->setDescription('Update a message.');
//        $out = $this->schema($this->fullSchema(), 'out');
//
//        $body = $in->validate($body);
//
//        $message = $this->messageByID($id);
//
//        if ($message['InsertUserID'] !== $this->getSession()->UserID) {
//            $this->checkModerationPermission();
//        }
//
//        $conversation = $this->conversationByID($message['ConversationID']);
//
//        $this->conversationMessageModel->save($body, $conversation);
//    }

    /**
     * Add a message.
     *
     * @param array $body The request body.
     * @throws NotFoundException If the conversation was not found.
     * @throws ClientException If trying to add message to conversation you are not a participant of.
     * @throws ServerException If the message could not be created.
     * @return array
     */
    public function post(array $body) {
        $this->permission('Conversations.Conversations.Add');

        $in = $this->postSchema('in')->setDescription('Add a message.');
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body);

        $conversation = $this->conversationByID($body['conversationID']);
        if (!$this->conversationModel->inConversation($conversation['ConversationID'], $this->getSession()->UserID)) {
            throw new ClientException('You can not add a message to a conversation that you are not a participant of.');
        }

        $messageData = ApiUtils::convertInputKeys($body);
        $messageID = $this->conversationMessageModel->save($messageData, $conversation);
        $this->validateModel($this->conversationMessageModel, true);
        if (!$messageID) {
            throw new ServerException('Unable to insert message.', 500);
        }

        $message = $this->messageByID($messageID);
        $message = $this->normalizeOutput($message);
        return $out->validate($message);
    }

    /**
     * Get a post schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function postSchema($type) {
        static $postSchema;

        if ($postSchema === null) {
            $postSchema = $this->schema(
                Schema::parse([
                    'conversationID',
                    'body',
                    'format:s?' => 'The input format of the record.',
                ])->add($this->fullSchema()),
                'MessagePost'
            );
        }

        return $this->schema($postSchema, $type);
    }

}
