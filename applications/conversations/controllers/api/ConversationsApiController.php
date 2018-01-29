<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\Exception\ConfigurationException;
use Vanilla\ApiUtils;

/**
 * API Controller for the `/conversations` resource.
 */
class ConversationsApiController extends AbstractApiController {

    /** @var Gdn_Configuration */
    private $config;

    /** @var ConversationModel */
    private $conversationModel;

    /** @var UserModel */
    private $userModel;

    /**
     * ConversationsApiController constructor.
     *
     * @param Gdn_Configuration $config
     * @param ConversationModel $conversationModel
     * @param UserModel $userModel
     */
    public function __construct(
        Gdn_Configuration $config,
        ConversationModel $conversationModel,
        UserModel $userModel
    ) {
        $this->config = $config;
        $this->conversationModel = $conversationModel;
        $this->userModel = $userModel;
    }

    /**
     * Check that the user has moderation rights over conversations.
     *
     * @throws ConfigurationException Throws an exception when the site is not configured for moderating conversations.
     * @throws \Vanilla\Exception\PermissionException Throws an
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
     * @param int|null $viewingUserID The user viewing the conversation. Should only be set if user is part of the conversation.
     * @param int $participants The max number of participants to join to the conversation.
     * If zero then the participants aren't joined at all.
     * @throws NotFoundException Throws an exception if the conversation could not be found.
     * @return array
     */
    private function conversationByID($id, $viewingUserID = null, $participants = 0) {
        if ($viewingUserID) {
            $options = ['viewingUserID' => $viewingUserID];
        } else {
            $options = [];
        }

        $conversation = $this->conversationModel->getID($id, DATASET_TYPE_ARRAY, $options);
        if (!$conversation) {
            throw new NotFoundException('Conversation');
        }

        if ($participants) {
            $data = [&$conversation];
            $this->conversationModel->joinParticipants($data, $participants);
        }

        return $conversation;
    }

//
//    Uncomment and test once ConversationModel::delete() is properly implemented.
//    See https://github.com/vanilla/vanilla/issues/5897 for details.
//
//    /**
//     * Delete a conversation.
//     *
//     * @param int $id The ID of the conversation.
//     * @≠=throws NotFoundException if the conversation could not be found.
//     * @throws MethodNotAllowedException if Conversations.Moderation.Allow !== true.
//     */
//    public function delete($id) {
//        if (!$this->config->get('Conversations.Moderation.Allow', false)) {
//            throw new MethodNotAllowedException();
//        }
//
//        $this->permission('Conversations.Moderation.Manage');
//
//        $this->schema(['id:i' => 'The conversation ID'])->setDescription('Delete a conversation.');
//        $this->schema([], 'out');
//
//        $this->conversationByID($id);
//        $this->conversationModel->deleteID($id);
//    }


    /**
     * Leave a conversation.
     *
     * @param int $id The ID of the conversation.
     * @throws NotFoundException if the conversation could not be found.
     */
    public function delete_leave($id) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamSchema()->setDescription('Leave a conversation.');
        $this->schema([], 'out');

        $this->conversationByID($id);

