<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Controllers\API;

use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Models\CustomPageModel;

class CustomPagesApiController extends \AbstractApiController
{
    public function __construct(private CustomPageModel $customPageModel)
    {
    }

    /**
     * List custom pages.
     *
     * @param array $query
     * @return array
     */
    public function index(array $query): array
    {
        $this->permission("settings.manage");
        $in = $this->indexSchema();
        $query = $in->validate($query);

        $rows = $this->customPageModel->selectWithLayoutID($query);
        return $rows;
    }

    /**
     * Get a custom page.
     *
     * @param int $id
     * @return array
     */
    public function get(int $id): array
    {
        $row = $this->getCustomPage($id);
        return $row;
    }

    /**
     * Create a custom page.
     *
     * @param array $body
     * @return array
     */
    public function post(array $body): array
    {
        $this->permission("settings.manage");
        $in = $this->schema($this->customPageModel->commonPostPatchSchema());
        $body = $in->validate($body);

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
        $this->permission("settings.manage");
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
        $this->permission("settings.manage");
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
            "status:a?" => [
                "style" => "form",
                "default" => CustomPageModel::ALL_STATUSES,
                "items" => [
                    "type" => "string",
                    "enum" => CustomPageModel::ALL_STATUSES,
                ],
            ],
        ]);
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
}
