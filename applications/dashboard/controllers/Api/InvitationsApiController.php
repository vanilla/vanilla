<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Utility\CapitalCaseScheme;

/**
 * API Controller for the `/invitations` resource.
 */
class InvitationsApiController extends AbstractApiController {

    /** @var CapitalCaseScheme */
    private $caseScheme;

    /** @var InvitationModel */
    private $invitationModel;

    /** @var UserModel */
    private $userModel;

    /**
     * InvitationsApiController constructor.
     *
     * @param CapitalCaseScheme $caseScheme
     * @param Gdn_Configuration $configuration
     * @param InvitationModel $invitationModel
     * @param UserModel $userModel
     * @throws ClientException if the site is not configured for user invitations.
     */
    public function __construct(CapitalCaseScheme $caseScheme, GDN_Configuration $configuration, InvitationModel $invitationModel, UserModel $userModel) {
        $registrationMethod = strtolower($configuration->get('Garden.Registration.Method'));
        if ($registrationMethod !== 'invitation') {
            throw new ClientException('The site is not configured for the invitation registration method.');
        }

        $this->caseScheme = $caseScheme;
        $this->invitationModel = $invitationModel;
        $this->userModel = $userModel;
    }

    /**
     * Delete an invitation.
     *
     * @param int $id The invitation ID.
     */
    public function delete($id) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamSchema()->setDescription('Delete an invitation.');
        $out = $this->schema([], 'out');

        $row = $this->invitationByID($id);

        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }

        $this->invitationModel->deleteID($id);
    }

    /**
     * Get a schema instance comprised of all available invitation fields.
     *
     * @return Schema Returns a schema object.
     */
    public function fullSchema() {
        static $schema;

        if ($schema === null) {
            $schema = Schema::parse([
                'invitationID:i' => 'A unique numerical ID for the invitation.',
                'email:s' => 'The email address associated with an invitation.',
                'code:s' => 'An invitation code.',
                'status' => [
                    'description' => 'Current status for the invitation.',
                    'enum' => ['accepted', 'pending']
                ],
                'insertUserID:i' => 'User who created the invitation.',
                'dateInserted:dt' => 'When the invitation was created.',
                'acceptedUser:o|n?' => $this->getUserFragmentSchema(),
                'acceptedUserID:i|n' => 'User who accepted the invitation.',
                'dateAccepted:dt|n' => 'When the invitation was accepted.',
                'dateExpires:dt|n' => 'When the expiration is set to expire.'
            ]);
        }

        return $schema;
    }

    /**
     * Get a single invitation.
     *
     * @param int $id The ID of the invitation.
     * @return array
     */
    public function get($id) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamSchema()->setDescription('Get an invitation.');
        $out = $this->schema($this->fullSchema(), 'out');

        $row = $this->invitationByID($id);

        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }

        $this->prepareRow($row);
        $this->userModel->expandUsers($row, ['AcceptedUserID']);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only invitation record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema($type = 'in') {
        static $ipParamSchema;

        if ($ipParamSchema === null) {
            $ipParamSchema = Schema::parse(['id:i' => 'The invitation ID.']);
        }

        return $this->schema($ipParamSchema, $type);
    }

    /**
     * Get a list of invitations for the current user.
     *
     * @param array $query The request query.
     * @return array
     */
    public function index(array $query) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'page:i?' => [
                'description' => 'Page number.',
                'default' => 1,
                'minimum' => 1,
                'maximum' => 100
            ],
            'limit:i?' => [
                'description' => 'The number of items per page.',
                'default' => 30,
                'minimum' => 1,
                'maximum' => 100
            ],
            'expand:b?' => 'Expand associated records.'
        ], 'in')->setDescription('Get a list of invitations sent by the current user.');
        $out = $this->schema([
            ':a' => $this->fullSchema()
        ], 'out');

        $query = $in->validate($query);
        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        $rows = $this->invitationModel->getByUserID(
            $this->getSession()->UserID,
            '',
            $limit,
            $offset,
            false
        )->resultArray();

        if (!empty($query['expand'])) {
            $this->userModel->expandUsers($rows, ['AcceptedUserID']);
        }

        foreach ($rows as &$row) {
            $this->prepareRow($row);
        }
        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Get a single invitation record by its unique ID.
     *
     * @param int $id The invitation ID.
     * @throws NotFoundException if the invitation could not be found.
     * @return array
     */
    public function invitationByID($id) {
        $row = $this->invitationModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Invitation');
        }
        return $row;
    }

    /**
     * Create a new invitation.
     *
     * @param array $body The request body.
     * @return Data
     */
    public function post(array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'email:s' => 'The email address where the invitation should be sent.'
        ], 'in')->setDescription('Create a new invitation.');
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body);
        $invitationData = $this->caseScheme->convertArrayKeys($body);
        $row = $this->invitationModel->save($invitationData, ['ReturnRow' => true]);
        $this->validateModel($this->invitationModel);
        $this->prepareRow($row);

        $result = $out->validate($row);
        return new Data($result, 201);
    }

    /**
     * Prepare the current row for output.
     *
     * @param array $row
     */
    public function prepareRow(array &$row) {
        $row['Status'] = empty($row['AcceptedUserID']) ? 'pending' : 'accepted';
    }
}
