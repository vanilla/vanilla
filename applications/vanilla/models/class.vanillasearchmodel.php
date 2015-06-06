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
    public function DiscussionModel($Value = false) {
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
    public function DiscussionSql($SearchModel, $AddMatch = true) {
        // Get permission and limit search categories if necessary.
        if ($AddMatch) {
            $Perms = CategoryModel::CategoryWatch();

            if ($Perms !== true) {
                $this->SQL->WhereIn('d.CategoryID', $Perms);
            }

            // Build search part of query.
            $SearchModel->AddMatchSql($this->SQL, 'd.Name, d.Body', 'd.DateInserted');
        }

        // Build base query
        $this->SQL
            ->Select('d.DiscussionID as PrimaryID, d.Name as Title, d.Body as Summary, d.Format, d.CategoryID, d.Score')
            ->Select('d.DiscussionID', "concat('/discussion/', %s)", 'Url')
            ->Select('d.DateInserted')
            ->Select('d.InsertUserID as UserID')
            ->Select("'Discussion'", '', 'RecordType')
            ->From('Discussion d');

        if ($AddMatch) {
            // Execute query.
            $Result = $this->SQL->GetSelect();

            // Unset SQL
            $this->SQL->Reset();
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
    public function CommentSql($SearchModel, $AddMatch = true) {
        if ($AddMatch) {
            // Get permission and limit search categories if necessary.
            $Perms = CategoryModel::CategoryWatch();
            if ($Perms !== true) {
                $this->SQL->WhereIn('d.CategoryID', $Perms);
            }

            // Build search part of query
            $SearchModel->AddMatchSql($this->SQL, 'c.Body', 'c.DateInserted');
        }

        // Build base query
        $this->SQL
            ->Select('c.CommentID as PrimaryID, d.Name as Title, c.Body as Summary, c.Format, d.CategoryID, c.Score')
            ->Select("'/discussion/comment/', c.CommentID, '/#Comment_', c.CommentID", "concat", 'Url')
            ->Select('c.DateInserted')
            ->Select('c.InsertUserID as UserID')
            ->Select("'Comment'", '', 'RecordType')
            ->From('Comment c')
            ->Join('Discussion d', 'd.DiscussionID = c.DiscussionID');

        if ($AddMatch) {
            // Exectute query
            $Result = $this->SQL->GetSelect();

            // Unset SQL
            $this->SQL->Reset();
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
    public function Search($SearchModel) {
        $SearchModel->AddSearch($this->DiscussionSql($SearchModel));
        $SearchModel->AddSearch($this->CommentSql($SearchModel));
    }
}
