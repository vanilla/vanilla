<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class Gdn_SearchModel extends Gdn_Model {
	/// CONSTANTS ///
	protected $NoiseWords = array('and', 'are', 'but', 'for', 'have', 'not', 'out', 'that', 'the', 'this', 'you', 'was', 'with');
	
	/// PROPERTIES ///
	
	protected $_KeywordCache = array();
	
	/// METHODS ///
	public function AddTableType($TableName, $PermissionTableName = '') {
		$Data = $this->SQL->GetWhere('TableType', array('TableName' => $TableName))->FirstRow();
		if(!$Data) {
			$this->SQL->Insert('TableType', array('TableName' => $TableName, 'PermissionTableName' => $PermissionTableName));
		}
	}
	
	public function Delete($Document) {
		$DocumentID = NULL;
		
		if(is_array($Document)) {
			// Get the document id.
			if(!array_key_exists('DocumentID', $Document)) {
				// See if there is already a document.
				$Data = $this->SQL->GetWhere('SearchDocument', array('TableName' => $Document['TableName'], 'PrimaryID' => $Document['PrimaryID']))->FirstRow();
				if($Data) {
					// The document was found, but must be updated.
					$DocumentID = $Data->DocumentID;
				} else {
					$DocumentID = NULL;
				}
				
			} else {
				$DocumentID = $Document['DocumentID'];
			}
		} else {
			$DocumentID = $Document;
		}
		
		// Delete the keyword mappings.
		$this->SQL->Delete('SearchKeywordDocument', array('DocumentID' => $DocumentID));
		$this->SQL->Delete('SearchDocument', array('DocumentID' => $DocumentID));
	}
	
	/**
	 * Filter out all non-indexable words from a keyword string or array.
	 *
	 * @param mixed $Keywords Either a string or an array of strings that contains the keywords.
	 * @return array An array of strings containing only the keywords to index.
	 */
	public function FilterKeywords(&$Keywords) {
		// Convert the noise words to an associative array for speed.
		if(array_key_exists(0, $this->NoiseWords)) {
			$this->NoiseWords = array_fill_keys($this->NoiseWords, TRUE);
		}
		
		if(is_string($Keywords)) {
			// Remove tags and accents.
			$Keywords = utf8_decode(self::StripAccents($Keywords)); //self::StripAccents(strip_tags($Keywords)));
			$Keywords = strtolower(strip_tags($Keywords));
			// Split on non-words, but support tagging with "@" and "#" characters.
			$Keywords = preg_split('/([^\w@#]|_)+/', strip_tags($Keywords));
		}
		
		$count = count($Keywords);
		for($i = 0; $i < $count; ++$i) {
			$Keyword = $Keywords[$i];
			if(strlen($Keyword) <= 2 || strlen($Keyword) > 50 || array_key_exists($Keyword, $this->NoiseWords)) {
				// The keyword is either empty or a noise word, so unset it.
				unset($Keywords[$i]);
			} else {
				$Keywords[$i] = $Keyword;
			}
		}
		
		foreach($Keywords as $i => $Keyword) {
			$Keywords[$i] = utf8_encode($Keyword);
		}
		
		return $Keywords;
	}
	
	public function Index($Document, $Keywords = NULL) {
		$DocumentID = NULL;
		
		// Get the keywords ready for inserting.
		if(is_null($Keywords)) {
			$Keywords = ArrayValue('Summary', $Document, '');
		}
		$this->FilterKeywords($Keywords);
		if(!is_array($Keywords) || count($Keywords) == 0)
			return;
		$Keywords = array_fill_keys($Keywords, NULL);
		$KeywordsToDelete = array();
		
		self::_TrimString('Title', $Document, 50);
		self::_TrimString('Summary', $Document, 200);
		
		// Get the document id.
		if(!array_key_exists('DocumentID', $Document)) {
			// See if there is already a document.
			$Data = $this->SQL->GetWhere('SearchDocument', array('TableName' => $Document['TableName'], 'PrimaryID' => $Document['PrimaryID']))->FirstRow();
			if($Data) {
				// The document was found, but must be updated.
				$DocumentID = $Data->DocumentID;
			} else {
				$DocumentID = NULL;
			}
			
		} else {
			$DocumentID = $Document['DocumentID'];
		}
		
		// Insert or update the document.
		$Set = array_intersect_key($Document, array('TableName' => '', 'PrimaryID' => '', 'PermissionJunctionID' => '', 'Title' => '', 'Summary' => '', 'Url' => '', 'InsertUserID' => '', 'DateInserted' => ''));
		if(is_null($DocumentID)) {
			// There was no document so insert it.
			if(!array_key_exists('DateInserted', $Set)) {
				$Set['DateInserted'] = Format::ToDateTime();
			}
			
			$DocumentID = $this->SQL->Insert('SearchDocument', $Set);
		} else {
			$this->SQL->Update('SearchDocument', $Set, array('DocumentID' => $DocumentID))->Put();
			
			// Get the list of current keywords.
			$Data = $this->SQL->Select('k.KeywordID, k.Keyword')
				->From('SearchKeyword k')
				->Join('SearchKeywordDocument d', 'k.KeywordID = d.KeywordID')
				->Where('d.DocumentID', $DocumentID)
				->Get();
			
			while($Row = $Data->NextRow()) {
				$Keyword = $Row->Keyword;
				$this->_KeywordCache[$Keyword] = $Row->KeywordID;
				if(array_key_exists($Keyword, $Keywords)) {
					// The keyword doesn't have to be inserted.
					unset($Keywords[$Keyword]);
				} else {
					// The keyword has to be deleted.
					$KeywordsToDelete[] = $Row->KeywordID;
				}
			}
		}
		
		// Insert the keywords.
		$Set = array();
		foreach($Keywords as $Keyword => $KeywordID) {
			if(!is_null($KeywordID))
				continue;
			
			$Keyword = substr($Keyword, 0, 50);
			
			// Make sure the keyword is inserted.
			if(array_key_exists($Keyword, $this->_KeywordCache)) {
				$KeywordID = $this->_KeywordCache[$Keyword];
			} else {
				$Data = $this->SQL->GetWhere('SearchKeyword', array('Keyword' => $Keyword))->FirstRow();
				if($Data === FALSE) {
					$KeywordID = $this->SQL->Insert('SearchKeyword', array('Keyword' => $Keyword));
				} else {
					$KeywordID = $Data->KeywordID;
				}
				$this->_KeywordCache[$Keyword] = $KeywordID;
			}
			
			// Build up the set statement.
			$Set[] = array('KeywordID' => $KeywordID, 'DocumentID' => $DocumentID);
		}
		
		// Delete the keyword links.
		if(count($KeywordsToDelete) > 0) {
			$this->SQL->WhereIn('KeywordID', $KeywordsToDelete)->Delete('SearchKeywordDocument', array('DocumentID' => $DocumentID));
		}
		
		// Insert the link to this document.
		$this->SQL->Insert('SearchKeywordDocument', $Set);
	}
	
	public function Search($Search, $Offset = 0, $Limit = 20) {
		// Check to see if this is a quoted search.
		if(preg_match('/["\'].*["\']/', $Search)) {
			$All = TRUE;
		} else {
			$All = FALSE;
		}
		
		$Keywords = $this->FilterKeywords($Search);
		
		// Grab the keyword IDs first.
		$KeywordIDs = array();
		if(count($Keywords) > 0) {	
			$this->SQL->Select('k.KeywordID, k.Keyword')
				->From('SearchKeyword k')
				->WhereIn('k.Keyword', $Keywords);
		
				$Data = $this->SQL->Get()->ResultArray();
				foreach($Data as $Row) {
					$KeywordIDs[] = $Row['KeywordID'];
				}
				
			if($All && count($Keywords) != count($KeywordIDs)) {
				// All keywords must match, but some of the keywords aren't in the database.
				$KeywordIDs = array();
			}
		}
 
		$this->SQL
			->Select('d.PrimaryID, d.Title, d.Summary, d.Url, d.DateInserted')
			->Select('*', 'count', 'Relavence')
			->Select('u.UserID, u.Name')
			->From('SearchKeywordDocument kd')
			->Join('SearchDocument d', 'd.DocumentID = kd.DocumentID')
			->Join('User u', 'd.InsertUserID = u.UserID')
			->GroupBy()
			->Limit($Limit, $Offset)
			->OrderBy('Relavence', 'desc');
			
		$Session = Gdn::Session();
		if(!(is_object($Session->User) && $Session->User->Admin == '1')) {
			// Add the permission in for the search.
			$this->SQL->Join('Permission p', 'd.PermissionJunctionID = p.JunctionID and p.`Vanilla.Discussions.View` = 1');
		}
			
		if($All) {
			$this->SQL->Having('count(*) >=', count($Keywords));
		}
		
		if(count($KeywordIDs) == 0) {
			$this->SQL->Where('0', '1');
		} elseif(count($KeywordIDs) == 1) {
			$this->SQL->Where('kd.KeywordID', $KeywordIDs[0]);
		} else {
			foreach($KeywordIDs as $i => $Keyword) {
				$KeywordIDs[$i] = '@'.$Keyword;
			}
			$this->SQL->WhereIn('kd.KeywordID', $KeywordIDs);
		}
		
		$this->EventArguments['Search'] = $Search;
		$this->EventArguments['Keywords'] = $Keywords;
		$this->EventArguments['KeywordIDs'] = $KeywordIDs;
		$this->FireEvent('AfterBuildSearchQuery');
		
		$Result = $this->SQL->Get();
		$Result->DefaultDatasetType = DATASET_TYPE_ARRAY;
		return $Result;
	}
	
	public static function StripAccents($String) {
		$Fr = 'ÀÁÃÄÅÂÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáãäåâæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ';
		$To = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyrr';
		
		$Result = strtr(utf8_decode($String), utf8_decode($Fr), $To);
		$Result = utf8_decode($Result);
		
		return $Result;
	}
	
	protected static function _TrimString($Key, &$Array, $Length) {
		if(array_key_exists($Key, $Array)) {
			$Value = trim(strip_tags($Array[$Key]));
			if(strlen($Value) > $Length) {
				$Value = utf8_encode(substr(utf8_decode($Value), 0, $Length - 3)) . '...';
			}
			$Array[$Key] = $Value;
		}
	}
}