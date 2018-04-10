<?php
/**
 * Vanilla Search model
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Manages searches for Vanilla forums.
 */
class VanillaSearchModel extends Gdn_Model {

    /** @var object DiscussionModel */
    protected $_DiscussionModel = false;

    /**
     * Makes a discussion model available.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object $Value DiscussionModel.
     * @return object DiscussionModel.
     */
    public function discussionModel($Value = false) {
        if ($Value !== false) {
            $this->_DiscussionModel = $Value;
        }
        if ($this->_DiscussionModel === false) {
            require_once(__DIR__.DS.'class.discussionmodel.php');
            $this->_DiscussionModel = new DiscussionModel();
        }
        return $this->_DiscussionModel;
    }

    /**
     * Execute discussion search query.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object $searchModel SearchModel (Dashboard)
     * @return object SQL result.
     */
    public function discussionSql($searchModel, $addMatch = true) {
        // Get permission and limit search categories if necessary.
        if ($addMatch) {
            $perms = CategoryModel::instance()->getVisibleCategoryIDs();

            if ($perms !== true) {
                $this->SQL->whereIn('d.CategoryID', $perms);
            }

            // Build search part of query.
            $searchModel->addMatchSql($this->SQL, 'd.Name, d.Body', 'd.DateInserted');
        }

        // Build base query
        $this->SQL
            ->select('d.DiscussionID as PrimaryID, d.Name as Title, d.Body as Summary, d.Format, d.CategoryID, d.Score')
            ->select('d.DiscussionID', "concat('/discussion/', %s)", 'Url')
            ->select('d.DateInserted')
            ->select('d.InsertUserID as UserID')
            ->select("'Discussion'", '', 'RecordType')
            ->from('Discussion d');

        if ($addMatch) {
            // Execute query.
            $result = $this->SQL->getSelect();

            // Unset SQL
            $this->SQL->reset();
        } else {
            $result = $this->SQL;
        }

        return $result;
    }

    /**
     * Execute comment search query.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object $searchModel SearchModel (Dashboard)
     * @return object SQL result.
     */
    public function commentSql($searchModel, $addMatch = true) {
        if ($addMatch) {
            // Get permission and limit search categories if necessary.
            $perms = CategoryModel::instance()->getVisibleCategoryIDs();
            if ($perms !== true) {
                $this->SQL->whereIn('d.CategoryID', $perms);
            }

            // Build search part of query
            $searchModel->addMatchSql($this->SQL, 'c.Body', 'c.DateInserted');
        }

        // Build base query
        $this->SQL
            ->select('c.CommentID as PrimaryID, d.Name as Title, c.Body as Summary, c.Format, d.CategoryID, c.Score')
            ->select("'/discussion/comment/', c.CommentID, '/#Comment_', c.CommentID", "concat", 'Url')
            ->select('c.DateInserted')
            ->select('c.InsertUserID as UserID')
            ->select("'Comment'", '', 'RecordType')
            ->from('Comment c')
            ->join('Discussion d', 'd.DiscussionID = c.DiscussionID');

        if ($addMatch) {
            // Exectute query
            $result = $this->SQL->getSelect();

            // Unset SQL
            $this->SQL->reset();
        } else {
            $result = $this->SQL;
        }

        return $result;
    }

    /**
     * Add the searches for Vanilla to the search model.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object $searchModel SearchModel (Dashboard)
     */
    public function search($searchModel) {
        $searchModel->addSearch($this->discussionSql($searchModel));
        $searchModel->addSearch($this->commentSql($searchModel));
    }
}
