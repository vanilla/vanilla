<?php
/**
 * @author Gary Pomerant <gpomerant@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license MIT
 */

namespace Vanilla\Forum\Layout\Middleware;

use CategoryModel;
use Garden\Hydrate\DataResolverInterface;
use Garden\Hydrate\Middleware\AbstractMiddleware;
use Garden\Schema\Schema;

/**
 * Middleware that filters based on roleID.
 */
class CategoryFilterMiddleware extends AbstractMiddleware
{
    /** @var CategoryModel */

    private $categoryModel;

    /**
     * DI.
     *
     * @param CategoryModel $categoryModel
     */
    public function __construct(CategoryModel $categoryModel)
    {
        $this->categoryModel = $categoryModel;
    }

    /**
     * Process middleware params
     *
     * @param array $nodeData
     * @param array $middlewareParams
     * @param array $hydrateParams
     * @param DataResolverInterface $next
     * @return mixed|null
     */
    protected function processInternal(
        array $nodeData,
        array $middlewareParams,
        array $hydrateParams,
        DataResolverInterface $next
    ) {
        $categoryID = $hydrateParams["categoryID"] ?? null;
        // Filter on supplied categoryID middleware params here
        if ($categoryID !== null) {
            $response = $this->filterCategoryID($middlewareParams, $categoryID, $nodeData);
            if (is_null($response)) {
                return null;
            }
        }
        return $next->resolve($nodeData, $middlewareParams);
    }

    /**
     * Filter a node if the category doesn't match middleware .
     *
     * @param array $categoryFilter The middleware definition.
     * @param int $categoryID The requested categoryID
     * @param array $data The node data.
     *
     * @return int[]|null
     */
    private function filterCategoryID(array $categoryFilter, int $categoryID, array $data): ?array
    {
        $catChildren = $categoryFilter["includeChildCategories"] ?? false;
        $catFilterID = $categoryFilter["categoryID"];
        if (!is_int($catFilterID)) {
            $catArray = $this->categoryModel::categories($catFilterID);
            $catFilterID = $catArray["CategoryID"];
        }
        $categoryFilterIDs[] = $catFilterID;
        if ($catChildren) {
            $categoryFilterIDs = $this->categoryModel->getCategoriesDescendantIDs($categoryFilterIDs);
        }
        if (!in_array($categoryID, $categoryFilterIDs)) {
            return null;
        }
        return $data;
    }

    /**
     * Get the middleware schema.
     *
     * @return Schema
     */
    public function getSchema(): Schema
    {
        $schema = new Schema([
            "x-no-hydrate" => true,
            "description" => "Category filter middleware.",
            "type" => "object",
            "properties" => [
                "categoryID" => [
                    "type" => ["string", "integer"],
                    "description" =>
                        "Add category based filter by ID or name for the current node." .
                        "Only categories configured here will see the contents of the node.",
                ],
                "includeChildCategories" => [
                    "type" => "boolean",
                    "description" => "Filter will include all child categories.",
                ],
            ],
        ]);
        return $schema;
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return "category-filter";
    }
}
