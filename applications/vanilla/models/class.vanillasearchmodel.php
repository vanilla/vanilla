<?php
/**
 * Vanilla Search model
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
            require_once(dirname(__FILE__).DS.'class.discussionmodel.php');
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
     * @param object $SearchModel SearchModel (Dashboard)
     * @return object SQL result.
     */
    public function discussionSql($SearchModel, $AddMatch = true) {
        // Get permission and limit search categories if necessary.
        if ($AddMatch) {
            $Perms = CategoryModel::CategoryWatch();

            if ($Perms !== true) {
                $this->SQL->whereIn('d.CategoryID', $Perms);
            }

            // Build search part of query.
            $SearchModel->AddMatchSql($this->SQL, 'd.Name, d.Body', 'd.DateInserted');
        }

        // Build base query
        $this->SQL
            ->select('d.DiscussionID as PrimaryID, d.Name as Title, d.Body as Summary, d.Format, d.CategoryID, d.Score')
            ->select('d.DiscussionID', "concat('/discussion/', %s)", 'Url')
            ->select('d.DateInserted')
            ->select('d.InsertUserID as UserID')
            ->select("'Discussion'", '', 'RecordType')
            ->from('Discussion d');

        if ($AddMatch) {
            // Execute query.
            $Result = $this->SQL->GetSelect();

            // Unset SQL
            $this->SQL->reset();
        } else {
            $Result = $this->SQL;
        }

        return $Result;
    }

    /**
     * Execute comment search query.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object $SearchModel SearchModel (Dashboard)
     * @return object SQL result.
     */
    public function commentSql($SearchModel, $AddMatch = true) {
        if ($AddMatch) {
            // Get permission and limit search categories if necessary.
            $Perms = CategoryModel::CategoryWatch();
            if ($Perms !== true) {
                $this->SQL->whereIn('d.CategoryID', $Perms);
            }

            // Build search part of query
            $SearchModel->AddMatchSql($this->SQL, 'c.Body', 'c.DateInserted');
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

        if ($AddMatch) {
            // Exectute query
            $Result = $this->SQL->GetSelect();

            // Unset SQL
            $this->SQL->reset();
        } else {
            $Result = $this->SQL;
        }

        return $Result;
    }

    /**
     * Add the searches for Vanilla to the search model.
     *
     * @since 2.0.0
     * @access public
     *
     * @param object $SearchModel SearchModel (Dashboard)
     */
    public function search($SearchModel) {
        $SearchModel->AddSearch($this->DiscussionSql($SearchModel));
        $SearchModel->AddSearch($this->CommentSql($SearchModel));
    }
}
