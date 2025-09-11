<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;
use Garden\Web\Exception\HttpException;
use Vanilla\Dashboard\Controllers\API\CustomPagesApiController;
use Vanilla\Models\CustomPageModel;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;

class CustomPageSearchType extends AbstractSearchType
{
    public function __construct(
        private CustomPagesApiController $customPagesApiController,
        private \Gdn_Session $session,
        private \UserModel $userModel
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getKey(): string
    {
        return "customPage";
    }

    /**
     * @inheritdoc
     */
    public function getRecordType(): string
    {
        return "customPage";
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return "customPage";
    }

    /**
     * @inheritdoc
     */
    public function getResultItems(array $recordIDs, SearchQuery $query): array
    {
        try {
            $statuses = $query->getQueryParameter("customPageStatus", [CustomPageModel::STATUS_PUBLISHED]);
            if ($this->shouldQueryNonPublished($query)) {
                $statuses[] = CustomPageModel::STATUS_UNPUBLISHED;
            }
            $results = $this->customPagesApiController
                ->index([
                    "customPageID" => implode(",", $recordIDs),
                    "status" => $statuses,
                    "expand" => [ModelUtils::EXPAND_CRAWL],
                ])
                ->getData();

            $resultItems = array_map(function ($result) {
                $mapped = ArrayUtils::remapProperties($result, [
                    "recordID" => "customPageID",
                ]);

                return new SearchResultItem($mapped);
            }, $results);
            return $resultItems;
        } catch (HttpException $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);
            return [];
        }
    }

    /**
     * @inheritdoc
     */
    public function applyToQuery(SearchQuery $query)
    {
        if ($query instanceof MysqlSearchQuery) {
            $query->addSql("");
        } else {
            $query->addIndex($this->getIndex());

            $statuses = $query->getQueryParameter("customPageStatus", [CustomPageModel::STATUS_PUBLISHED]);
            if ($this->shouldQueryNonPublished($query)) {
                $statuses[] = CustomPageModel::STATUS_UNPUBLISHED;
            }
            $query->setFilter("status", $statuses);

            if (!$this->session->checkPermission("site.manage")) {
                $currentUserRoleIDs = $this->userModel->getRoleIDs($this->session->UserID);
                $currentUserRoleIDs[] = null;
                $query->setFilter("roleIDs", $currentUserRoleIDs);

                \Gdn::eventManager()->fire("customPageSearchType_alterSearchQuery", $query);
            }
        }
    }

    /**
     * Whether we should query unpublished pages.
     *
     * @param SearchQuery $query
     *
     * @return bool
     */
    private function shouldQueryNonPublished(SearchQuery $query): bool
    {
        return $this->session->checkPermission("site.manage") &&
            in_array(CustomPageModel::STATUS_UNPUBLISHED, $query->getQueryParameter("customPageStatus", []));
    }

    /**
     * @inheritdoc
     */
    public function getSorts(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getQuerySchema(): Schema
    {
        return Schema::parse([
            "customPageStatus:a?" => [
                "style" => "form",
                "default" => [CustomPageModel::STATUS_PUBLISHED],
                "items" => [
                    "type" => "string",
                    "enum" => CustomPageModel::ALL_STATUSES,
                ],
                "x-search-filter" => true,
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function validateQuery(SearchQuery $query): void
    {
    }

    /**
     * @inheritdoc
     */
    public function getSingularLabel(): string
    {
        return \Gdn::translate("Custom Page");
    }

    /**
     * @inheritdoc
     */
    public function getPluralLabel(): string
    {
        return \Gdn::translate("Custom Pages");
    }

    /**
     * @inheritdoc
     */
    public function getDTypes(): ?array
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function guidToRecordID(int $guid): ?int
    {
        return null;
    }
}
