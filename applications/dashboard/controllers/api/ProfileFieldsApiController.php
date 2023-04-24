<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Exception;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\SchemaUtils;

/**
 * Controller for the /profile-fields endpoints
 */
class ProfileFieldsApiController extends \AbstractApiController
{
    /** @var ProfileFieldModel  */
    protected $profileFieldModel;

    /**
     * ProfileFieldsApiController constructor.
     * @param ProfileFieldModel $profileFieldModel
     */
    public function __construct(ProfileFieldModel $profileFieldModel)
    {
        $this->profileFieldModel = $profileFieldModel;
    }

    /**
     * List profile fields.
     *
     * @throws HttpException|ValidationException|PermissionException|Exception
     */
    public function index(array $query = []): Data
    {
        $in = $this->schema($this->profileFieldModel->getQuerySchema());
        $query = $in->validate($query);

        $where = ApiUtils::queryToFilters($in, $query);
        $rows = $this->profileFieldModel->getProfileFields($where);

        $out = $this->schema($this->profileFieldModel->getOutputSchema(), ["ProfileFieldOutput", "out"]);
        SchemaUtils::validateArray($rows, $out, true);

        return new Data($rows);
    }

    /**
     * Create a profile field.
     *
     * @param array $body
     * @return Data
     * @throws HttpException|PermissionException|ValidationException|Exception
     */
    public function post(array $body): Data
    {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema($this->profileFieldModel->getFullSchema(), ["ProfileFieldPost", "in"]);

        $body = $in->validate($body);
        $this->profileFieldModel->insert($body);
        $apiName = $body["apiName"] ?? null;

        $profileField = $this->getProfileFieldByApiName($apiName);
        return new Data($profileField, ["status" => 201]);
    }

    /**
     * Update a profile field.
     *
     * @param string $apiName
     * @param array $body
     * @return Data
     * @throws HttpException|NotFoundException|PermissionException|ValidationException|Exception
     */
    public function patch(string $apiName, array $body): Data
    {
        $this->permission("Garden.Settings.Manage");

        $profileField = $this->getProfileFieldByApiName($apiName);

        $in = $this->schema($this->profileFieldModel->getPatchSchema(), ["ProfileFieldPatch", "in"]);
        $body = $in->validate($body);

        $this->handleDropdownOptions($body, $profileField);

        $body = ArrayUtils::mergeRecursive($profileField, $body);

        $insertSchema = $this->schema($this->profileFieldModel->getFullSchema(), ["ProfileFieldFull", "in"]);
        $body = $insertSchema->validate($body);

        $this->profileFieldModel->update($body, ["apiName" => $apiName]);
        $profileField = $this->getProfileFieldByApiName($apiName);
        return new Data($profileField);
    }

    /**
     * Update sort values for records using a apiName => sort mapping.
     *
     * @param array $body
     * @return void
     * @throws HttpException|PermissionException|ValidationException|Exception
     */
    public function put_sorts(array $body)
    {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema(Schema::parse([":o" => "Key-value mapping of apiName => sort"]));
        $body = $in->validate($body);
        $this->profileFieldModel->updateSorts($body);
    }

    /**
     * Delete a profile field.
     *
     * @param string $apiName
     * @return void
     * @throws HttpException|NotFoundException|PermissionException|ValidationException|Exception
     */
    public function delete(string $apiName)
    {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema(Schema::parse(["apiName:s"]));
        $in->validate(["apiName" => $apiName]);

        $profileField = $this->getProfileFieldByApiName($apiName);

        // If the profileField destined to deletion is a core field, throw an error.
        if (!empty($profileField["isCoreField"])) {
            throw new Exception("This field is used by a core feature & can't be deleted.", 403);
        }

        $this->profileFieldModel->delete(["apiName" => $apiName]);
    }

    /**
     * Handle the dropdownOptions field when patching a profile field.
     *
     * @param array $body
     * @param array $profileField
     */
    private function handleDropdownOptions(array $body, array &$profileField)
    {
        // If we're setting new dropdownOptions, replace them rather than merging them.
        if (isset($body["dropdownOptions"])) {
            unset($profileField["dropdownOptions"]);
        }

        // If we're changing the formType to something other than "dropdown", null out the options.
        if (
            $profileField["formType"] === ProfileFieldModel::FORM_TYPE_DROPDOWN &&
            isset($body["formType"]) &&
            $body["formType"] !== ProfileFieldModel::FORM_TYPE_DROPDOWN
        ) {
            $profileField["dropdownOptions"] = null;
        }
    }

    /**
     * Helper method for retrieving a profile field by apiName
     *
     * @param string $apiName
     * @return array
     * @throws NotFoundException|ValidationException
     */
    protected function getProfileFieldByApiName(string $apiName): array
    {
        try {
            $profileField = $this->profileFieldModel->selectSingle(["apiName" => $apiName]);
            $outputSchema = $this->schema($this->profileFieldModel->getOutputSchema(), ["ProfileFieldOutput", "out"]);
            return $outputSchema->validate($profileField);
        } catch (NoResultsException $e) {
            throw new NotFoundException("Profile Field with apiName $apiName not found", [], $e);
        }
    }
}
