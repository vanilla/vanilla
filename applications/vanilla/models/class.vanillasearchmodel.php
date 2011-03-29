<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/
/**
 * VanilalSearch Model
 *
 * @package Vanilla
 */
 
/**
 * Manages searches for Vanilla forums.
 *
 * @since 2.0.0
 * @package Vanilla
 */
class VanillaSearchModel extends Gdn_Model {
   /**
    * @var object DiscussionModel
    */	
	protected $_DiscussionModel = FALSE;
	
	/**
	 * Makes a discussion model available.
	 * 
    * @since 2.0.0
    * @access public
	 * 
	 * @param object $Value DiscussionModel.
	 * @return object DiscussionModel.
	 */
	public function DiscussionModel($Value = FALSE) {
		if($Value !== FALSE) {
			$this->_DiscussionModel = $Value;
		}
		if($this->_DiscussionModel === FALSE) {
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
	public function DiscussionSql($SearchModel) {
		// Get permission and limit search categories if necessary
		$Perms = CategoryModel::CategoryWatch();
      if($Perms !== TRUE) {
         $this->SQL->WhereIn('d.CategoryID', $Perms, FALSE);
      }
		
		// Build search part of query
		$SearchModel->AddMatchSql($this->SQL, 'd.Name, d.Body', 'd.DateInserted');
		
		// Build base query
		$this->SQL
			->Select('d.DiscussionID as PrimaryID, d.Name as Title, d.Body as Summary')
			->Select('d.DiscussionID', "concat('/discussion/', %s)", 'Url')
			->Select('d.DateInserted')
			->Select('d.InsertUserID as UserID, u.Name, u.Photo')
			->From('Discussion d')
			->Join('User u', 'd.InsertUserID = u.UserID', 'left');
		
		// Execute query
		$Result = $this->SQL->GetSelect();
		
		// Unset SQL
		$this->SQL->Reset();
		
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
	public function CommentSql($SearchModel) {
		// Get permission and limit search categories if necessary
		$Perms = CategoryModel::CategoryWatch();
      if($Perms !== TRUE) {
         $this->SQL->WhereIn('d.CategoryID', $Perms);
      }
		
		// Build search part of query
		$SearchModel->AddMatchSql($this->SQL, 'c.Body', 'c.DateInserted');
		
		// Build base query
		$this->SQL
			->Select('c.CommentID as PrimaryID, d.Name as Title, c.Body as Summary')
			->Select("'/discussion/comment/', c.CommentID, '/#Comment_', c.CommentID", "concat", 'Url')
			->Select('c.DateInserted')
			->Select('c.InsertUserID as UserID, u.Name, u.Photo')
			->From('Comment c')
			->Join('Discussion d', 'd.DiscussionID = c.DiscussionID')
			->Join('User u', 'u.UserID = c.InsertUserID', 'left');
		
		// Exectute query
		$Result = $this->SQL->GetSelect();
		
		// Unset SQL
		$this->SQL->Reset();
		
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