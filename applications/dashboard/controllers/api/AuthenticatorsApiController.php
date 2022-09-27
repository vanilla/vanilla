<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\JSON\Transformer;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Dashboard\AuthenticatorTypeService;
use Vanilla\Models\UserAuthenticationProviderFragmentSchema;
use Vanilla\Utility\ModelUtils;

/**
 * Class AuthenticatorsApiController
 */
class AuthenticatorsApiController extends AbstractApiController
{
    /** @var Gdn_AuthenticationProviderModel */
    private $authenticatorModel;

    /** @var AuthenticatorTypeService $authenticatorTypeService */
    private $authenticatorTypeService;

    /**
     * AuthenticatorsApiController constructor.
     *
     * @param Gdn_AuthenticationProviderModel $authenticatorModel
     * @param AuthenticatorTypeService $authenticatorTypeService
     */
    public function __construct(
        Gdn_AuthenticationProviderModel $authenticatorModel,
        AuthenticatorTypeService $authenticatorTypeService
    ) {
        $this->authenticatorModel = $authenticatorModel;
        $this->authenticatorTypeService = $authenticatorTypeService;
    }

    /**
     * Select a single authenticator by its numeric ID.
     *
     * @param int $id
     * @throws NotFoundException
     * @return array
     */
    private function authenticatorByID(int $id, bool $normalize = true): array
    {
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
     * @param int $id Authenticator ID.
     * @return Data
     */
    public function delete(int $id): void
    {
        $this->permission("Garden.Settings.Manage");

        $this->authenticatorByID($id);
        $this->authenticatorModel->deleteID($id);
    }

    /**
     * Get a single authenticator by its numeric ID.
     *
     * @param int $id Authenticator ID.
     * @return Data Authenticator data posted back.
     */
    public function get(int $id): Data
    {
        $this->permission("Garden.Settings.Manage");

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
    public function index(array $query = []): Data
    {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema(
            [
                "page:i?" => [
                    "default" => 1,
                    "minimum" => 1,
                ],
                "limit:i?" => [
                    "default" => 10,
                    "minimum" => 1,
                    "maximum" => ApiUtils::getMaxLimit(),
                ],
                "type:s?" => [
                    "x-filter" => ["field" => "AuthenticationSchemeAlias"],
                ],
            ],
            "in"
        );
        $out = $this->schema([":a" => new UserAuthenticationProviderFragmentSchema()], "out");

        $query = $in->validate($query);
        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $where = ApiUtils::queryToFilters($in, $query);

        $rows = $this->authenticatorModel->getWhere($where, "", "asc", $limit, $offset)->resultArray();
        $result = array_map([$this->authenticatorModel, "normalizeRow"], $rows);

        $data = $out->validate($result);

        $paging = ApiUtils::morePagerInfo($data, "/api/v2/authenticators", $query, $in);
        return new Data($data, ["paging" => $paging]);
    }

    /**
     * Normalize API input for storage using legacy models.
     *
     * @param array $input
     * @return array
     */
    private function normalizeInput(array $input): array
    {
        $transformer = new Transformer([
            "AssociationSecret" => "secret",
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
     * @param int $id AuthenticatorID.
     * @param array $body data to update for the authenticator.
     * @return Data Authenticator data posted back.
     */
    public function patch(int $id, array $body): Data
    {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema(["active", "default", "visible"], "in")->add(
            new UserAuthenticationProviderFragmentSchema()
        );
        $out = $this->schema(new UserAuthenticationProviderFragmentSchema(), "out");

        $this->authenticatorByID($id);

        $body = $in->validate($body, true);
        $data = $this->normalizeInput($body);
        $this->authenticatorModel->update($data, [$this->authenticatorModel->PrimaryKey => $id]);

        $row = $this->authenticatorByID($id);
        $result = $out->validate($row);
        return new Data($result);
    }

    /**
     * Post to insert new authenticator.
     *
     * @param array $body Data to insert.
     * @return Data Authenticator data posted back.
     */
    public function post(array $body): Data
    {
        $this->permission("Garden.Settings.Manage");
        $in = $this->schema(new UserAuthenticationProviderFragmentSchema());

        $dataType = $in->validate($body, true);

        $authenticatorTypes = $this->authenticatorTypeService->getAuthenticatorTypes();
        $typeSchema = false;
        //Get the schema of the authentication type.
        if (is_array($authenticatorTypes)) {
            foreach ($authenticatorTypes as $authenticatorType) {
                $authType = $authenticatorType->getAuthenticatorType();
                if (strcasecmp($authType["authenticatorType"], $dataType["type"]) === 0) {
                    $typeSchema = $authType["schema"];
                    break;
                }
            }
        }
        if ($typeSchema === false) {
            throw new NotFoundException("Authenticator type not found");
        }

        $out = $this->schema($typeSchema, "out")->add(new UserAuthenticationProviderFragmentSchema(), true);
        $typeSchemaIn = $this->schema($typeSchema);
        // Validate type with error, and merge with array from default.
        $attributes = $typeSchemaIn->validate($body);
        // Convert from what is provided in the post, to what's DB is expecting.
        $data = array_merge($this->normalizeInput($dataType), $attributes["attributes"]);
        // Save the authenticator
        $authenticatorByID = $this->authenticatorModel->save($data);
        if ($authenticatorByID === false) {
            ModelUtils::validationResultToValidationException($this->authenticatorModel);
        }
        // Retreave the authenticator based on ID;
        $row = $this->authenticatorModel
            ->getWhere([Gdn_AuthenticationProviderModel::COLUMN_KEY => $authenticatorByID])
            ->nextRow(DATASET_TYPE_ARRAY);
        $row = $this->authenticatorModel->normalizeRow($row);
        $result = $out->validate($row, true);
        return new Data($result);
    }
}
