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
 * Discussion Model
 *
 * @package Vanilla
 */
 
/**
 * Manages discussions.
 *
 * @since 2.0.0
 * @package Vanilla
 */
class DiscussionModel extends VanillaModel {
   /**
    * @var array
    */
   protected $_CategoryPermissions = NULL;
   
   /**
    * Class constructor. Defines the related database table name.
    * 
    * @since 2.0.0
    * @access public
    */
   public function __construct() {
      parent::__construct('Discussion');
   }
   
   /**
    * Builds base SQL query for discussion data.
    * 
    * Events: AfterDiscussionSummaryQuery.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param array $AdditionalFields Allows selection of additional fields as Alias=>Table.Fieldname.
    */
   public function DiscussionSummaryQuery($AdditionalFields = array()) {
      // Verify permissions (restricting by category if necessary)
      $Perms = $this->CategoryPermissions();
      if($Perms !== TRUE) {
         $this->SQL->WhereIn('d.CategoryID', $Perms);
      }
      
      // Buid main query
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
		
		// Add any additional fields that were requested	
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
   
   /**
    * Gets the data for multiple discussions based on the given criteria.
    * 
    * Sorts results based on config options Vanilla.Discussions.SortField
    * and Vanilla.Discussions.SortDirection.
    * Events: BeforeGet, AfterAddColumns.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $Offset Number of discussions to skip.
    * @param int $Limit Max number of discussions to return.
    * @param array $Wheres SQL conditions.
    * @param array $AdditionalFields Allows selection of additional fields as Alias=>Table.Fieldname.
    * @return object SQL result.
    */
   public function Get($Offset = '0', $Limit = '', $Wheres = '', $AdditionalFields = NULL) {
      if ($Limit == '') 
         $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 50);

      $Offset = !is_numeric($Offset) || $Offset < 0 ? 0 : $Offset;
      
      $Session = Gdn::Session();
      $UserID = $Session->UserID > 0 ? $Session->UserID : 0;
      $this->DiscussionSummaryQuery($AdditionalFields);
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
      
		$this->FireEvent('BeforeGet');
      
		// Get sorting options from config
		$SortField = C('Vanilla.Discussions.SortField', 'd.DateLastComment');
		if (!in_array($SortField, array('d.DateLastComment', 'd.DateInserted')))
			$SortField = 'd.DateLastComment';
			
		$SortDirection = C('Vanilla.Discussions.SortDirection', 'desc');
		if ($SortDirection != 'asc')
			$SortDirection = 'desc';
			
		$this->SQL->OrderBy($SortField, $SortDirection);
      
      // Set range and fetch
      $Data = $this->SQL
         ->Limit($Limit, $Offset)
         ->Get();
         
      // If not looking at discussions filtered by bookmarks or user, filter announcements out.
		if (!isset($Wheres['w.Bookmarked']) && !isset($Wheres['d.InsertUserID']))
			$this->RemoveAnnouncements($Data);
		
		// Change discussions returned based on additional criteria	
		$this->AddDiscussionColumns($Data);
		
		// Prep and fire event
		$this->EventArguments['Data'] = $Data;
		$this->FireEvent('AfterAddColumns');
		
		return $Data;
   }
   
   /**
    * Removes undismissed announcements from the data.
    *
    * @since 2.0.0
    * @access public
    *
    * @param object $Data SQL result.
    */
   public function RemoveAnnouncements($Data) {
      $Result = &$Data->Result();
      foreach($Result as $Key => &$Discussion) {
         if ($Discussion->Announce == 1 && $Discussion->Dismissed == 0) {
            // Unset discussions that are announced and not dismissed
            unset($Result[$Key]);
         }
      }
   }
	
	/**
    * Modifies discussion data before it is returned.
    *
    * Takes archiving into account and fixes inaccurate comment counts.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param object $Data SQL result.
    */
	public function AddDiscussionColumns($Data) {
		// Change discussions based on archiving.
		$ArchiveTimestamp = Gdn_Format::ToTimestamp(Gdn::Config('Vanilla.Archive.Date', 0));
		$Result = &$Data->Result();
		foreach($Result as &$Discussion) {
			if($Discussion->DateLastComment && Gdn_Format::ToTimestamp($Discussion->DateLastComment) <= $ArchiveTimestamp) {
				$Discussion->Closed = '1';
				if ($Discussion->CountCommentWatch) {
					$Discussion->CountUnreadComments = $Discussion->CountComments - $Discussion->CountCommentWatch;
				} else {
					$Discussion->CountUnreadComments = 0;
				}
			} else {
				$Discussion->CountUnreadComments = $Discussion->CountComments - $Discussion->CountCommentWatch;
			}
			// Logic for incomplete comment count.
			if ($Discussion->CountCommentWatch == 0 && $DateLastViewed = GetValue('DateLastViewed', $Discussion)) {
				$Discussion->CountUnreadComments = 0;
				if (Gdn_Format::ToTimestamp($DateLastViewed) >= Gdn_Format::ToTimestamp($Discussion->LastDate))
					$Discussion->CountCommentWatch = $Discussion->CountComments;
			}
			$Discussion->CountUnreadComments = is_numeric($Discussion->CountUnreadComments) ? $Discussion->CountUnreadComments : 0;
			$Discussion->CountCommentWatch = is_numeric($Discussion->CountCommentWatch) ? $Discussion->CountCommentWatch : 0;
/*			decho('CountComments: '
				.$Discussion->CountComments.'; CountCommentWatch: '
				.$Discussion->CountCommentWatch.'; CountUnreadComments: '
				.$Discussion->CountUnreadComments
			);
*/
		}
	}
	
