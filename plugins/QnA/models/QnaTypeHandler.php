<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Exception\ClientException;

/**
 * Class QnaTypeHandler
 */

class QnaTypeHandler extends \Vanilla\AbstractTypeHandler
{
    const HANDLER_TYPE = "Question";

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var \Gdn_Session */
    private $session;

    /** @var QnAPlugin */
    private $qnaPlugin;

    /** @var CategoryModel */
    private $categoryModel;

    /**
     * QnATypeHandler constructor.
     *
     * @param DiscussionModel $discussionModel
     * @param \Gdn_Session $session
     * @param QnAPlugin $qnAPlugin
     * @param CategoryModel $categoryModel
     */
    public function __construct(
        DiscussionModel $discussionModel,
        \Gdn_Session $session,
        QnAPlugin $qnAPlugin,
        CategoryModel $categoryModel
    ) {
        $this->discussionModel = $discussionModel;
        $this->session = $session;
        $this->qnaPlugin = $qnAPlugin;
        $this->categoryModel = $categoryModel;
        $this->setTypeHandlerName(self::HANDLER_TYPE);
    }

    /**
     * Handler the type conversion
     *
     * @param array $from
     * @param string $to
     * @throws ClientException If category doesn't allow record type.
     */
    public function handleTypeConversion(array $from, $to)
    {
        $categoryID = $from["CategoryID"] ?? null;
        $category = $this->categoryModel->getID($categoryID, DATASET_TYPE_ARRAY);
        $allowedTypes = $category["AllowedDiscussionTypes"] ?? [];

        if (in_array(self::HANDLER_TYPE, $allowedTypes) || empty($allowedTypes)) {
            $permissionCategoryID = $from["PermissionCategoryID"] ?? null;
            $this->session->checkPermission("Vanilla.Discussions.Edit", true, "Category", $permissionCategoryID);
            $this->convertTo($from, $to);
        } else {
            throw new ClientException(
                "Category #{$categoryID} doesn't allow for " . self::HANDLER_TYPE . " type records"
            );
        }
    }

    /**
     * Convert the handlers type.
     *
     * @param array $record
     * @param string $to
     */
    public function convertTo(array $record, string $to)
    {
        $id = $record["DiscussionID"] ?? null;
        $this->qnaPlugin->updateRecordType($id, $record, $to);
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
        // answer clean up not implemented.
        return true;
    }
}
