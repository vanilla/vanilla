<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Garden\Container\ContainerException;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Dashboard\Models\InterestModel;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Navigation\BreadcrumbProviderNotFoundException;

/**
 * Interests API controller.
 */
class InterestsApiController extends \AbstractApiController
{
    /**
     * D.I.
     *
     * @param InterestModel $interestModel
     */
    public function __construct(
        protected InterestModel $interestModel,
        protected \DiscussionsApiController $discussionsApi,
        protected \CategoriesApiController $categoriesApi
    ) {
    }

    /**
     * Get a list of interests with optional filters applied.
     *
     * @param array $query
     * @return Data
     * @throws ClientException
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function index(array $query)
    {
        $this->interestModel->ensureSuggestedContentEnabled();
        $this->permission("settings.manage");
        $in = $this->schema([
            "name:s?",
            "categoryIDs:a?" => "i",
            "tagIDs:a?" => "i",
            "profileFields:a?" => "s",
            "isDefault:b?",
        ]);
        $out = $this->schema([":a" => $this->outputSchema()]);

        $query = $in->validate($query);

        $rows = $this->interestModel->getWhere($query);
        $rows = $out->validate($rows);

        return new Data($rows);
    }

    /**
     * Get an interest.
     *
     * @param int $id
     * @return Data
     * @throws ClientException
     * @throws HttpException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function get(int $id): Data
    {
        $this->interestModel->ensureSuggestedContentEnabled();
        $this->permission("settings.manage");
        $out = $this->schema($this->outputSchema(), "out");
        $row = $this->getInterestByID($id);
        $row = $out->validate($row);

        return new Data($row);
    }

    /**
     * Create an interest.
     *
     * @param array $body
     * @return Data
     * @throws ClientException
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function post(array $body): Data
    {
        $this->interestModel->ensureSuggestedContentEnabled();
        $this->permission("settings.manage");

        $in = $this->postSchema();
        $in = $this->interestModel->applyValidators($in);
        $body = $in->validate($body);
        $id = $this->interestModel->insert($body);

        return $this->get($id);
    }

    /**
     * Update an interest.
     *
     * @param int $id
     * @param array $body
     * @return Data
     * @throws ClientException
     * @throws HttpException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function patch(int $id, array $body): Data
    {
        $this->interestModel->ensureSuggestedContentEnabled();
        $this->permission("settings.manage");

        $in = $this->patchSchema();
        $in = $this->interestModel->applyValidators($in, $id);
        $body = $in->validate($body);
        $this->getInterestByID($id);
        $this->interestModel->update($body, ["interestID" => $id]);

        return $this->get($id);
    }

    /**
     * Delete an interest.
     *
     * @param int $id
     * @return Data
     */
    public function delete(int $id)
    {
        $this->interestModel->ensureSuggestedContentEnabled();
        $this->permission("settings.manage");

        $this->getInterestByID($id);
        $this->interestModel->update(["isDeleted" => true], ["interestID" => $id]);

        return new Data([], 204);
    }

    /**
     * Enable or disable Suggested Content.
     *
     * @param array $body
     * @return Data
     */
    public function put_toggleSuggestedContent(array $body)
    {
        FeatureFlagHelper::ensureFeature(InterestModel::SUGGESTED_CONTENT_FEATURE_FLAG);
        $this->permission("settings.manage");
        $in = $this->schema(["enabled:b"]);
        $out = $this->schema(["enabled:b"], "out");
        $body = $in->validate($body);
        \Gdn::config()->saveToConfig(InterestModel::CONF_SUGGESTED_CONTENT_ENABLED, $body["enabled"]);
        $enabled = $out->validate([
            "enabled" => \Gdn::config()->get(InterestModel::CONF_SUGGESTED_CONTENT_ENABLED),
        ]);

        return new Data($enabled);
    }

    /**
     * Get suggested categories and content for the current user.
     *
     * @param array $query
     * @return Data
     * @throws NotFoundException
     * @throws ContainerException
     * @throws ValidationException
     * @throws ClientException
     * @throws HttpException
     * @throws PermissionException
     * @throws BreadcrumbProviderNotFoundException
     */
    public function get_suggestedContent(array $query = []): Data
    {
        $this->interestModel->ensureSuggestedContentEnabled();
        $isUserAuthenticated = $this->getSession()->UserID > 0;

        $in = $this->schema([
            "suggestedContentLimit:i?" => ["minimum" => 1, "maximum" => 20],
            "suggestedContentExcerptLength:i" => ["default" => 200],
            "suggestedFollowsLimit:i?" => ["minimum" => 1, "maximum" => 20],
            "excludedCategoryIDs:a" => ["default" => [], "items" => ["type" => "integer"]],
        ]);
        $query = $in->validate($query);

        $discussionSuggestions = $this->discussionsApi
            ->index([
                "limit" => $query["suggestedContentLimit"],
                "expand" => ["all", "-body"],
                "sort" => "-" . \DiscussionModel::SORT_EXPIRIMENTAL_TRENDING,
                "slotType" => "w",
                "suggested" => true,
                "excerptLength" => $query["suggestedContentExcerptLength"],
                "excludedCategoryIDs" => $query["excludedCategoryIDs"],
            ])
            ->getData();

        $categorySuggestions = [];

        // Only signed-in users can follow categories
        if ($isUserAuthenticated) {
            $this->permission("Garden.SignIn.Allow");

            $suggestedQuery = [
                "excludedCategoryIDs" => $query["excludedCategoryIDs"],
            ];

            if (isset($query["suggestedFollowsLimit"])) {
                $suggestedQuery["limit"] = $query["suggestedFollowsLimit"];
            }

            $categorySuggestions = $this->categoriesApi->get_suggested($suggestedQuery)->getData();
        }

        $output = [
            "discussions" => isset($query["suggestedContentLimit"]) ? $discussionSuggestions : [],
            "categories" => isset($query["suggestedFollowsLimit"]) ? $categorySuggestions : [],
        ];

        return new Data($output);
    }

    /**
     * Helper method that retrieves the interest from the database and throws an exception if it doesn't exist.
     *
     * @param int $id
     * @return array
     * @throws NotFoundException
     */
    private function getInterestByID(int $id): array
    {
        try {
            $row = $this->interestModel->getInterest($id);
        } catch (NoResultsException $e) {
            throw new NotFoundException("Interest");
        }
        return $row;
    }

    /**
     * Returns the schema for displaying interests.
     *
     * @return Schema
     */
    private function outputSchema(): Schema
    {
        return Schema::parse([
            "interestID",
            "apiName",
            "name",
            "profileFieldMapping",
            "categoryIDs",
            "tagIDs",
            "isDefault:b",
            "profileFields",
            "categories",
            "tags",
            "dateInserted",
            "dateUpdated",
            "insertUserID",
            "updateUserID",
        ]);
    }

    /**
     * Returns the schema for creating interests.
     *
     * @return Schema
     */
    private function postSchema(): Schema
    {
        $schema = $this->schema(["apiName:s"])->merge($this->patchSchema());
        return $schema;
    }

    /**
     * Returns the schema for updating interests.
     *
     * @return Schema
     */
    private function patchSchema(): Schema
    {
        $schema = $this->schema([
            "name:s",
            "profileFieldMapping:o?",
            "categoryIDs:a?" => "i",
            "tagIDs:a?" => [
                "items" => ["type" => "integer"],
                "maxItems" => \Gdn::config(InterestModel::CONF_SUGGESTED_CONTENT_MAX_TAG_COUNT, 30),
            ],
            "isDefault:b?" => ["default" => false],
        ]);
        return $schema;
    }
}
