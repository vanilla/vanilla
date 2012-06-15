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
   protected static $_CategoryPermissions = NULL;

   public $Watching = FALSE;
   
   /**
    * Class constructor. Defines the related database table name.
    * 
    * @since 2.0.0
    * @access public
    */
   public function __construct() {
      parent::__construct('Discussion');
   }
   
   public function Counts($Column, $From = FALSE, $To = FALSE, $Max = FALSE) {
      $Result = array('Complete' => TRUE);
      switch ($Column) {
         case 'CountComments':
            $this->Database->Query(DBAModel::GetCountSQL('count', 'Discussion', 'Comment'));
            break;
         case 'FirstCommentID':
            $this->Database->Query(DBAModel::GetCountSQL('min', 'Discussion', 'Comment', $Column));
            break;
         case 'LastCommentID':
            $this->Database->Query(DBAModel::GetCountSQL('max', 'Discussion', 'Comment', $Column));
            break;
         case 'DateLastComment':
            $this->Database->Query(DBAModel::GetCountSQL('max', 'Discussion', 'Comment', $Column, 'DateInserted'));
            $this->SQL
               ->Update('Discussion')
               ->Set('DateLastComment', 'DateInserted', FALSE, FALSE)
               ->Where('DateLastComment', NULL)
               ->Put();
            break;
         case 'LastCommentUserID':
            if (!$Max) {
               // Get the range for this update.
               $DBAModel = new DBAModel();
               list($Min, $Max) = $DBAModel->PrimaryKeyRange('Discussion');
               
               if (!$From) {
                  $From = $Min;
                  $To = $Min + DBAModel::$ChunkSize - 1;
               }
            }
            $this->SQL
               ->Update('Discussion d')
               ->Join('Comment c', 'c.CommentID = d.LastCommentID')
               ->Set('d.LastCommentUserID', 'c.InsertUserID', FALSE, FALSE)
               ->Where('d.DiscussionID >=', $From)
               ->Where('d.DiscussionID <=', $To)
               ->Put();
            $Result['Complete'] = $To >= $Max;
            
            $Percent = round($To * 100 / $Max);
            if ($Percent > 100 || $Result['Complete'])
               $Result['Percent'] = '100%';
            else
               $Result['Percent'] = $Percent.'%';
            
            
            $From = $To + 1;
            $To = $From + DBAModel::$ChunkSize - 1;
            $Result['Args']['From'] = $From;
            $Result['Args']['To'] = $To;
            $Result['Args']['Max'] = $Max;
            break;
         default:
            throw new Gdn_UserException("Unknown column $Column");
      }
      return $Result;
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
   public function DiscussionSummaryQuery($AdditionalFields = array(), $Join = TRUE) {
      // Verify permissions (restricting by category if necessary)
      if ($this->Watching)
         $Perms = CategoryModel::CategoryWatch();
      else
         $Perms = self::CategoryPermissions();
      
      if($Perms !== TRUE) {
         $this->SQL->WhereIn('d.CategoryID', $Perms);
      }
      
      // Buid main query
      $this->SQL
         ->Select('d.*')
         ->Select('d.InsertUserID', '', 'FirstUserID')
         ->Select('d.DateInserted', '', 'FirstDate')
         ->Select('d.DateLastComment', '', 'LastDate')
         ->Select('d.LastCommentUserID', '', 'LastUserID')
         ->From('Discussion d');
      
      if ($Join) {
         $this->SQL
            ->Select('iu.Name', '', 'FirstName') // <-- Need these for rss!
            ->Select('iu.Photo', '', 'FirstPhoto')
            ->Select('iu.Email', '', 'FirstEmail')
            ->Join('User iu', 'd.InsertUserID = iu.UserID', 'left') // First comment author is also the discussion insertuserid
         
            ->Select('lcu.Name', '', 'LastName')
            ->Select('lcu.Photo', '', 'LastPhoto')
            ->Select('lcu.Email', '', 'LastEmail')
            ->Join('User lcu', 'd.LastCommentUserID = lcu.UserID', 'left') // Last comment user
         
            ->Select('ca.Name', '', 'Category')
            ->Select('ca.UrlCode', '', 'CategoryUrlCode')
            ->Select('ca.PermissionCategoryID')
            ->Join('Category ca', 'd.CategoryID = ca.CategoryID', 'left'); // Category
         
      }
		
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
    * @return Gdn_DataSet SQL result.
    */
   public function Get($Offset = '0', $Limit = '', $Wheres = '', $AdditionalFields = NULL) {
      if ($Limit == '') 
         $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 50);

      $Offset = !is_numeric($Offset) || $Offset < 0 ? 0 : $Offset;
      
      $Session = Gdn::Session();
      $UserID = $Session->UserID > 0 ? $Session->UserID : 0;
      $this->DiscussionSummaryQuery($AdditionalFields, FALSE);
         
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
      
      
      $this->SQL->Limit($Limit, $Offset);
      
      $this->EventArguments['SortField'] = C('Vanilla.Discussions.SortField', 'd.DateLastComment');
      $this->EventArguments['SortDirection'] = C('Vanilla.Discussions.SortDirection', 'desc');
		$this->EventArguments['Wheres'] = &$Wheres;
		$this->FireEvent('BeforeGet'); // @see 'BeforeGetCount' for consistency in results vs. counts
      
      $IncludeAnnouncements = FALSE;
      if (strtolower(GetValue('Announce', $Wheres)) == 'all') {
         $IncludeAnnouncements = TRUE;
         unset($Wheres['Announce']);
      }

      if (is_array($Wheres))
         $this->SQL->Where($Wheres);
      
		// Get sorting options from config
		$SortField = $this->EventArguments['SortField'];
		if (!in_array($SortField, array('d.DiscussionID', 'd.DateLastComment', 'd.DateInserted')))
			$SortField = 'd.DateLastComment';
		
		$SortDirection = $this->EventArguments['SortDirection'];
		if ($SortDirection != 'asc')
			$SortDirection = 'desc';
			
		$this->SQL->OrderBy($SortField, $SortDirection);
      
      // Set range and fetch
      $Data = $this->SQL->Get();
         
      // If not looking at discussions filtered by bookmarks or user, filter announcements out.
      if (!$IncludeAnnouncements) {
         if (!isset($Wheres['w.Bookmarked']) && !isset($Wheres['d.InsertUserID']))
            $this->RemoveAnnouncements($Data);
      }
		
		// Change discussions returned based on additional criteria	
		$this->AddDiscussionColumns($Data);
      
      // Join in the users.
      Gdn::UserModel()->JoinUsers($Data, array('FirstUserID', 'LastUserID'));
      CategoryModel::JoinCategories($Data);
		
      if (C('Vanilla.Views.Denormalize', FALSE))
         $this->AddDenormalizedViews($Data);
      
		// Prep and fire event
		$this->EventArguments['Data'] = $Data;
		$this->FireEvent('AfterAddColumns');
		
		return $Data;
   }
   
   /**
    * Gets the data for multiple unread discussions based on the given criteria.
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
    * @return Gdn_DataSet SQL result.
    */
   public function GetUnread($Offset = '0', $Limit = '', $Wheres = '', $AdditionalFields = NULL) {
      if ($Limit == '') 
         $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 50);

      $Offset = !is_numeric($Offset) || $Offset < 0 ? 0 : $Offset;
      
      $Session = Gdn::Session();
      $UserID = $Session->UserID > 0 ? $Session->UserID : 0;
      $this->DiscussionSummaryQuery($AdditionalFields, FALSE);
         
      if ($UserID > 0) {
         $this->SQL
            ->Select('w.UserID', '', 'WatchUserID')
            ->Select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
            ->Select('w.CountComments', '', 'CountCommentWatch')
            ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$UserID, 'left')
            //->BeginWhereGroup()
            //->Where('w.DateLastViewed', NULL)
            //->OrWhere('d.DateLastComment >', 'w.DateLastViewed')
            //->EndWhereGroup()
            ->Where('d.CountComments >', 'COALESCE(w.CountComments, 0)', TRUE, FALSE);
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
      
      
      $this->SQL->Limit($Limit, $Offset);
      
      $this->EventArguments['SortField'] = C('Vanilla.Discussions.SortField', 'd.DateLastComment');
      $this->EventArguments['SortDirection'] = C('Vanilla.Discussions.SortDirection', 'desc');
		$this->EventArguments['Wheres'] = &$Wheres;
		$this->FireEvent('BeforeGetUnread'); // @see 'BeforeGetCount' for consistency in results vs. counts
      
      $IncludeAnnouncements = FALSE;
      if (strtolower(GetValue('Announce', $Wheres)) == 'all') {
         $IncludeAnnouncements = TRUE;
         unset($Wheres['Announce']);
      }

      if (is_array($Wheres))
         $this->SQL->Where($Wheres);
      
		// Get sorting options from config
		$SortField = $this->EventArguments['SortField'];
		if (!in_array($SortField, array('d.DiscussionID', 'd.DateLastComment', 'd.DateInserted')))
			$SortField = 'd.DateLastComment';
		
		$SortDirection = $this->EventArguments['SortDirection'];
		if ($SortDirection != 'asc')
			$SortDirection = 'desc';
			
		$this->SQL->OrderBy($SortField, $SortDirection);
      
      // Set range and fetch
      $Data = $this->SQL->Get();
         
      // If not looking at discussions filtered by bookmarks or user, filter announcements out.
      if (!$IncludeAnnouncements) {
         if (!isset($Wheres['w.Bookmarked']) && !isset($Wheres['d.InsertUserID']))
            $this->RemoveAnnouncements($Data);
      }
		
		// Change discussions returned based on additional criteria	
		$this->AddDiscussionColumns($Data);
      
      // Join in the users.
      Gdn::UserModel()->JoinUsers($Data, array('FirstUserID', 'LastUserID'));
      CategoryModel::JoinCategories($Data);
		
      if (C('Vanilla.Views.Denormalize', FALSE))
         $this->AddDenormalizedViews($Data);
      
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
      $Result =& $Data->Result();
      $Unset = FALSE;
      
      foreach($Result as $Key => &$Discussion) {
         if (isset($this->_AnnouncementIDs)) {
            if (in_array($Discussion->DiscussionID, $this->_AnnouncementIDs)) {
               unset($Result[$Key]);
               $Unset = TRUE;
            }
         } elseif ($Discussion->Announce == 1 && $Discussion->Dismissed == 0) {
            // Unset discussions that are announced and not dismissed
            unset($Result[$Key]);
            $Unset = TRUE;
         }
      }
      if ($Unset) {
         // Make sure the discussions are still in order for json encoding.
         $Result = array_values($Result);
      }
   }
   
   public function AddDenormalizedViews(&$Discussions) {
      if ($Discussions instanceof Gdn_DataSet) {
         $Result = $Discussions->Result();
         foreach($Result as &$Discussion) {
            $CacheKey = "QueryCache.Discussion.{$Discussion->DiscussionID}.CountViews";
            $CacheViews = Gdn::Cache()->Get($CacheKey);
            if ($CacheViews !== Gdn_Cache::CACHEOP_FAILURE)
               $Discussion->CountViews = $CacheViews;
         }
      } else {
         if (isset($Discussions->DiscussionID)) {
            $Discussion = $Discussions;
            $CacheKey = "QueryCache.Discussion.{$Discussion->DiscussionID}.CountViews";
            $CacheViews = Gdn::Cache()->Get($CacheKey);
            if ($CacheViews !== Gdn_Cache::CACHEOP_FAILURE)
               $Discussion->CountViews = $CacheViews;
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
         $Discussion->Name = Gdn_Format::Text($Discussion->Name);
         $Discussion->Url = DiscussionUrl($Discussion);

			if($Discussion->DateLastComment && Gdn_Format::ToTimestamp($Discussion->DateLastComment) <= $ArchiveTimestamp) {
				$Discussion->Closed = '1';
				if ($Discussion->CountCommentWatch) {
					$Discussion->CountUnreadComments = $Discussion->CountComments - $Discussion->CountCommentWatch;
				} else {
					$Discussion->CountUnreadComments = 0;
				}
			} elseif ($Discussion->CountCommentWatch === NULL) {
            // Allow for discussions to just be new.
            $Discussion->CountUnreadComments = TRUE;
         } else {
				$Discussion->CountUnreadComments = $Discussion->CountComments - $Discussion->CountCommentWatch;
			}
			// Logic for incomplete comment count.
			if ($Discussion->CountCommentWatch == 0 && $DateLastViewed = GetValue('DateLastViewed', $Discussion)) {
            $Discussion->CountUnreadComments = TRUE;
				if (Gdn_Format::ToTimestamp($DateLastViewed) >= Gdn_Format::ToTimestamp($Discussion->LastDate)) {
					$Discussion->CountCommentWatch = $Discussion->CountComments;
               $Discussion->CountUnreadComments = 0;
            }
			}
         if ($Discussion->CountUnreadComments === NULL)
            $Discussion->CountUnreadComments = 0;
			elseif ($Discussion->CountUnreadComments < 0)
            $Discussion->CountUnreadComments = 0;
         
         
         $Discussion->CountCommentWatch = is_numeric($Discussion->CountCommentWatch) ? $Discussion->CountCommentWatch : 0;
         
         if ($Discussion->LastUserID == NULL) {
            $Discussion->LastUserID = $Discussion->InsertUserID;
            $Discussion->LastDate = $Discussion->DateInserted;
         }
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

      // Get the discussion IDs of the announcements.
      $CacheKey = 'Announcements';
      
      $AnnouncementIDs = $this->SQL
         ->Cache($CacheKey)
         ->Select('d.DiscussionID')
         ->From('Discussion d')
         ->Where('d.Announce >', '0')->Get()->ResultArray();

      $AnnouncementIDs = ConsolidateArrayValuesByKey($AnnouncementIDs, 'DiscussionID');

      // Short circuit querying when there are no announcements.
      if (count($AnnouncementIDs) == 0)
         return new Gdn_DataSet();

      $this->DiscussionSummaryQuery(array(), FALSE);
      
      if (!empty($Wheres))
         $this->SQL->Where($Wheres);

      if ($UserID) {
         $this->SQL->Select('w.UserID', '', 'WatchUserID')
         ->Select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
         ->Select('w.CountComments', '', 'CountCommentWatch')
         ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$UserID, 'left');
      } else {
         // Don't join in the user table when we are a guest.
         $this->SQL->Select('null as WatchUserID, null as DateLastViewed, null as Dismissed, null as Bookmarked, null as CountCommentWatch');
      }
      
      // Add conditions passed.
//      if (is_array($Wheres))
//         $this->SQL->Where($Wheres);
//
//      $this->SQL
//         ->Where('d.Announce', '1');

      $this->SQL->WhereIn('d.DiscussionID', $AnnouncementIDs);
      
      // If we aren't viewing announcements in a category then only show global announcements.
      if (!$Wheres) {
         $this->SQL->Where('d.Announce', 1);
      } else {
         $this->SQL->Where('d.Announce >', 0);
      }

      // If we allow users to dismiss discussions, skip ones this user dismissed
      if (C('Vanilla.Discussions.Dismiss', 1) && $UserID) {
         $this->SQL
            ->Where('coalesce(w.Dismissed, \'0\')', '0', FALSE);
      }

      $this->SQL
         ->OrderBy('d.DateLastComment', 'desc')
         ->Limit($Limit, $Offset);

      $Data = $this->SQL->Get();
      
      // Save the announcements that were fetched for later removal.
      $AnnouncementIDs = array();
      foreach ($Data as $Row) {
         $AnnouncementIDs[] = GetValue('DiscussionID', $Row);
      }
      $this->_AnnouncementIDs = $AnnouncementIDs;
			
		$this->AddDiscussionColumns($Data);
      
      if (C('Vanilla.Views.Denormalize', FALSE))
         $this->AddDenormalizedViews($Data);
      
      Gdn::UserModel()->JoinUsers($Data, array('FirstUserID', 'LastUserID'));
      CategoryModel::JoinCategories($Data);
      
		// Prep and fire event
		$this->EventArguments['Data'] = $Data;
		$this->FireEvent('AfterAddColumns');

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
   public static function CategoryPermissions($Escape = FALSE) {
      if(is_null(self::$_CategoryPermissions)) {
         $Session = Gdn::Session();
         
         if((is_object($Session->User) && $Session->User->Admin)) {
            self::$_CategoryPermissions = TRUE;
			} elseif(C('Garden.Permissions.Disabled.Category')) {
				if($Session->CheckPermission('Vanilla.Discussions.View'))
					self::$_CategoryPermissions = TRUE;
				else
					self::$_CategoryPermissions = array(); // no permission
         } else {
            $SQL = Gdn::SQL();
            
            $Categories = CategoryModel::Categories();
            $IDs = array();
            
            foreach ($Categories as $ID => $Category) {
               if ($Category['PermsDiscussionsView']) {
                  $IDs[] = $ID;
               }
            }

            // Check to see if the user has permission to all categories. This is for speed.
            $CategoryCount = count($Categories);
            
            if (count($IDs) == $CategoryCount)
               self::$_CategoryPermissions = TRUE;
            else {
               self::$_CategoryPermissions = array();
               foreach($IDs as $ID) {
                  self::$_CategoryPermissions[] = ($Escape ? '@' : '').$ID;
               }
            }
         }
      }
      
      return self::$_CategoryPermissions;
   }
   
   public function FetchPageInfo($Url) {
      $PageInfo = FetchPageInfo($Url);
      
      $Title = GetValue('Title', $PageInfo, '');
      if ($Title == '')
         $Title = FormatString(T('Undefined discussion subject.'), array('Url' => $Url));
      else {
         if ($Strip = C('Vanilla.Embed.StripPrefix'))
            $Title = StringBeginsWith($Title, $Strip, TRUE, TRUE);
         
         if ($Strip = C('Vanilla.Embed.StripSuffix'))
            $Title = StringEndsWith($Title, $Strip, TRUE, TRUE);
      }
      $Title = trim($Title);
      
      $Description = GetValue('Description', $PageInfo, '');
      $Images = GetValue('Images', $PageInfo, array());
      $Body = FormatString(T('EmbeddedDiscussionFormat'), array(
          'Title' => $Title,
          'Excerpt' => $Description,
          'Image' => (count($Images) > 0 ? Img(GetValue(0, $Images), array('class' => 'LeftAlign')) : ''),
          'Url' => $Url
      ));
      if ($Body == '')
         $Body = $ForeignUrl;
      if ($Body == '')
         $Body = FormatString(T('EmbeddedNoBodyFormat.'), array('Url' => $Url));
      
      $Result = array(
          'Name' => $Title,
          'Body' => $Body,
          'Format' => 'Html');
          
      return $Result;
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
      if (is_array($Wheres) && count($Wheres) == 0)
         $Wheres = '';
      
      // Check permission and limit to categories as necessary
      if ($this->Watching)
         $Perms = CategoryModel::CategoryWatch();
      else
         $Perms = self::CategoryPermissions();
      
      if (!$Wheres || (count($Wheres) == 1 && isset($Wheres['d.CategoryID']))) {
         // Grab the counts from the faster category cache.
         if (isset($Wheres['d.CategoryID'])) {
            $CategoryIDs = (array)$Wheres['d.CategoryID'];
            if ($Perms === FALSE)
               $CategoryIDs = array();
            elseif (is_array($Perms))
               $CategoryIDs = array_intersect($CategoryIDs, $Perms);
            
            if (count($CategoryIDs) == 0) {
               return 0;
            } else {
               $Perms = $CategoryIDs;
            }
         }
         
         $Categories = CategoryModel::Categories();
         $Count = 0;
         
         foreach ($Categories as $Cat) {
            if (is_array($Perms) && !in_array($Cat['CategoryID'], $Perms))
               continue;
            $Count += (int)$Cat['CountDiscussions'];
         }
         return $Count;
      }
      
      if ($Perms !== TRUE) {
         $this->SQL->WhereIn('c.CategoryID', $Perms);
      }
      
      $this->EventArguments['Wheres'] = &$Wheres;
		$this->FireEvent('BeforeGetCount'); // @see 'BeforeGet' for consistency in count vs. results
         
      $this->SQL
         ->Select('d.DiscussionID', 'count', 'CountDiscussions')
         ->From('Discussion d')
         ->Join('Category c', 'd.CategoryID = c.CategoryID')
         ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.Gdn::Session()->UserID, 'left')
         ->Where($Wheres);
      
      $Result = $this->SQL
         ->Get()
         ->FirstRow()
         ->CountDiscussions;
      
      return $Result;
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
   public function GetUnreadCount($Wheres = '', $ForceNoAnnouncements = FALSE) {
      if (is_array($Wheres) && count($Wheres) == 0)
         $Wheres = '';
      
      // Check permission and limit to categories as necessary
      if ($this->Watching)
         $Perms = CategoryModel::CategoryWatch();
      else
         $Perms = self::CategoryPermissions();
      
      if (!$Wheres || (count($Wheres) == 1 && isset($Wheres['d.CategoryID']))) {
         // Grab the counts from the faster category cache.
         if (isset($Wheres['d.CategoryID'])) {
            $CategoryIDs = (array)$Wheres['d.CategoryID'];
            if ($Perms === FALSE)
               $CategoryIDs = array();
            elseif (is_array($Perms))
               $CategoryIDs = array_intersect($CategoryIDs, $Perms);
            
            if (count($CategoryIDs) == 0) {
               return 0;
            } else {
               $Perms = $CategoryIDs;
            }
         }
         
         $Categories = CategoryModel::Categories();
         $Count = 0;
         
         foreach ($Categories as $Cat) {
            if (is_array($Perms) && !in_array($Cat['CategoryID'], $Perms))
               continue;
            $Count += (int)$Cat['CountDiscussions'];
         }
         return $Count;
      }
      
      if ($Perms !== TRUE) {
         $this->SQL->WhereIn('c.CategoryID', $Perms);
      }
      
      $this->EventArguments['Wheres'] = &$Wheres;
		$this->FireEvent('BeforeGetUnreadCount'); // @see 'BeforeGet' for consistency in count vs. results
         
      $this->SQL
         ->Select('d.DiscussionID', 'count', 'CountDiscussions')
         ->From('Discussion d')
         ->Join('Category c', 'd.CategoryID = c.CategoryID')
         ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.Gdn::Session()->UserID, 'left')
         //->BeginWhereGroup()
         //->Where('w.DateLastViewed', NULL)
         //->OrWhere('d.DateLastComment >', 'w.DateLastViewed')
         //->EndWhereGroup()
         ->Where('d.CountComments >', 'COALESCE(w.CountComments, 0)', TRUE, FALSE)
         ->Where($Wheres);
      
      $Result = $this->SQL
         ->Get()
         ->FirstRow()
         ->CountDiscussions;
      
      return $Result;
   }

   /**
    * Get data for a single discussion by ForeignID.
    * 
    * @since 2.0.18
    * @access public
    * 
	 * @param int $ForeignID Foreign ID of discussion to get.
	 * @return object SQL result.
	 */
   public function GetForeignID($ForeignID, $Type = '') {
      $Hash = strlen($ForeignID) > 32 ? md5($ForeignID) : $ForeignID;
      $Session = Gdn::Session();
      $this->FireEvent('BeforeGetForeignID');
      $this->SQL
         ->Select('d.*')
         ->Select('ca.Name', '', 'Category')
         ->Select('ca.UrlCode', '', 'CategoryUrlCode')
         ->Select('ca.PermissionCategoryID')
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
         ->Where('d.ForeignID', $Hash);
		
		if ($Type != '')
			$this->SQL->Where('d.Type', $Type);
			
		$Discussion = $this->SQL
         ->Get()
         ->FirstRow();
      
      if (C('Vanilla.Views.Denormalize', FALSE))
         $this->AddDenormalizedViews($Discussion);
              
      return $Discussion;
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
         ->Select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
         ->Select('w.CountComments', '', 'CountCommentWatch')
         ->Select('d.DateLastComment', '', 'LastDate')
         ->Select('d.LastCommentUserID', '', 'LastUserID')
         ->From('Discussion d')
         ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$Session->UserID, 'left')
         ->Where('d.DiscussionID', $DiscussionID)
         ->Get()
         ->FirstRow();
      
      if (!$Data)
         return $Data;
      
      $Data->Name = Gdn_Format::Text($Data->Name);
      $Data->Attributes = @unserialize($Data->Attributes);
      $Data->Url = DiscussionUrl($Data);
      
      // Join in the category.
      $Category = CategoryModel::Categories($Data->CategoryID);
      if (!$Category) $Category = FALSE;
      $Data->Category = $Category['Name'];
      $Data->CategoryUrlCode = $Category['UrlCode'];
      $Data->PermissionCategoryID = $Category['PermissionCategoryID'];
      
      // Join in the users.
      $Data = array($Data);
      Gdn::UserModel()->JoinUsers($Data, array('LastUserID', 'InsertUserID'));
      CategoryModel::JoinCategories($Data);
      $Data = $Data[0];
      
//         ->Select('lcu.Name', '', 'LastName')
//			->Select('iu.Name', '', 'InsertName')
//			->Select('iu.Photo', '', 'InsertPhoto')
//         ->Select('iu.Email', '', 'InsertEmail')
//         ->Join('User iu', 'd.InsertUserID = iu.UserID', 'left') // Insert user
//			->Join('Comment lc', 'd.LastCommentID = lc.CommentID', 'left') // Last comment
//         ->Join('User lcu', 'lc.InsertUserID = lcu.UserID', 'left') // Last comment user
		
		// Close if older than archive date
		if (
			$Data
         && $Data->DateLastComment
			&& Gdn_Format::ToTimestamp($Data->DateLastComment) <= Gdn_Format::ToTimestamp(Gdn::Config('Vanilla.Archive.Date', 0))
		) {
			$Data->Closed = '1';
		}
      
      if (C('Vanilla.Views.Denormalize', FALSE))
         $this->AddDenormalizedViews($Data);
		
		return $Data;
   }
   
   /**
    * Get discussions that have IDs in the provided array.
    * 
    * @since 2.0.18
    * @access public
    * 
	 * @param array $DiscussionIDs Array of DiscussionIDs to get.
	 * @return object SQL result.
	 */
   public function GetIn($DiscussionIDs) {
      $Session = Gdn::Session();
      $this->FireEvent('BeforeGetIn');
      $Result = $this->SQL
         ->Select('d.*')
         ->Select('ca.Name', '', 'Category')
         ->Select('ca.UrlCode', '', 'CategoryUrlCode')
         ->Select('ca.PermissionCategoryID')
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
         ->WhereIn('d.DiscussionID', $DiscussionIDs)
         ->Get();
      
      // Spliting views off to side table. Aggregate cached keys here.
      if (C('Vanilla.Views.Denormalize', FALSE))
         $this->AddDenormalizedViews($Result);
      
      return $Result;
   }
   
   public static function GetViewsFallback($DiscussionID) {
      
      // Not found. Check main table.
      $Views = GetValue('CountViews', Gdn::SQL()
         ->Select('CountViews')
         ->From('Discussion')
         ->Where('DiscussionID', $DiscussionID)
         ->Get()->FirstRow(DATASET_TYPE_ARRAY), NULL);
      
      // Found. Insert into denormalized table and return.
      if (!is_null($Views))
         return $Views;
      
      return NULL;
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
         $this->SQL->Options('Ignore', TRUE);
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
      
      // See if there is a source ID.
      if (GetValue('SourceID', $FormPostValues)) {
         $DiscussionID = $this->SQL->GetWhere('Discussion', ArrayTranslate($FormPostValues, array('Source', 'SourceID')))->Value('DiscussionID');
         if ($DiscussionID)
            $FormPostValues['DiscussionID'] = $DiscussionID;
      }
      
      $Insert = $DiscussionID == '' ? TRUE : FALSE;
		$this->EventArguments['Insert'] = $Insert;
      
      if ($Insert) {
         unset($FormPostValues['DiscussionID']);
         // If no categoryid is defined, grab the first available.
         if (ArrayValue('CategoryID', $FormPostValues) === FALSE)
            $FormPostValues['CategoryID'] = $this->SQL->Get('Category', 'CategoryID', '', 1)->FirstRow()->CategoryID;
            
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
      $this->Validate($FormPostValues, $Insert);
      $ValidationResults = $this->ValidationResults();
      
      // If the body is not required, remove it's validation errors.
      $BodyRequired = C('Vanilla.DiscussionBody.Required', TRUE);
      if (!$BodyRequired && array_key_exists('Body', $ValidationResults))
         unset($ValidationResults['Body']);
      
      if (count($ValidationResults) == 0) {
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
               
               // Clear the cache if necessary.
               if (GetValue('Announce', $Stored) != GetValue('Announce', $Fields)) {
                  $CacheKeys = array('Announcements');

                  $Announce = GetValue('Announce', $Discussion);
                  $this->SQL->Cache($CacheKeys);
               }

               $this->SQL->Put($this->Name, $Fields, array($this->PrimaryKey => $DiscussionID));

               $Fields['DiscussionID'] = $DiscussionID;
               LogModel::LogChange('Edit', 'Discussion', (array)$Fields, (array)$Stored);
               

               if($Stored->CategoryID != $Fields['CategoryID']) 
                  $StoredCategoryID = $Stored->CategoryID;
            } else {
               // Inserting.
               if (!GetValue('Format', $Fields))
                  $Fields['Format'] = C('Garden.InputFormatter', '');

               // Check for spam.
               $Spam = SpamModel::IsSpam('Discussion', $Fields);
               
               // Clear the cache if necessary.
               if (GetValue('Announce', $Fields)) {
                  $CacheKeys = array('Announcements');

                  $Announce = GetValue('Announce', $Discussion);
                  $this->SQL->Cache($CacheKeys);
               }

               if (!$Spam) {
                  $DiscussionID = $this->SQL->Insert($this->Name, $Fields);
                  $Fields['DiscussionID'] = $DiscussionID;
                  
                  // Update the cache.
                  if ($DiscussionID && Gdn::Cache()->ActiveEnabled()) {
                     $CategoryCache = array(
                         'LastDiscussionID' => $DiscussionID,
                         'LastCommentID' => NULL,
                         'LastTitle' => Gdn_Format::Text($Fields['Name']), // kluge so JoinUsers doesn't wipe this out.
                         'LastUserID' => $Fields['InsertUserID'],
                         'LastDateInserted' => $Fields['DateInserted'],
                         'LastUrl' => DiscussionUrl($Fields)
                     );
                     CategoryModel::SetCache($Fields['CategoryID'], $CategoryCache);
                  }
               } else {
                  return SPAM;
               }
               
               // Assign the new DiscussionID to the comment before saving.
               $FormPostValues['IsNewDiscussion'] = TRUE;
               $FormPostValues['DiscussionID'] = $DiscussionID;
               
               // Notify users of mentions.
					$DiscussionName = ArrayValue('Name', $Fields, '');
               $Story = ArrayValue('Body', $Fields, '');
               
               
               
               $NotifiedUsers = array();
               $UserModel = Gdn::UserModel();
               $ActivityModel = new ActivityModel();
               if (GetValue('Type', $FormPostValues))
                  $Code = 'HeadlineFormat.Discussion.'.$FormPostValues['Type'];
               else
                  $Code = 'HeadlineFormat.Discussion';
               
               $HeadlineFormat = T($Code, '{ActivityUserID,user} Started a new discussion. <a href="{Url,html}">{Data.Name,text}</a>');
               $Activity = array(
                   'ActivityType' => 'Discussion',
                   'ActivityUserID' => $Fields['InsertUserID'],
                   'HeadlineFormat' => $HeadlineFormat,
                   'RecordType' => 'Discussion',
                   'RecordID' => $DiscussionID,
                   'Route' => DiscussionUrl($Fields),
                   'Data' => array('Name' => $DiscussionName)
               );
               
               // Notify all of the users that were mentioned in the discussion.
               $Usernames = array_merge(GetMentions($DiscussionName), GetMentions($Story));
               $Usernames = array_unique($Usernames);
               
               foreach ($Usernames as $Username) {
                  $User = $UserModel->GetByUsername($Username);
                  if (!$User)
                     continue;
                  
                  // Check user can still see the discussion.
                  if (!$UserModel->GetCategoryViewPermission($User->UserID, GetValue('CategoryID', $Fields)))
                     continue;
                  
                  $Activity['HeadlineFormat'] = T('HeadlineFormat.Mention', '{ActivityUserID,user} mentioned you in <a href="{Url,html}">{Data.Name,text}</a>');
                  
                  $Activity['NotifyUserID'] = GetValue('UserID', $User);
                  $ActivityModel->Queue($Activity, 'Mention');
               }
               
               // Notify everyone that has advanced notifications.
               try {
                  $Fields['DiscussionID'] = $DiscussionID;
                  $this->NotifyNewDiscussion($Fields, $ActivityModel, $Activity);
               } catch(Exception $Ex) {
                  throw $Ex;
               }
               
               // Throw an event for users to add their own events.
               $this->EventArguments['Discussion'] = $Fields;
               $this->EventArguments['Activity'] = $Activity;
               $this->EventArguments['NotifiedUsers'] = $NotifiedUsers;
               $this->EventArguments['MentionedUsers'] = $Usernames;
               $this->EventArguments['ActivityModel'] = $ActivityModel;
               $this->FireEvent('BeforeNotification');

               // Send all notifications.
               $ActivityModel->SaveQueue();
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
            $this->UpdateDiscussionCount($CategoryID, ($Insert ? $DiscussionID : FALSE));
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
    *
    * @param type $Discussion
    * @param type $NotifiedUsers
    * @param ActivityModel $ActivityModel 
    */
   public function NotifyNewDiscussion($Discussion, $ActivityModel, $Activity) {
      if (is_numeric($Discussion)) {
         $Discussion = $this->GetID($Discussion);
      }
      
      $CategoryID = GetValue('CategoryID', $Discussion);
      
      // Figure out the category that governs this notification preference.
      $i = 0;
      $Category = CategoryModel::Categories($CategoryID);
      if (!$Category)
         return;
      
      while ($Category['Depth'] > 2 && $i < 20) {
         if (!$Category || $Category['Archived'])
            return;
         $i++;
         $Category = CategoryModel::Categories($Category['ParentCategoryID']);
      } 

      // Grab all of the users that need to be notified.
      $Data = $this->SQL
         ->WhereIn('Name', array('Preferences.Email.NewDiscussion.'.$Category['CategoryID'], 'Preferences.Popup.NewDiscussion.'.$Category['CategoryID']))
         ->Get('UserMeta')->ResultArray();
      
//      decho($Data, 'Data');
      
      
      $NotifyUsers = array();
      foreach ($Data as $Row) {
         if (!$Row['Value'])
            continue;
         
         $UserID = $Row['UserID'];
         $Name = $Row['Name'];
         if (strpos($Name, '.Email.') !== FALSE) {
            $NotifyUsers[$UserID]['Emailed'] = ActivityModel::SENT_PENDING;
         } elseif (strpos($Name, '.Popup.') !== FALSE) {
            $NotifyUsers[$UserID]['Notified'] = ActivityModel::SENT_PENDING;
         }
      }
      
//      decho($NotifyUsers);
      
      $InsertUserID = GetValue('InsertUserID', $Discussion);
      foreach ($NotifyUsers as $UserID => $Prefs) {
         if ($UserID == $InsertUserID)
            continue;
         
         $Activity['NotifyUserID'] = $UserID;
         $Activity['Emailed'] = GetValue('Emailed', $Prefs, FALSE);
         $Activity['Notified'] = GetValue('Notified', $Prefs, FALSE);
         $ActivityModel->Queue($Activity);
         
//         decho($Activity, 'die');
      }
      
//      die();
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
   public function UpdateDiscussionCount($CategoryID, $DiscussionID = FALSE) {
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
                coalesce(count(d.DiscussionID), 0) as CountDiscussions,
                coalesce(sum(d.CountComments), 0) as CountComments
              from :_Discussion d
              $Where
              group by d.CategoryID
            ) d
              on c.CategoryID = d.CategoryID
            set 
               c.CountDiscussions = coalesce(d.CountDiscussions, 0),
               c.CountComments = coalesce(d.CountComments, 0)";
			$Sql = str_replace(':_', $this->Database->DatabasePrefix, $Sql);
			$this->Database->Query($Sql, $Params, 'DiscussionModel_UpdateDiscussionCount');
			
		} elseif (is_numeric($CategoryID)) {
         $this->SQL
            ->Select('d.DiscussionID', 'count', 'CountDiscussions')
            ->Select('d.CountComments', 'sum', 'CountComments')
            ->From('Discussion d')
            ->Where('d.CategoryID', $CategoryID);
         
			$this->AddArchiveWhere();
			
			$Data = $this->SQL->Get()->FirstRow();
         $CountDiscussions = (int)GetValue('CountDiscussions', $Data, 0);
         $CountComments = (int)GetValue('CountComments', $Data, 0);
         
         if ($DiscussionID) {
            $this->SQL
               ->Set('LastDiscussionID', $DiscussionID)
               ->Set('LastCommentID', NULL);
         }
         
         $CategoryModel = new CategoryModel();
         $CategoryModel->SetField($CategoryID,
            array('CountDiscussions' => $CountDiscussions,
               'CountComments' => $CountComments));
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
   public function SetProperty($DiscussionID, $Property, $ForceValue = NULL) {
      if ($ForceValue !== NULL) {
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
	public function AddView($DiscussionID, $Views = 0) {
      $Views++;
      if (C('Vanilla.Views.Denormalize', FALSE) && Gdn::Cache()->ActiveEnabled()) {
         $CacheKey = "QueryCache.Discussion.{$DiscussionID}.CountViews";
         
         // Increment. If not success, create key.
         $Incremented = Gdn::Cache()->Increment($CacheKey);
         if ($Incremented === Gdn_Cache::CACHEOP_FAILURE)
            Gdn::Cache()->Store($CacheKey, $Views);
         
         // Every X views, writeback to Discussions
         if (($Views % C('Vanilla.Views.DenormalizeWriteback',100)) == 0) {
            Gdn::Database()->Query("UPDATE {$this->Database->DatabasePrefix}Discussion 
            SET CountViews={$Views}
            WHERE DiscussionID={$DiscussionID}");
         }
      } else {
         $this->SQL
            ->Update('Discussion')
            ->Set('CountViews', 'CountViews + 1', FALSE)
            ->Where('DiscussionID', $DiscussionID)
            ->Put();
      }
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

      $DiscussionData = $this->SQL
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
         ->Get();
			
		$this->AddDiscussionColumns($DiscussionData);
		$Discussion = $DiscussionData->FirstRow();

      if ($Discussion->WatchUserID == '') {
         $this->SQL->Options('Ignore', TRUE);
         $this->SQL
            ->Insert('UserDiscussion', array(
               'UserID' => $UserID,
               'DiscussionID' => $DiscussionID,
               'Bookmarked' => $State
            ));
         $Discussion->Bookmarked = TRUE;
      } else {
         $State = ($Discussion->Bookmarked == '1' ? '0' : '1');
         $this->SQL
            ->Update('UserDiscussion')
            ->Set('Bookmarked', $State)
            ->Where('UserID', $UserID)
            ->Where('DiscussionID', $DiscussionID)
            ->Put();
         $Discussion->Bookmarked = $State;
      }
		
		// Update the cached bookmark count on the discussion
		$BookmarkCount = $this->BookmarkCount($DiscussionID);
		$this->SQL->Update('Discussion')
			->Set('CountBookmarks', $BookmarkCount)
			->Where('DiscussionID', $DiscussionID)
			->Put();
      $this->CountDiscussionBookmarks = $BookmarkCount;
		
		
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
   public function Delete($DiscussionID, $Options = array()) {
		// Retrieve the users who have bookmarked this discussion.
		$BookmarkData = $this->GetBookmarkUsers($DiscussionID);

      $Data = $this->SQL
         ->Select('*')
         ->From('Discussion')
         ->Where('DiscussionID', $DiscussionID)
         ->Get()->FirstRow(DATASET_TYPE_ARRAY);
      
      $UserID = FALSE;
      $CategoryID = FALSE;
      if ($Data) {
         $UserID = $Data['InsertUserID'];
         $CategoryID = $Data['CategoryID'];
      }
      
      // Prep and fire event
      $this->EventArguments['DiscussionID'] = $DiscussionID;
      $this->FireEvent('DeleteDiscussion');
      
      // Execute deletion of discussion and related bits
      $this->SQL->Delete('Draft', array('DiscussionID' => $DiscussionID));

      $Log = GetValue('Log', $Options, TRUE);
      $LogOptions = GetValue('LogOptions', $Options, array());
      if ($Log === TRUE)
         $Log = 'Delete';
      
      LogModel::BeginTransaction();
      LogModel::Insert($Log, 'Discussion', $Data, $LogOptions);
      
      // Log all of the comment deletes.
      $Comments = $this->SQL->GetWhere('Comment', array('DiscussionID' => $DiscussionID))->ResultArray();
      foreach ($Comments as $Comment) {
         LogModel::Insert($Log, 'Comment', $Comment, $LogOptions);
      }
      
      $this->SQL->Delete('Comment', array('DiscussionID' => $DiscussionID));
      $this->SQL->Delete('Discussion', array('DiscussionID' => $DiscussionID));
      
		$this->SQL->Delete('UserDiscussion', array('DiscussionID' => $DiscussionID));
      $this->UpdateDiscussionCount($CategoryID);
      
      // Get the user's discussion count
      $CountDiscussions = $this->SQL
         ->Select('DiscussionID', 'count', 'CountDiscussions')
         ->From('Discussion')
         ->Where('InsertUserID', $UserID)
         ->Get()->Value('CountDiscussions', 0);
      
      // Save the count to the user table
      $this->SQL
         ->Update('User')
         ->Set('CountDiscussions', $CountDiscussions)
         ->Where('UserID', $UserID)
         ->Put();

		// Update bookmark counts for users who had bookmarked this discussion
		foreach ($BookmarkData->Result() as $User) {
			$this->SetUserBookmarkCount($User->UserID);
		}
			
      return TRUE;
   }
}
