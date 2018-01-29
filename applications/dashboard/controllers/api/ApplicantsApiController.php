<?php
/**
* @copyright 2009-2018 Vanilla Forums Inc.
* @license GPLv2
*/

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;

/**
 * API Controller for managing applicants.
 */
class ApplicantsApiController extends AbstractApiController {

    /** @var Schema */
    private $idParamSchema;

    /** @var UserModel */
    private $userModel;

    /**
     * ApplicantsApiController constructor.
     *
     * @param Gdn_Configuration $configuration
     * @param UserModel $userModel
     * @throws ClientException if the registration method on the site is not approval.
     */
    public function __construct(Gdn_Configuration $configuration, UserModel $userModel) {
        $registrationMethod = strtolower($configuration->get('Garden.Registration.Method'));
        if ($registrationMethod !== 'approval') {
            throw new ClientException('The site is not configured for the approval registration method.');
        }
        $this->userModel = $userModel;
    }

    /**
     * Delete an applicant. For now, this will decline the applicant.
     *
     * @param int $id The ID of the applicant.
     * @throws ClientException if the applicant has been approved.
     */
    public function delete($id) {
        $this->permission('Garden.Users.Approve');

        $this->idParamSchema()->setDescription('Delete an applicant.');
        $this->schema([], 'out');

        $this->userByID($id);

        if ($this->userModel->isApplicant($id) === false) {
            throw new ClientException('The specified applicant is already an active user.');
        }

        $this->userModel->decline($id);
    }

    /**
     * Get a schema instance comprised of all available applicant fields.
     *
     * @return Schema Returns a schema object.
     */
    public function fullSchema() {
        static $fullSchema;

        if ($fullSchema === null) {
            $fullSchema = Schema::parse([
                'applicantID:i' => 'Unique ID associated with the applicant.',
                'email:s' => 'Email address associated with the applicant.',
                'name:s' => 'Username on the applicant.',
                'discoveryText:s' => 'Reason why you want to join.',
                'status:s' => [
                    'description' => 'Current status of the applicant.',
                    'enum' => ['approved', 'declined', 'pending']
                ],
                'insertIPAddress:s' => 'IP address of the user who created the applicant.',
                'dateInserted:dt' => 'When the applicant was created.'
            ]);
        }

        return $fullSchema;
    }

    /**
     * Get a single applicant.
     *
     * @param int $id The ID of the applicant.
     * @throws NotFoundException if the applicant could not be found.
     * @return array
     */
    public function get($id) {
        $this->permission('Garden.Users.Approve');

        $this->idParamSchema()->setDescription('Get an applicant.');
        $out = $this->schema($this->fullSchema(), 'out');

        $row = $this->userByID($id);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only applicant record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema($type = 'in') {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse(['id:i' => 'The applicant ID.']),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * Get a list of current applicants.
     *
     * @param $query
     * @return Data
     */
    public function index(array $query) {
        $this->permission('Garden.Users.Approve');

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
            ]
        ], 'in')->setDescription('Get a list of current applicants.');
        $out = $this->schema(
            [':a' => $this->fullSchema()],
            'out'
        );

        $query = $in->validate($query);

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);
        $rows = $this->userModel->getApplicants($limit, $offset)->resultArray();

        $rows = array_map([$this, 'normalizeOutput'], $rows);

        $result = $out->validate($rows);

        $paging = ApiUtils::numberedPagerInfo($this->userModel->getApplicantCount(), '/api/v2/applicants', $query, $in);

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Approve or decline an applicant.
     *
     * @param int $id
     * @param array $body
     * @throws ClientException if the user record is not an applicant.
     * @return array
     */
    public function patch($id, array $body) {
        $this->permission('Garden.Users.Approve');

        $this->idParamSchema('in');
        $in = $this->schema([
            'status:s' => [
                'description' => 'Current status of the applicant.',
                'enum' => ['approved', 'declined']
            ]
        ], 'in')->setDescription('Modify an applicant.');
        $out = $this->schema($this->fullSchema(), 'out');

        $row = $this->userByID($id);

        if ($this->userModel->isApplicant($id) === false) {
            throw new ClientException('The applicant specified is already an active user.');
        }

        $body = $in->validate($body, true);

        if (array_key_exists('status', $body)) {
            switch ($body['status']) {
                case 'approved':
                    if ($this->userModel->approve($id)) {
                        $row['status'] = 'approved';
                    }
                    break;
                case 'declined':
                    if ($this->userModel->decline($id)) {
                        $row['status'] = 'declined';
                    }
                    break;
            }
        }

        $this->validateModel($this->userModel);

        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Create an applicant.
     *
     * @param array $body
     * @throws ClientException if the terms of service flag is false.
     * @throws ServerException if a valid user ID is not returned when creating the applicant record.
     * @return Data
     */
    public function post(array $body) {
        $this->permission(\Vanilla\Permissions::BAN_CSRF);

        $in = $this->schema([
            'email:s' => 'The email address for the user.',
            'name:s' => 'A username for the user.',
            'password:s' => 'A password for the user.',
            'discoveryText:s' => 'Why does the user wish to join?'
        ], 'in')->setDescription('Create an applicant.');
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body);

        $this->userModel->validatePasswordStrength($body['password'], $body['name']);

        $userData = ApiUtils::convertInputKeys($body);
        $userID = $this->userModel->register($userData);
        $this->validateModel($this->userModel);

        if (filter_var($userID, FILTER_VALIDATE_INT)) {
            $row = $this->userByID($userID);
        } else {
            throw new ServerException('An unknown error occurred while attempting to create the applicant.', 500);
        }
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
        $dbRecord['ApplicantID'] = $dbRecord['UserID'];
        unset($dbRecord['UserID']);
        $dbRecord['Status'] = 'pending';

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        return $schemaRecord;
    }

    /**
     * Get an applicant by its numeric ID.
     *
     * @param int $id The user ID.
     * @throws NotFoundException if the user could not be found.
     * @return array
     */
    public function userByID($id) {
        $row = $this->userModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row || $row['Deleted'] > 0) {
            throw new NotFoundException('Applicant');
        }
        return $row;
    }
}
