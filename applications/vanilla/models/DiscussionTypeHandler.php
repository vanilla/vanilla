<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use CategoryModel;
use DiscussionModel;
use Garden\Web\Exception\ClientException;
use Vanilla\Forum\Models\PostMetaModel;

/**
 * Class DiscussionTypeHandler
 *
 * @package Vanilla
 */
class DiscussionTypeHandler extends AbstractTypeHandler
{
    const HANDLER_TYPE = "Discussion";

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var \Gdn_Session */
    private $session;

    /** @var CategoryModel */
    private $categoryModel;

    /**
     * IdeaAbstractType constructor.
     *
     * @param DiscussionModel $discussionModel
     * @param \Gdn_Session $session
     * @param CategoryModel $categoryModel
     */
    public function __construct(
        DiscussionModel $discussionModel,
        \Gdn_Session $session,
        CategoryModel $categoryModel,
        private PostMetaModel $postMetaModel
    ) {
        $this->discussionModel = $discussionModel;
        $this->session = $session;
        $this->setTypeHandlerName(self::HANDLER_TYPE);
        $this->categoryModel = $categoryModel;
    }

    /**
     * Handle conversion of record.
     *
     * @param array $from
     * @param string $to
     * @param array|null $postFields
     * @throws ClientException|\Throwable If category doesn't allow record type.
     */
    public function handleTypeConversion(array $from, $to, ?array $postFields)
    {
        $categoryID = $from["CategoryID"] ?? null;
        $category = $this->categoryModel->getID($categoryID, DATASET_TYPE_ARRAY);

        if ($this->categoryModel->isPostTypeAllowed($category, $to)) {
            $permissionCategoryID = $from["PermissionCategoryID"] ?? null;
            $this->session->checkPermission("Vanilla.Discussions.Edit", true, "Category", $permissionCategoryID);
            $this->convertTo($from, $to);
            $this->postMetaModel->updatePostFields($to, $from["DiscussionID"], $postFields ?? []);
        } else {
            throw new ClientException("Category #{$categoryID} doesn't allow for $to type records");
        }
    }

    /**
     * Convert the handlers type.
     *
     * @param array $record
     * @param string $to
     */
    public function convertTo(array $record, $to)
    {
        $id = $record["DiscussionID"] ?? null;
        $this->discussionModel->setType($id, $to, true);
        $discussionStatusModel = \Gdn::getContainer()->get(\DiscussionStatusModel::class);
        $discussionStatusModel->determineAndUpdateDiscussionStatus($id);
    }

    /**
     * Convert any related records|data (ie. comments)
     *
     * @param array $record
     * @param string $to
     * @return bool
     */
    public function cleanUpRelatedData(array $record, string $to)
    {
        // Delete fields associated with the previous post type.
        $this->postMetaModel->delete([
            "recordType" => $record["postTypeID"] ?? null,
            "recordID" => $record["DiscussionID"],
        ]);
        return true;
    }
}
