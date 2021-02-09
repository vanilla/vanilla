<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\JSON\Transformer;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Models\UserAuthenticationProviderFragmentSchema;

/**
 * Class AuthenticatorsApiController
 */
class AuthenticatorsApiController extends AbstractApiController  {

    /** @var Gdn_AuthenticationProviderModel */
    private $authenticatorModel;

    /**
     * AuthenticatorsApiController constructor.
     *
     * @param Gdn_AuthenticationProviderModel $authenticatorModel
     */
    public function __construct(Gdn_AuthenticationProviderModel $authenticatorModel) {
        $this->authenticatorModel = $authenticatorModel;
    }

    /**
     * Select a single authenticator by its numeric ID.
     *
     * @param int $id
     * @throws NotFoundException
     * @return array
     */
    private function authenticatorByID(int $id, bool $normalize = true): array {
        $row = $this->authenticatorModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException("Authenticator");
        }

        if ($normalize) {
            $row = $this->authenticatorModel->normalizeRow($row);
        }
        return $row;
    }

    /**
     * Delete a single authenticator by its numeric ID.
     *
     * @param int $id
     * @return Data
     */
    public function delete(int $id): void {
        $this->permission("Garden.Setting.Manage");

        $in = $this->schema([], "in");
        $out = $this->schema([], "out");

        $this->authenticatorByID($id);
        $this->authenticatorModel->deleteID($id);
    }

    /**
     * Get a single authenticator by its numeric ID.
     *
     * @param int $id
     * @return Data
     */
    public function get(int $id): Data {
        $this->permission("Garden.Setting.Manage");

        $in = $this->schema([], "in");
        $out = $this->schema(new UserAuthenticationProviderFragmentSchema(), "out");

        $row = $this->authenticatorByID($id);

        $result = $out->validate($row);
        return new Data($result);
    }

    /**
     * List all known authenticators.
     *
     * @return Data
     */
    public function index(array $query = []): Data {
        $this->permission("Garden.Setting.Manage");

        $in = $this->schema([
            'page:i?' => [
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'default' => 10,
                'minimum' => 1,
                'maximum' => ApiUtils::getMaxLimit(),
            ],
            "type:s?" => [
                "x-filter" => ["field" => "AuthenticationSchemeAlias"],
            ],
        ], "in");
        $out = $this->schema([":a" => new UserAuthenticationProviderFragmentSchema()], "out");

        $query = $in->validate($query);
        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);

        $where = ApiUtils::queryToFilters($in, $query);

        $rows = $this->authenticatorModel
            ->getWhere($where, "", "asc", $limit, $offset)
            ->resultArray();
        $result = array_map([$this->authenticatorModel, "normalizeRow"], $rows);

        $data = $out->validate($result);

        $paging = ApiUtils::morePagerInfo($data, "/api/v2/authenticators", $query, $in);
        return new Data($data, ['paging' => $paging]);
    }

    /**
     * Normalize API input for storage using legacy models.
     *
     * @param array $input
     * @return array
     */
    private function normalizeInput(array $input): array {
        $transformer = new Transformer([
            "Active" => "active",
            "AuthenticateUrl" => "/urls/authenticateUrl",
            "AuthenticationKey" => "clientID",
            "AuthenticationSchemeAlias" => "type",
            "IsDefault" => "default",
            "Name" => "name",
            "PasswordUrl" => "/urls/passwordUrl",
            "ProfileUrl" => "/urls/profileUrl",
            "RegisterUrl" => "/urls/registerUrl",
            "SignInUrl" => "/urls/signInUrl",
            "SignOutUrl" => "/urls/signOutUrl",
            "UserAuthenticationProviderID" => "authenticatorID",
            "Visible" => "visible",
        ]);

        $input = $transformer->transform($input);
        return $input;
    }

    /**
     * Update an authenticator.
     *
     * @param int $id
     * @param array $body
     * @return Data
     */
    public function patch(int $id, array $body): Data {
        $this->permission("Garden.Setting.Manage");

        $in = $this->schema([
            "active",
            "default",
            "visible",
        ], "in")->add(new UserAuthenticationProviderFragmentSchema());
        $out = $this->schema(new UserAuthenticationProviderFragmentSchema(), "out");

        $this->authenticatorByID($id);

        $body = $in->validate($body, true);
        $data = $this->normalizeInput($body);
        $this->authenticatorModel->update($data, [$this->authenticatorModel->PrimaryKey => $id]);

        $row = $this->authenticatorByID($id);
        $result = $out->validate($row);
        return new Data($result);
    }
}