	/**
    * Add SQL Where to account for archive date.
    * 
    * @since 2.0.0
    * @access public
    *
	 * @param object $Sql Gdn_SQLDriver
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

   /**
    * Gets announced discussions.
    * 
    * @since 2.0.0
    * @access public
    * 
	 * @param array $Wheres SQL conditions.
	 * @return object SQL result.
	 */
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
      
      // Add conditions passed.
      if (is_array($Wheres))
         $this->SQL->Where($Wheres);

      $this->SQL
         ->Where('d.Announce', '1');

      // If we allow users to dismiss discussions, skip ones this user dismissed
      if (C('Vanilla.Discussions.Dismiss', 1)) {
         $this->SQL
            ->BeginWhereGroup()
            ->Where('w.Dismissed is null')
            ->OrWhere('w.Dismissed', '0')
            ->EndWhereGroup();
      }

      $this->SQL
         ->OrderBy('d.DateLastComment', 'desc')
         ->Limit($Limit, $Offset);

      $Data = $this->SQL->Get();
			
		$this->AddDiscussionColumns($Data);
		
		return $Data;
   }
   
   /**
    * Gets all users who have bookmarked the specified discussion.
    * 
    * @since 2.0.0
    * @access public
    * 
	 * @param int $DiscussionID Unique ID to find bookmarks for.
	 * @return object SQL result.
	 */
   public function GetBookmarkUsers($DiscussionID) {
      return $this->SQL
         ->Select('UserID')
         ->From('UserDiscussion')
         ->Where('DiscussionID', $DiscussionID)
         ->Where('Bookmarked', '1')
         ->Get();
   }
   
   /**
    * Identify current user's category permissions and set as local array.
    * 
    * @since 2.0.0
    * @access public
    * 
	 * @param bool $Escape Prepends category IDs with @
	 * @return array Protected local _CategoryPermissions
	 */
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

   /**
    * Count how many discussions match the given criteria.
    * 
    * @since 2.0.0
    * @access public
    * 
	 * @param array $Wheres SQL conditions.
	 * @param bool $ForceNoAnnouncements Not used.
	 * @return int Number of discussions.
	 */
   public function GetCount($Wheres = '', $ForceNoAnnouncements = FALSE) {
      $Session = Gdn::Session();
      $UserID = $Session->UserID > 0 ? $Session->UserID : 0;
      if (is_array($Wheres) && count($Wheres) == 0)
         $Wheres = '';
      
      // Check permission and limit to categories as necessary  
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

   /**
    * Get data for a single discussion by ID.
    * 
    * @since 2.0.0
    * @access public
    * 
	 * @param int $DiscussionID Unique ID of discussion to get.
	 * @return object SQL result.
	 */
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
		
		// Close if older than archive date
		if (
			$Data
         && $Data->DateLastComment
			&& Gdn_Format::ToTimestamp($Data->DateLastComment) <= Gdn_Format::ToTimestamp(Gdn::Config('Vanilla.Archive.Date', 0))
		) {
			$Data->Closed = '1';
		}
		
		return $Data;
   }
   
   /**
    * Marks the specified announcement as dismissed by the specified user.
    *
    * @since 2.0.0
    * @access public
    * 
    * @param int $DiscussionID Unique ID of discussion being affected.
    * @param int $UserID Unique ID of the user being affected.
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
   
   /**
    * Inserts or updates the discussion via form values.
    * 
    * Events: BeforeSaveDiscussion, AfterSaveDiscussion.
    *
    * @since 2.0.0
    * @access public
    * 
    * @param array $FormPostValues Data sent from the form model.
    * @return int $DiscussionID Unique ID of the discussion.
    */
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
      
      // Set checkbox values to zero if they were unchecked
      if (ArrayValue('Announce', $FormPostValues, '') === FALSE)
         $FormPostValues['Announce'] = 0;

      if (ArrayValue('Closed', $FormPostValues, '') === FALSE)
         $FormPostValues['Closed'] = 0;

      if (ArrayValue('Sink', $FormPostValues, '') === FALSE)
         $FormPostValues['Sink'] = 0;
		
		//	Prep and fire event
		$this->EventArguments['FormPostValues'] = &$FormPostValues;
		$this->EventArguments['DiscussionID'] = $DiscussionID;
		$this->FireEvent('BeforeSaveDiscussion');
         
      // Validate the form posted values
      if ($this->Validate($FormPostValues, $Insert)) {
         // If the post is new and it validates, make sure the user isn't spamming
         if (!$Insert || !$this->CheckForSpam('Discussion')) {
            // Get all fields on the form that relate to the schema
            $Fields = $this->Validation->SchemaValidationFields(); 
            
            // Get DiscussionID if one was sent
            $DiscussionID = intval(ArrayValue('DiscussionID', $Fields, 0));
            
            // Remove the primary key from the fields for saving
            $Fields = RemoveKeyFromArray($Fields, 'DiscussionID');
            
            $Discussion = FALSE;
            $StoredCategoryID = FALSE;
            
            if ($DiscussionID > 0) {
               // Updating
               $Stored = $this->GetID($DiscussionID);
               $this->SQL->Put($this->Name, $Fields, array($this->PrimaryKey => $DiscussionID));
               if($Stored->CategoryID != $Fields['CategoryID']) 
                  $StoredCategoryID = $Stored->CategoryID;
            } else {
               // Inserting
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
            
            // Get CategoryID of this discussion
            $Data = $this->SQL
               ->Select('CategoryID')
               ->From('Discussion')
               ->Where('DiscussionID', $DiscussionID)
               ->Get();
            
            $CategoryID = FALSE;
            if ($Data->NumRows() > 0)
               $CategoryID = $Data->FirstRow()->CategoryID;
            
            // Update discussion counter for affected categories
            $this->UpdateDiscussionCount($CategoryID);
            if ($StoredCategoryID)
               $this->UpdateDiscussionCount($StoredCategoryID);
				
				// Fire an event that the discussion was saved.
				$this->EventArguments['FormPostValues'] = $FormPostValues;
				$this->EventArguments['Fields'] = $Fields;
				$this->EventArguments['DiscussionID'] = $DiscussionID;
				$this->FireEvent('AfterSaveDiscussion');

         }
      }
      
      return $DiscussionID;
   }
   
   /**
    * Adds new discussion to activity feed.
    *
    * @since 2.0.0
    * @access public
    * 
    * @param int $UserID User performing the activity.
    * @param int $DiscussionID Unique ID of the discussion.
    * @param string $DiscussionName Name of the discussion created.
    */
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
    * @since 2.0.0
    * @access public
    *
    * @param int $CategoryID Unique ID of category we are updating.
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
	 * Update and get bookmark count for the specified user.
	 *
    * @since 2.0.0
    * @access public
    *
    * @param int $UserID Unique ID of user to update.
    * @return int Total number of bookmarks user has.
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
    * Updates a discussion field.
    * 
    * By default, this toggles the specified between '1' and '0'. If $ForceValue
    * is provided, the field is set to this value instead. An example use is
    * announcing and unannouncing a discussion.
    *
    * @param int $DiscussionID Unique ID of discussion being updated.
    * @param string $Property Name of field to be updated.
    * @param mixed $ForceValue If set, overrides toggle behavior with this value.
    * @return mixed Value that was ultimately set for the field.
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
      
	/**
	 * Sets the discussion score for specified user.
	 *
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique ID of discussion to update.
    * @param int $UserID Unique ID of user setting score.
    * @param int $Score New score for discussion.
    * @return int Total score.
    */
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

	/**
	 * Gets the discussion score for specified user.
	 *
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique ID of discussion getting score for.
    * @param int $UserID Unique ID of user whose score we're getting.
    * @return int Total score.
    */
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
	 * Increments view count for the specified discussion.
	 *
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique ID of discussion to get +1 view.
    */
	public function AddView($DiscussionID) {
      $this->SQL
         ->Update('Discussion')
         ->Set('CountViews', 'CountViews + 1', FALSE)
         ->Where('DiscussionID', $DiscussionID)
         ->Put();
	}

   /**
    * Bookmarks (or unbookmarks) a discussion for specified user.
    * 
    * Events: AfterBookmarkDiscussion.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique ID of discussion to (un)bookmark.
    * @param int $UserID Unique ID of user doing the (un)bookmarking.
    * @param object $Discussion Discussion data.
    * @return bool Current state of the bookmark (TRUE for bookmarked, FALSE for unbookmarked).
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
		
		// Prep and fire event	
      $this->EventArguments['Discussion'] = $Discussion;
      $this->EventArguments['State'] = $State;
      $this->FireEvent('AfterBookmarkDiscussion');
      
      return $State == '1' ? TRUE : FALSE;
   }
   
   /**
    * Gets number of bookmarks specified discussion has (all users).
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique ID of discussion for which to tally bookmarks.
    * @return int Total number of bookmarks.
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
    * Gets number of bookmarks specified user has.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $UserID Unique ID of user for which to tally bookmarks.
    * @return int Total number of bookmarks.
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
	 * 
	 * Events: DeleteDiscussion.
	 *
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique ID of discussion to delete.
    * @return bool Always returns TRUE.
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
      
      // Prep and fire event
      $this->EventArguments['DiscussionID'] = $DiscussionID;
      $this->FireEvent('DeleteDiscussion');
      
      // Execute deletion of discussion and related bits
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
