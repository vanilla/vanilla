<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;

/**
 * API Controller for the `/invites` resource.
 */
class InvitesApiController extends AbstractApiController {

    /** @var InvitationModel */
    private $invitationModel;

    /** @var UserModel */
    private $userModel;

    /**
     * InvitesApiController constructor.
     *
     * @param Gdn_Configuration $configuration
     * @param InvitationModel $invitationModel
     * @param UserModel $userModel
     * @throws ClientException if the site is not configured for user invitations.
     */
    public function __construct(GDN_Configuration $configuration, InvitationModel $invitationModel, UserModel $userModel) {
        $registrationMethod = strtolower($configuration->get('Garden.Registration.Method'));
        if ($registrationMethod !== 'invitation') {
            throw new ClientException('The site is not configured for the invitation registration method.');
        }

        $this->invitationModel = $invitationModel;
        $this->userModel = $userModel;
    }

    /**
     * Delete an invite.
     *
     * @param int $id The invite ID.
     */
    public function delete($id) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamSchema()->setDescription('Delete an invite.');
        $out = $this->schema([], 'out');

        $row = $this->inviteByID($id);

        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }

        $this->invitationModel->deleteID($id);
    }

    /**
     * Get a schema instance comprised of all available invite fields.
     *
     * @return Schema Returns a schema object.
     */
    public function fullSchema() {
        static $schema;

        if ($schema === null) {
            $schema = Schema::parse([
                'inviteID:i' => 'A unique numerical ID for the invite.',
                'email:s' => 'The email address associated with an invite.',
                'code:s' => 'An invite code.',
                'status' => [
                    'description' => 'Current status for the invite.',
                    'enum' => ['accepted', 'pending']
                ],
                'insertUserID:i' => 'User who created the invite.',
                'dateInserted:dt' => 'When the invite was created.',
                'acceptedUser:o|n?' => $this->getUserFragmentSchema(),
                'acceptedUserID:i|n' => 'User who accepted the invite.',
                'dateAccepted:dt|n' => 'When the invite was accepted.',
                'dateExpires:dt|n' => 'When the expiration is set to expire.'
            ]);
        }

        return $schema;
    }

    /**
     * Get a single invite.
     *
     * @param int $id The ID of the invite.
     * @return array
     */
    public function get($id) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamSchema()->setDescription('Get an invite.');
        $out = $this->schema($this->fullSchema(), 'out');

        $row = $this->inviteByID($id);

        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }

        $this->userModel->expandUsers($row, ['AcceptedUserID']);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only invite record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema($type = 'in') {
        static $ipParamSchema;

        if ($ipParamSchema === null) {
            $ipParamSchema = Schema::parse(['id:i' => 'The invite ID.']);
        }

        return $this->schema($ipParamSchema, $type);
    }

    /**
     * Get a list of invites for the current user.
     *
     * @param array $query The request query.
     * @return Data
     */
    public function index(array $query) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
                'maximum' => 100
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => 30,
                'minimum' => 1,
                'maximum' => 100
            ],
            'expand?' => ApiUtils::getExpandDefinition(['acceptedUser'])
        ], 'in')->setDescription('Get a list of invites sent by the current user.');
        $out = $this->schema([
            ':a' => $this->fullSchema()
        ], 'out');

        $query = $in->validate($query);
        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        $userID = $this->getSession()->UserID;

        $rows = $this->invitationModel->getByUserID(
            $userID,
            '',
            $limit,
            $offset,
            false
        )->resultArray();

        // Expand associated rows.
        $this->userModel->expandUsers(
            $rows,
            $this->resolveExpandFields($query, ['acceptedUser' => 'acceptedUserID'])
        );

        $rows = array_map([$this, 'normalizeOutput'], $rows);

        $result = $out->validate($rows);

        $paging = ApiUtils::numberedPagerInfo($this->invitationModel->getCount(['InsertUserID' => $userID]), '/api/v2/invites', $query, $in);

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Get a single invite record by its unique ID.
     *
     * @param int $id The invite ID.
     * @throws NotFoundException if the invite could not be found.
     * @return array
     */
    public function inviteByID($id) {
        $row = $this->invitationModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Invite');
        }
        return $row;
    }

    /**
     * Create a new invite.
     *
     * @param array $body The request body.
     * @return Data
     */
    public function post(array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'email:s' => 'The email address where the invite should be sent.'
        ], 'in')->setDescription('Create a new invite.');
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body);
        $inviteData = ApiUtils::convertInputKeys($body);
        $row = $this->invitationModel->save($inviteData, ['ReturnRow' => true]);
        $this->validateModel($this->invitationModel);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @return array Return a Schema record.
     */
    public function normalizeOutput(array $dbRecord) {
        $dbRecord['InviteID'] = $dbRecord['InvitationID'];
        $dbRecord['Status'] = empty($dbRecord['AcceptedUserID']) ? 'pending' : 'accepted';

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        return $schemaRecord;
    }
}