        $this->conversationModel->clear($id, $this->getSession()->UserID);
    }

    /**
     * Get the schema definition comprised of all available conversation fields.
     *
     * @return Schema
     */
    private function fullSchema() {
        /** @var Schema $schema */
        static $schema;

        if ($schema === null) {
            $schemaDefinition = [
                'conversationID:i' => 'The ID of the conversation.',
                'name:s' => 'The name of the conversation.',
                'body:s' => 'The most recent unread message in the conversation.',
                'url:s' => 'The URL of the conversation.',
                'dateInserted:dt' => 'When the conversation was created.',
                'insertUserID:i' => 'The user that created the conversation.',
                'insertUser:o?' => $this->getUserFragmentSchema(),
                'countParticipants:i' => 'The number of participants on the conversation.',
                'participants?' => $this->getParticipantsSchema(),
                'countMessages:i' => 'The number of messages on the conversation.',
                'unread:bool?' => 'Whether the conversation has an unread indicator.',
                'countUnread:int?' => 'The number of unread messages.',
                'lastMessage:o?' => [
                    'insertUserID:i' => 'The author of the your most recent message.',
                    'dateInserted:dt' => 'The date of the message.',
                    'insertUser' => $this->getUserFragmentSchema(),
                ]
//                'countReadMessages:n|i?' => 'The number of unread messages by the current user on the conversation.',
//                'dateLastViewed:n|dt?' => 'When the conversation was last viewed by the current user.',
            ];

            // We unset to preserve the order of the parameters.
            if (!$this->config->get('Conversations.Subjects.Visible', false)) {
                unset($schemaDefinition['name:s?']);
            }

            // Name this schema so that it can be read by swagger.
            $schema = $this->schema($schemaDefinition, 'Conversation');
        }

        return $schema;
    }

    /**
     * Get a conversation.
     *
     * @param int $id The ID of the conversation.
     * @throws NotFoundException if the conversation could not be found.
     * @return array
     */
    public function get($id) {
        $this->permission('Conversations.Conversations.Add');

        $this->idParamSchema()->setDescription('Get a conversation.');
        $out = $this->schema($this->fullSchema(), 'out');

        $isInConversation = $this->conversationModel->inConversation($id, $this->getSession()->UserID);
        if ($isInConversation) {
            $viewingUserID = $this->getSession()->UserID;
        } else {
            $viewingUserID = null;
        }

        $conversation = $this->conversationByID($id, $viewingUserID, 5);

        // We check for the moderation permission after we get the conversation to make sure that it actually exists.
        if (!$isInConversation) {
            $this->checkModerationPermission();
        }

        $this->userModel->expandUsers($conversation, ['InsertUserID']);
        $conversation = $this->normalizeOutput($conversation);

        return $out->validate($conversation);
    }

    /**
     * Get the conversation participants.
     *
     * @param int $id The ID of the conversation.
     * @param array $query The query string.
     * @throws NotFoundException if the conversation could not be found.
     * @return Data
     */
    public function get_participants($id, array $query) {
        $this->permission('Conversations.Conversations.Add');

        $this->idParamSchema();

        $in = $this->schema([
            'status:s?' => [
                'description' => 'Filter by participant status.',
                'enum' => ['all', 'participating', 'deleted'],
                'default' => 'participating'
            ],
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => 5,
                'minimum' => 5,
                'maximum' => 100
            ],
            'expand?' => ApiUtils::getExpandDefinition(['user'])
        ], 'in')->setDescription('Get participants of a conversation.');
        $out = $this->schema($this->getParticipantsSchema(), 'out');

        $query = $this->filterValues($query);
        $query = $in->validate($query);

        $this->conversationByID($id);

        if (!$this->conversationModel->inConversation($id, $this->getSession()->UserID)) {
            $this->checkModerationPermission();
        }

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        $active = null;
        $status = $query['status'];
        if ($status == 'deleted') {
            $active = false;
        } elseif ($status == 'participating') {
            $active = true;
        }

        $conversationMembers = $this->conversationModel->getConversationMembers($id, false, $limit, $offset, $active);
        $data = array_values($conversationMembers);

        // Expand associated rows.
        $this->userModel->expandUsers(
            $data,
            $this->resolveExpandFields($query, ['user' => 'UserID'])
        );
        $data = array_map([$this, 'normalizeParticipantOutput'], $data);

        $result = $out->validate($data);

        $paging = ApiUtils::numberedPagerInfo(
            $this->conversationModel->getConversationMembersCount($id, $active),
            "/api/v2/conversations/$id/participants",
            $query,
            $in
        );

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * List conversations of a user.
     *
     * @param array $query The query string.
     * @return Data
     */
    public function index(array $query) {
        $this->permission('Conversations.Conversations.Add');

        $in = $this->schema([
            'insertUserID:i?' => 'Filter by author.',
            'participantUserID:i?' => 'Filter by participating user. (Has no effect if insertUserID is used)',
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => $this->config->get('Conversations.Conversations.PerPage', 50),
                'minimum' => 1,
                'maximum' => 100
            ],
            'expand?' => ApiUtils::getExpandDefinition(['insertUser', 'lastInsertUser']),
        ], 'in')->setDescription('List user conversations.');
        $out = $this->schema([':a' => $this->fullSchema()], 'out');

        $query = $this->filterValues($query);
        $query = $in->validate($query);

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        if (!empty($query['insertUserID'])) {
            if ($query['insertUserID'] !== $this->getSession()->UserID) {
                $this->checkModerationPermission();
            }

            $conversations = $this->conversationModel->getWhere(
                ['InsertUserID' => $query['insertUserID']],
                'DateInserted',
                'Desc',
                $limit,
                $offset
            )->resultArray();

            $this->conversationModel->joinParticipants($conversations);
        } else {
            $participantUserID = isset($query['participantUserID']) ? $query['participantUserID'] : $this->getSession()->UserID;

            if ($participantUserID !== $this->getSession()->UserID) {
                $this->checkModerationPermission();
            }

            $conversations = $this->conversationModel->get2($participantUserID, $offset, $limit)->resultArray();
        }

        // Expand associated rows.
        $this->userModel->expandUsers(
            $conversations,
            $this->resolveExpandFields(
                $query,
                ['insertUser' => 'InsertUserID', 'lastInsertUser' => 'LastInsertUserID']
            )
        );
        $conversations = array_map([$this, 'normalizeOutput'], $conversations);

        $result = $out->validate($conversations);

        $paging = ApiUtils::morePagerInfo($result, '/api/v2/conversations', $query, $in);

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Get an ID-only conversation record schema.
     *
     * @return Schema Returns a schema object.
     */
    private function idParamSchema() {
        return $this->schema(['id:i' => 'The conversation ID.'], 'in');
    }

    /**
     * Add a conversation.
     *
     * @param array $body The request body.
     * @throws ServerException If the conversation could not be created.
     * @return array
     */
    public function post(array $body) {
        $this->permission('Conversations.Conversations.Add');

        $in = $this->postSchema('in')->setDescription('Add a conversation.');
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body);
        $conversationData = $this->normalizeInput($body);

        $conversationID = $this->conversationModel->save($conversationData, ['ConversationOnly' => true]);
        $this->validateModel($this->conversationModel, true);
        if (!$conversationID) {
            throw new ServerException('Unable to insert conversation.', 500);
        }

        $conversation = $this->conversationByID($conversationID, $this->getSession()->UserID, 5);
        $conversation = $this->normalizeOutput($conversation);
        return $out->validate($conversation);
    }

    /**
     * Add participants to a conversation.
     *
     * @param int $id The ID of the conversation.
     * @param array $body The request body.
     * @throws NotFoundException if the conversation could not be found.
     * @throws ServerException If the participants could not be added.
     * @return array
     */
    public function post_participants($id, array $body) {
        $this->permission('Conversations.Conversations.Add');

        $this->idParamSchema();

        $in = $this->postSchema('in')->setDescription('Add participants to a conversation.');
        $out = $this->schema($this->getParticipantsSchema(), 'out');

        $body = $in->validate($body);

        // Not found exception thrown if the conversation does not exist.
        $this->conversationByID($id);

        if (!$this->conversationModel->inConversation($id, $this->getSession()->UserID)) {
            $this->checkModerationPermission();
        }

        $success = $this->conversationModel->addUserToConversation($id, $body['participantUserIDs']);
        if (!$success) {
            throw new ServerException('Unable to add participants.', 500);
        }

        $conversationMembers = $this->conversationModel->getConversationMembers(
            ['conversationID' => $id, 'UserID' => $body['participantUserIDs']],
            false
        );
        $data = array_values($conversationMembers);

        $this->userModel->expandUsers($data, ['UserID']);
        $data = array_map([$this, 'normalizeParticipantOutput'], $data);

        return $out->validate($data);
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
            $inSchema = [
                'participantUserIDs:a' => [
                    'items' => [
                        'type'  => 'integer',
                    ],
                    'description' => 'List of userID of the participants.',
                ],
                'name' => null,
            ];
            if (!$this->config->get('Conversations.Subjects.Visible', false)) {
                unset($inSchema['name']);
            }

            $postSchema = $this->schema(
                Schema::parse($inSchema)->add($this->fullSchema()),
                'ConversationPost'
            );
        }

        return $this->schema($postSchema, $type);
    }

    /**
     * Normalize a Schema record to match the database definition.
     *
     * @param array $schemaRecord Schema record.
     * @return array Return a database record.
     */
    public function normalizeInput(array $schemaRecord) {
        if (array_key_exists('name', $schemaRecord)) {
            $schemaRecord['subject'] = $schemaRecord['name'];
            unset($schemaRecord['name']);
        }
        if (array_key_exists('participantUserIDs', $schemaRecord)) {
            $schemaRecord['recipientUserID'] = $schemaRecord['participantUserIDs'];
            unset($schemaRecord['participantUserIDs']);
        }

        $dbRecord = ApiUtils::convertInputKeys($schemaRecord);
        return $dbRecord;
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @return array Return a Schema record.
     */
    public function normalizeOutput(array $dbRecord) {
        if (!empty($dbRecord['Subject'])) {
            $dbRecord['name'] = $dbRecord['Subject'];
            unset($dbRecord['Subject']);
        } else {
            $dbRecord['name'] = ConversationModel::participantTitle($dbRecord, false);
        }
        $dbRecord['body'] = isset($dbRecord['LastBody'])
            ? Gdn_Format::to($dbRecord['LastBody'], $dbRecord['LastFormat'])
            : t('No messages.');
        $dbRecord['url'] = url("/conversations/{$dbRecord['ConversationID']}", true);

        if (array_key_exists('CountNewMessages', $dbRecord)) {
            $dbRecord['unread'] = $dbRecord['CountNewMessages'] > 0;
            $dbRecord['countUnread'] = $dbRecord['CountNewMessages'];
        }

        if (array_key_exists('Participants', $dbRecord)) {
            // Do views a favor and move the current user to the end of the list.
            foreach ($dbRecord['Participants'] as $i => $row) {
                if ($row['UserID'] == $this->getSession()->UserID) {
                    $index = $i;
                    break;
                }
            }
            if (isset($index)) {
                $me = array_splice($dbRecord['Participants'], $index, 1);
                $dbRecord['Participants'] = array_merge($dbRecord['Participants'], $me);
            }

            $dbRecord['Participants'] = array_map([$this, 'normalizeParticipantOutput'], $dbRecord['Participants']);
        }

        if (isset($dbRecord['LastInsertUser'])) {
            $dbRecord['lastMessage'] = [
                'insertUserID' => $dbRecord['LastInsertUserID'],
                'dateInserted' => $dbRecord['LastDateInserted'],
                'insertUser' => $dbRecord['LastInsertUser']
            ];
        }

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        return $schemaRecord;
    }

    /**
     * Translate a row's Deleted field to a Status value.
     *
     * @param array $dbRecord
     * @return array
     */
    private function normalizeParticipantOutput(array $dbRecord) {
        if ($dbRecord['Deleted'] == 0) {
            $dbRecord['Status'] = 'participating';
        } else {
            $dbRecord['Status'] = 'deleted';
        }
        if (isset($row['Name']) && isset($row['Photo'])) {
            $dbRecord['User'] = [
                'UserID' => $row['UserID'],
                'Name' => $row['Name'],
                'PhotoUrl' => empty($dbRecord['PhotoUrl']) ? UserModel::getDefaultAvatarUrl($dbRecord) : Gdn_Upload::url($dbRecord['PhotoUrl'])
            ];
        }

        return ApiUtils::convertOutputKeys($dbRecord);
    }

    /**
     * The schema for a list of participants in a conversation.
     *
     * @return Schema Returns a schema with the **ConversationParticipants** ID.
     */
    private function getParticipantsSchema() {
        return Schema::parse([
            ':a' => [
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'userID' => [
                            'type' => 'integer',
                            'description' => 'The userID of the participant.',
                        ],
                        'user' => $this->getUserFragmentSchema(),
                        'status' => [
                            'description' => 'Participation status of the user.',
                            'type' => 'string',
                            'enum' => ['participating', 'deleted']
                        ],
                    ],
                    'required' => ['userID', 'status']
                ],
                'description' => 'List of participants.',
            ]
        ])->setID('ConversationParticipants');
    }
}
