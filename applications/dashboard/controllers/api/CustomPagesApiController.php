<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Controllers\API;

use Garden\Container\ContainerException;
use Garden\Schema\RefNotFoundException;
use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Garden\Web\Data;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Exception\PermissionException;
use Vanilla\Models\CustomPageModel;
use Vanilla\Layout\LayoutModel;
use Vanilla\ApiUtils;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Schema\RangeExpression;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\ModelUtils;

class CustomPagesApiController extends \AbstractApiController
{
    public function __construct(private CustomPageModel $customPageModel, private LayoutModel $layoutModel)
    {
    }

    /**
     * List custom pages.
     *
     * @param array $query
     * @return Data
     */
    public function index(array $query): Data
    {
        $this->permission();
        $in = $this->indexSchema();
        $query = $in->validate($query);

        $customPageSchema = CrawlableRecordSchema::applyExpandedSchema(
            $this->fullSchema(),
            "customPage",
            $query["expand"] ?? []
        );
        $out = $this->schema([":a" => $customPageSchema], "out");

        $where = ApiUtils::queryToFilters($in, $query);
        $rows = $this->customPageModel->selectWithLayoutID($where);

        $rows = $this->normalizeRows($rows, $query["expand"]);

        $rows = $out->validate($rows);

        $rows = array_slice($rows, 0, CustomPageModel::MAX_CUSTOM_PAGE_COUNT);

        // Paging was added here to satisfy crawlable resource tests. Note only one page of results is expected.
        $paging = ApiUtils::morePagerInfo(
            $rows,
            "/api/v2/custom-pages",
            ["page" => 1, "limit" => CustomPageModel::MAX_CUSTOM_PAGE_COUNT + 1] + $query,
            $in
        );
        return new Data($rows, ["paging" => $paging]);
    }

    /**
     * Get a custom page.
     *
     * @param int $id
     * @return array
     */
    public function get(int $id): array
    {
        $this->permission();
        return $this->getCustomPage($id);
    }

    /**
     * Create a custom page.
     *
     * @param array $body
     * @return array
     */
    public function post(array $body): array
    {
        $this->permission("site.manage");

        // AIDEV-NOTE: Handle copyLayoutID option to copy existing layout data
        if (isset($body["copyLayoutID"])) {
            $copyLayoutID = $body["copyLayoutID"];
            unset($body["copyLayoutID"]); // Remove from body so it doesn't interfere with validation

            // Validate that copyLayoutID is an integer or string
            if (!is_int($copyLayoutID) && !is_string($copyLayoutID)) {
                throw new ClientException("copyLayoutID must be an integer or string");
            }

            try {
                // Retrieve the existing layout to copy
                $existingLayout = $this->layoutModel->getByID($copyLayoutID);
            } catch (\Vanilla\Exception\Database\NoResultsException $e) {
                throw new NotFoundException("Layout", context: ["copyLayoutID" => $copyLayoutID]);
            }

            // Create layoutData from the copied layout
            $body["layoutData"] = [
                "name" => $body["seoTitle"], // Replace name with seoTitle as requested
                "layout" => $existingLayout["layout"],
                "titleBar" => $existingLayout["titleBar"] ?? null,
                "layoutViewType" => $existingLayout["layoutViewType"],
            ];
        }

        $in = $this->schema($this->customPageModel->commonPostPatchSchema());
        $body = $in->validate($body);

        if ($this->customPageModel->queryTotalCount() >= 500) {
            throw new ClientException("You have reached the maximum number of pages.");
        }

        $id = $this->customPageModel->insert($body);
        return $this->getCustomPage($id);
    }

    /**
     * Update a custom page.
     *
     * @param int $id
     * @param array $body
     * @return array
     */
    public function patch(int $id, array $body): array
    {
        $this->permission("site.manage");
        $this->getCustomPage($id);
        $in = $this->schema($this->customPageModel->commonPostPatchSchema($id));
        $body = $in->validate($body, true);

        $this->customPageModel->updateByID($id, $body);
        return $this->getCustomPage($id);
    }

    /**
     * Delete a custom page.
     *
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        $this->permission("site.manage");
        $this->getCustomPage($id);
        $this->customPageModel->deleteByID($id);
    }

    /**
     * Return a schema for validating index endpoint query.
     *
     * @return Schema
     */
    private function indexSchema(): Schema
    {
        return $this->schema([
            "customPageID?" => RangeExpression::createSchema([":int"])->setField("x-filter", true),
            "status:a?" => [
                "style" => "form",
                "default" => CustomPageModel::ALL_STATUSES,
                "items" => [
                    "type" => "string",
                    "enum" => CustomPageModel::ALL_STATUSES,
                ],
                "x-filter" => true,
            ],
            "expand?" => ApiUtils::getExpandDefinition(["crawl"]),
        ]);
    }

    /**
     * Apply normalization and permission-based filtering.
     *
     * @param array $rows
     * @param array|bool|null $expand
     * @return array
     */
    private function normalizeRows(array $rows, array|bool|null $expand = []): array
    {
        $result = [];
        foreach ($rows as $row) {
            if (!$this->customPageModel->canViewCustomPage($row)) {
                continue;
            }

            if (ModelUtils::isExpandOption(ModelUtils::EXPAND_CRAWL, $expand)) {
                $row["canonicalID"] = "customPage_{$row["customPageID"]}";
                $row["scope"] = !empty($row["roleIDs"])
                    ? CrawlableRecordSchema::SCOPE_RESTRICTED
                    : CrawlableRecordSchema::SCOPE_PUBLIC;
                $row["excerpt"] = $row["seoDescription"] ?? "";
                $row["name"] = $row["seoTitle"] ?? "";
                $row["bodyPlainText"] = $row["seoDescription"] ?? "";

                $siteSectionModel = \Gdn::getContainer()->get(SiteSectionModel::class);
                $siteSection = $siteSectionModel->getByID($row["siteSectionID"]);
                $row["locale"] = $siteSection->getContentLocale();
            }
            $options = ["expand" => $expand];
            $row = $this->getEventManager()->fireFilter("customPagesApiController_normalizeRow", $row, $options);
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Helper method to retrieve a custom page by its ID.
     *
     * @param int $id
     * @return array
     * @throws NotFoundException
     */
    private function getCustomPage(int $id): array
    {
        $customPage = $this->customPageModel->getCustomPage($id);

        if (empty($customPage)) {
            throw new NotFoundException(context: ["customPageID" => $id]);
        }

        if (!$this->customPageModel->canViewCustomPage($customPage)) {
            throw new NotFoundException(context: ["customPageID" => $id]);
        }

        return $customPage;
    }

    /**
     * Returns full output schema.
     *
     * @return Schema
     */
    private function fullSchema(): Schema
    {
        return $this->customPageModel
            ->schema()
            ->merge(Schema::parse(["url:s?", "bodyPlainText:s?" => ["x-localize" => true]]));
    }
}
