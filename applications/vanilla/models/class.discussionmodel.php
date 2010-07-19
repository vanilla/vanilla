<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class DiscussionModel extends VanillaModel {
   /**
    * Class constructor.
    */
   public function __construct() {
      parent::__construct('Discussion');
   }
   
   public function DiscussionSummaryQuery($AdditionalFields = array()) {
      $Perms = $this->CategoryPermissions();
      if($Perms !== TRUE) {
         $this->SQL->WhereIn('d.CategoryID', $Perms);
      }
      
      $this->SQL
         ->Select('d.InsertUserID', '', 'FirstUserID')
         ->Select('d.DateInserted', '', 'FirstDate')
			->Select('d.CountBookmarks')
         ->Select('iu.Name', '', 'FirstName') // <-- Need these for rss!
         ->Select('iu.Photo', '', 'FirstPhoto')
         ->Select('d.Body') // <-- Need these for rss!
         ->Select('d.Format') // <-- Need these for rss!
         ->Select('d.DateLastComment', '', 'LastDate')
         ->Select('d.LastCommentUserID', '', 'LastUserID')
         ->Select('lcu.Name', '', 'LastName')
         ->Select("' &rarr; ', pc.Name, ca.Name", 'concat_ws', 'Category')
         ->Select('ca.UrlCode', '', 'CategoryUrlCode')
         ->From('Discussion d')
         ->Join('User iu', 'd.InsertUserID = iu.UserID', 'left') // First comment author is also the discussion insertuserid
         ->Join('User lcu', 'd.LastCommentUserID = lcu.UserID', 'left') // Last comment user
         ->Join('Category ca', 'd.CategoryID = ca.CategoryID', 'left') // Category
         ->Join('Category pc', 'ca.ParentCategoryID = pc.CategoryID', 'left'); // Parent category
			
		if(is_array($AdditionalFields)) {
			foreach($AdditionalFields as $Alias => $Field) {
				// See if a new table needs to be joined to the query.
				$TableAlias = explode('.', $Field);
				$TableAlias = $TableAlias[0];
				if(array_key_exists($TableAlias, $Tables)) {
					$Join = $Tables[$TableAlias];
					$this->SQL->Join($Join[0], $Join[1]);
					unset($Tables[$TableAlias]);
				}
				
				// Select the field.
				$this->SQL->Select($Field, '', is_numeric($Alias) ? '' : $Alias);
			}
		}
         
      $this->FireEvent('AfterDiscussionSummaryQuery');
   }
   
   public function Get($Offset = '0', $Limit = '', $Wheres = '', $AdditionalFields = NULL) {
      if ($Limit == '') 
         $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 50);

      $Offset = !is_numeric($Offset) || $Offset < 0 ? 0 : $Offset;
      
      $Session = Gdn::Session();
      $UserID = $Session->UserID > 0 ? $Session->UserID : 0;
      $this->DiscussionSummaryQuery();
      $this->SQL
         ->Select('d.*');
         
      if ($UserID > 0) {
         $this->SQL
            ->Select('w.UserID', '', 'WatchUserID')
            ->Select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
            ->Select('w.CountComments', '', 'CountCommentWatch')
            ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$UserID, 'left');
      } else {
			$this->SQL
				->Select('0', '', 'WatchUserID')
				->Select('now()', '', 'DateLastViewed')
				->Select('0', '', 'Dismissed')
				->Select('0', '', 'Bookmarked')
				->Select('0', '', 'CountCommentWatch')
				->Select('d.Announce','','IsAnnounce');
      }
		
		$this->AddArchiveWhere($this->SQL);
      
      if (is_array($Wheres))
         $this->SQL->Where($Wheres);
			
		// If not looking at discussions filtered by bookmarks or user, filter announcements out.
		if (!isset($Wheres['w.Bookmarked']) && !isset($Wheres['d.InsertUserID']))
			$this->SQL->Where('d.Announce<>', '1');
			
		$this->FireEvent('BeforeGet');
      
      $Data = $this->SQL
         ->OrderBy('d.DateLastComment', 'desc')
         ->Limit($Limit, $Offset)
         ->Get();
			
		$this->AddDiscussionColumns($Data);
		
		return $Data;
   }
	
	public function AddDiscussionColumns($Data) {
		// Change discussions based on archiving.
		$ArchiveTimestamp = Gdn_Format::ToTimestamp(Gdn::Config('Vanilla.Archive.Date', 0));
		$Result = &$Data->Result();
		foreach($Result as &$Discussion) {
			if(Gdn_Format::ToTimestamp($Discussion->DateLastComment) <= $ArchiveTimestamp) {
				$Discussion->Closed = '1';
				if($Discussion->CountCommentWatch) {
					$Discussion->CountUnreadComments = $Discussion->CountComments - $Discussion->CountCommentWatch;
				} else {
					$Discussion->CountUnreadComments = 0;
				}
			} else {
				$Discussion->CountUnreadComments = $Discussion->CountComments - $Discussion->CountCommentWatch;
			}
		}
	}
	
	/**
	 * @param Gdn_SQLDriver $Sql
	 */
	public function AddArchiveWhere($Sql = NULL) {
		if(is_null($Sql))
			$Sql = $this->SQL;
		
		$Exclude = Gdn::Config('Vanilla.Archive.Exclude');
		if($Exclude) {
			$ArchiveDate = Gdn::Config('Vanilla.Archive.Date');
			if($ArchiveDate) {
				$Sql->Where('d.DateLastComment >', $ArchiveDate);
			}
		}
	}

   public function GetAnnouncements($Wheres = '') {
      $Session = Gdn::Session();
      $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 50);
      $Offset = 0;
      $UserID = $Session->UserID > 0 ? $Session->UserID : 0;
      $this->DiscussionSummaryQuery();
      $this->SQL
         ->Select('d.*')
         ->Select('w.UserID', '', 'WatchUserID')
         ->Select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
         ->Select('w.CountComments', '', 'CountCommentWatch')
         ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$UserID, 'left');
      
      if (is_array($Wheres))
         $this->SQL->Where($Wheres);
         
      $Data = $this->SQL
         ->Where('d.Announce', '1')
			->BeginWhereGroup()
         ->Where('w.Dismissed is null')
         ->OrWhere('w.Dismissed', '0')
         ->OrderBy('d.DateLastComment', 'desc')
         ->Limit($Limit, $Offset)
         ->Get();
			
		$this->AddDiscussionColumns($Data);
		return $Data;
   }
   
   // Returns all users who have bookmarked the specified discussion
   public function GetBookmarkUsers($DiscussionID) {
      return $this->SQL
         ->Select('UserID')
         ->From('UserDiscussion')
         ->Where('DiscussionID', $DiscussionID)
         ->Where('Bookmarked', '1')
         ->Get();
   }
   
   protected $_CategoryPermissions = NULL;
   
   public function CategoryPermissions($Escape = FALSE) {
      if(is_null($this->_CategoryPermissions)) {
         $Session = Gdn::Session();
         
         if((is_object($Session->User) && $Session->User->Admin == '1')) {
            $this->_CategoryPermissions = TRUE;
			} elseif(C('Garden.Permissions.Disabled.Category')) {
				if($Session->CheckPermission('Vanilla.Discussions.View'))
					$this->_CategoryPermissions = TRUE;
				else
					$this->_CategoryPermissions = array(); // no permission
         } else {
            $Data = $this->SQL
               ->Select('c.CategoryID')
               ->From('Category c')
               ->Permission('Vanilla.Discussions.View', 'c', 'CategoryID')
               ->Get();
            
            $Data = $Data->ResultArray();
            $this->_CategoryPermissions = array();
            foreach($Data as $Row) {
               $this->_CategoryPermissions[] = ($Escape ? '@' : '').$Row['CategoryID'];
            }
         }
      }
      
      return $this->_CategoryPermissions;
   }

   public function GetCount($Wheres = '', $ForceNoAnnouncements = FALSE) {
      $Session = Gdn::Session();
      $UserID = $Session->UserID > 0 ? $Session->UserID : 0;
      if (is_array($Wheres) && count($Wheres) == 0)
         $Wheres = '';
         
      $Perms = $this->CategoryPermissions();
      if($Perms !== TRUE) {
         $this->SQL->WhereIn('c.CategoryID', $Perms);
      }
         
      // Small optimization for basic queries
      if ($Wheres == '') {
         $this->SQL
            ->Select('c.CountDiscussions', 'sum', 'CountDiscussions')
            ->From('Category c');
      } else {
         $this->SQL
	         ->Select('d.DiscussionID', 'count', 'CountDiscussions')
	         ->From('Discussion d')
            ->Join('Category c', 'd.CategoryID = c.CategoryID')
	         ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$UserID, 'left')
            ->Where($Wheres);
      }
      return $this->SQL
         ->Get()
         ->FirstRow()
         ->CountDiscussions;
   }

   public function GetID($DiscussionID) {
      $Session = Gdn::Session();
      $this->FireEvent('BeforeGetID');
      $Data = $this->SQL
         ->Select('d.*')
         ->Select('ca.Name', '', 'Category')
         ->Select('ca.UrlCode', '', 'CategoryUrlCode')
         ->Select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
         ->Select('w.CountComments', '', 'CountCommentWatch')
         ->Select('d.DateLastComment', '', 'LastDate')
         ->Select('d.LastCommentUserID', '', 'LastUserID')
         ->Select('lcu.Name', '', 'LastName')
			->Select('iu.Name', '', 'InsertName')
			->Select('iu.Photo', '', 'InsertPhoto')
         ->From('Discussion d')
         ->Join('Category ca', 'd.CategoryID = ca.CategoryID', 'left')
         ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$Session->UserID, 'left')
			->Join('User iu', 'd.InsertUserID = iu.UserID', 'left') // Insert user
			->Join('Comment lc', 'd.LastCommentID = lc.CommentID', 'left') // Last comment
         ->Join('User lcu', 'lc.InsertUserID = lcu.UserID', 'left') // Last comment user
         ->Where('d.DiscussionID', $DiscussionID)
         ->Get()
         ->FirstRow();
		
		if (
			$Data
			&& Gdn_Format::ToTimestamp($Data->DateLastComment) <= Gdn_Format::ToTimestamp(Gdn::Config('Vanilla.Archive.Date', 0))
		) {
			$Data->Closed = '1';
		}
		return $Data;
   }
   
   /**
    * Marks the specified announcement as dismissed by the specified user.
    *
    * @param int The unique id of the discussion being affected.
    * @param int The unique id of the user being affected.
    */
   public function DismissAnnouncement($DiscussionID, $UserID) {
      $Count = $this->SQL
         ->Select('UserID')
         ->From('UserDiscussion')
         ->Where('DiscussionID', $DiscussionID)
         ->Where('UserID', $UserID)
         ->Get()
         ->NumRows();
         
      $CountComments = $this->SQL
         ->Select('CountComments')
         ->From('Discussion')
         ->Where('DiscussionID', $DiscussionID)
         ->Get()
         ->FirstRow()
         ->CountComments;
      
      if ($Count > 0) {
         $this->SQL
            ->Update('UserDiscussion')
            ->Set('CountComments', $CountComments)
            ->Set('DateLastViewed', Gdn_Format::ToDateTime())
            ->Set('Dismissed', '1')
            ->Where('DiscussionID', $DiscussionID)
            ->Where('UserID', $UserID)
            ->Put();
      } else {
         $this->SQL->Insert(
            'UserDiscussion',
            array(
               'UserID' => $UserID,
               'DiscussionID' => $DiscussionID,
               'CountComments' => $CountComments,
               'DateLastViewed' => Gdn_Format::ToDateTime(),
               'Dismissed' => '1'
            )
         );
      }
   }
   
   public function Save($FormPostValues) {
      $Session = Gdn::Session();
      
      // Define the primary key in this model's table.
      $this->DefineSchema();
      
      // Add & apply any extra validation rules:      
      $this->Validation->ApplyRule('Body', 'Required');
      $MaxCommentLength = Gdn::Config('Vanilla.Comment.MaxLength');
      if (is_numeric($MaxCommentLength) && $MaxCommentLength > 0) {
         $this->Validation->SetSchemaProperty('Body', 'Length', $MaxCommentLength);
         $this->Validation->ApplyRule('Body', 'Length');
      }      
      
      // Get the DiscussionID from the form so we know if we are inserting or updating.
      $DiscussionID = ArrayValue('DiscussionID', $FormPostValues, '');
      $Insert = $DiscussionID == '' ? TRUE : FALSE;
		$this->EventArguments['Insert'] = $Insert;
      
      if ($Insert) {
         unset($FormPostValues['DiscussionID']);
         // If no categoryid is defined, grab the first available.
         if (ArrayValue('CategoryID', $FormPostValues) === FALSE)
            $FormPostValues['CategoryID'] = $this->SQL->Get('Category', '', '', 1)->FirstRow()->CategoryID;
            
         $this->AddInsertFields($FormPostValues);
         // $FormPostValues['LastCommentUserID'] = $Session->UserID;
         $FormPostValues['DateLastComment'] = Gdn_Format::ToDateTime();
      }
      // Add the update fields because this table's default sort is by DateUpdated (see $this->Get()).
      $this->AddUpdateFields($FormPostValues);
      
      // Remove checkboxes from the fields if they were unchecked
      if (ArrayValue('Announce', $FormPostValues, '') === FALSE)
         unset($FormPostValues['Announce']);

      if (ArrayValue('Closed', $FormPostValues, '') === FALSE)
         unset($FormPostValues['Closed']);

      if (ArrayValue('Sink', $FormPostValues, '') === FALSE)
         unset($FormPostValues['Sink']);
			
		$this->EventArguments['FormPostValues'] = $FormPostValues;
		$this->EventArguments['DiscussionID'] = $DiscussionID;
		$this->FireEvent('BeforeSaveDiscussion');
         
      // Validate the form posted values
      if ($this->Validate($FormPostValues, $Insert)) {
         // If the post is new and it validates, make sure the user isn't spamming
         if (!$Insert || !$this->CheckForSpam('Discussion')) {
            $Fields = $this->Validation->SchemaValidationFields(); // All fields on the form that relate to the schema
            $DiscussionID = intval(ArrayValue('DiscussionID', $Fields, 0));
            $Fields = RemoveKeyFromArray($Fields, 'DiscussionID'); // Remove the primary key from the fields for saving
            $Discussion = FALSE;
            if ($DiscussionID > 0) {
               $this->SQL->Put($this->Name, $Fields, array($this->PrimaryKey => $DiscussionID));
            } else {
					$Fields['Format'] = Gdn::Config('Garden.InputFormatter', '');
               $DiscussionID = $this->SQL->Insert($this->Name, $Fields);
               // Assign the new DiscussionID to the comment before saving
               $FormPostValues['IsNewDiscussion'] = TRUE;
               $FormPostValues['DiscussionID'] = $DiscussionID;
               
               // Notify users of mentions
               $DiscussionName = ArrayValue('Name', $Fields, '');
               $Usernames = GetMentions($DiscussionName);
               $UserModel = Gdn::UserModel();
               foreach ($Usernames as $Username) {
                  $User = $UserModel->GetByUsername($Username);
                  if ($User && $User->UserID != $Session->UserID) {
                     AddActivity(
                        $Session->UserID,
                        'DiscussionMention',
                        '',
                        $User->UserID,
                        '/discussion/'.$DiscussionID.'/'.Gdn_Format::Url($DiscussionName)
                     );
                  }
               }
					
               // Notify any users who were mentioned in the comment
					$DiscussionName = ArrayValue('Name', $Fields, '');
               $Story = ArrayValue('Body', $Fields, '');
               $Usernames = GetMentions($Story);
               $NotifiedUsers = array();
               foreach ($Usernames as $Username) {
                  $User = $UserModel->GetByUsername($Username);
                  if ($User && $User->UserID != $Session->UserID) {
                     $NotifiedUsers[] = $User->UserID;   
                     $ActivityModel = new ActivityModel();   
                     $ActivityID = $ActivityModel->Add(
                        $Session->UserID,
                        'CommentMention',
                        Anchor(Gdn_Format::Text($DiscussionName), '/discussion/'.$DiscussionID.'/'.Gdn_Format::Url($DiscussionName), FALSE),
                        $User->UserID,
                        '',
                        '/discussion/'.$DiscussionID.'/'.Gdn_Format::Url($DiscussionName),
                        FALSE
                     );
                     $ActivityModel->SendNotification($ActivityID, $Story);
                  }
               }
					
               $this->RecordActivity($Session->UserID, $DiscussionID, $DiscussionName);
            }
            $Data = $this->SQL
               ->Select('CategoryID')
               ->From('Discussion')
               ->Where('DiscussionID', $DiscussionID)
               ->Get();
            
            $CategoryID = FALSE;
            if ($Data->NumRows() > 0)
               $CategoryID = $Data->FirstRow()->CategoryID;

            $this->UpdateDiscussionCount($CategoryID);
				
				// Fire an event that the discussion was saved.
				$this->EventArguments['FormPostValues'] = $FormPostValues;
				$this->EventArguments['Fields'] = $Fields;
				$this->EventArguments['DiscussionID'] = $DiscussionID;
				$this->FireEvent('AfterSaveDiscussion');

         }
      }
      return $DiscussionID;
   }
   
   public function RecordActivity($UserID, $DiscussionID, $DiscussionName) {
      // Report that the discussion was created
      AddActivity(
         $UserID,
         'NewDiscussion',
         Anchor(Gdn_Format::Text($DiscussionName), 'discussion/'.$DiscussionID.'/'.Gdn_Format::Url($DiscussionName))
      );
      
      // Get the user's discussion count
      $Data = $this->SQL
         ->Select('DiscussionID', 'count', 'CountDiscussions')
         ->From('Discussion')
         ->Where('InsertUserID', $UserID)
         ->Get();
      
      // Save the count to the user table
      $this->SQL
         ->Update('User')
         ->Set('CountDiscussions', $Data->NumRows() > 0 ? $Data->FirstRow()->CountDiscussions : 0)
         ->Where('UserID', $UserID)
         ->Put();
   }
   
   /**
    * Updates the CountDiscussions value on the category based on the CategoryID
    * being saved. 
    *
    * @param int The DiscussionID relating to the category we are updating.
    */
   public function UpdateDiscussionCount($CategoryID) {
		if(strcasecmp($CategoryID, 'All') == 0) {
			$Exclude = (bool)Gdn::Config('Vanilla.Archive.Exclude');
			$ArchiveDate = Gdn::Config('Vanilla.Archive.Date');
			$Params = array();
			$Where = '';
			
			if($Exclude && $ArchiveDate) {
				$Where = 'where d.DateLastComment > :ArchiveDate';
				$Params[':ArchiveDate'] = $ArchiveDate;
			}
			
			// Update all categories.
			$Sql = "update :_Category c
left join (
  select
    d.CategoryID,
    count(d.DiscussionID) as CountDiscussions
  from :_Discussion d
  $Where
  group by d.CategoryID
) d
  on c.CategoryID = d.CategoryID
set c.CountDiscussions = coalesce(d.CountDiscussions, 0)";
			$Sql = str_replace(':_', $this->Database->DatabasePrefix, $Sql);
			$this->Database->Query($Sql, $Params, 'DiscussionModel_UpdateDiscussionCount');
			
		} elseif (is_numeric($CategoryID) && $CategoryID > 0) {
         $this->SQL
            ->Select('d.DiscussionID', 'count', 'CountDiscussions')
            ->From('Discussion d')
            ->Where('d.CategoryID', $CategoryID);
         
			$this->AddArchiveWhere();
			
			$Data = $this->SQL->Get()->FirstRow();
         $Count = $Data ? $Data->CountDiscussions : 0;
         
         if ($Count >= 0) {
            $this->SQL
               ->Update('Category')
               ->Set('CountDiscussions', $Count)
               ->Where('CategoryID', $CategoryID)
               ->Put();
         }
      }
   }
	
	/**
	 * Set the bookmark count for the specified user. Returns the bookmark count.
	 */
	public function SetUserBookmarkCount($UserID) {
		$Count = $this->UserBookmarkCount($UserID);
      $this->SQL
         ->Update('User')
         ->Set('CountBookmarks', $Count)
         ->Where('UserID', $UserID)
         ->Put();
		
		return $Count;
	}
   
   /**
    * Announces (or unannounces) a discussion. Returns the value that was set.
    */
   public function SetProperty($DiscussionID, $Property, $ForceValue = FALSE) {
      if ($ForceValue !== FALSE) {
         $Value = $ForceValue;
      } else {
         $Value = '1';
         $Discussion = $this->GetID($DiscussionID);
         $Value = ($Discussion->$Property == '1' ? '0' : '1');
      }
      $this->SQL
         ->Update('Discussion')
         ->Set($Property, $Value)
         ->Where('DiscussionID', $DiscussionID)
         ->Put();
      return $Value;
   }
      
	// Sets the UserDiscussion Score value
	public function SetUserScore($DiscussionID, $UserID, $Score) {
		// Insert or update the UserDiscussion row
		$this->SQL->Replace(
			'UserDiscussion',
			array('Score' => $Score),
			array('DiscussionID' => $DiscussionID, 'UserID' => $UserID)
		);
		
		// Get the total new score
		$TotalScore = $this->SQL->Select('Score', 'sum', 'TotalScore')
			->From('UserDiscussion')
			->Where('DiscussionID', $DiscussionID)
			->Get()
			->FirstRow()
			->TotalScore;
			
		// Update the Discussion's cached version
		$this->SQL->Update('Discussion')
			->Set('Score', $TotalScore)
			->Where('DiscussionID', $DiscussionID)
			->Put();
			
		return $TotalScore;
	}

	// Gets the UserDiscussion Score value for the specified user
	public function GetUserScore($DiscussionID, $UserID) {
		$Data = $this->SQL->Select('Score')
			->From('UserDiscussion')
			->Where('DiscussionID', $DiscussionID)
			->Where('UserID', $UserID)
			->Get()
			->FirstRow();
		
		return $Data ? $Data->Score : 0;
	}

	/**
	 * Increments the view count for the specified discussion.
	 */
	public function AddView($DiscussionID) {
      $this->SQL
         ->Update('Discussion')
         ->Set('CountViews', 'CountViews + 1', FALSE)
         ->Where('DiscussionID', $DiscussionID)
         ->Put();
	}

   /**
    * Bookmarks (or unbookmarks) a discussion. Returns the current state of the
    * bookmark (ie. TRUE for bookmarked, FALSE for unbookmarked)
    */
   public function BookmarkDiscussion($DiscussionID, $UserID, &$Discussion = NULL) {
      $State = '1';

      $Discussion = $this->SQL
         ->Select('d.*')
         ->Select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
         ->Select('w.CountComments', '', 'CountCommentWatch')
         ->Select('w.UserID', '', 'WatchUserID')
         ->Select('d.DateLastComment', '', 'LastDate')
         ->Select('d.LastCommentUserID', '', 'LastUserID')
         ->Select('lcu.Name', '', 'LastName')
         ->From('Discussion d')
         ->Join('UserDiscussion w', "d.DiscussionID = w.DiscussionID and w.UserID = $UserID", 'left')
			->Join('User lcu', 'd.LastCommentUserID = lcu.UserID', 'left') // Last comment user
         ->Where('d.DiscussionID', $DiscussionID)
         ->Get()
         ->FirstRow();

      if ($Discussion->WatchUserID == '') {
         $this->SQL
            ->Insert('UserDiscussion', array(
               'UserID' => $UserID,
               'DiscussionID' => $DiscussionID,
               'Bookmarked' => $State
            ));
      } else {
         $State = ($Discussion->Bookmarked == '1' ? '0' : '1');
         $this->SQL
            ->Update('UserDiscussion')
            ->Set('Bookmarked', $State)
            ->Where('UserID', $UserID)
            ->Where('DiscussionID', $DiscussionID)
            ->Put();
      }
		
		// Update the cached bookmark count on the discussion
		$BookmarkCount = $this->BookmarkCount($DiscussionID);
		$this->SQL->Update('Discussion')
			->Set('CountBookmarks', $BookmarkCount)
			->Where('DiscussionID', $DiscussionID)
			->Put();
			
      $this->EventArguments['Discussion'] = $Discussion;
      $this->EventArguments['State'] = $State;
      $this->FireEvent('AfterBookmarkDiscussion');
      return $State == '1' ? TRUE : FALSE;
   }
   
   /**
    * The number of bookmarks the specified $DiscussionID has.
    */
   public function BookmarkCount($DiscussionID) {
      $Data = $this->SQL
         ->Select('DiscussionID', 'count', 'Count')
         ->From('UserDiscussion')
         ->Where('DiscussionID', $DiscussionID)
         ->Where('Bookmarked', '1')
         ->Get()
         ->FirstRow();
         
      return $Data !== FALSE ? $Data->Count : 0;
   }

   /**
    * The number of bookmarks the specified $UserID has.
    */
   public function UserBookmarkCount($UserID) {
      $Data = $this->SQL
         ->Select('ud.DiscussionID', 'count', 'Count')
         ->From('UserDiscussion ud')
         ->Join('Discussion d', 'd.DiscussionID = ud.DiscussionID')
         ->Where('ud.UserID', $UserID)
         ->Where('ud.Bookmarked', '1')
         ->Get()
         ->FirstRow();
         
      return $Data !== FALSE ? $Data->Count : 0;
   }
   
	/**
	 * Delete a discussion. Update and/or delete all related data.
	 */
   public function Delete($DiscussionID) {
		// Retrieve the users who have bookmarked this discussion.
		$BookmarkData = $this->GetBookmarkUsers($DiscussionID);

      $Data = $this->SQL
         ->Select('CategoryID,InsertUserID')
         ->From('Discussion')
         ->Where('DiscussionID', $DiscussionID)
         ->Get();
      
      $UserID = FALSE;
      $CategoryID = FALSE;
      if ($Data->NumRows() > 0) {
         $UserID = $Data->FirstRow()->InsertUserID;
         $CategoryID = $Data->FirstRow()->CategoryID;
      }
      
      $this->SQL->Delete('Draft', array('DiscussionID' => $DiscussionID));
      $this->SQL->Delete('Comment', array('DiscussionID' => $DiscussionID));
      $this->SQL->Delete('Discussion', array('DiscussionID' => $DiscussionID));
		$this->SQL->Delete('UserDiscussion', array('DiscussionID' => $DiscussionID));
      $this->UpdateDiscussionCount($CategoryID);
      
      // Get the user's discussion count
      $Data = $this->SQL
         ->Select('DiscussionID', 'count', 'CountDiscussions')
         ->From('Discussion')
         ->Where('InsertUserID', $UserID)
         ->Get();
      
      // Save the count to the user table
      $this->SQL
         ->Update('User')
         ->Set('CountDiscussions', $Data->NumRows() > 0 ? $Data->FirstRow()->CountDiscussions : 0)
         ->Where('UserID', $UserID)
         ->Put();

		// Update bookmark counts for users who had bookmarked this discussion
		foreach ($BookmarkData->Result() as $User) {
			$this->SetUserBookmarkCount($User->UserID);
		}
			
      return TRUE;
   }
}
