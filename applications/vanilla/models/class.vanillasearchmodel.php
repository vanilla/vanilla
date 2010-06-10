<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class VanillaSearchModel extends Gdn_Model {
	/// PROPERTIES /// 
	
	protected $_DiscussionModel = FALSE;
	/**
	 * Get/set the category model.
	 * @param DiscussionModel $Value The value to set.
	 * @return DiscussionModel
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
	
	
	/// METHODS ///
	public function DiscussionSql($SearchModel) {
		$Perms = $this->DiscussionModel()->CategoryPermissions(TRUE);
      if($Perms !== TRUE) {
         $this->SQL->WhereIn('d.CategoryID', $Perms, FALSE);
      }
		
		$SearchModel->AddMatchSql($this->SQL, 'd.Name, d.Body', 'd.DateInserted');
		
		$this->SQL
			->Select('d.DiscussionID as PrimaryID, d.Name as Title, d.Body as Summary')
			->Select('d.DiscussionID', "concat('/discussion/', %s)", 'Url')
			->Select('d.DateInserted')
			->Select('d.InsertUserID as UserID, u.Name')
			->From('Discussion d')
			->Join('User u', 'd.InsertUserID = u.UserID', 'left');
		
		$Result = $this->SQL->GetSelect();
		$this->SQL->Reset();
		return $Result;
	}
	
	public function CommentSql($SearchModel) {
		$Perms = $this->DiscussionModel()->CategoryPermissions(TRUE);
      if($Perms !== TRUE) {
         $this->SQL->WhereIn('d.CategoryID', $Perms, FALSE);
      }
		
		$SearchModel->AddMatchSql($this->SQL, 'c.Body', 'c.DateInserted');
		
		$this->SQL
			->Select('c.CommentID as PrimaryID, d.Name as Title, c.Body as Summary')
			->Select("'/discussion/comment/', c.CommentID, '/#Comment_', c.CommentID", "concat", 'Url')
			->Select('c.DateInserted')
			->Select('c.InsertUserID, u.Name')
			->From('Comment c')
			->Join('Discussion d', 'd.DiscussionID = c.DiscussionID')
			->Join('User u', 'u.UserID = d.InsertUserID', 'left');
		
		$Result = $this->SQL->GetSelect();
		$this->SQL->Reset();
		return $Result;
	}
	
	/**
	 * Add the searches for vanilla to the search model.
	 * @param SearchModel $SearchModel
	 */
	public function Search($SearchModel) {
		$SearchModel->AddSearch($this->DiscussionSql($SearchModel));
		$SearchModel->AddSearch($this->CommentSql($SearchModel));
	}
}